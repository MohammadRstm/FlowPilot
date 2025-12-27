<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User as ModelsUser;

class UserPostSeeder extends Seeder{

    public function run(): void{
        ModelsUser::factory(50)->create();
    }
}
