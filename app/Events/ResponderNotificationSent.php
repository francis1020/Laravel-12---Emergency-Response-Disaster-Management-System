<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResponderNotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;
    public $targetUserId;

    public function __construct(array $notificationData, $targetUserId)
    {
        $this->data = $notificationData;
        $this->targetUserId = $targetUserId;
    }

    public function broadcastOn()
    {
        return new Channel('user.' . $this->targetUserId);
    }

    public function broadcastAs()
    {
        return 'responder.notification';
    }
}

