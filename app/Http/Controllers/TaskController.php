<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $tasks = $project->tasks()->with('assignedUser')->get();

        $userId = Auth::id();

        $tasksFormatted = $tasks->map(function ($task) use ($userId) {
            return [
                'id'         => $task->id,
                'content'      => $task->content,
                'status'     => $task->status,
                'assigned_to' => $task->assignedUser
                    ? [
                        'id' => $task->assignedUser->id,
                        'name' => $task->assignedUser->name,
                        'icon' => $task->assignedUser->icon
                    ]
                    : null,
                'updated_at' => $task->updated_at,
                'created_at' => $task->created_at,
                'is_me'      => $task->assigned_to === $userId,
            ];
        });

        return response()->json($tasksFormatted);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'content'       => 'required|string|max:255',
            'status'      => 'in:todo,in_progress,in_review,done',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $task = $project->tasks()->create($validated);

        return response()->json([
            'id' => $task->id,
            'content' => $task->content,
            'status' => $task->status,
            'assigned_to' => $task->assignedUser
                ? [
                    'id' => $task->assignedUser->id,
                    'name' => $task->assignedUser->name,
                    'icon' => $task->assignedUser->icon
                ]
                : null,
            'updated_at' => $task->updated_at,
            'created_at' => $task->created_at,
            'is_me' => Auth::id() === $task->assigned_to,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $task = Task::with('assigned_to:id,name')->findOrFail($id);

        return response()->json([
            'id' => $task->id,
            'content' => $task->content,
            'status' => $task->status,
            'assigned_to' => $task->assigned_to,
            'is_me' => Auth::id() === $task->assigned_to
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project, Task $task)
    {
        $this->authorize('update', $project);

        if ($task->project_id !== $project->id) {
            return response()->json(['error' => 'The task does not belong to this project.'], 403);
        }

        $validated = $request->validate([
            'content'       => 'string|max:255',
            'status'      => 'in:todo,in_progress,in_review,done',
            'assigned_to' => [
                'nullable',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($project) {
                    $isValid = $value === $project->owner_id
                        || $project->sharedWith()->where('user_id', $value)->exists();

                    if (!$isValid) {
                        $fail('The selected user is not a member of the project.');
                    }
                }
            ],
        ]);

        $task->update($validated);

        return response()->json([
            'id' => $task->id,
            'content' => $task->content,
            'status' => $task->status,
            'assigned_to' => $task->assignedUser
                ? [
                    'id' => $task->assignedUser->id,
                    'name' => $task->assignedUser->name,
                    'icon' => $task->assignedUser->icon
                ]
                : null,
            'updated_at' => $task->updated_at,
            'created_at' => $task->created_at,
            'is_me' => Auth::id() === $task->assigned_to,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project, Task $task)
    {
        $this->authorize('delete', $project);

        if ($task->project_id !== $project->id) {
            return response()->json(['message' => 'The task does not belong to this project'], 403);
        }

        $task->delete();

        return response()->json(['message' => 'Deleted task']);
    }
}
