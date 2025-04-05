<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('Admin123!'),
                'credit' => 1000.00
            ]
        );

        // Make sure the roles exist
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $customerRole = Role::firstOrCreate(['name' => 'Customer', 'guard_name' => 'web']);
        $employeeRole = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);

        // Assign admin role to admin user
        $admin->syncRoles([$adminRole]);

        // Create an example employee
        $employee = User::updateOrCreate(
            ['email' => 'employee@example.com'],
            [
                'name' => 'Example Employee',
                'password' => bcrypt('Employee123!'),
                'credit' => 0.00
            ]
        );
        $employee->syncRoles([$employeeRole]);

        // Create an example customer
        $customer = User::updateOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Example Customer',
                'password' => bcrypt('Customer123!'),
                'credit' => 500.00
            ]
        );
        $customer->syncRoles([$customerRole]);
    }
} 