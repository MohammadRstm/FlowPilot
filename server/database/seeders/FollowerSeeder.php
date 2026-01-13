<?php

namespace Database\Seeders;

use App\Models\Follower;
use Illuminate\Database\Seeder;

class FollowerSeeder extends Seeder{

    public function run(): void{
        Follower::factory(50)->create();
    }
}
