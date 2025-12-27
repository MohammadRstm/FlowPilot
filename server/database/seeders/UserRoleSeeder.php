<?php

namespace Database\Seeders;

use App\Models\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder{// static
    
    public function run(): void{
        UserRole::insert([
            ['name' => 'admin'],
            ['name' => 'user']
        ]);
    }
}
