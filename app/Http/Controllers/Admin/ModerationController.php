<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunityReport;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModerationController extends Controller
{
    public function index(): Response
    {
        $pendingReports = CommunityReport::with(['user', 'video.user'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        $pendingVideos = Video::with(['user', 'topic'])
            ->where('is_public', true)
            ->where('moderation_status', 'pending')
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('Admin/Moderation', [
            'pendingReports' => $pendingReports,
            'pendingVideos' => $pendingVideos,
        ]);
    }

    public function approveVideo(Video $video): RedirectResponse
    {
        $video->update(['moderation_status' => 'approved']);
        return back()->with('success', 'Video disetujui untuk tayang di Galeri Komunitas.');
    }

    public function rejectVideo(Video $video): RedirectResponse
    {
        $video->update([
            'moderation_status' => 'rejected',
            'is_public' => false,
        ]);
        return back()->with('success', 'Video ditolak dan dikembalikan ke status pribadi.');
    }

    public function resolveReport(Request $request, CommunityReport $report): RedirectResponse
    {
        $action = $request->input('action', 'dismissed'); // dismissed, action_taken

        $report->update([
            'status' => $action,
            'notes' => $request->input('notes', ''),
        ]);

        if ($action === 'action_taken') {
            $report->video->update([
                'is_public' => false,
                'moderation_status' => 'flagged',
            ]);
        }

        return back()->with('success', 'Laporan moderasi berhasil diproses.');
    }
}
