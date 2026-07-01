<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateGenreRequestTest extends TestCase
{
    use RefreshDatabase;

    public function validator(array $data, ?int $genreId): \Illuminate\Validation\Validator
    {
        $request = new UpdateGenreRequest;

        if ($genreId) {
            $route = new Route('PUT', '/genres/{genre}', []);
            $route->bind(new Request);
            $route->setParameter('genre', $genreId);

            $request->setRouteResolver(fn () => $route);
        }

        return Validator::make($data, $request->rules(), $request->messages());
    }

    /** @test */
    public function 更新時にジャンル名が自分自身のレコードと重複していてもバリデーションエラーにならない(): void
    {
        // Arrange
        $existingGenre = Genre::factory()->create(['name' => '存在するジャンル名']);

        // Act
        $validator = $this->validator(['name' => $existingGenre->name], $existingGenre->id);

        // Assert
        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function 更新時にジャンル名が他のレコードと重複している場合はバリデーションエラーになる(): void
    {
        // Arrange
        $existingGenre = Genre::factory()->create(['name' => '存在するジャンル名']);
        $myGenre = Genre::factory()->create(['name' => '更新前のジャンル名']);

        // Act
        $validator = $this->validator(['name' => '存在するジャンル名'], $myGenre->id);

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }
}
