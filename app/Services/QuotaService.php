<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\User;
use App\Models\Video;

class QuotaService
{
    /**
     * Check if user can create a new video.
     */
    public function canCreateVideo(User $user): array
    {
        // Admin has unlimited quota
        if ($user->isAdmin()) {
            return ['allowed' => true];
        }

        // Check user quota
        if ($user->quota_used >= $user->quota_total) {
            return [
                'allowed' => false,
                'message' => 'Kuota pembuatan video Anda telah habis (' . $user->quota_used . '/' . $user->quota_total . '). Hubungi administrator untuk penambahan kuota.',
            ];
        }

        // Check global daily render limit
        $dailyLimit = (int) AppSetting::get('app_daily_render_limit', 50);
        $todayRenders = Video::whereDate('created_at', today())->count();

        if ($todayRenders >= $dailyLimit) {
            return [
                'allowed' => false,
                'message' => 'Batas render harian sistem aplikasi telah tercapai (' . $todayRenders . '/' . $dailyLimit . '). Silakan coba kembali besok.',
            ];
        }

        // Check concurrent active processing jobs for this user
        $activeProcessing = Video::where('user_id', $user->id)
            ->whereIn('status', ['queued', 'preparing_references', 'generating_script', 'generating_narration', 'generating_visuals', 'rendering'])
            ->count();

        if ($activeProcessing >= 2) {
            return [
                'allowed' => false,
                'message' => 'Anda memiliki ' . $activeProcessing . ' proses pembuatan video yang sedang berjalan. Tunggu hingga selesai sebelum membuat video baru.',
            ];
        }

        return ['allowed' => true];
    }
}
