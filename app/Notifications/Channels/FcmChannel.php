<?php

namespace App\Notifications\Channels;

use App\Services\FcmPushService;

class FcmChannel
{
    public function __construct(private readonly FcmPushService $push) {}

    public function send(object $notifiable, object $notification): void
    {
        $this->push->sendToUser(
            $notifiable->getKey(),
            $notification->title,
            $notification->message,
            array_filter([
                'event' => $notification->event,
                'resource_type' => $notification->resourceType,
                'resource_id' => $notification->resourceId,
            ], static fn ($value) => $value !== null),
        );
    }
}
