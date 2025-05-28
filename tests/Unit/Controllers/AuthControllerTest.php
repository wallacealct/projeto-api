<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Http\Controllers\Api\AuthController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase; // Use RefreshDatabase for tests involving user creation/DB state
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Mockery; // Although less common for controller unit tests, can be used

class AuthControllerTest extends TestCase
{
    use RefreshDatabase; // Recommended for auth tests to ensure clean DB state

    protected function setUp(): void
    {
        parent::setUp();
        // Mock Log facade to prevent actual logging during tests
        Log::shouldReceive("info")->zeroOrMoreTimes();
        Log::shouldReceive("warning")->zeroOrMoreTimes();
        Log::shouldReceive("error")->zeroOrMoreTimes();
        Log::shouldReceive("debug")->zeroOrMoreTimes();
    }

    protected function tearDown(): void
    {
        Mockery::close(); // Close Mockery if used
        parent::tearDown();
    }

    /** @test */
    public function user_can_register_successfully()
    {
        $userData = [
            "name" => "Test User",
            "email" => "register@example.com",
            "password" => "password123",
            "password_confirmation" => "password123",
        ];

        $response = $this->postJson("/api/auth/register", $userData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     "success",
                     "message",
                     "user" => ["id", "name", "email", "created_at", "updated_at"],
                     "access_token",
                     "token_type",
                     "expires_in",
                 ])
                 ->assertJson([
                     "success" => true,
                     "message" => "Usuário registrado com sucesso!",
                     "user" => [
                         "name" => "Test User",
                         "email" => "register@example.com",
                     ],
                     "token_type" => "bearer",
                 ]);

        $this->assertDatabaseHas("users", [
            "email" => "register@example.com",
            "name" => "Test User",
        ]);
    }

    /** @test */
    public function registration_fails_with_invalid_data()
    {
        $userData = [
            "name" => "Te", // Too short
            "email" => "invalid-email",
            "password" => "123", // Too short
            "password_confirmation" => "12345", // Doesn't match
        ];

        $response = $this->postJson("/api/auth/register", $userData);

        $response->assertStatus(400) // Controller returns 400 on validation fail
                 ->assertJsonStructure([
                     "success",
                     "message",
                     "data" => ["name", "email", "password"],
                 ])
                 ->assertJson([
                     "success" => false,
                     "message" => "Validation errors",
                 ]);
        $this->assertDatabaseMissing("users", ["email" => "invalid-email"]);
    }

     /** @test */
    public function registration_fails_if_email_already_exists()
    {
        // Create a user first
        User::factory()->create(["email" => "existing@example.com"]);

        $userData = [
            "name" => "Another User",
            "email" => "existing@example.com", // Duplicate email
            "password" => "password123",
            "password_confirmation" => "password123",
        ];

        $response = $this->postJson("/api/auth/register", $userData);

        $response->assertStatus(400)
                 ->assertJsonStructure(["success", "message", "data" => ["email"]])
                 ->assertJsonPath("data.email.0", "The email has already been taken."); // Default Laravel message
    }


    /** @test */
    public function user_can_login_successfully()
    {
        $user = User::factory()->create([
            "email" => "login@example.com",
            "password" => Hash::make("password123"),
        ]);

        $credentials = [
            "email" => "login@example.com",
            "password" => "password123",
        ];

        $response = $this->postJson("/api/auth/login", $credentials);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     "success",
                     "access_token",
                     "token_type",
                     "expires_in",
                 ])
                 ->assertJson([
                     "success" => true,
                     "token_type" => "bearer",
                 ]);
    }

    /** @test */
    public function login_fails_with_incorrect_password()
    {
        User::factory()->create([
            "email" => "loginfail@example.com",
            "password" => Hash::make("password123"),
        ]);

        $credentials = [
            "email" => "loginfail@example.com",
            "password" => "wrongpassword",
        ];

        $response = $this->postJson("/api/auth/login", $credentials);

        $response->assertStatus(401)
                 ->assertJson([
                     "success" => false,
                     "message" => "Credenciais inválidas.",
                 ]);
    }

    /** @test */
    public function login_fails_with_non_existent_email()
    {
         $credentials = [
            "email" => "nosuchuser@example.com",
            "password" => "password123",
        ];

        $response = $this->postJson("/api/auth/login", $credentials);

        $response->assertStatus(401)
                 ->assertJson([
                     "success" => false,
                     "message" => "Credenciais inválidas.",
                 ]);
    }

     /** @test */
    public function login_fails_with_validation_errors()
    {
         $credentials = [
            "email" => "notanemail",
            // Missing password
        ];

        $response = $this->postJson("/api/auth/login", $credentials);

        $response->assertStatus(422) // Controller returns 422 on validation fail
                 ->assertJsonStructure(["success", "message", "data" => ["email", "password"]])
                 ->assertJson([
                     "success" => false,
                     "message" => "Validation errors",
                 ]);
    }


    /** @test */
    public function authenticated_user_can_get_their_profile()
    {
        $user = User::factory()->create();
        // Manually generate token for the user
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            "Authorization" => "Bearer " . $token,
        ])->getJson("/api/auth/me");

        $response->assertStatus(200)
                 ->assertJsonStructure(["success", "data" => ["id", "name", "email"]])
                 ->assertJson([
                     "success" => true,
                     "data" => [
                         "id" => $user->id,
                         "email" => $user->email,
                         "name" => $user->name,
                     ]
                 ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_profile()
    {
        $response = $this->getJson("/api/auth/me"); // No token provided

        $response->assertStatus(401); // JWT middleware should return 401
                 //->assertJson(["message" => "Unauthenticated."]); // Check specific message if needed
    }

    /** @test */
    public function authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            "Authorization" => "Bearer " . $token,
        ])->postJson("/api/auth/logout");

        $response->assertStatus(200)
                 ->assertJson([
                     "success" => true,
                     "message" => "Logout realizado com sucesso.",
                 ]);

        // Optionally: Assert the token is blacklisted (requires more setup or specific JWT features)
        // $this->assertTrue(JWTAuth::manager()->getBlacklist()->has(JWTAuth::setToken($token)->getPayload()));
    }

     /** @test */
    public function unauthenticated_user_cannot_logout()
    {
        $response = $this->postJson("/api/auth/logout"); // No token

        $response->assertStatus(401);
                 //->assertJson(["message" => "Unauthenticated."]);
    }


    /** @test */
    public function authenticated_user_can_refresh_token()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Wait a second to ensure the new token is different if timestamps are involved
        sleep(1);

        $response = $this->withHeaders([
            "Authorization" => "Bearer " . $token,
        ])->postJson("/api/auth/refresh");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     "success",
                     "access_token",
                     "token_type",
                     "expires_in",
                 ])
                 ->assertJson(["success" => true, "token_type" => "bearer"]);

        // Assert the new token is different from the old one
        $newToken = $response->json("access_token");
        $this->assertNotNull($newToken);
        $this->assertNotEquals($token, $newToken);
    }

    /** @test */
    public function unauthenticated_user_cannot_refresh_token()
    {
        $response = $this->postJson("/api/auth/refresh"); // No token

        $response->assertStatus(401);
                 //->assertJson(["message" => "Unauthenticated."]);
    }

}

