<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        foreach ($users as $user) {
            $count = fake()->numberBetween(3, 5);

            $bookIds = $books->random($count)->pluck('id');

            $user->favoriteBooks()->syncWithoutDetaching($bookIds);
        }
    }
}
