<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TopicAdminController extends Controller
{
    public function index(): Response
    {
        $topics = Topic::withCount('videos')->orderBy('title')->paginate(15);
        return Inertia::render('Admin/Topics', ['topics' => $topics]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:500'],
            'category' => ['required', 'string', 'max:100'],
            'cultural_context' => ['required', 'string', 'max:150'],
            'is_popular' => ['boolean'],
            'icon_name' => ['required', 'string'],
        ]);

        $validated['slug'] = Str::slug($validated['title']);

        Topic::create($validated);

        return back()->with('success', 'Topik edukasi baru berhasil ditambahkan.');
    }

    public function update(Request $request, Topic $topic): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:500'],
            'category' => ['required', 'string', 'max:100'],
            'cultural_context' => ['required', 'string', 'max:150'],
            'is_popular' => ['boolean'],
            'icon_name' => ['required', 'string'],
        ]);

        $topic->update($validated);

        return back()->with('success', 'Topik berhasil diperbarui.');
    }

    public function destroy(Topic $topic): RedirectResponse
    {
        $topic->delete();
        return back()->with('success', 'Topik berhasil dihapus.');
    }
}
