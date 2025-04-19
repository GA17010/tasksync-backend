<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $projects = Project::with('owner')
            ->where('owner_id', $request->user()->id)
            ->orWhereHas('sharedWith', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->get();

        $projectsFormatted = $projects->map(function ($project) {
            return [
                'id'          => $project->id,
                'name'        => $project->name,
                'description' => $project->description,
                'owner'       => [
                    'id'   => $project->owner->id,
                    'name' => $project->owner->name,
                ],
                'created_at'  => $project->created_at,
                'updated_at'  => $project->updated_at,
            ];
        });

        return response()->json($projectsFormatted);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project = Project::create([
            'name'      => $validated['name'],
            'description' => $validated['description'] ?? '',
            'owner_id'  => $request->user()->id,
        ]);

        $project->load('owner');

        return response()->json([
            'id'          => $project->id,
            'name'        => $project->name,
            'description' => $project->description,
            'owner'       => [
                'id'   => $project->owner->id,
                'name' => $project->owner->name,
            ],
            'created_at'  => $project->created_at,
            'updated_at'  => $project->updated_at,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        $this->authorize('view', $project);

        return response()->json($project);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name'        => 'string|max:255',
            'description' => 'nullable|string',
        ]);

        $project->update($validated);

        $project->load('owner');

        return response()->json([
            'id'          => $project->id,
            'name'        => $project->name,
            'description' => $project->description,
            'owner'       => [
                'id'   => $project->owner->id,
                'name' => $project->owner->name,
            ],
            'created_at'  => $project->created_at,
            'updated_at'  => $project->updated_at,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json(['message' => 'Project deleted']);
    }

    public function share(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $project->sharedWith()->syncWithoutDetaching([$request->user_id]);

        return response()->json(['message' => 'User added to project']);
    }

    public function sharedUsers(Project $project)
    {
        $this->authorize('view', $project);

        $sharedUsers = $project->sharedWith()
            ->select('users.id as user_id', 'users.name', 'users.email')
            ->get()
            ->makeHidden('pivot');

        return response()->json($sharedUsers);
    }

    public function unshare(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $project->sharedWith()->detach($validated['user_id']);

        return response()->json(['message' => 'Access successfully removed']);
    }
}
