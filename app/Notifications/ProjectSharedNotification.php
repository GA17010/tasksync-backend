<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectSharedNotification extends Notification
{
    use Queueable;

    private $project;
    private $sharedByUser;

    public function __construct(Project $project, User $sharedByUser)
    {
        $this->project = $project;
        $this->sharedByUser = $sharedByUser;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'interaction',
            'prefix' => null,
            'main' => "{$this->sharedByUser->name}",
            'suffix' => "has added you as a collaborator on '{$this->project->name}'",
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'shared_by' => [
                'id' => $this->sharedByUser->id,
                'name' => $this->sharedByUser->name,
            ],
        ];
    }
}
