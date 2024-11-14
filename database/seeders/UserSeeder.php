<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Membuat atau mengambil role Super Admin dan Admin
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        
        // Membuat Super Admin dan menetapkan role
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@gmail.com',
            'password' => bcrypt('12345678'),
        ]);
        $superAdmin->assignRole($superAdminRole);

        // Membuat Admin dan menetapkan role
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('12345678'),
        ]);
        $admin->assignRole($adminRole);

        // Membuat atau memperbarui permissions
        Permission::firstOrCreate(['name' => 'view users settings', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'can view products', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'can delete product', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'can add product', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'can edit products', 'guard_name' => 'web']);

        // Menetapkan permissions ke role Super Admin dan Admin
        $superAdminRole->givePermissionTo(['view users settings', 'can view products', 'can add product', 'can edit products']);
        $adminRole->givePermissionTo(['can view products']);
    }
}
