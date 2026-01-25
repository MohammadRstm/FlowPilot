<?php

namespace Tests\Unit;

use App\Exceptions\UserFacingException;
use App\Models\User;
use App\Service\AuthService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Google_Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful user creation
     */
    public function test_create_user_with_email_and_password(): void
    {
        $userData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $result = AuthService::createUser($userData);

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
     * Test user creation from Google login
     */
    public function test_create_user_from_google(): void
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
        $this->assertEquals('jane@example.com', $result['user']['email']);

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNull($user->password);
    }

    /**
     * Test successful login with valid credentials
     */
    public function test_login_with_valid_credentials(): void
    {
        $password = 'testpassword123';
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make($password),
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => $password,
        ];

        $result = AuthService::login($credentials);

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($user->id, $result['user']['id']);
        $this->assertEquals('test@example.com', $result['user']['email']);
    }

    /**
     * Test login with non-existent user
     */
    public function test_login_with_non_existent_user(): void
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
     * Test login with incorrect password
     */
    public function test_login_with_incorrect_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correctpassword'),
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
     * Test login with Google account that has no password
     */
    public function test_login_with_google_account_no_password(): void
    {
        User::factory()->create([
            'email' => 'google@example.com',
            'password' => null,
        ]);

        $credentials = [
            'email' => 'google@example.com',
            'password' => 'anypassword',
        ];

        $this->expectException(UserFacingException::class);
        $this->expectExceptionMessage('This account uses Google login. Please continue with Google');

        AuthService::login($credentials);
    }

    /**
     * Test set password for new user (no existing password)
     */
    public function test_set_password_for_new_user(): void
    {
        $user = User::factory()->create([
            'password' => null,
        ]);

        $data = [
            'new_password' => 'newpassword123',
        ];

        AuthService::setPassword($user, $data);

        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /**
     * Test set password for existing user with correct current password
     */
    public function test_set_password_with_correct_current_password(): void
    {
        $currentPassword = 'currentpassword123';
        $user = User::factory()->create([
            'password' => Hash::make($currentPassword),
        ]);

        $data = [
            'current_password' => $currentPassword,
            'new_password' => 'newpassword456',
        ];

        AuthService::setPassword($user, $data);
        $user->refresh();

        $this->assertTrue(Hash::check('newpassword456', $user->password));
    }

    /**
     * Test set password with incorrect current password
     */
    public function test_set_password_with_incorrect_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correctpassword'),
        ]);

        $data = [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword',
        ];

        $this->expectException(UserFacingException::class);
        $this->expectExceptionMessage('Current password is incorrect');

        AuthService::setPassword($user, $data);
    }

    /**
     * Test set password with missing current password when user has password
     */
    public function test_set_password_missing_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('existingpassword'),
        ]);

        $data = [
            'current_password' => null,
            'new_password' => 'newpassword',
        ];

        $this->expectException(UserFacingException::class);
        $this->expectExceptionMessage('Current password is incorrect');

        AuthService::setPassword($user, $data);
    }

    /**
     * Test successful unlink Google account
     */
    public function test_unlink_google_account_with_password(): void
    {
        $user = User::factory()->create([
            'google_id' => 'google789',
            'password' => Hash::make('password123'),
        ]);

        AuthService::unlinkGoogle($user);
        $user->refresh();

        $this->assertNull($user->google_id);
    }

    /**
     * Test unlink Google account without password
     */
    public function test_unlink_google_account_without_password(): void
    {
        $user = User::factory()->create([
            'google_id' => 'google789',
            'password' => null,
        ]);

        $this->expectException(UserFacingException::class);
        $this->expectExceptionMessage('Set a password before unlinking Google');

        AuthService::unlinkGoogle($user);
    }

    /**
     * Test successful N8N account linking
     */
    public function test_link_n8n_account_successfully(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'http://localhost:5678/api/v1/workflows' => Http::response([
                'data' => [
                    ['id' => 1, 'name' => 'Workflow 1'],
                ],
            ]),
        ]);

        $data = [
            'api_key' => 'valid_api_key',
            'base_url' => 'http://localhost:5678',
        ];

        AuthService::linkN8nAccount($user, $data);
        $user->refresh();

        $this->assertEquals('valid_api_key', $user->n8n_api_key);
        $this->assertEquals('http://localhost:5678', $user->n8n_base_url);
    }

    /**
     * Test N8N account linking with invalid JSON response
     */
    public function test_link_n8n_account_with_invalid_json_response(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'http://localhost:5678/api/v1/workflows' => Http::response(
                'Invalid JSON',
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $data = [
            'api_key' => 'valid_api_key',
            'base_url' => 'http://localhost:5678',
        ];

        $this->expectException(UserFacingException::class);
        $this->expectExceptionMessage('Invalid n8n response.Consider using a different api key');

        AuthService::linkN8nAccount($user, $data);
    }

    /**
     * Test N8N account linking with missing data key in response
     */
    public function test_link_n8n_account_with_missing_data_key(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'http://localhost:5678/api/v1/workflows' => Http::response(
                ['invalid_key' => []],
                200,
                ['Content-Type' => 'application/json']
            ),
        ]);

        $data = [
            'api_key' => 'valid_api_key',
            'base_url' => 'http://localhost:5678',
        ];

        $this->expectException(UserFacingException::class);
        $this->expectExceptionMessage('Invalid n8n API response.Consider using a different api key');

        AuthService::linkN8nAccount($user, $data);
    }

    /**
     * Test JWT token is valid and contains correct payload
     */
    public function test_generated_token_is_valid(): void
    {
        $user = User::factory()->create(['id' => 1]);

        $credentials = [
            'email' => $user->email,
            'password' => 'password123',
        ];

        $user->update(['password' => Hash::make('password123')]);
        $result = AuthService::login($credentials);

        $token = $result['token'];
        $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));

        $this->assertEquals(1, $decoded->sub);
        $this->assertEquals(config('app.url'), $decoded->iss);
    }

    /**
     * Test authentication return format structure
     */
    public function test_authentication_return_format(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        $password = 'password123';
        $user->update(['password' => Hash::make($password)]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => $password,
        ];

        $result = AuthService::login($credentials);

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('id', $result['user']);
        $this->assertArrayHasKey('first_name', $result['user']);
        $this->assertArrayHasKey('last_name', $result['user']);
        $this->assertArrayHasKey('email', $result['user']);

        $this->assertEquals($user->id, $result['user']['id']);
        $this->assertEquals('Test', $result['user']['first_name']);
        $this->assertEquals('User', $result['user']['last_name']);
        $this->assertEquals('test@example.com', $result['user']['email']);
    }

    /**
     * Helper method to mock Google verification
     */
    private function mockGoogleVerification(array $payload): void
    {
        $mock = $this->mock(Google_Client::class);
        $mock->shouldReceive('verifyIdToken')
            ->andReturn($payload);
    }
}
