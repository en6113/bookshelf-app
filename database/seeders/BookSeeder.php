<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::all();

        $books = [
            [
                'user_id' => $user->random()->id,
                'title' => '吾輩は猫である',
                'author' => '夏目漱石',
                'isbn' => 9784101010014,
                'published_date' => '1905-01-01',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
                'genre' => '小説',
            ],
            [
                'user_id' => $user->random()->id,
                'title' => '人を動かす',
                'author' => 'D・カーネギー',
                'isbn' => 9784422100524,
                'published_date' => '1936-10-01',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=2',
                'genre' => ['ビジネス', '自己啓発'],
            ],
            [
                'user_id' => $user->random()->id,
                'title' => 'リーダブルコード',
                'author' => 'Dustin Boswell',
                'isbn' => 9784873115658,
                'published_date' => '2012-06-23',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=3',
                'genre' => ['技術書'],
            ],
            [
                'user_id' => $user->random()->id,
                'title' => '7つの習慣',
                'author' => 'スティーブン・R・コヴィー',
                'isbn' => 9784863940246,
                'published_date' => '2013-08-30',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=4',
                'genre' => ['ビジネス', '自己啓発'],
            ],
            [
                'user_id' => $user->random()->id,
                'title' => '坊っちゃん',
                'author' => '夏目漱石',
                'isbn' => 9784101010021,
                'published_date' => '1906-04-01',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=5',
                'genre' => ['小説'],
            ],
            [
                'user_id' => $user->random()->id,
                'title' => 'サピエンス全史',
                'author' => 'ユヴァル・ノア・ハラリ',
                'isbn' => 9784309226712,
                'published_date' => '2016-09-08',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=6',
                'genre' => ['歴史', '科学'],
            ],
            [
                'user_id' => $user->random()->id,
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn' => 9784048930598,
                'published_date' => '2017-12-18',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=7',
                'genre' => ['技術書'],
            ],
            [
                'user_id' => $user->random()->id,
                'title' => '嫌われる勇気',
                'author' => '岸見一郎・古賀史健',
                'isbn' => 9784478025819,
                'published_date' => '2013-12-13',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=8',
                'genre' => ['自己啓発'],
            ],
            [
                'user_id' => $user->random()->id,
                'title' => '火花',
                'author' => '又吉直樹',
                'isbn' => 9784163902302,
                'published_date' => ' 2015-03-11',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=9',
                'genre' => ['小説'],
            ],
            [
                'user_id' => $user->random()->id,
                'title' => 'FACTFULNESS',
                'author' => 'ハンス・ロスリング',
                'isbn' => 9784822289607,
                'published_date' => '2019-01-11',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=10',
                'genre' => ['ビジネス', '科学'],
            ],
            [
                'user_id' => $user->random()->id,
                'title' => 'コンテナ物語',
                'author' => 'マルク・レビンソン',
                'isbn' => 9784822251468,
                'published_date' => '2007-01-18',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=11',
                'genre' => ['ビジネス', '歴史'],
            ],
        ];

        foreach ($books as $data) {
            // dataからgenreを除外し、descriptionをfactoryで生成して合体
            $bookData = collect($data)->except(['genre'])->merge([
                'description' => fake()->realText(200),
            ])->toArray();

            $newBook = Book::firstOrCreate(
                ['isbn' => $data['isbn']],
                $bookData
            );

            // genre名からgenreIdを取得して中間テーブルに保存
            $genreNames = Arr::wrap($data['genre']);
            $genreIds = Genre::whereIn('name', $genreNames)->pluck('id')->toArray();
            $newBook->genres()->sync($genreIds);
        }
    }
}
