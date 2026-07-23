<?php

namespace Tests\Unit\Console;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class KernelTest extends TestCase
{
    /** @test */
    public function 通知送信と期限切れ処理が毎日20時にスケジュールされている(): void
    {
        $schedule = $this->app->make(Schedule::class);
        $events = collect($schedule->events());

        $sendEvent = $events->first(fn($e) => str_contains($e->command, 'notifications:send'));
        $expiredEvent = $events->first(fn($e) => str_contains($e->command, 'reading-plans:expired'));

        $this->assertNotNull($sendEvent);
        $this->assertNotNull($expiredEvent);
        $this->assertSame('0 20 * * *', $sendEvent->expression);
        $this->assertSame('0 20 * * *', $expiredEvent->expression);
    }

    /** @test */
    public function 通知送信は期限切れ処理より先にスケジュールされている(): void
    {
        $schedule = $this->app->make(Schedule::class);
        $commands = collect($schedule->events())->map(fn($e) => $e->command);

        $sendIndex = $commands->search(fn($c) => str_contains($c, 'notifications:send'));
        $expiredIndex = $commands->search(fn($c) => str_contains($c, 'reading-plans:expired'));

        $this->assertLessThan($expiredIndex, $sendIndex);
    }
}
