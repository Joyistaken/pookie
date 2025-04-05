<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Schema;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Make sure display_name column exists
        if (!Schema::hasColumn('permissions', 'display_name')) {
            return;
        }

        // Define permissions
        $permissions = [
            ['name' => 'show_users', 'display_name' => 'Show Users'],
            ['name' => 'edit_users', 'display_name' => 'Edit Users'],
            ['name' => 'delete_users', 'display_name' => 'Delete Users'],
            ['name' => 'admin_users', 'display_name' => 'Administer Users'],
            ['name' => 'delete_products', 'display_name' => 'Delete Products'],
            ['name' => 'edit_products', 'display_name' => 'Edit Products'],
            ['name' => 'add_products', 'display_name' => 'Add Products'],
            ['name' => 'manage_customer_credit', 'display_name' => 'Manage Customer Credit']
        ];

        // Create permissions
        foreach ($permissions as $permissionData) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionData['name'], 'guard_name' => 'web']
            );
            $permission->display_name = $permissionData['display_name'];
            $permission->save();
        }

        // Get roles
        $adminRole = Role::where('name', 'Admin')->first();
        $employeeRole = Role::where('name', 'Employee')->first();

        if ($adminRole) {
            // Give all permissions to admin
            $adminRole->syncPermissions(Permission::all());
        }

        if ($employeeRole) {
            // Give employee specific permissions
            $employeePermissions = [
                'show_users',
                'edit_products',
                'add_products',
                'delete_products',
                'manage_customer_credit'
            ];
            
            $employeeRole->syncPermissions(
                Permission::whereIn('name', $employeePermissions)->get()
            );
        }
    }
} 