<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $reviews = Review::all();

        foreach ($reviews as $review) {
            $count = fake()->numberBetween(0, 3);

            $otherUsers = $users->reject(fn ($user) => $user->id === $review->user_id);

            $userIds = $otherUsers->random($count)->pluck('id');

            $review->LikedByUsers()->syncWithoutDetaching($userIds);
        }
    }
}
