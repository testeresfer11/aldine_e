<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PostShare;
use App\Traits\SendResponseTrait;
use App\Models\ChatRoom;
use App\Models\ChatRoomUser;


class PostShareController extends Controller
{
    use SendResponseTrait;

    public function share(Request $request)
{
    $request->validate([
        'post_id' => 'required|exists:posts,id',
        'recipient_ids' => 'required|array|min:1',
        'recipient_ids.*' => 'exists:users,id',
        'share_type' => 'required|in:named,anonymous',
    ]);

    $senderId = auth()->id();
    $sharedPosts = [];

    foreach ($request->recipient_ids as $recipientId) {
        // Save shared post
        $sharedPosts[] = PostShare::create([
            'post_id' => $request->post_id,
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'share_type' => $request->share_type,
        ]);

        $chatRoomName = 'Chat_' . min($senderId, $recipientId) . '_' . max($senderId, $recipientId);

        // Check if chat room already exists
        $chatRoom = ChatRoom::firstOrCreate(
            ['name' => $chatRoomName],
            ['name' => $chatRoomName]
        );

        // Add both users if not already added
        foreach ([$senderId, $recipientId] as $userId) {
            ChatRoomUser::firstOrCreate([
                'chat_room_id' => $chatRoom->id,
                'user_id' => $userId,
            ]);
        }
    }

    return $this->apiResponse('success', 200, 'Post shared successfully with selected users', $sharedPosts);
}


}
