<?php

namespace Tests\Unit\Notifications;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanReminderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function timingが期日経過時の場合、正しいタイトルとメッセージが生成される(): void
    {
        $book = Book::factory()->create(['title' => '期日経過時のタイトル']);
        $plan = ReadingPlan::factory()->create(['book_id' => $book->id]);

        $notification = new ReadingPlanReminder($plan, 'over_due_date');
        $array = $notification->toArray($plan->user);

        $this->assertEquals('期日が過ぎました', $array['title']);
        $this->assertStringContainsString('期日経過時のタイトル', $array['body']);
    }

    /** @test */
    public function timingが期日3日前の場合、正しいタイトルとメッセージが生成される(): void
    {
        $book = Book::factory()->create(['title' => '期日3日前のタイトル']);
        $plan = ReadingPlan::factory()->create(['book_id' => $book->id]);

        $notification = new ReadingPlanReminder($plan, 'before_3_days');
        $array = $notification->toArray($plan->user);

        $this->assertEquals('まもなく期日です', $array['title']);
        $this->assertStringContainsString('期日3日前のタイトル', $array['body']);
    }

    /** @test */
    public function timingが期日当日の場合、正しいタイトルとメッセージが生成される(): void
    {
        $book = Book::factory()->create(['title' => '期日当日のタイトル']);
        $plan = ReadingPlan::factory()->create(['book_id' => $book->id]);

        $notification = new ReadingPlanReminder($plan, 'today');
        $array = $notification->toArray($plan->user);

        $this->assertEquals('本日が期日です', $array['title']);
        $this->assertStringContainsString('期日当日のタイトル', $array['body']);
    }

    /** @test */
    public function timingが期日3日後の場合、正しいタイトルとメッセージが生成される(): void
    {
        $book = Book::factory()->create(['title' => '期日3日後のタイトル']);
        $plan = ReadingPlan::factory()->create(['book_id' => $book->id]);

        $notification = new ReadingPlanReminder($plan, 'after_3_days');
        $array = $notification->toArray($plan->user);

        $this->assertEquals('期日が過ぎています', $array['title']);
        $this->assertStringContainsString('期日3日後のタイトル', $array['body']);
    }
}
