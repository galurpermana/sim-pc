<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::factory()->create([
            'name' => 'super Admin',
            'email' => 'superadmin@admin',
            'password' => bcrypt('12345678'),
        ]);

        \App\Models\User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@admin',
            'password' => bcrypt('12345678'),
        ]);

        DB::table('roles')->insert([
            [
                'id' => 1,
                'name' => 'Super Admin',
                'guard_name' => 'web',
            ],
            [
                'id' => 2,
                'name' => 'Admin',
                'guard_name' => 'web',
            ],
        ]);

        DB::table('permissions')->insert([
            [
                'id' => 1,
                'name' => 'view users settings',
                'guard_name' => 'web',
            ],

            [
                'id' => 4,
                'name' => 'can view products',
                'guard_name' => 'web',
            ],

            [
                'id' => 5,
                'name' => 'can delete products',
                'guard_name' => 'web',
            ],

            [
                'id' => 6,
                'name' => 'can add products',
                'guard_name' => 'web',
            ],

            [
                'id' => 7,
                'name' => 'can edit products',
                'guard_name' => 'web',
            ],
        ]);

        DB::table('role_has_permissions')->insert([
            [
            'permission_id' => 1,
            'role_id' => 1,
            ],
            [
                'permission_id' => 4,
                'role_id' => 1,
            ],
            
            [
                'permission_id' => 6,
                'role_id' => 1,
            ],

            [
                'permission_id' => 7,
                'role_id' => 1,
            ],

            [
                'permission_id' => 4,
                'role_id' => 2,
            ],
            
        ]);
    }
}
