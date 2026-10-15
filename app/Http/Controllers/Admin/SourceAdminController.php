<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Source;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SourceAdminController extends Controller
{
    public function index(): Response
    {
        $sources = Source::withCount('storyboardScenes')->orderBy('title')->paginate(15);
        return Inertia::render('Admin/Sources', ['sources' => $sources]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'author_or_institution' => ['required', 'string', 'max:150'],
            'source_type' => ['required', 'string', 'max:100'],
            'reference_section' => ['nullable', 'string', 'max:200'],
            'tradition_context' => ['required', 'string', 'max:100'],
            'region' => ['required', 'string', 'max:100'],
            'verification_status' => ['required', 'string', 'in:verified,under_review,rejected'],
            'link_or_document' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        Source::create($validated);

        return back()->with('success', 'Sumber rujukan Hindu berhasil ditambahkan.');
    }

    public function update(Request $request, Source $source): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'author_or_institution' => ['required', 'string', 'max:150'],
            'source_type' => ['required', 'string', 'max:100'],
            'reference_section' => ['nullable', 'string', 'max:200'],
            'tradition_context' => ['required', 'string', 'max:100'],
            'region' => ['required', 'string', 'max:100'],
            'verification_status' => ['required', 'string', 'in:verified,under_review,rejected'],
            'link_or_document' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $source->update($validated);

        return back()->with('success', 'Sumber rujukan berhasil diperbarui.');
    }

    public function destroy(Source $source): RedirectResponse
    {
        $source->delete();
        return back()->with('success', 'Sumber rujukan berhasil dihapus.');
    }
}
