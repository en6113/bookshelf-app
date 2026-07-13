<?php

namespace Tests\Feature\Console;

use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneNotificationsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 通知後30日を経過した通知は削除される(): void
    {
        $notification = Notification::factory()->create([
            'created_at' => now()->subDays(32),
        ]);

        $this->artisan('notifications:prune')->assertExitCode(0);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    /** @test */
    public function 通知後29日の通知は削除されない(): void
    {
        $notification = Notification::factory()->create([
            'created_at' => now()->subDays(29),
        ]);

        $this->artisan('notifications:prune')->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
        ]);
    }
}
