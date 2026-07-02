<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions
        $permissions = [
            'view properties', 'create properties', 'edit properties', 'delete properties',
            'view agents', 'create agents', 'edit agents', 'delete agents',
            'view inquiries', 'manage inquiries',
            'view agencies', 'manage agencies',
            'view users', 'manage users',
            'view subscriptions', 'manage subscriptions',
            'view settings', 'manage settings',
            'view reviews', 'approve reviews',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Roles with permissions
        $visitor = Role::firstOrCreate(['name' => 'visitor', 'guard_name' => 'web']);
        $visitor->givePermissionTo(['view properties', 'view agents']);

        $agent = Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']);
        $agent->givePermissionTo([
            'view properties', 'create properties', 'edit properties',
            'view agents', 'view inquiries', 'manage inquiries',
            'view reviews',
        ]);

        $agency = Role::firstOrCreate(['name' => 'agency', 'guard_name' => 'web']);
        $agency->givePermissionTo([
            'view properties', 'create properties', 'edit properties', 'delete properties',
            'view agents', 'create agents', 'edit agents', 'delete agents',
            'view inquiries', 'manage inquiries',
            'view subscriptions',
        ]);

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::all());
    }
}
