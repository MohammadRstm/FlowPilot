<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserPost as ModelsUserPost;

class UserPostSeeder extends Seeder{
    public function run(): void{
        ModelsUserPost::factory(50)->create();
    }
}
