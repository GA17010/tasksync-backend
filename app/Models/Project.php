<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Task;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'owner_id',
    ];

    // Relación: el dueño del proyecto (usuario que lo creó)
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // Relación: tareas asociadas al proyecto
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function sharedWith()
    {
        return $this->belongsToMany(User::class, 'project_user');
    }
}
