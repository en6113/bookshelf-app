<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rating = $this->faker->numberBetween(1, 5);

        $comments = [
            1 => '期待外れでした。内容が薄く、あまり参考にならなかったです。',
            2 => '少し読みづらかったです。個人的にはあまり合わなかったです。',
            3 => '普通に面白かったです。世代によって賛否がわかれそうだなと思いました。',
            4 => 'とても面白かったです！気づきが多く、学びになる一冊でした。',
            5 => '最高の一冊です！手元に置いて何度も読み返したいと思います。',
        ];

        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'rating' => $rating,
            'comment' => $comments[$rating],
        ];
    }
}
