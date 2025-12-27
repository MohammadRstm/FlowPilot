<?php

namespace Database\Seeders;

use App\Models\Follower;
use App\Models\PostComment;
use Illuminate\Database\Seeder;
use App\Models\User as ModelsUser;
use App\Models\UserCopilotHistory;
use App\Models\UserPost as ModelsUserPost;
use App\Models\UserRole;

class UserPostSeeder extends Seeder{

    public function run(): void{
        ModelsUser::factory(50)
            ->has(
                ModelsUserPost::factory()
                ->count(3)
                ->has(
                    PostComment::fake()
                    ->count(10)
                )
            )
            ->has(
                UserCopilotHistory::factory()
                ->count(3)
            )
            ->has(
                Follower::factory()
                ->count(50)
            )
            ->has(
                Follower::factory()
                ->count(50)
            )
            ->create();
    }
}



