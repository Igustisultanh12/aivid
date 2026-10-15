<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsageAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AiUsageLog::with(['user', 'video']);

        if ($type = $request->input('type')) {
            $query->where('request_type', $type);
        }

        $logs = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'total_requests' => AiUsageLog::count(),
            'total_prompt_tokens' => (int) AiUsageLog::sum('prompt_tokens'),
            'total_candidate_tokens' => (int) AiUsageLog::sum('candidates_tokens'),
            'total_cost_usd' => (float) AiUsageLog::sum('estimated_cost_usd'),
            'avg_response_time_ms' => (int) AiUsageLog::avg('response_time_ms'),
        ];

        return Inertia::render('Admin/Usage', [
            'logs' => $logs,
            'stats' => $stats,
            'filters' => $request->only(['type']),
        ]);
    }
}
