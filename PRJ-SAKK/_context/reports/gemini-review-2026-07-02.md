Cross-Layer Audit Session Report: SAKK Wallet Platform

Date: July 2, 2026

Status: Gate 6 (Staging/UAT Consolidation)

Desk: External Principal Architect Review Desk

1. Test-Environment Safety, Isolation & State Reset
Defense-in-Depth Isolation & Production Safety

Relying solely on a TestCase::setUp check is an incomplete safety net when dealing with local SQLite production files. If a configuration cache bypass occurs early in the Laravel application boot cycle, destructive migrations can run before TestCase::setUp executes. We must implement a multi-layered barrier.

[ php artisan test ] 
        │
        ▼
[ tests/bootstrap.php ] ───► Fails if bootstrap/cache/config.php exists
        │
        ▼
[ AppServiceProvider::boot ] ───► Aborts if env == 'production' && runningInConsole() && argv contains 'test'
        │
        ▼
[ DatabaseServiceProvider ] ───► Enforces Strict SQLite Memory Driver Override

Layer 1: The Bootstrap Cache Breaker (tests/bootstrap.php)

Modify the test bootstrap file to perform a destructive check against any cached configurations before PHPUnit even loads the framework environment:

PHP
<?php
// tests/bootstrap.php

$cachePath = __DIR__ . '/../bootstrap/cache/config.php';
if (file_exists($cachePath)) {
    echo "\033[31mCRITICAL ERROR: Configuration cache file detected at $cachePath\033[0m\n";
    echo "Running tests with cached configuration will corrupt production SQLite data.\n";
    echo "Execute 'php artisan config:clear' and rerun the suite.\n";
    exit(1);
}

require __DIR__ . '/../vendor/autoload.php';

Layer 2: Runtime Environment Guard (app/Providers/AppServiceProvider.php)

Inject an un-bypassable guard inside the application boot sequence that evaluates the live connection parameter, independent of environment files:

PHP
public function boot(): void
{
    if (app()->runningInConsole() && app()->environment('production')) {
        $commands = ['test', 'db:seed', 'migrate:fresh', 'migrate:refresh'];
        $argv = $_SERVER['argv'] ?? [];
        
        foreach ($commands as $command) {
            if (in_array($command, $argv)) {
                logger()->critical('PRODUCTION BLOWOUT GUARD: Blocked destructive console command.', ['argv' => $argv]);
                throw new \RuntimeException('Execution Halted: Destructive testing commands are structurally banned in APP_ENV=production.');
            }
        }
    }
}

Layer 3: Automated Shadow Backups

Add an automated fallback hook within phpunit.xml using a local listener or shell script that creates a point-in-time snapshot of database.sqlite prior to test executions:

Bash
# Executed automatically in CI/CD or local test wrappers
cp database/database.sqlite database/database.sqlite.bak_test

Eradicating Test Suite Contamination (~26 Suite Failures)

The 26 test failures are caused by leaky state across isolated test containers. Laravel’s RefreshDatabase trait handles database transactions but leaves the Cache driver, Rate Limiters, and Container singletons untouched.

The Fix: Global State Reset Hook

Introduce a custom base trait ResetsFrameworkState to be used by your core TestCase:

PHP
namespace Tests;

use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Facade;

trait ResetsFrameworkState
{
    protected function tearDownFrameworkState(): void
    {
        // 1. Flush the specific cache store utilized during testing
        Cache::store('array')->flush();
        Cache::flush();

        // 2. Clear out the Rate Limiter state
        $limiter = app(RateLimiter::class);
        $reflection = new \ReflectionClass($limiter);
        $timersProperty = $reflection->getProperty('timers');
        $timersProperty->setAccessible(true);
        $timersProperty->setValue($limiter, []);

        // 3. Purge container singletons that build internal memory states
        $resolvedInstances = [
            \App\Services\CCPaymentService::class,
            \App\Repositories\WalletRepository::class
        ];

        foreach ($resolvedInstances as $instance) {
            $this->app->forgetInstance($instance);
        }

        Facade::clearResolvedInstances();
    }
}


Wire this directly into the base TestCase::tearDown() method right before calling parent::tearDown().

2. Config-Override Systemic Null-Safety
Upstream Fix: The Coalescing Overlay Pattern

Modifying values directly on top of configuration arrays without type guarantees breaks class dependencies that expect strict strings or integers. The fix must happen within ServiceConfigOverrideProvider before the values mutate the runtime application configuration state.

