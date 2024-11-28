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
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Psr7\Message;

class ChatMessageController extends Controller
{
    protected $firebaseService;
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function index(GetMessageRequest $request)
    {
        $data = $request->validated();
        $chatId = $data['chat_id'];
    
        $messages = ChatMessage::where('chat_id', $chatId)
            ->with('user')
            ->latest('created_at')
            ->get(); 
    
        return response()->json([
            'data' => $messages,
            'status' => 'success',
            'message' => 'Messages have been fetched successfully',
        ]);
    }
    

    public function store(StoreMessageRequest $request)
    {
        $user = auth()->user();

        // Jika user memiliki role 'user', cek apakah memiliki PaketTransaction aktif
        if ($user->role === 'user') {
            // Check if the user has an active PaketTransaction with a valid expiry_date
            $activePaketTransaction = PaketTransaction::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('expiry_date', '>', Carbon::now())
                ->latest('expiry_date')
                ->first();

            // Jika tidak ada paket aktif atau sudah expired, blok akses chat
            if (!$activePaketTransaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your chat access has expired. Please purchase a new package to continue chatting.',
                ], 403);
            }
        }

        // Jika role adalah 'dokter' atau jika ada PaketTransaction aktif untuk 'user', lanjutkan pengiriman pesan
        $data = $request->validated();
        $data['user_id'] = $user->id;

        $chatMessage = ChatMessage::create($data);
        $chatMessage->load('user');

        // Trigger a broadcast event if needed (e.g., to notify other chat participants)
        $this->sendNotificationToOther($chatMessage);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $chatMessage,
        ], 201);
    }


    private function sendNotificationToOther(ChatMessage $chatMessage)
    {
        broadcast(new NewMessageSent($chatMessage))->toOthers();
    
        $chatId = $chatMessage->chat_id;
        $senderUserId = auth()->id();
    
        $chat = Chat::where('id', $chatId)
            ->with(['participants' => function ($query) use ($senderUserId) {
                $query->where('user_id', '!=', $senderUserId);
            }])
            ->first();
    
        if ($chat && $chat->participants->isNotEmpty()) {
            $otherUser = $chat->participants->first();
            $receiverUser = $otherUser->user;
    
            if (!empty($receiverUser->notification_token)) {
                try {
                    $this->firebaseService->sendNotification(
                        $receiverUser->notification_token,
                        $chatMessage->user->name,
                        $chatMessage->message,
                        ''
                    );
                } catch (\Exception $e) {
                    Log::error('Error sending Firebase notification', [
                        'error' => $e->getMessage(),
                        'chat_id' => $chatMessage->chat_id,
                    ]);
                }
            }
        }
    }
}
