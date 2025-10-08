<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FriendRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Notifications\FriendRequestNotification;
use App\Notifications\FriendRespondedNotification;
use Illuminate\Support\Facades\DB;

class FriendController extends Controller
{
    public function sendRequest(Request $request)
    {
        $request->validate([
            'receiver_email' => 'required|email|exists:users,email',
        ]);

        $sender_id = Auth::id();

        $receiver = User::where('email', $request->receiver_email)->first();

        if (!$receiver) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($sender_id === $receiver->id) {
            return response()->json(['message' => 'You cannot send a request to yourself.'], 400);
        }

        $existing = FriendRequest::where(function ($q) use ($sender_id, $receiver) {
            $q->where('sender_id', $sender_id)
                ->where('receiver_id', $receiver->id);
        })->first();

        if ($existing) {
            return response()->json(['message' => 'A request or friendship already exists.'], 400);
        }

        $existing = FriendRequest::where(function ($q) use ($sender_id, $receiver) {
            $q->where('receiver_id', $sender_id)
                ->where('sender_id', $receiver->id)
                ->whereIn('status', ['accepted', 'pending']);
        })->first();

        if ($existing) {
            return response()->json(['message' => 'A request or friendship already exists.'], 400);
        }

        $existing = FriendRequest::where(function ($q) use ($sender_id, $receiver) {
            $q->where('receiver_id', $sender_id)
                ->where('sender_id', $receiver->id)
                ->where('status', 'rejected');
        })->first();

        if($existing){
            $existing->delete();
        }

        $friendRequest = FriendRequest::create([
            'sender_id'   => $sender_id,
            'receiver_id' => $receiver->id,
        ]);

        $sender = Auth::user();

        $receiver->notify(new FriendRequestNotification($sender, $friendRequest->id));

        return response()->json([
            'id' => $receiver->id,
            'name' => $receiver->name,
            'nickname' => $receiver->nickname,
            'icon' => $receiver->icon,
            'isMe' => $sender_id === $receiver->id
        ], 201);
    }

    public function respondRequest(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:friend_requests,id',
            'action' => 'required|in:accepted,rejected',
        ]);

        $friendRequest = FriendRequest::findOrFail($request->id);
        $user = User::find(Auth::id());

        if ($friendRequest->receiver_id !== $user->id) {
            return response()->json(['error' => 'You cannot respond to this request.'], 403);
        }

        $friendRequest->update([
            'status' => $request->action,
        ]);

        $user->notifications()
            ->where('data->id', $friendRequest->id)
            ->update([
                'read_at' => now(),
                'data'    => DB::raw("jsonb_set(data, '{status}', '\"responded\"', true)")
            ]);

        $friendRequest->sender->notify(new FriendRespondedNotification($friendRequest->receiver, $friendRequest->id, $request->action));

        return response()->json(['message' => 'Updated application.']);
    }

    public function listFriends()
    {
        $userId = Auth::id();

        $friends = FriendRequest::where('status', 'accepted')
            ->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)->orWhere('receiver_id', $userId);
            })
            ->with(['sender:id,name,nickname,icon', 'receiver:id,name,nickname,icon'])
            ->get()
            ->map(function ($req) use ($userId) {
                $friend = $req->sender_id == $userId ? $req->receiver : $req->sender;
                return [
                    'id' => $friend->id,
                    'name' => $friend->name,
                    'nickname' => $friend->nickname,
                    'icon' => $friend->icon,
                    'isMe' => $friend->id == $userId,
                ];
            });

        $me = Auth::user();

        $friends->push([
            'id' => $me->id,
            'name' => $me->name,
            'nickname' => $me->nickname,
            'icon' => $me->icon,
            'isMe' => true,
        ]);

        return response()->json($friends);
    }

    public function pendingRequests()
    {
        $userId = Auth::id();

        $pending = FriendRequest::where('receiver_id', $userId)
            ->where('status', 'pending')
            ->with('sender:id,name')
            ->get();

        return response()->json($pending);
    }
}
