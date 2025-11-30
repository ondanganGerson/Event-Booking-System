<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user registration with valid data.
     *
     * @return void
     */
    public function testUserCanRegisterWithValidData()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '1234567890',
            'role' => 'customer',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'role',
                    ],
                    'token',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'User registered successfully',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'role' => 'customer',
        ]);
    }

    /**
     * Test user registration defaults to customer role.
     *
     * @return void
     */
    public function testUserRegistrationDefaultsToCustomerRole()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'role' => 'customer',
        ]);
    }

    /**
     * Test user registration fails with duplicate email.
     *
     * @return void
     */
    public function testUserCannotRegisterWithDuplicateEmail()
    {
        factory(User::class)->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation errors',
            ])
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test user registration fails with invalid data.
     *
     * @return void
     */
    public function testUserCannotRegisterWithInvalidData()
    {
        $response = $this->postJson('/api/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation errors',
            ])
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * Test user registration fails when passwords don't match.
     *
     * @return void
     */
    public function testUserCannotRegisterWithMismatchedPasswords()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test user can login with valid credentials.
     *
     * @return void
     */
    public function testUserCanLoginWithValidCredentials()
    {
        $user = factory(User::class)->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                    ],
                    'token',
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ]);

        $this->assertNotNull($user->fresh()->api_token);
    }

    /**
     * Test user cannot login with invalid credentials.
     *
     * @return void
     */
    public function testUserCannotLoginWithInvalidCredentials()
    {
        factory(User::class)->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test user cannot login with non-existent email.
     *
     * @return void
     */
    public function testUserCannotLoginWithNonExistentEmail()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test login requires email and password.
     *
     * @return void
     */
    public function testLoginRequiresEmailAndPassword()
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Test authenticated user can logout.
     *
     * @return void
     */
    public function testAuthenticatedUserCanLogout()
    {
        $user = factory(User::class)->create([
            'api_token' => 'test-token-12345',
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);

        $this->assertNull($user->fresh()->api_token);
    }

    /**
     * Test unauthenticated user cannot logout.
     *
     * @return void
     */
    public function testUnauthenticatedUserCannotLogout()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    /**
     * Test authenticated user can access me endpoint.
     *
     * @return void
     */
    public function testAuthenticatedUserCanAccessMeEndpoint()
    {
        $user = factory(User::class)->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'customer',
            'api_token' => 'test-token-12345',
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'role' => 'customer',
                ],
            ]);
    }

    /**
     * Test unauthenticated user cannot access me endpoint.
     *
     * @return void
     */
    public function testUnauthenticatedUserCannotAccessMeEndpoint()
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    /**
     * Test login generates new API token each time.
     *
     * @return void
     */
    public function testLoginGeneratesNewApiToken()
    {
        $user = factory(User::class)->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'api_token' => 'old-token',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        $this->assertNotEquals('old-token', $user->fresh()->api_token);
    }

    /**
     * Test user can register with different roles.
     *
     * @return void
     */
    public function testUserCanRegisterWithDifferentRoles()
    {
        $roles = ['admin', 'organizer', 'customer'];

        foreach ($roles as $role) {
            $response = $this->postJson('/api/register', [
                'name' => "Test {$role}",
                'email' => "{$role}@example.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => $role,
            ]);

            $response->assertStatus(201);

            $this->assertDatabaseHas('users', [
                'email' => "{$role}@example.com",
                'role' => $role,
            ]);
        }
    }

    /**
     * Test user cannot register with invalid role.
     *
     * @return void
     */
    public function testUserCannotRegisterWithInvalidRole()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'invalid_role',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }
}
