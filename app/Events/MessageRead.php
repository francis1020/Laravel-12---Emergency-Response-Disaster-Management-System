<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Message;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageIds;
    public $reportId;
    public $readByUserId;
    public $readByUserName;
    public $allReaders; // Array of message_id => [readers]

    public function __construct($messageIds, $reportId, $readByUserId, $readByUserName = null, $allReaders = [])
    {
        $this->messageIds = is_array($messageIds) ? $messageIds : [$messageIds];
        $this->reportId = $reportId;
        $this->readByUserId = $readByUserId;
        $this->readByUserName = $readByUserName;
        $this->allReaders = $allReaders; // Format: [messageId => [['user_id' => X, 'user_name' => 'Name'], ...]]
    }

    public function broadcastOn()
    {
        return new Channel('message.read');
    }

    public function broadcastAs()
    {
        return 'message.read';
    }
}
