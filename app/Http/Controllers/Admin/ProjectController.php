<?php

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Category;
use App\Models\Technology;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $currentUserId = Auth::id();
        if ($currentUserId == 1) {
            $projects = Project::paginate(3);
            // $projects = Project::all();
        } else {
            $projects = Project::where('user_id', $currentUserId)->paginate(3);
        }
        return view('admin.projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all();
        $technologies = Technology::all();
        return view('admin.projects.create', compact('categories', 'technologies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request)
    {
        $data = $request->validated();
        // $slug = Str::slug($data['title']);
        $slug = Project::getSlug($data['title']);
        $data['slug'] = $slug;
        $data['user_id'] = Auth::id();
        if ($request->hasFile('image')) {
            $path = Storage::put('images', $data['image']);
            $data['image'] = $path;
        }
        $project = Project::create($data);
        if ($request->has('technologies')) {
            $project->technologies()->attach($request->technologies);
        }
        return redirect()->route('admin.projects.show', $project->slug);
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        $currentUserId = Auth::id();
        if ($currentUserId == $project->user_id || $currentUserId == 1) {
            return view('admin.projects.show', compact('project'));
        }
        abort(403);
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        $currentUserId = Auth::id();
        if ($currentUserId != $project->user_id && $currentUserId != 1) {
            abort(403);
        }
        $categories = Category::all();
        $technologies = Technology::all();
        return view('admin.projects.edit', compact('project', 'categories', 'technologies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $currentUserId = Auth::id();
        if ($currentUserId != $project->user_id && $currentUserId != 1) {
            abort(403);
        }
        $data = $request->validated();
        $data['slug'] = $project->slug;

        if ($project->title !== $data['title']) {
            $slug = Project::getSlug($data['title']);
            $data['slug'] = $slug;
        }

        $data['user_id'] = $project->user_id;

        if ($request->hasFile('image')) {
            if (Storage::exists($project->image)) {
                Storage::delete($project->image);
            }
            $path = Storage::put('images', $data['image']);
            $data['image'] = $path;
        }

        $project->update($data);

        if ($request->has('technologies')) {
            $project->technologies()->sync($request->technologies);
        } else {
            $project->technologies()->detach();
        }
        return redirect()->route('admin.projects.show', $project->slug);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $currentUserId = Auth::id();
        if ($currentUserId != $project->user_id && $currentUserId != 1) {
            abort(403);
        }
        $project->technologies()->sync([]);
        if ($project->image) {
            Storage::delete($project->image);
        }
        $project->delete();
        return redirect()->route('admin.projects.index')->with('message', "$project->title deleted successfully");
    }
}
