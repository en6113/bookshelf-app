<?php

namespace Tests\Feature\Api;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function メールが未入力の場合はバリデーションエラーになる(): void
    {
        $response = $this->postJson('/api/login', ['password' => 'password']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    /** @test */
    public function パスワードが未入力の場合はバリデーションエラーになる(): void
    {
        $response = $this->postJson('/api/login', ['email' => 'test@example.com']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }

    /** @test */
    public function ユーザーはトークンを_jso_n形式で取得できる(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token']);
    }

    /** @test */
    public function 存在しないemailの場合、トークンを取得できない(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'unexit@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function パスワードが異なる場合、トークンを取得できない(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function 取得したトークンで保護_ap_iにアクセスできる(): void
    {
        // Arrange
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);
        $genres = Genre::factory()->count(2)->create();
        $validData = [
            'user_id' => $user->id,
            'title' => 'テストタイトル',
            'author' => '著者名',
            'isbn' => '1234567890123',
            'published_date' => '2026/05/30',
            'genres' => $genres->pluck('id')->toArray(),
        ];

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->json('token');

        // Act
        $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/books', $validData);

        // Assert
        $response->assertStatus(201);
    }
}
