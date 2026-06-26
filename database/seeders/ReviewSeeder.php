<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        $distribution = [2, 3, 4, 2, 3, 4, 2, 3, 4, 2, 3]; // 合計32件

        foreach ($books as $index => $book) {
            $shuffledUsers = $users->shuffle();

            for ($i = 0; $i < $distribution[$index]; $i++) {
                $user = $shuffledUsers[$i];

                Review::factory()->create([
                    'book_id' => $book->id,
                    'user_id' => $user->id,
                ]);
            }
        }
    }
}
