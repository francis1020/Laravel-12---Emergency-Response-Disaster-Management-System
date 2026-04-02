<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Message;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new Channel('message.sent');
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }

    public function broadcastWith()
    {
        // Ensure user relationship is loaded
        if (!$this->message->relationLoaded('user')) {
            $this->message->load('user');
        }
        
        // Get the serialized message data
        $messageArray = $this->message->toArray();
        
        // Ensure user avatar is included in the broadcast
        if ($this->message->user) {
            // Initialize user array if not present
            if (!isset($messageArray['user'])) {
                $messageArray['user'] = [];
            }
            
            // Avatar should already be set by controller using generateUserAvatar() method
            // Use the avatar from the message object to ensure consistency
            if (isset($this->message->user->avatar) && !empty($this->message->user->avatar)) {
                $messageArray['user']['avatar'] = $this->message->user->avatar;
            } else {
                // Fallback: Generate avatar using same method as navigation bar (default settings)
                // This ensures same name = same color as navigation bar avatar
                try {
                    // Use default Laravolt Avatar settings (100x100 from config) - matches navigation bar
                    // Same method call as navigation bar: \Laravolt\Avatar\Facade::create($name)->toBase64()
                    $avatar = \Laravolt\Avatar\Facade::create($this->message->user->name ?? 'Unknown')->toBase64();
                    if (!empty($avatar)) {
                        $messageArray['user']['avatar'] = $avatar;
                    }
                } catch (\Exception $e) {
                    // Avatar generation failed - frontend will use fallback
                }
            }
            
            // Ensure user name and id are included
            if (!isset($messageArray['user']['name']) && $this->message->user->name) {
                $messageArray['user']['name'] = $this->message->user->name;
            }
            if (!isset($messageArray['user']['id']) && $this->message->user->id) {
                $messageArray['user']['id'] = $this->message->user->id;
            }
        }
        
        // Return the message data with avatar included
        // The 'message' key matches what SerializesModels would create from the $message property
        return [
            'message' => $messageArray
        ];
    }
}

