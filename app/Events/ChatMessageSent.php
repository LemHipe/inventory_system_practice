<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $chatId,
        public readonly string $messageId,
        public readonly int $senderId,
        public readonly string $content,
        public readonly string $createdAt,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('chat.' . $this->chatId);
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->messageId,
            'chat_id' => $this->chatId,
            'sender_id' => $this->senderId,
            'content' => $this->content,
            'created_at' => $this->createdAt,
        ];
    }
}
