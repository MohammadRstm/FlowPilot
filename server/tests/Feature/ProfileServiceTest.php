<?php

namespace Tests\Feature;

use App\Models\Follower;
use App\Models\User;
use App\Models\UserPost;
use App\Services\ProfileService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test getting complete profile details for a user
     */
    public function test_get_profile_details()
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        UserPost::factory()->count(3)->create([
            'user_id' => $user->id,
            'likes' => 10,
            'imports' => 5,
        ]);

        $profile = ProfileService::getProfileDetails($user->id);

        $this->assertNotNull($profile);
        $this->assertEquals($user->id, $profile['user']['id']);
        $this->assertEquals('John', $profile['user']['first_name']);
        $this->assertEquals('Doe', $profile['user']['last_name']);
        $this->assertArrayHasKey('totals', $profile);
        $this->assertArrayHasKey('followers', $profile);
        $this->assertArrayHasKey('following', $profile);
        $this->assertArrayHasKey('posts', $profile);
        $this->assertArrayHasKey('workflows', $profile);
        $this->assertEquals(3, $profile['totals']['posts_count']);
    }

    /**
     * Test toggling follow on/off between users
     */
    public function test_toggle_follow_functionality()
    {
        $follower = User::factory()->create();
        $toBeFollowed = User::factory()->create();

        // Initial follow
        $result = ProfileService::toggeleFollow($follower->id, $toBeFollowed->id);
        $this->assertTrue($result['following']);

        $this->assertDatabaseHas('followers', [
            'follower_id' => $follower->id,
            'followed_id' => $toBeFollowed->id,
        ]);

        // Unfollow
        $result = ProfileService::toggeleFollow($follower->id, $toBeFollowed->id);
        $this->assertFalse($result['following']);

        $this->assertDatabaseMissing('followers', [
            'follower_id' => $follower->id,
            'followed_id' => $toBeFollowed->id,
        ]);
    }

    /**
     * Test follow validation - user cannot follow themselves
     */
    public function test_cannot_follow_yourself()
    {
        $user = User::factory()->create();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You cannot follow yourself');

        ProfileService::toggeleFollow($user->id, $user->id);
    }

    /**
     * Test checking follow relationships between users
     */
    public function test_is_following_user()
    {
        $viewer = User::factory()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // viewer follows user
        Follower::create([
            'follower_id' => $viewer->id,
            'followed_id' => $user->id,
        ]);

        // user follows otherUser (for follow-back check)
        Follower::create([
            'follower_id' => $user->id,
            'followed_id' => $otherUser->id,
        ]);

        $followStatus = ProfileService::isFollowingUser($user->id, $viewer->id);

        $this->assertTrue($followStatus['isFollowing']);
        $this->assertFalse($followStatus['isBeingFollowed']);
    }

}
