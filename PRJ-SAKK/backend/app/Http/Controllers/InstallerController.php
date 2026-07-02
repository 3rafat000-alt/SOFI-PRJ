<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Fee;
use App\Models\ExchangeRate;
use App\Models\KycLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallerController extends Controller
{
    public function requirements()
    {
        // Check if already installed
        if ($this->isInstalled()) {
            return redirect('/');
        }
        
        $phpRequirements = [
            'PHP >= 8.2' => version_compare(PHP_VERSION, '8.2.0', '>='),
        ];
        
        $extensions = [
            'BCMath' => extension_loaded('bcmath'),
            'Ctype' => extension_loaded('ctype'),
            'Fileinfo' => extension_loaded('fileinfo'),
            'JSON' => extension_loaded('json'),
            'Mbstring' => extension_loaded('mbstring'),
            'OpenSSL' => extension_loaded('openssl'),
            'PDO' => extension_loaded('pdo'),
            'PDO SQLite' => extension_loaded('pdo_sqlite'),
            'Tokenizer' => extension_loaded('tokenizer'),
            'XML' => extension_loaded('xml'),
        ];
        
        $permissions = [
            'storage/app' => is_writable(storage_path('app')),
            'storage/framework' => is_writable(storage_path('framework')),
            'storage/logs' => is_writable(storage_path('logs')),
            'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
            'database' => is_writable(database_path()),
        ];
        
        $allPassed = !in_array(false, $phpRequirements) 
                  && !in_array(false, $extensions)
                  && !in_array(false, $permissions);
        
        return view('installer.requirements', compact('phpRequirements', 'extensions', 'permissions', 'allPassed'));
    }
    
    public function database()
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }
        
        return view('installer.database');
    }
    
    public function databaseStore(Request $request)
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }

        $validated = $request->validate([
            'db_driver' => 'required|in:sqlite,mysql,pgsql',
            'db_host' => 'required_unless:db_driver,sqlite',
            'db_port' => 'required_unless:db_driver,sqlite',
            'db_name' => 'required_unless:db_driver,sqlite',
            'db_user' => 'required_unless:db_driver,sqlite',
            'db_password' => 'nullable',
        ]);
        
        // Update .env file
        $envPath = base_path('.env');
        $envContent = File::get($envPath);
        
        if ($validated['db_driver'] === 'sqlite') {
            // Create SQLite database file
            $dbPath = database_path('database.sqlite');
            if (!File::exists($dbPath)) {
                File::put($dbPath, '');
            }
            
            $envContent = preg_replace('/DB_CONNECTION=.*/', 'DB_CONNECTION=sqlite', $envContent);
            $envContent = preg_replace('/DB_DATABASE=.*/', 'DB_DATABASE=' . $dbPath, $envContent);
        } else {
            $envContent = preg_replace('/DB_CONNECTION=.*/', 'DB_CONNECTION=' . $validated['db_driver'], $envContent);
            $envContent = preg_replace('/DB_HOST=.*/', 'DB_HOST=' . $validated['db_host'], $envContent);
            $envContent = preg_replace('/DB_PORT=.*/', 'DB_PORT=' . $validated['db_port'], $envContent);
            $envContent = preg_replace('/DB_DATABASE=.*/', 'DB_DATABASE=' . $validated['db_name'], $envContent);
            $envContent = preg_replace('/DB_USERNAME=.*/', 'DB_USERNAME=' . $validated['db_user'], $envContent);
            $envContent = preg_replace('/DB_PASSWORD=.*/', 'DB_PASSWORD=' . ($validated['db_password'] ?? ''), $envContent);
        }
        
        File::put($envPath, $envContent);
        
        // Clear config cache
        Artisan::call('config:clear');
        
        // Run migrations
        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Exception $e) {
            return back()->withErrors(['database' => 'Database connection failed: ' . $e->getMessage()]);
        }
        
        // Store in session for later
        session(['installer.database' => $validated]);
        
        return redirect()->route('installer.admin');
    }
    
    public function admin()
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }
        
        return view('installer.admin');
    }
    
    public function adminStore(Request $request)
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);
        
        // Create admin user
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'kyc_level' => 2, // VERIFIED (max defined level); 3 is undefined in config/kyc.php
            'status' => 'active',
        ]);

        // is_admin is guarded (not mass-assignable); set it explicitly for the installer admin.
        $user->forceFill(['is_admin' => true])->save();
        
        session(['installer.admin' => [
            'email' => $validated['email'],
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
        ]]);
        
        return redirect()->route('installer.settings');
    }
    
    public function settings()
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }
        
        return view('installer.settings');
    }
    
    public function settingsStore(Request $request)
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }

        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'default_currency' => 'required|string|size:3',
            'fee_deposit' => 'required|numeric|min:0|max:100',
            'fee_withdrawal' => 'required|numeric|min:0|max:100',
        ]);
        
        // Update .env
        $envPath = base_path('.env');
        $envContent = File::get($envPath);
        
        $envContent = preg_replace('/APP_NAME=.*/', 'APP_NAME="' . $validated['app_name'] . '"', $envContent);
        $envContent = preg_replace('/APP_URL=.*/', 'APP_URL=' . $validated['app_url'], $envContent);

        // Completing the installer means going live: force production-safe flags
        // so a fresh deploy never serves debug stack traces or runs in local mode.
        $envContent = preg_replace('/^APP_ENV=.*/m', 'APP_ENV=production', $envContent);
        $envContent = preg_replace('/^APP_DEBUG=.*/m', 'APP_DEBUG=false', $envContent);

        File::put($envPath, $envContent);
        Artisan::call('config:clear');
        
        // Seed default data
        $this->seedDefaultData($validated);
        
        // Mark as installed
        File::put(storage_path('installed'), date('Y-m-d H:i:s'));
        
        session(['installer.settings' => $validated]);
        
        return redirect()->route('installer.complete');
    }
    
    public function complete()
    {
        $settings = session('installer.settings', []);
        $admin = session('installer.admin', []);
        $database = session('installer.database', []);

        if (empty($settings) && !$this->isInstalled()) {
            return redirect()->route('installer.requirements');
        }
        
        return view('installer.complete', compact('settings', 'admin', 'database'));
    }
    
    private function isInstalled(): bool
    {
        return File::exists(storage_path('installed'));
    }
    
    private function seedDefaultData(array $settings): void
    {
        // Essential reference data a fresh install needs to actually function.
        // Every seeder below is idempotent (updateOrCreate / firstOrCreate) and
        // ships only empty placeholders — no secrets, no demo/PII rows. The
        // default AdminSeeder is intentionally excluded (the admin is created
        // from the install form), as are the AgentSeeder / MerchantSeeder demo
        // rows. Previously this only seeded KYC + Fees, leaving a fresh server
        // with NO exchange rate (currency convert broke), NO system settings,
        // and NO third-party integration rows to configure.
        $seeders = [
            \Database\Seeders\SystemSettingsSeeder::class,   // app settings
            \Database\Seeders\KycLevelSeeder::class,         // KYC levels (config/kyc.php)
            \Database\Seeders\ExchangeRateSeeder::class,     // currency rates incl. SYP base
            \Database\Seeders\FeeSeeder::class,              // fee rows (valid enum types)
            \Database\Seeders\CCPaymentSeeder::class,        // CCPayment integration row
            \Database\Seeders\StripeSeeder::class,           // Stripe integration row (cards gate)
            \Database\Seeders\VirtualCardsSeeder::class,     // virtual-cards feature row
            \Database\Seeders\MessagingSeeder::class,        // messaging integration row
            \Database\Seeders\PushNotificationsSeeder::class,// notifications integration row
            \Database\Seeders\GoogleMapsSeeder::class,       // maps integration row
            \Database\Seeders\GoldPriceSeeder::class,        // gold karats + auto-price settings
            \Database\Seeders\SystemConfigSeeder::class,     // service configs + notification templates
        ];

        foreach ($seeders as $seeder) {
            Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]);
        }

        // Apply the deposit/withdrawal percentages chosen during install.
        Fee::where('code', Fee::CODE_DEPOSIT_USDT)->update(['percentage' => $settings['fee_deposit']]);
        Fee::where('code', Fee::CODE_WITHDRAW_USDT)->update(['percentage' => $settings['fee_withdrawal']]);
    }
}
