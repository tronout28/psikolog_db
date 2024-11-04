<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Http\Requests\GetChatRequest;
use App\Http\Requests\StoreChatRequest;


class ChatController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(GetChatRequest $request)
    {
        $data = $request->validated();

        $isPrivate = 1;

        if ($request->has('is_private')) {
            $isPrivate = (int) $data['is_private'];
        }

        $chats = Chat::where('is_private', $isPrivate)
            ->hasParticipant(auth()->user()->id)
            ->with(['lastmessage.user', 'participants.user']) // Eager load relationships
            ->latest('updated_at')
            ->get();

        return response()->json($chats);
    }

    

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreChatRequest $request)
    {
       $data = $this->prepareStoreData($request);

       if($data ['userId'] === $data['otherUserId']){
           return response()->json(['message' => 'You cannot create chat with yourself'], 422);
       }

       $previousChat = $this->gtetPreviousChat($data['otherUserId']);

       if($previousChat === null){
              $chat = Chat::create($data['data']);
              $chat->participants()->createMany([
                ['user_id' => $data['userId']],
                ['user_id' => $data['otherUserId']]
              ]);

              $chat->refresh()->load('participants.user');
              return response()->json($chat);
         } else {
              return response()->json($previousChat->load('lastmessage.user', 'participants.user'));
       }
    }

    private function gtetPreviousChat(int $otherUserId) : mixed
    {
        $userId = auth()->user()->id;
        
        return Chat::where('is_private', 1)
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->whereHas('participants', function ($q) use ($otherUserId) {
                $q->where('user_id', $otherUserId);
            })
            ->first();
    }

    private function prepareStoreData(StoreChatRequest $request)
    {
        $data = $request->validated();
        $otherUserId = (int)$data['user_id'];

        unset($data['user_id']);
        $data['created_by'] = auth()->user()->id;

        return [
            'otherUserId' => $otherUserId,
            'userId'=> auth()->user()->id,
            'data' => $data
        ];
    }

    /**
     * Display the specified resource.
     */
    public function show(Chat $chat)
    {
        $chat->load('lastmessage.user', 'participants.user');

        return response()->json($chat);
    }
}
