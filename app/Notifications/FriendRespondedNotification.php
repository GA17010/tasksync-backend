<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FriendRespondedNotification extends Notification
{
    use Queueable;

    private $receiverUser;
    private $friendRequestId;
    private $action;

    public function __construct(User $receiverUser, $friendRequestId, $action)
    {
        $this->receiverUser = $receiverUser;
        $this->friendRequestId = $friendRequestId;
        $this->action = $action;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'id' => $this->friendRequestId,
            'type' => 'interaction',
            'prefix' => null,
            'main' => "{$this->receiverUser->name}",
            'suffix' => " has {$this->action} your friend request.",
            'requested_by' => [
                'id' => $this->receiverUser->id,
                'name' => $this->receiverUser->name,
            ],
        ];
    }
}
