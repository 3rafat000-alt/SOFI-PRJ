<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TaskController as AdminTaskController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\WorkspaceController as AdminWorkspaceController;
use App\Http\Controllers\Install\InstallController;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Installer (only reachable until the lock file exists)
|--------------------------------------------------------------------------
*/
Route::middleware('not_installed')->prefix('install')->name('install.')->group(function (): void {
    Route::get('/', [InstallController::class, 'index'])->name('index');
    Route::get('database', [InstallController::class, 'database'])->name('database');
    Route::post('database', [InstallController::class, 'runDatabase'])->name('database.run');
    Route::get('admin', [InstallController::class, 'admin'])->name('admin');
    Route::post('admin', [InstallController::class, 'createAdmin'])->name('admin.create');
    Route::get('complete', [InstallController::class, 'complete'])->name('complete');
});

/*
|--------------------------------------------------------------------------
| Admin panel (Blade · admin guard · requires installed)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware('installed')->group(function (): void {

    // Guest admin
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.submit');

    // Authenticated admin
    Route::middleware('auth:admin')->group(function (): void {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::delete('users/{id}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::post('users/{id}/restore', [AdminUserController::class, 'restore'])->name('users.restore');

        Route::get('workspaces', [AdminWorkspaceController::class, 'index'])->name('workspaces.index');
        Route::get('workspaces/{id}', [AdminWorkspaceController::class, 'show'])->name('workspaces.show');

        Route::get('projects', [AdminProjectController::class, 'index'])->name('projects.index');
        Route::get('tasks', [AdminTaskController::class, 'index'])->name('tasks.index');

        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

        Route::get('logs', [LogController::class, 'index'])->name('logs.index');
    });
});

/*
|--------------------------------------------------------------------------
| Public site (only once installed)
|--------------------------------------------------------------------------
*/
Route::middleware('installed')->group(function (): void {

    // Landing page on the root domain
    Route::get('/', function () {
        return view('landing', [
            'appName' => SystemSetting::get('app_name', 'TaskSync Pro'),
        ]);
    })->name('landing');

    // Vue 3 SPA shell mounted at /app (client-side routing → always index.html).
    Route::get('/app/{any?}', function () {
        $index = public_path('app/index.html');

        abort_unless(file_exists($index), 503, 'SPA build missing — run `npm run build` in src/frontend and copy dist to public/app.');

        return response()->file($index);
    })->where('any', '.*')->name('app');
});
