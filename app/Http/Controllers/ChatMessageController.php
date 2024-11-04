<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetMessageRequest;
use App\Http\Requests\StoreMessageRequest;
use App\Models\ChatMessage;
use GuzzleHttp\Psr7\Message;

class ChatMessageController extends Controller
{
    public function index(GetMessageRequest $request)
    {
        $data = $request->validated();

        $chatId = $data['chat_id'];
        $currentPage = $data['page'];
        $pageSize = $data['page_size'] ?? 15;

        $messages = ChatMessage::where('chat_id', $chatId)
            ->with('user')
            ->latest('created_at')
            ->simplePaginate(
                $pageSize, 
                ['*'], 
                'page', 
                $currentPage
            );

        return response()->json([
            'data'=>$messages->getCollection(),
            'status' => 'success',
            'message' => 'messages has been fetched successfully',
        ]);
    }

    public function store(StoreMessageRequest $request)
    {
        $data = $request->validated();

        $data['user_id'] = auth()->user()->id;

        $chatMessage = ChatMessage::create($data);
        $chatMessage->load('user');

        // TODO send broadcast event to pusher and send notification to on signal services

        return response()->json([$chatMessage,'message has sent successfully'], 201);
    }
}
