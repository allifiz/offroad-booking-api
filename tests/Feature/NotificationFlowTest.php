<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\OperationalNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_and_mark_notifications_as_read(): void
    {
        $user = User::factory()->customer()->create();
        $user->notify(new OperationalNotification(
            event: 'payment.paid',
            title: 'Pembayaran diterima',
            message: 'Pembayaran kamu sudah diterima.',
            resourceType: 'payment',
            resourceId: 10,
        ));

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/notifications?unread_only=1');
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unread_count', 1)
            ->assertJsonPath('data.notifications.data.0.data.event', 'payment.paid');

        $notificationId = $user->unreadNotifications()->firstOrFail()->id;

        $this->patchJson("/api/v1/notifications/{$notificationId}/read")
            ->assertOk()
            ->assertJsonPath('data.read_at', fn ($value) => $value !== null);

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::factory()->driver()->create();
        $other = User::factory()->driver()->create();
        $owner->notify(new OperationalNotification('assignment.offered', 'Assignment baru', 'Ada assignment baru.'));
        $notificationId = $owner->notifications()->firstOrFail()->id;

        Sanctum::actingAs($other);

        $this->patchJson("/api/v1/notifications/{$notificationId}/read")->assertNotFound();
        $this->assertNull($owner->notifications()->firstOrFail()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->admin()->create();
        $user->notify(new OperationalNotification('assignment.accepted', 'Assignment diterima', 'Driver menerima assignment.'));
        $user->notify(new OperationalNotification('withdrawal.paid', 'Withdrawal dibayar', 'Withdrawal telah dibayar.'));

        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.updated', 2);

        $this->assertSame(0, $user->unreadNotifications()->count());
    }
}
