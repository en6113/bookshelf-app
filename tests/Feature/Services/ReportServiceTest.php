<?php

namespace Tests\Feature\Services;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ReportService;
    }

    /** @test */
    public function 総レビュー数、読書冊数、平均評価が正しく取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        Review::factory()->for($user)->create(['rating' => 5]);
        Review::factory()->for($user)->create(['rating' => 4]);
        Review::factory()->for($user)->create(['rating' => 4]);
        $otherUser = User::factory()->create();
        Review::factory()->for($otherUser)->count(2)->create();

        // Act
        $result = $this->service->getReadingStats($user);

        // Assert
        $this->assertSame(3, $result['summary']['total_reviews']);
        $this->assertSame(3, $result['summary']['books_read']);
        $this->assertSame(4.33, $result['summary']['average_rating']);
    }

    /** @test */
    public function 読了冊数の統計で、同じ書籍への複数レビューは1冊と数える(): void
    {
        // Arrange
        $user = User::factory()->create();
        $book = Book::factory()->create();
        Review::factory()->for($user)->for($book)->count(2)->create();

        // Act
        $result = $this->service->getReadingStats($user);

        // Assert
        $this->assertSame(2, $result['summary']['total_reviews']);
        $this->assertSame(1, $result['summary']['books_read']);
    }

    /** @test */
    public function 評価分布は、評価ごとのレビュー件数が正しく取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        Review::factory()->for($user)->count(2)->create(['rating' => 5]);
        Review::factory()->for($user)->count(3)->create(['rating' => 3]);
        Review::factory()->for($user)->count(1)->create(['rating' => 1]);

        // Act
        $result = $this->service->getReadingStats($user);

        // Assert
        $expectedDistribution = [1, 0, 3, 0, 2];
        $this->assertSame($expectedDistribution, $result['rating_distribution']->toArray());
    }

    /** @test */
    public function 高評価書籍top5は、評価4以上の書籍で構成され評価とレビュー作成日時の降順になっている(): void
    {
        // Arrange
        $user = User::factory()->create();
        Book::factory()->count(2)->hasReviews(1, ['user_id' => $user->id, 'rating' => 5])->create();
        Book::factory()->hasReviews(1, ['user_id' => $user->id, 'rating' => 3])->create();

        Book::factory()->hasReviews(1, ['user_id' => $user->id, 'rating' => 4, 'created_at' => now()->subDay()])->create(['title' => '表示されない本']);
        Book::factory()->count(3)->hasReviews(1, ['user_id' => $user->id, 'rating' => 4])->create();

        // Act
        $result = $this->service->getReadingStats($user);

        // Assert
        $this->assertSame([5, 5, 4, 4, 4], array_column($result['top_rated_books'], 'rating'));

        $titles = array_column($result['top_rated_books'], 'title');
        $this->assertNotContains('表示されない本', $titles);
    }

    /** @test */
    public function 高評価書籍top5は、最大5件表示される(): void
    {
        // Arrange
        $user = User::factory()->create();
        Book::factory()->count(6)->hasReviews(1, ['user_id' => $user->id, 'rating' => 5])->create();

        // Act
        $result = $this->service->getReadingStats($user);

        // Assert
        $this->assertCount(5, $result['top_rated_books']);
    }

    /** @test */
    public function ジャンル別評価傾向top5は、ジャンルごとのレビュー数と平均評価が正しく取得され平均評価順になっている(): void
    {
        // Arrange
        $user = User::factory()->create();

        $highRatingGenre = Genre::factory()->create();
        $lowRatingGenre = Genre::factory()->create();

        Book::factory()
            ->hasReviews(1, ['user_id' => $user->id, 'rating' => 5])
            ->hasAttached($highRatingGenre)
            ->create();

        Book::factory()
            ->hasReviews(1, ['user_id' => $user->id, 'rating' => 4])
            ->hasAttached($highRatingGenre)
            ->create();

        Book::factory()
            ->hasReviews(1, ['user_id' => $user->id, 'rating' => 3])
            ->hasAttached($lowRatingGenre)
            ->create();

        Book::factory()
            ->hasReviews(1, ['user_id' => $user->id, 'rating' => 2])
            ->hasAttached($lowRatingGenre)
            ->create();

        // Act
        $result = $this->service->getReadingStats($user);

        // Assert
        $this->assertCount(2, $result['genre_ratings']);

        $this->assertSame($highRatingGenre->id, $result['genre_ratings'][0]['id']);
        $this->assertSame(2, $result['genre_ratings'][0]['count']);
        $this->assertSame(4.5, $result['genre_ratings'][0]['average_rating']);

        $this->assertSame($lowRatingGenre->id, $result['genre_ratings'][1]['id']);
        $this->assertSame(2, $result['genre_ratings'][1]['count']);
        $this->assertSame(2.5, $result['genre_ratings'][1]['average_rating']);
    }

    /** @test */
    public function ジャンル別評価傾向top5は最大5件表示される(): void
    {
        // Arrange
        $user = User::factory()->create();
        $genres = Genre::factory()->count(6)->create();

        Book::factory()->count(6)
            ->hasReviews(1, ['user_id' => $user->id, 'rating' => 4])
            ->hasAttached($genres)
            ->create();

        // Act
        $result = $this->service->getReadingStats($user);

        // Assert
        $this->assertCount(5, $result['genre_ratings']);
    }

    /** @test */
    public function ジャンル別評価傾向top5は、1冊が複数ジャンルをもつとき、その評価を各ジャンルに数える(): void
    {
        // Arrange
        $user = User::factory()->create();
        $genres = Genre::factory()->count(5)->create();

        Book::factory()
            ->hasReviews(1, ['user_id' => $user->id, 'rating' => 4])
            ->hasAttached($genres)
            ->create();

        // Act
        $result = $this->service->getReadingStats($user);

        // Assert
        $this->assertCount(5, $result['genre_ratings']);
        foreach ($result['genre_ratings'] as $genreRating) {
            $this->assertSame(1, $genreRating['count']);
            $this->assertSame(4.0, $genreRating['average_rating']);
        }
    }

    /** @test */
    public function レビューが0件の場合は空の統計が返る(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->service->getReadingStats($user);

        // Assert
        $this->assertSame(0, $result['summary']['total_reviews']);
        $this->assertSame(0, $result['summary']['books_read']);
        $this->assertEquals(0, $result['summary']['average_rating']);
        $this->assertSame([0, 0, 0, 0, 0], $result['rating_distribution']->toArray());
        $this->assertSame([], $result['top_rated_books']);
        $this->assertSame([], $result['genre_ratings']);
    }
}
