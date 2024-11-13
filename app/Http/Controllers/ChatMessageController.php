<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetMessageRequest;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Chat;
use App\Models\PaketTransaction;
use App\Services\FirebaseService;
use App\Models\ChatMessage;
use Carbon\Carbon;
use App\Events\NewMessageSent;
use GuzzleHttp\Psr7\Message;

class ChatMessageController extends Controller
{
    // protected $firebaseService;
    // public function __construct(FirebaseService $firebaseService)
    // {
    //     $this->firebaseService = $firebaseService;
    // }

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
        $user = auth()->user();
        
        // Check if the user has an active PaketTransaction with a valid expiry_date
        $activePaketTransaction = PaketTransaction::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expiry_date', '>', Carbon::now())
            ->latest('expiry_date')
            ->first();

        // If no active package is found or the package has expired, block chat access
        if (!$activePaketTransaction) {
            return response()->json([
                'success' => false,
                'message' => 'Your chat access has expired. Please purchase a new package to continue chatting.',
            ], 403);
        }

        // If active package exists, proceed with sending the message
        $data = $request->validated();
        $data['user_id'] = $user->id;

        $chatMessage = ChatMessage::create($data);
        $chatMessage->load('user');

        // Trigger a broadcast event if needed (e.g., to notify other chat participants)
        // broadcast(new NewMessageSent($chatMessage))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $chatMessage,
        ], 201);
    }

    private function sendNotificationToOther(ChatMessage $chatMessage)
    {
        //$chatId = $chatMessage->chat_id;

       // broadcast(new NewMessageSent($chatMessage))->toOthers();
    }
}
