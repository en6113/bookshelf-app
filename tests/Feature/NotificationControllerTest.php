<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ユーザーは通知一覧画面にアクセスでき、自分に送られた通知を見ることができる(): void
    {
        // arrange
        $user = User::factory()->create();
        $notifications = Notification::factory()->count(3)->create([
            'notifiable_id' => $user->id,
        ]);
        $otherUser = User::factory()->create();
        $otherNotification = Notification::factory()->create([
            'notifiable_id' => $otherUser->id,
        ]);

        // act
        $response = $this->actingAs($user)->get(route('notifications.index'));

        // assert
        $response->assertStatus(200);
        $response->assertViewIs('notifications.index');

        $viewNotifications = $response->viewData('notifications');
        $this->assertCount(3, $viewNotifications);
        $this->assertTrue($viewNotifications->contains($notifications->first()));
        $this->assertFalse($viewNotifications->contains($otherNotification));
    }

    /** @test */
    public function 通知一覧画面は新しい順に並んでいる(): void
    {
        // arrange
        $user = User::factory()->create();
        $old = Notification::factory()->create([
            'notifiable_id' => $user->id,
            'created_at' => 2026 - 07 - 13,
        ]);
        $new = Notification::factory()->create([
            'notifiable_id' => $user->id,
            'created_at' => 2026 - 07 - 10,
        ]);

        // act
        $response = $this->actingAs($user)->get(route('notifications.index'));

        // assert
        $viewNotifications = $response->viewData('notifications');
        $this->assertEquals($new->id, $viewNotifications->get(0)->id);
        $this->assertEquals($old->id, $viewNotifications->get(1)->id);
    }

    /** @test */
    public function ユーザーは通知を既読にすることができ、通知一覧画面にリダイレクトされる(): void
    {
        // arrange
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'notifiable_id' => $user->id,
        ]);

        // act
        $response = $this->actingAs($user)->post(route('notifications.read', $notification->id));

        // assert
        $response->assertRedirect(route('notifications.index'));
        $response->assertSessionHas('success', '通知を既読にしました');
        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
            'read_at' => null,
        ]);
    }

    /** @test */
    public function 他人の通知を既読にすることはできない(): void
    {
        // arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherNotification = Notification::factory()->create([
            'notifiable_id' => $otherUser->id,
        ]);

        // act
        $response = $this->actingAs($user)->post(route('notifications.read', $otherNotification->id));

        // assert
        $response->assertNotFound();
        $this->assertDatabaseHas('notifications', [
            'id' => $otherNotification->id,
            'read_at' => null,
        ]);
    }

    /** @test */
    public function 存在しない通知を既読にすることはできない(): void
    {
        // arrange
        $user = User::factory()->create();

        // act
        $response = $this->actingAs($user)->post(route('notifications.read', ['id' => 99999]));

        // assert
        $response->assertNotFound();
    }
}
