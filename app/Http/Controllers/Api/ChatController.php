<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use Domain\Auth\ValueObjects\UserId;
use Domain\Chat\Services\ChatService;
use Domain\Chat\ValueObjects\ChatId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService
    ) {}

    public function index(): JsonResponse
    {
        $chats = $this->chatService->listChats();

        return response()->json([
            'success' => true,
            'data' => array_map(function ($chat) {
                return [
                    'id' => $chat->getId()->getValue(),
                    'name' => $chat->getName(),
                    'created_by' => $chat->getCreatedBy()->getValue(),
                    'created_at' => $chat->getCreatedAt()->format('Y-m-d H:i:s'),
                ];
            }, $chats),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $chat = $this->chatService->createChat(
            $validated['name'],
            new UserId((int) $request->user()->id)
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $chat->getId()->getValue(),
                'name' => $chat->getName(),
                'created_by' => $chat->getCreatedBy()->getValue(),
                'created_at' => $chat->getCreatedAt()->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    public function messages(string $chatId): JsonResponse
    {
        $messages = $this->chatService->getMessages(new ChatId($chatId), 50);

        return response()->json([
            'success' => true,
            'data' => array_map(function ($m) {
                return [
                    'id' => $m->getId()->getValue(),
                    'chat_id' => $m->getChatId()->getValue(),
                    'sender_id' => $m->getSenderId()->getValue(),
                    'content' => $m->getContent(),
                    'created_at' => $m->getCreatedAt()->format('Y-m-d H:i:s'),
                ];
            }, $messages),
        ]);
    }

    public function sendMessage(Request $request, string $chatId): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $message = $this->chatService->sendMessage(
            new ChatId($chatId),
            new UserId((int) $request->user()->id),
            $validated['content']
        );

        event(new ChatMessageSent(
            chatId: $message->getChatId()->getValue(),
            messageId: $message->getId()->getValue(),
            senderId: $message->getSenderId()->getValue(),
            content: $message->getContent(),
            createdAt: $message->getCreatedAt()->format('Y-m-d H:i:s')
        ));

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $message->getId()->getValue(),
                'chat_id' => $message->getChatId()->getValue(),
                'sender_id' => $message->getSenderId()->getValue(),
                'content' => $message->getContent(),
                'created_at' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }
}
