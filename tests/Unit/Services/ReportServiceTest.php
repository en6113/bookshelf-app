<?php

namespace Tests\Unit\Services;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ReportService;
    }

    /** @test */
    public function 総レビュー数が正しく取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        Review::factory()->for($user)->count(3)->create();
        Review::factory()->count(10)->create();

        // Act
        $result = $this->service->getReadingStats($user);

        // Assert
        $this->assertEquals(3, $result['summary']['total_reviews']);
    }

    /** @test */
    public function 読了冊数が正しく取得できる(): void
    {
        /*
        // Arrange
        $user = User::factory()->create();
        ReadingPlan::factory()->for($user)->count(3)->create([
            'status' => 'completed',
        ]);
        ReadingPlan::factory()->for($user)->count(3)->create([
            'status' => 'progress',
        ]);
        ReadingPlan::factory()->count(10)->create([
            'status' => 'completed',
        ]);

        // Act
        $result = $this->service->getReadingStats($user);

        // Assert
        $this->assertEquals(3, $result['summary']['books_read']);
        */
    }

    /** @test */
    public function 平均評価が正しく取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        Review::factory()->for($user)->create(['rating' => 5]);
        Review::factory()->for($user)->create(['rating' => 4]);
        Review::factory()->for($user)->create(['rating' => 4]);

        // Act
        $result = $this->service->getReadingStats($user);

        // Assert
        $this->assertEquals(4.33, round($result['summary']['average_rating'], 2));
    }

    /** @test */
    public function 評価ごとのレビュー件数が正しく取得できる(): void
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
    public function 高評価書籍top5は、評価4以上の書籍で構成され最大5件かつ高評価順になっている(): void
    {
        // Arrange
        $user = User::factory()->create();
        Book::factory()->count(2)->hasReviews(1, ['user_id' => $user->id, 'rating' => 5])->create();
        Book::factory()->count(3)->hasReviews(1, ['user_id' => $user->id, 'rating' => 4])->create();

        Book::factory()->count(1)->hasReviews(1, ['user_id' => $user->id, 'rating' => 4])->create();
        Book::factory()->count(1)->hasReviews(1, ['user_id' => $user->id, 'rating' => 3])->create();

        // Act
        $result = $this->service->getReadingStats($user);

        // Assert
        $this->assertCount(5, $result['top_rated_books']);

        $this->assertEquals(5, $result['top_rated_books'][0]['rating']);
        $this->assertEquals(5, $result['top_rated_books'][1]['rating']);
        $this->assertEquals(4, $result['top_rated_books'][2]['rating']);
        $this->assertEquals(4, $result['top_rated_books'][3]['rating']);
        $this->assertEquals(4, $result['top_rated_books'][4]['rating']);
    }

    /** @test */
    public function ジャンル別評価傾向top5は、ジャンルごとのレビュー数と平均評価が正しく取得され平均評価順になっている(): void
    {
        // Arrange
        $user = User::factory()->create();

        $highRatingGenre = Genre::factory()->create();
        $middleRatingGenre = Genre::factory()->create();
        $lowRatingGenre = Genre::factory()->create();
        $nonRatingGenre = Genre::factory()->create();

        Book::factory()->count(2)
            ->hasReviews(1, ['user_id' => $user->id, 'rating' => 5])
            ->hasAttached($highRatingGenre)
            ->create();

        Book::factory()->count(3)
            ->hasReviews(1, ['user_id' => $user->id, 'rating' => 4])
            ->hasAttached($middleRatingGenre)
            ->create();

        Book::factory()->count(2)
            ->hasReviews(1, ['user_id' => $user->id, 'rating' => 2])
            ->hasAttached($lowRatingGenre)
            ->create();

        $otherUser = User::factory()->create();
        Book::factory()->hasReviews(1, ['user_id' => $otherUser->id, 'rating' => 5])
            ->hasAttached($lowRatingGenre)
            ->create();

        // Act
        $result = $this->service->getReadingStats($user);

        // Assert
        $this->assertCount(3, $result['genre_ratings']);

        $this->assertEquals($highRatingGenre->id, $result['genre_ratings'][0]['id']);
        $this->assertEquals(2, $result['genre_ratings'][0]['count']);
        $this->assertEquals(5.0, $result['genre_ratings'][0]['average_rating']);

        $this->assertEquals($middleRatingGenre->id, $result['genre_ratings'][1]['id']);
        $this->assertEquals(3, $result['genre_ratings'][1]['count']);
        $this->assertEquals(4.0, $result['genre_ratings'][1]['average_rating']);

        $this->assertEquals($lowRatingGenre->id, $result['genre_ratings'][2]['id']);
        $this->assertEquals(2, $result['genre_ratings'][2]['count']);
        $this->assertEquals(2.0, $result['genre_ratings'][2]['average_rating']);

        foreach ($result['genre_ratings'] as $genreRating) {
            $this->assertGreaterThanOrEqual(1, $genreRating['average_rating']);
        }
    }
}
