<?php

namespace Database\Seeders;

use App\Models\PostComment;
use App\Models\User as ModelsUser;
use App\Models\UserCopilotHistory;
use App\Models\UserPost as ModelsUserPost;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder{
    use WithoutModelEvents;

    public function run(): void{
        // generate users with posts and copilot history
        $users = ModelsUser::factory(50)
            ->has(
                ModelsUserPost::factory()->count(3), 
                'posts'
            )
            ->has(UserCopilotHistory::factory()->count(3), 'copilotHistory')
            ->create();

        // collect all user IDs for comment assignment
        $userIds = $users->pluck('id');

        // link followers and create comments
        $users->each(function ($user) use ($users, $userIds) {

            // link followers
            $toFollow = $users->where('id', '!=', $user->id)->random(rand(5, 15));
            $user->followings()->attach($toFollow->pluck('id'));

            // link comments to posts
            $user->posts->each(function ($post) use ($userIds, $user) {
                // exclude the post author
                $possibleCommenters = $userIds->filter(fn($id) => $id !== $user->id);

                for ($i = 0; $i < 10; $i++) {
                    $post->comments()->create([
                        'content' => fake()->sentence(),
                        'user_id' => $possibleCommenters->random(),
                    ]);
                }
            });
        });
    }
}
