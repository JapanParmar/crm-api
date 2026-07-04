<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class, // 1. Roles & permissions first
            AdminSeeder::class,          // 2. Admin user
            CrmDataSeeder::class,        // 3. Employees + all CRM data
        ]);
    }
}