PHP
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ServiceConfig;
use Illuminate\Support\Facades\Schema;

class ServiceConfigOverrideProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Fail-open strategy if database migrations haven't run yet
        if (!app()->runningInConsole() || Schema::hasTable('service_configs')) {
            $this->hydrateConfigurations();
        }
    }

    private function hydrateConfigurations(): void
    {
        // Fetch all dynamic database configurations
        $overrides = ServiceConfig::all()->groupBy('service_name');

        foreach ($overrides as $service => $settings) {
            $currentConfig = config("services.{$service}", []);
            $hydratedSettings = [];

            foreach ($settings as $setting) {
                // Systemic Protection: Skip keys explicitly set to null or empty string in DB
                if (is_null($setting->value)) {
                    continue;
                }
                
                $hydratedSettings[$setting->key_name] = $setting->value;
            }

            // Recursive merge preserving original structure where database keys are missing
            if (!empty($hydratedSettings)) {
                config([
                    "services.{$service}" => array_merge($currentConfig, $hydratedSettings)
                ]);
            }
        }
    }
}

Administrative Guard: Structural Schema Verification

To ensure no future administrative entry introduces invalid configuration data types, we implement a strict validation rule at the model/repository layer when saving settings:

PHP
namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveServiceConfigRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'service_name' => 'required|string',
            'key_name'     => 'required|string',
            'value'        => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value === null || $value === '') {
                        $fail("The configuration value for {$attribute} cannot be nullified.");
                    }
                }
            ],
        ];
    }
}

3. Withdrawal Recovery & Processing Safeguards
[ User Requests Withdrawal ]
             │
             ▼
┌────────────────────────────────────────┐
│   Check Idempotency & Wallet Balance   │
└────────────────────────────────────────┘
             │
             ▼
┌────────────────────────────────────────┐
│     Phase A: Commit Local Debit        │
│   (gateway_dispatched = false)         │
└────────────────────────────────────────┘
             │
             ▼  ◄─── Process Dies Here (The Recovery Window)
┌────────────────────────────────────────┐
│   Phase B: Dispatch to External API    │
│   (gateway_dispatched = true)          │
└────────────────────────────────────────┘

Server-Side Idempotency Engine

To prevent race conditions during form resubmissions or concurrent automated connection requests, we require an atomic idempotency interceptor table.

Schema Design
PHP
Schema::create('idempotency_keys', function (Blueprint $table) {
    $table->string('key')->primary(); // Formatted as "withdraw:{user_id}:{client_generated_uuid}"
    $table->json('cached_response');
    $table->timestamp('expires_at')->index();
    $table->timestamps();
});

Implementation Pattern

Prior to running Phase A, query this state table. If the key exists, block execution and return the pending or cached JSON response immediately.

Reconciliation Sweeper Design

When a hard process failure cuts execution short right after Phase A commits, the funds remain locked as PENDING, but gateway_dispatched remains false.

The Command: app/Console/Commands/ReconcileOrphanWithdrawals.php
PHP
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Services\CCPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ReconcileOrphanWithdrawals extends Command
{
    protected $signature = 'sakk:money:reconcile-withdrawals';
    protected $description = 'Reconciles stranded optimistic-debit transaction entries.';

    public function handle(CCPaymentService $gateway): void
    {
        // Target stranded transactions older than 10 minutes to avoid active worker collision
        $orphans = Transaction::where('type', 'withdrawal')
            ->where('status', 'PENDING')
            ->where('metadata->gateway_dispatched', false)
            ->where('created_at', '<=', now()->subMinutes(10))
            ->get();

        foreach ($orphans as $tx) {
            $lock = Cache::lock("reconcile:tx:{$tx->id}", 60);

            if (!$lock->get()) {
                continue;
            }

            try {
                DB::transaction(function () use ($tx, $gateway) {
                    // Lock target wallet row cleanly in ascending index orders
                    $wallet = DB::table('wallets')
                        ->where('id', $tx->wallet_id)
                        ->lockForUpdate()
                        ->first();

                    // Verify against remote gateway via idempotent reference ID
                    $gatewayState = $gateway->checkTransactionStatus($tx->reference);

                    if ($gatewayState->isNotFound()) {
                        // Case 1: Gateway never received transaction -> Safe local reversal
                        DB::table('wallets')
                            ->where('id', $tx->wallet_id)
                            ->increment('balance', $tx->amount);

                        $tx->update([
                            'status' => 'FAILED',
                            'metadata' => array_merge($tx->metadata, ['failure_reason' => 'Orphan recovery reversal'])
                        ]);
                        
                        $this->info("Transaction {$tx->id} safely reversed.");
                    } else {
                        // Case 2: Gateway processed it but local runtime failed to catch response
                        $tx->update([
                            'status' => $gatewayState->getNormalizedStatus(),
                            'metadata' => array_merge($tx->metadata, ['gateway_dispatched' => true])
                        ]);
                        
                        $this->warn("Transaction {$tx->id} synchronized to gateway status.");
                    }
                });
            } catch (\Exception $e) {
                logger()->error("Failed to reconcile transaction {$tx->id}: " . $e->getMessage());
            } finally {
                $lock->release();
            }
        }
    }
}

