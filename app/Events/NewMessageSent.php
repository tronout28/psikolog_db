<?php

namespace App\Events;

use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NewMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(private ChatMessage $chatMessage)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn() 
    {
        return new PrivateChannel('chat.' . $this->chatMessage->chat_id);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * The event's broadcast data.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        $data = [
            'chat_id' => $this->chatMessage->chat_id,
            'message' => $this->chatMessage->toArray(),
            'user_id' => $this->chatMessage->user_id, // Make sure user_id is included here
        ];
    
        Log::info('Broadcast Data:', $data);
    
        return $data;
    
    }
}
