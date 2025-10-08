<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FriendRequestNotification extends Notification
{
    use Queueable;

    private $requestedByUser;
    private $friendRequestId;

    public function __construct(User $requestedByUser, $friendRequestId)
    {
        $this->requestedByUser = $requestedByUser;
        $this->friendRequestId = $friendRequestId;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'id' => $this->friendRequestId,
            'type' => 'invitation',
            'prefix' => null,
            'main' => "{$this->requestedByUser->name}",
            'suffix' => "invited you to be friends.",
            'requested_by' => [
                'id' => $this->requestedByUser->id,
                'name' => $this->requestedByUser->name,
            ],
            'status' => 'pending',
        ];
    }
}