4. SQLite → MySQL Migration Readiness

SQLite lacks real row-level isolation controls and strict type structures, which frequently hides concurrency issues. Moving to MySQL require strict runtime checking.

Pre-Migration Checklist

[ ] Verify Column Precision for Currencies: Ensure all currency schemas use explicit explicit storage sizes (decimal(16,4)) rather than double or floating point types, which cause subtle precision loss over time.

[ ] Audit Raw Database Functions: Remove any SQLite-specific raw query statements (e.g., strftime, JSON_SET arrays) and replace them with standard Laravel Eloquent equivalents or query builder json parameters.

[ ] Enforce MySQL Strict Mode Compatibility: Ensure local environments mirror the target production configuration exactly (STRICT_TRANS_TABLES, ERROR_FOR_DIVISION_BY_ZERO, NO_ENGINE_SUBSTITUTION). This catches any silent text truncations or default data errors early.

[ ] Normalize Database Collation Consistency: Enforce standard utf8mb4 configurations (utf8mb4_unicode_ci) globally to handle complex character arrays safely.

Concurrency Stress Matrix

To expose lock-order deadlocks before moving to Gate 7, implement this dual-process concurrent integration test. This forces real row locking states to execute during test environments:

PHP
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\ParallelTesting;

class ConcurrencyMigrationTest extends TestCase
{
    /** @test */
    public function it_proves_ascending_lock_order_prevents_deadlocks_under_load()
    {
        // This test requires a valid MySQL/MariaDB database driver configured
        if (DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite does not support granular row locking stress testing.');
        }

        $walletA = Wallet::factory()->create(['balance' => 100000]);
        $walletB = Wallet::factory()->create(['balance' => 100000]);

        // Simulate simultaneous mutual execution loops
        // Process 1 locks A then B; Process 2 locks B then A (the standard deadlock trap)
        $pid = pcntl_fork();

        if ($pid == 0) {
            // Child process execution
            DB::transaction(function () use ($walletA, $walletB) {
                // Enforce our deterministic ascending sorting logic:
                $ids = [$walletA->id, $walletB->id];
                sort($ids);

                $locked = Wallet::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
                
                // Perform arbitrary debit/credit mutations
                $locked->firstWhere('id', $walletA->id)->decrement('balance', 100);
                $locked->firstWhere('id', $walletB->id)->increment('balance', 100);
            });
            exit(0);
        } else {
            // Parent process execution
            DB::transaction(function () use ($walletA, $walletB) {
                $ids = [$walletA->id, $walletB->id];
                sort($ids); // The fix code must use deterministic sort!

                $locked = Wallet::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
                
                $locked->firstWhere('id', $walletB->id)->decrement('balance', 50);
                $locked->firstWhere('id', $walletA->id)->increment('balance', 50);
            });

            // Wait for both concurrent operations to conclude cleanly
            pcntl_wait($status);
        }

        $this->assertEquals(99950, $walletA->fresh()->balance);
        $this->assertEquals(100050, $walletB->fresh()->balance);
    }
}

5. Recommended Execution Sequence

This sequence prioritizes system stability and data protection before handling ledger recovery mechanics:

Phase 1: Test-Environment Safety Guards (Item 1)

Impact: Immediate protection against accidental data loss. Fixes the local SQLite cached-config vulnerability and cleans up suite contamination.

Phase 2: Upstream Config Null-Safety (Item 2)

Impact: System stabilization. Resolves current service boot problems and sets schema rules to prevent invalid data injections.

Phase 3: MySQL Readiness Execution (Item 4)

Impact: Identifies database bugs early. Runs schema modifications and stress tests before changing the target staging databases.

Phase 4: Withdrawal Recovery Sweeper (Item 3)

Impact: Completes the ledger architecture. Adds automated reconciliation and idempotency checking to handle process failures gracefully.