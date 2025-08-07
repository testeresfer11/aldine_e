<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Traits\SendResponseTrait;
use App\Models\{ChatRoom, ChatRoomUser, ChatMessage, StudyRoom, User, ChatMessageRead};

class ChatController extends Controller
{
    use SendResponseTrait;

    /**
     * functionName : create
     * createdDate  : 15-04-2025
     * purpose      : Create Chat room
     */

    public function create(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255|unique:chat_rooms,name',
        ]);

        // Create the chat room
        $chatRoom = ChatRoom::create([
            'name' => $request->name,

        ]);

        return $this->apiResponse('success', 200, 'Chat Room created successfully', $chatRoom);
    }


    /*end method create */

    /**
     * functionName : addUserToRoom
     * createdDate  : 15-04-2025
     * purpose      : Add user to chat room so that they can send and view messages
     */


    public function addUserToRoom(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'chat_room_id' => 'required|exists:chat_rooms,id',
            'user_id' => 'required|exists:users,id',
        ]);

        // Check if the user is already in the chat room 
        $existing = ChatRoomUser::where('chat_room_id', $request->chat_room_id)
            ->where('user_id', $request->user_id)
            ->first();

        if ($existing) {
            return $this->apiResponse('error', 200, 'User already added to the chat room.');
        }

        // Add user to the chat room
        $chatRoomUser = ChatRoomUser::create([
            'chat_room_id' => $request->chat_room_id,
            'user_id' => $request->user_id,
        ]);

        return $this->apiResponse('success', 200, 'User Added sucesfully in chat room', $chatRoomUser);
    }
    /*end method addUserToRoom */


    /**
     * functionName : saveMessage
     * createdDate  : 15-04-2025
     * purpose      : Send message to a chat roon and save in database
     */


    public function saveMessage(Request $request)
    {
        $request->validate([
            'chat_room_id' => 'required|exists:chat_rooms,id',
            'sender_id'    => 'required|exists:users,id',
            'message'      => 'nullable|string',
            'image'        => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
            'voice'        => 'nullable|file|mimes:mp3,wav,ogg|max:5120',
            'type'         => 'required'
        ]);

        $data = [
            'chat_room_id' => $request->chat_room_id,
            'sender_id'    => $request->sender_id,
            'message'      => $request->message,
            'type'        =>  $request->type,
        ];

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('images', $imageName, 'public');
            $data['image_url'] = config('app.url') . '/storage/' . $path;
        }

        if ($request->hasFile('voice')) {
            $voice = $request->file('voice');
            $voiceName = time() . '_' . uniqid() . '.' . $voice->getClientOriginalExtension();
            $path = $voice->storeAs('images', $voiceName, 'public');
            $data['voice_url'] = config('app.url') . '/storage/' . $path;
        }

        $message = ChatMessage::create($data);
        //send push notification to all users in the chat room
        $chatRoomUsers = ChatRoomUser::where('chat_room_id', $request->chat_room_id)->pluck('user_id')->toArray();
        $chatRoomUsers = array_diff($chatRoomUsers, [$request->sender_id]); // Exclude the sender
        foreach ($chatRoomUsers as $userId) {
            $this->sendPushNotification(
                'New Message in Chat Room',
                'You have a new message',
                'chat_message',
                $userId
            );
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Message saved successfully',
            'data'    => $message,
        ]);
    }


    /*end method saveMessage */


    public function getUserChatRooms()
    {
        $currentUserId = auth()->id();

        if (!$currentUserId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }

        $chatData = [];

        // 1. Group chat rooms (based on StudyRoom)
        $studyRooms = StudyRoom::whereHas('members', fn($q) => $q->where('user_id', $currentUserId))
            ->with('members')
            ->get();

        foreach ($studyRooms as $room) {
            $chatRoomName = 'StudyRoom_' . $room->id;
            $chatRoom = ChatRoom::where('name', $chatRoomName)->first();

            $lastMessage = $chatRoom
                ? ChatMessage::where('chat_room_id', $chatRoom->id)->latest('created_at')->first()
                : null;

            $chatData[] = [
                'room_id'        => $room->id,
                'name'           => $room->name,
                'is_group'       => true,
                'avatar'         => asset("storage/group-avatars/{$room->id}.png"),
                'chat_room_id'   => $chatRoom->id ?? null,
                'latest_message' => $lastMessage ? [
                    'sender_id'     => $lastMessage->sender_id,
                    'text'          => $lastMessage->message,
                    'image_message' => $lastMessage->image_url,
                    'voice_message' => $lastMessage->voice_url,
                    'sent_at'       => $lastMessage->created_at->diffForHumans(),
                ] : null,
                'unread_count'   => $chatRoom
                    ? $this->getUnreadCountForRoom($chatRoom->id, $currentUserId)
                    : 0,
                'latest_timestamp' => $lastMessage?->created_at ?? now()->subYears(10),
            ];
        }

        // 2. 1-to-1 personal chats
        $chatRoomsId = ChatRoomUser::where('user_id', $currentUserId)->pluck('chat_room_id');
        $chatRooms = ChatRoom::whereIn('id', $chatRoomsId)->get();

        foreach ($chatRooms as $room) {
            $userIds = ChatRoomUser::where('chat_room_id', $room->id)->pluck('user_id');

            if ($userIds->count() !== 2) {
                continue;
            }

            $otherUserId = $userIds->first(fn($id) => $id !== $currentUserId);
            $otherUser = User::find($otherUserId);

            $lastMessage = ChatMessage::where('chat_room_id', $room->id)
                ->latest('created_at')
                ->first();

            $chatData[] = [
                'room_id'        => null,
                'name'           => $otherUser?->full_name ?? 'Unknown',
                'is_group'       => false,
                'avatar'         => $otherUser?->profile_pic ?? 'https://aldine.esferasoft.in/images/user_dummy.png',
                'chat_room_id'   => $room->id,
                'latest_message' => $lastMessage ? [
                    'sender_id'     => $lastMessage->sender_id,
                    'text'          => $lastMessage->message,
                    'image_message' => $lastMessage->image_url,
                    'voice_message' => $lastMessage->voice_url,
                    'sent_at'       => $lastMessage->created_at->diffForHumans(),
                ] : null,
                'unread_count'   => $room
                    ? $this->getUnreadCountForRoom($room->id, $currentUserId)
                    : 0,

                'latest_timestamp' => $lastMessage?->created_at ?? now()->subYears(10),
            ];
        }
        // Final response: sort by latest message timestamp
        $sortedChats = collect($chatData)
            ->sortByDesc('latest_timestamp')
            ->map(function ($chat) {
                unset($chat['latest_timestamp']); // Clean before sending
                return $chat;
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'chats'  => $sortedChats,
        ]);
    }


    public function getAllMessagesInRoom($room_id)
    {
        $room = ChatRoom::findOrFail($room_id);

        $paginator = ChatMessage::with('sender')
            ->where('chat_room_id', $room_id)
            ->orderBy('created_at', 'asc')
            ->paginate(10);

        // Transform only the items, not the paginator
        $messages = $paginator->getCollection()->map(function ($msg) {
            return [
                'id'            => $msg->id,
                'sender_id'     => $msg->sender_id,
                'sender_name'   => $msg->sender->first_name ?? 'Unknown',
                'sender_avatar' => $msg->sender?->profile_pic
                    ? asset('storage/users/' . $msg->sender->profile_pic)
                    : asset('storage/users/default.png'),
                'message'   => $msg->message,
                'timestamp' => $msg->created_at,
                'type'      => $msg->type,
                'date'      => $msg->created_at->format('Y-m-d'),
            ];
        });

        // Replace the original items with the transformed ones
        $paginator->setCollection($messages);

        return response()->json([
            'status'  => 'success',
            'chat_room_id' => $room_id,
            'messages' => $paginator
        ]);
    }


    public function markMessageAsRead(Request $request)
    {
        $userId = auth()->id();
        $roomId = $request->room_id;

        // Get the latest message in that room
        $lastMessage = ChatMessage::where('chat_room_id', $roomId)
            ->latest('id')
            ->first();

        if ($lastMessage) {
            ChatMessageRead::updateOrCreate(
                [
                    'chat_message_id' => $lastMessage->id,
                    'user_id' => $userId,
                ],
                [
                    'read_at' => now()
                ]
            );
        }

        return response()->json(['status' => 'success']);
    }


    private function getUnreadCountForRoom($chatRoomId, $userId)
    {
        $lastReadMessageId = ChatMessageRead::where('user_id', $userId)
            ->whereIn('chat_message_id', function ($query) use ($chatRoomId) {
                $query->select('id')->from('chat_messages')->where('chat_room_id', $chatRoomId);
            })
            ->max('chat_message_id');

        return ChatMessage::where('chat_room_id', $chatRoomId)
            ->where('sender_id', '!=', $userId)
            ->when($lastReadMessageId, function ($query, $lastReadId) {
                return $query->where('id', '>', $lastReadId);
            })
            ->count();
    }
}
