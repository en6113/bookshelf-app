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

        foreach ($books as $book) {
            $reviewCount = rand(2, 4);

            for ($i = 0; $i < $reviewCount; $i++) {
                Review::factory()->create([
                    'book_id' => $book->id,
                    'user_id' => $users->random()->id,
                ]);
            }
        }
    }
}
