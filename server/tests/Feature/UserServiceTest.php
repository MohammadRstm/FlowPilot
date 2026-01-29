<?php

namespace Tests\Feature;


use App\Models\User;
use App\Services\UserService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserServiceTest extends TestCase{
    use RefreshDatabase;

    /**
     * Test get friends throws exception when name is empty
     */
    public function test_get_friends_throws_exception_for_empty_name()
    {
        $user = User::factory()->create();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Name is empty');

        UserService::getFriends('', $user->id);
    }

    /**
     * Test getting user account information
     */
    public function test_get_user_account()
    {
        $userWithPassword = User::factory()->create([
            'password' => bcrypt('password123'),
            'google_id' => null,
        ]);

        $userWithGoogle = User::factory()->create([
            'password' => null,
            'google_id' => 'google_123',
        ]);

        $userWithBoth = User::factory()->create([
            'password' => bcrypt('password123'),
            'google_id' => 'google_456',
        ]);

        $accountPassword = UserService::getUserAccount($userWithPassword->id);
        $this->assertTrue($accountPassword['normalAccount']);
        $this->assertFalse($accountPassword['googleAccount']);

        $accountGoogle = UserService::getUserAccount($userWithGoogle->id);
        $this->assertFalse($accountGoogle['normalAccount']);
        $this->assertTrue($accountGoogle['googleAccount']);

        $accountBoth = UserService::getUserAccount($userWithBoth->id);
        $this->assertTrue($accountBoth['normalAccount']);
        $this->assertTrue($accountBoth['googleAccount']);
    }

    /**
     * Test SSE headers are correctly formatted
     */
    public function test_return_sse_headers()
    {
        $headers = UserService::returnSseHeaders();

        $this->assertEquals('text/event-stream', $headers['Content-Type']);
        $this->assertEquals('no-cache', $headers['Cache-Control']);
        $this->assertEquals('keep-alive', $headers['Connection']);
        $this->assertEquals('no', $headers['X-Accel-Buffering']);
    }
}
