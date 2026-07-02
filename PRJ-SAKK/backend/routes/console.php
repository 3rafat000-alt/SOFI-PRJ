<?php

use App\Console\Commands\AutoRejectDevices;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Prune expired Sanctum tokens from the database.
Artisan::command('tokens:prune-expired', function () {
    $pruned = \Laravel\Sanctum\PersonalAccessToken::where('expires_at', '<', now())->delete();
    $this->info("Pruned {$pruned} expired token(s).");
})->purpose('Delete expired API tokens whose expires_at is in the past');

// ---------------------------------------------------------------------------
// Scheduled tasks. Laravel 12 reads the schedule from here (app/Console/Kernel
// is no longer bound), so all recurring jobs MUST be registered in this file.
// Requires a system cron running `php artisan schedule:run` every minute.
// ---------------------------------------------------------------------------

// Track the global gold spot price hourly and refresh per-karat buy/sell prices.
// The command self-guards on the `gold_auto_update` setting, so it is a cheap
// no-op while auto-update is disabled.
Schedule::command('gold:update-prices')
    ->hourly()
    ->withoutOverlapping()
    ->onFailure(fn () => logger()->error('gold:update-prices failed'));

// Auto-reject devices left pending approval for > 72h.
// (Was declared only in the dead app/Console/Kernel — never fired until moved here.)
Schedule::command('devices:auto-reject')
    ->hourly()
    ->withoutOverlapping()
    ->onFailure(fn () => logger()->error('devices:auto-reject failed'));

// Fire scheduled admin push broadcasts as they fall due.
// (Also rescued from the dead Kernel.)
Schedule::command('notifications:dispatch-scheduled')
    ->everyMinute()
    ->withoutOverlapping();

// Schedule: $schedule->command('tokens:prune-expired')->daily();

// Sweep crypto withdrawals stuck between the optimistic-debit Phase A commit
// and the Phase B gateway call (process crash in between leaves funds
// debited with the gateway never dispatched). Runs every 5 minutes; the
// command itself only touches rows older than its own age threshold
// (default 10 min), so this cadence gives every stuck row several passes.
Schedule::command('withdrawals:reconcile-pending')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onFailure(fn () => logger()->error('withdrawals:reconcile-pending failed'));

// =========================================================================
// Agent Schedules — Verification & Auto-Repair System
// =========================================================================

// Financial Reconciliation Agent: run every 5 minutes during business hours
// Audits ledger-balance drift, duplicate references, negative balances.
Schedule::command('agent:financial')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onFailure(fn () => logger()->error('agent:financial failed'));

// Execute pending signed repair actions every 2 minutes
// Picks up signed actions from any agent and executes them.
Schedule::call(function () {
    app(\App\Services\Agent\AgentOrchestrator::class)->executePendingRepairs();
})->everyTwoMinutes()->name('agent:execute-repairs')->withoutOverlapping();

// KYC Verification Agent: run every 15 minutes
// Auto-approves/rejects pending document verifications.
Schedule::command('agent:kyc')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onFailure(fn () => logger()->error('agent:kyc failed'));

// Summary run every hour — runs all agents + executes repairs
Schedule::command('agent:run-all --repair')
    ->hourly()
    ->withoutOverlapping()
    ->onFailure(fn () => logger()->error('agent:run-all failed'));

// Ledger-integrity auditor: runs the Financial Reconciliation Agent and reacts
// to CRITICAL findings (drift over threshold, negative balance, duplicate
// reference, broken balance invariant) with an admin alert + an env-gated
// response — alert-only in staging, hard disbursement lockdown in production.
// A mid-batch crash writing a debit without its matching credit would surface
// here within the hour (agent:financial itself already runs every 5 minutes;
// this command adds the missing admin-notify + halt reaction on top of it).
Schedule::command('audit:ledger')
    ->hourly()
    ->withoutOverlapping()
    ->onFailure(fn () => logger()->error('audit:ledger failed'));
