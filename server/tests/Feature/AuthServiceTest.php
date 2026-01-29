<?php

namespace Tests\Feature;

use App\Exceptions\UserFacingException;
use App\Models\User;
use App\Services\AuthService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user creation with email and password
     */
    public function test_create_user_with_password()
    {
        $userData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $result = AuthService::createUser($userData, 0);

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals('john@example.com', $result['user']['email']);
        $this->assertEquals('John', $result['user']['first_name']);
        $this->assertEquals('Doe', $result['user']['last_name']);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    /**
     * Test user creation from Google (without password)
     */
    public function test_create_user_from_google()
    {
        $userData = [
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'email' => 'jane@example.com',
            'password' => 'password123',
        ];

        $result = AuthService::createUser($userData, 1);

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        
        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNull($user->password);
    }

    /**
     * Test successful login with valid credentials
     */
    public function test_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $result = AuthService::login($credentials);

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($user->id, $result['user']['id']);
        $this->assertEquals('test@example.com', $result['user']['email']);
    }

    /**
     * Test login fails with non-existent email
     */
    public function test_login_with_invalid_email()
    {
        $credentials = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ];

        $this->expectException(UserFacingException::class);
        $this->expectExceptionMessage('Invalid credentials');

        AuthService::login($credentials);
    }

    /**
     * Test login fails with incorrect password
     */
    public function test_login_with_wrong_password()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ];

        $this->expectException(UserFacingException::class);
        $this->expectExceptionMessage('Invalid credentials');

        AuthService::login($credentials);
    }

    /**
     * Test login fails for Google-only account
     */
    public function test_login_fails_for_google_account_without_password()
    {
        User::factory()->create([
            'email' => 'google@example.com',
            'password' => null,
            'google_id' => 'google123',
        ]);

        $credentials = [
            'email' => 'google@example.com',
            'password' => 'password123',
        ];

        $this->expectException(UserFacingException::class);
        $this->expectExceptionMessage('This account uses Google login. Please continue with Google');

        AuthService::login($credentials);
    }

    /**
     * Test Google login fails with invalid token
     */
    public function test_google_login_fails_with_invalid_token()
    {
        $this->expectException(Exception::class);

        AuthService::googleLogin(['idToken' => 'invalid_token']);
    }

    /**
     * Test set password for user without password
     */
    public function test_set_password_for_user_without_password()
    {
        $user = User::factory()->create([
            'password' => null,
        ]);

        $data = [
            'current_password' => null,
            'new_password' => 'newpassword123',
        ];

        AuthService::setPassword($user, $data);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /**
     * Test set password with correct current password
     */
    public function test_set_password_with_correct_current_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
        ]);

        $data = [
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword123',
        ];

        AuthService::setPassword($user, $data);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
        $this->assertFalse(Hash::check('oldpassword123', $user->password));
    }

    /**
     * Test set password fails with incorrect current password
     */
    public function test_set_password_fails_with_incorrect_current_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
        ]);

        $data = [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword123',
        ];

        $this->expectException(UserFacingException::class);
        $this->expectExceptionMessage('Current password is incorrect');

        AuthService::setPassword($user, $data);
    }
}
