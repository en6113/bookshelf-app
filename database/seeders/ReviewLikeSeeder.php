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

        foreach ($users as $user) {
            $count = fake()->numberBetween(0, 3);

            $reviewIds = $reviews->random($count)->pluck('id');

            $user->Likedreviews()->syncWithoutDetaching($reviewIds);
        }
    }
}
