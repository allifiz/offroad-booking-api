<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OperationalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 30;

    public bool $failOnTimeout = true;

    public function __construct(
        public readonly string $event,
        public readonly string $title,
        public readonly string $message,
        public readonly ?string $resourceType = null,
        public readonly int|string|null $resourceId = null,
        public readonly array $meta = [],
    ) {
        $this->afterCommit();
        $this->onQueue('notifications');
    }

    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function via(object $notifiable): array
    {
        return $this->event === 'assignment.offered'
            ? ['database', FcmChannel::class]
            : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'resource_type' => $this->resourceType,
            'resource_id' => $this->resourceId,
            'meta' => $this->meta,
        ];
    }
}
