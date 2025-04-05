<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create purchases table if it doesn't exist
        if (!Schema::hasTable('purchases')) {
            Schema::create('purchases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->decimal('price_paid', 10, 2);
                $table->timestamps();
            });
        }

        // Create roles
        try {
            // Check if roles already exist
            if (!Role::where('name', 'Admin')->exists()) {
                $adminRole = Role::create(['name' => 'Admin']);
            } else {
                $adminRole = Role::where('name', 'Admin')->first();
            }
            
            if (!Role::where('name', 'Customer')->exists()) {
                $customerRole = Role::create(['name' => 'Customer']);
            } else {
                $customerRole = Role::where('name', 'Customer')->first();
            }
            
            if (!Role::where('name', 'Employee')->exists()) {
                $employeeRole = Role::create(['name' => 'Employee']);
            } else {
                $employeeRole = Role::where('name', 'Employee')->first();
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
                ['name' => 'manage_customer_credit', 'display_name' => 'Manage Customer Credit']
            ];
            
            $permissionObjects = [];
            
            foreach ($permissions as $perm) {
                $existingPerm = Permission::where('name', $perm['name'])->first();
                if (!$existingPerm) {
                    $permissionObjects[$perm['name']] = Permission::create([
                        'name' => $perm['name'], 
                        'display_name' => $perm['display_name']
                    ]);
                } else {
                    $permissionObjects[$perm['name']] = $existingPerm;
                }
            }

            // Assign permissions to roles
            if ($adminRole) {
                $adminRole->syncPermissions(array_values($permissionObjects));
            }

            if ($employeeRole) {
                $employeeRole->syncPermissions([
                    $permissionObjects['show_users'],
                    $permissionObjects['edit_products'],
                    $permissionObjects['add_products'],
                    $permissionObjects['delete_products'],
                    $permissionObjects['manage_customer_credit']
                ]);
            }

            // Create admin user if it doesn't exist
            $admin = \App\Models\User::where('email', 'admin@example.com')->first();
            if (!$admin) {
                $admin = \App\Models\User::create([
                    'name' => 'Admin User',
                    'email' => 'admin@example.com',
                    'password' => bcrypt('Admin123!'),
                    'credit' => 1000.00
                ]);
                if ($adminRole) {
                    $admin->assignRole($adminRole);
                }
            }
        } catch (\Exception $e) {
            // Log error but continue with migration
            \Log::error('Error setting up roles and permissions: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchases')) {
            Schema::dropIfExists('purchases');
        }
    }
}; 