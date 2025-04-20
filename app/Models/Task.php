<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Project;
use App\Models\User;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'status',
        'project_id',
        'assigned_to',
    ];

    // Relación: proyecto al que pertenece la tarea
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Relación: usuario asignado a la tarea
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
