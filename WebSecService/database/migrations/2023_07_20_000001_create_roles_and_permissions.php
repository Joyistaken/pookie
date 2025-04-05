<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only proceed if the spatie tables exist
        if (Schema::hasTable('roles') && Schema::hasTable('permissions')) {
            $this->createRolesAndPermissions();
            $this->createAdminUser();
        }
    }

    /**
     * Create roles and permissions.
     */
    private function createRolesAndPermissions(): void
    {
        // Check if roles already exist
        $adminRoleExists = DB::table('roles')->where('name', 'Admin')->exists();
        $customerRoleExists = DB::table('roles')->where('name', 'Customer')->exists();
        $employeeRoleExists = DB::table('roles')->where('name', 'Employee')->exists();

        // Create roles if they don't exist
        if (!$adminRoleExists) {
            DB::table('roles')->insert([
                'name' => 'Admin',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!$customerRoleExists) {
            DB::table('roles')->insert([
                'name' => 'Customer',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!$employeeRoleExists) {
            DB::table('roles')->insert([
                'name' => 'Employee',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create permissions if they don't exist
        $permissions = [
            ['name' => 'show_users', 'display_name' => 'Show Users'],
            ['name' => 'edit_users', 'display_name' => 'Edit Users'],
            ['name' => 'delete_users', 'display_name' => 'Delete Users'],
            ['name' => 'admin_users', 'display_name' => 'Administer Users'],
            ['name' => 'delete_products', 'display_name' => 'Delete Products'],
            ['name' => 'edit_products', 'display_name' => 'Edit Products'],
            ['name' => 'add_products', 'display_name' => 'Add Products'],
            ['name' => 'manage_customer_credit', 'display_name' => 'Manage Customer Credit'],
        ];

        foreach ($permissions as $permission) {
            $exists = DB::table('permissions')->where('name', $permission['name'])->exists();
            if (!$exists) {
                DB::table('permissions')->insert([
                    'name' => $permission['name'],
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Add display_name if the column exists
                if (Schema::hasColumn('permissions', 'display_name')) {
                    DB::table('permissions')
                        ->where('name', $permission['name'])
                        ->update(['display_name' => $permission['display_name']]);
                }
            }
        }

        // Assign permissions to roles
        $adminRoleId = DB::table('roles')->where('name', 'Admin')->value('id');
        $employeeRoleId = DB::table('roles')->where('name', 'Employee')->value('id');

        if ($adminRoleId) {
            $adminPermissions = DB::table('permissions')->pluck('id')->toArray();
            foreach ($adminPermissions as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $adminRoleId)
                    ->where('permission_id', $permissionId)
                    ->exists();
                
                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $adminRoleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }

        if ($employeeRoleId) {
            $employeePermissions = [
                'show_users', 'edit_products', 'add_products', 
                'delete_products', 'manage_customer_credit'
            ];
            
            foreach ($employeePermissions as $permissionName) {
                $permissionId = DB::table('permissions')->where('name', $permissionName)->value('id');
                if ($permissionId) {
                    $exists = DB::table('role_has_permissions')
                        ->where('role_id', $employeeRoleId)
                        ->where('permission_id', $permissionId)
                        ->exists();
                    
                    if (!$exists) {
                        DB::table('role_has_permissions')->insert([
                            'role_id' => $employeeRoleId,
                            'permission_id' => $permissionId,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Create admin user if it doesn't exist.
     */
    private function createAdminUser(): void
    {
        $adminExists = DB::table('users')->where('email', 'admin@example.com')->exists();
        
        if (!$adminExists) {
            // Add user
            $userId = DB::table('users')->insertGetId([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => bcrypt('Admin123!'),
                'credit' => 1000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Assign Admin role
            $adminRoleId = DB::table('roles')->where('name', 'Admin')->value('id');
            if ($adminRoleId && $userId) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $adminRoleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $userId,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration doesn't remove roles or permissions to avoid data loss
    }
}; 