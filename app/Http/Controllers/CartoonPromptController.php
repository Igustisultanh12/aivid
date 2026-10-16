<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\CartoonPromptProject;
use App\Models\CartoonPromptScene;
use App\Services\CartoonPromptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CartoonPromptController extends Controller
{
    public function __construct(
        protected CartoonPromptService $promptService
    ) {}

    /**
     * Display the cartoon prompt studio interface and saved projects.
     */
    public function index(Request $request): Response
    {
        $userId = Auth::id();
        $projectId = $request->query('project_id');

        $activeProject = null;
        if ($projectId) {
            $query = CartoonPromptProject::with('scenes')->where('id', $projectId);
            if ($userId) {
                $query->where(function ($q) use ($userId) {
                    $q->where('user_id', $userId)->orWhereNull('user_id');
                });
            }
            $activeProject = $query->first();
        }

        // If no active project specified, take the most recent project if user is logged in
        if (!$activeProject && $userId) {
            $activeProject = CartoonPromptProject::with('scenes')
                ->where('user_id', $userId)
                ->latest()
                ->first();
        }

        $savedProjects = [];
        if ($userId) {
            $savedProjects = CartoonPromptProject::where('user_id', $userId)
                ->latest()
                ->take(20)
                ->get(['id', 'title', 'theme', 'target_duration', 'visual_style', 'scene_count', 'created_at']);
        }

        return Inertia::render('CartoonPrompt/Index', [
            'activeProject' => $activeProject,
            'savedProjects' => $savedProjects,
            'themes' => array_values(CartoonPromptService::THEMES),
            'visualStyles' => array_values(CartoonPromptService::VISUAL_STYLES),
            'targetEngines' => array_values(CartoonPromptService::TARGET_ENGINES),
            'cameraMotions' => CartoonPromptService::CAMERA_MOTIONS,
            'geminiConfigured' => AppSetting::has('gemini_api_key'),
        ]);
    }

    /**
     * Generate a new set of per-frame cartoon prompts.
     */
    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'theme' => ['required', 'string', 'in:fable,dharma_kids,comedy,fantasy,anime_chibi,sci_fi,custom'],
            'custom_theme_title' => ['nullable', 'string', 'max:150'],
            'story_premise' => ['nullable', 'string', 'max:1000'],
            'target_duration' => ['required', 'integer', 'in:30,60'],
            'visual_style' => ['required', 'string'],
            'target_engine' => ['required', 'string'],
            'aspect_ratio' => ['required', 'string', 'in:9:16,16:9,1:1'],
            'character_description' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $this->promptService->generatePrompts($validated);

        if (!$result['success'] || empty($result['scenes'])) {
            return back()->with('error', 'Gagal memproses prompt kartun. Silakan coba lagi.');
        }

        // Create Project in Database
        $project = CartoonPromptProject::create([
            'user_id' => Auth::id(),
            'title' => $result['title'] ?? ($validated['title'] ?: 'Kisah Kartun Pilihan'),
            'theme' => $validated['theme'],
            'custom_theme_title' => $validated['custom_theme_title'] ?? null,
            'story_premise' => $result['story_premise'] ?? $validated['story_premise'],
            'target_duration' => (int) $result['target_duration'],
            'visual_style' => $validated['visual_style'],
            'target_engine' => $validated['target_engine'],
            'aspect_ratio' => $validated['aspect_ratio'],
            'character_description' => $result['character_description'] ?? $validated['character_description'],
            'scene_count' => count($result['scenes']),
            'total_duration' => (float) ($result['total_duration'] ?? $validated['target_duration']),
            'metadata' => [
                'engine_info' => $result['engine_info'] ?? null,
                'style_info' => $result['style_info'] ?? null,
            ],
        ]);

        // Insert Scenes
        foreach ($result['scenes'] as $sceneData) {
            CartoonPromptScene::create([
                'cartoon_prompt_project_id' => $project->id,
                'scene_number' => $sceneData['scene_number'],
                'start_second' => $sceneData['start_second'],
                'end_second' => $sceneData['end_second'],
                'duration' => $sceneData['duration'],
                'title' => $sceneData['title'],
                'action_description' => $sceneData['action_description'],
                'visual_prompt' => $sceneData['visual_prompt'],
                'camera_movement' => $sceneData['camera_movement'] ?? null,
                'dialogue_or_narration' => $sceneData['dialogue_or_narration'] ?? null,
                'sound_effects' => $sceneData['sound_effects'] ?? null,
                'music_mood' => $sceneData['music_mood'] ?? null,
                'negative_prompt' => $sceneData['negative_prompt'] ?? null,
            ]);
        }

        $durationLabel = $project->target_duration === 30 ? '30 Detik' : '1 Menit (60 Detik)';
        return redirect()->route('cartoon-prompts.index', ['project_id' => $project->id])
            ->with('success', "Rangkaian prompt video kartun {$durationLabel} ({$project->scene_count} frame/aksi) berhasil dibuat!");
    }

    /**
     * Show a specific project.
     */
    public function show(CartoonPromptProject $project): RedirectResponse
    {
        $this->authorizeProject($project);
        return redirect()->route('cartoon-prompts.index', ['project_id' => $project->id]);
    }

    /**
     * Update project metadata.
     */
    public function update(Request $request, CartoonPromptProject $project): RedirectResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'story_premise' => ['nullable', 'string', 'max:1000'],
            'character_description' => ['nullable', 'string', 'max:1000'],
            'visual_style' => ['nullable', 'string'],
            'target_engine' => ['nullable', 'string'],
        ]);

        $project->update($validated);

        return back()->with('success', 'Detail proyek kartun berhasil diperbarui.');
    }

    /**
     * Update single scene details.
     */
    public function updateScene(Request $request, CartoonPromptProject $project, CartoonPromptScene $scene): RedirectResponse
    {
        $this->authorizeProject($project);

        if ($scene->cartoon_prompt_project_id !== $project->id) {
            abort(404);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'action_description' => ['required', 'string'],
            'visual_prompt' => ['required', 'string'],
            'camera_movement' => ['nullable', 'string'],
            'dialogue_or_narration' => ['nullable', 'string'],
            'sound_effects' => ['nullable', 'string'],
            'music_mood' => ['nullable', 'string'],
            'negative_prompt' => ['nullable', 'string'],
            'duration' => ['nullable', 'numeric', 'min:1', 'max:30'],
        ]);

        $scene->update($validated);
        $project->recalculateTimeline();

        return back()->with('success', "Frame #{$scene->scene_number} berhasil diperbarui.");
    }

    /**
     * Add a new frame to the project.
     */
    public function addScene(Request $request, CartoonPromptProject $project): RedirectResponse
    {
        $this->authorizeProject($project);

        $lastScene = $project->scenes()->latest('scene_number')->first();
        $nextNumber = ($lastScene?->scene_number ?? 0) + 1;
        $startSec = $lastScene ? (float) $lastScene->end_second : 0.0;
        $duration = 5.0;
        $endSec = round($startSec + $duration, 2);

        $styleMeta = CartoonPromptService::VISUAL_STYLES[$project->visual_style] ?? CartoonPromptService::VISUAL_STYLES['pixar_3d'];
        $engineMeta = CartoonPromptService::TARGET_ENGINES[$project->target_engine] ?? CartoonPromptService::TARGET_ENGINES['universal'];

        $defaultPrompt = sprintf(
            "%s. Character: %s. Action: Character engages in dynamic playful movement with joyous expression. Setting: Colorful cartoon background. Camera: Smooth tracking shot. Lighting: Warm vibrant illumination. %s%s",
            $styleMeta['style_prompt'],
            $project->character_description ?: 'Cartoon protagonist',
            $styleMeta['style_prompt'],
            $engineMeta['suffix']
        );

        CartoonPromptScene::create([
            'cartoon_prompt_project_id' => $project->id,
            'scene_number' => $nextNumber,
            'start_second' => $startSec,
            'end_second' => $endSec,
            'duration' => $duration,
            'title' => "Frame {$nextNumber}: Aksi Tambahan",
            'action_description' => "Karakter melanjutkan aksi menarik di dunia kartun dengan penuh semangat.",
            'visual_prompt' => $defaultPrompt,
            'camera_movement' => "Smooth tracking shot following character",
            'dialogue_or_narration' => "Ayo kita lanjutkan!",
            'sound_effects' => "*Whoosh!* *Boing!*",
            'music_mood' => "Playful upbeat cartoon melody",
            'negative_prompt' => $styleMeta['negative'],
        ]);

        $project->recalculateTimeline();

        return back()->with('success', "Frame baru (#{$nextNumber}) berhasil ditambahkan ke timeline.");
    }

    /**
     * Delete a scene from project.
     */
    public function deleteScene(CartoonPromptProject $project, CartoonPromptScene $scene): RedirectResponse
    {
        $this->authorizeProject($project);

        if ($scene->cartoon_prompt_project_id !== $project->id) {
            abort(404);
        }

        if ($project->scenes()->count() <= 1) {
            return back()->with('error', 'Proyek harus memiliki minimal 1 frame/adegan.');
        }

        $sceneNumber = $scene->scene_number;
        $scene->delete();

        $project->recalculateTimeline();

        return back()->with('success', "Frame #{$sceneNumber} berhasil dihapus dari timeline.");
    }

    /**
     * Regenerate a single scene's prompt.
     */
    public function regenerateScenePrompt(CartoonPromptProject $project, CartoonPromptScene $scene): RedirectResponse
    {
        $this->authorizeProject($project);

        if ($scene->cartoon_prompt_project_id !== $project->id) {
            abort(404);
        }

        $this->promptService->regenerateSingleScene($project, $scene);

        return back()->with('success', "Prompt untuk Frame #{$scene->scene_number} berhasil diregenerasi!");
    }

    /**
     * Export project to file (TXT, Markdown, or JSON).
     */
    public function export(CartoonPromptProject $project, string $format): HttpResponse|JsonResponse
    {
        $this->authorizeProject($project);

        $scenes = $project->scenes()->orderBy('scene_number')->get();
        $safeTitle = Str::slug($project->title, '_');

        if ($format === 'json') {
            $data = [
                'project_id' => $project->id,
                'title' => $project->title,
                'theme' => $project->theme,
                'target_duration_seconds' => $project->target_duration,
                'total_duration_seconds' => $project->total_duration,
                'visual_style' => $project->visual_style,
                'target_engine' => $project->target_engine,
                'aspect_ratio' => $project->aspect_ratio,
                'character_description' => $project->character_description,
                'story_premise' => $project->story_premise,
                'scene_count' => $scenes->count(),
                'scenes' => $scenes->map(fn($s) => [
                    'frame' => $s->scene_number,
                    'timestamp' => $s->formatted_timestamp,
                    'start_second' => $s->start_second,
                    'end_second' => $s->end_second,
                    'duration_second' => $s->duration,
                    'title' => $s->title,
                    'action_description' => $s->action_description,
                    'visual_prompt' => $s->visual_prompt,
                    'camera_movement' => $s->camera_movement,
                    'dialogue' => $s->dialogue_or_narration,
                    'sfx' => $s->sound_effects,
                    'music' => $s->music_mood,
                    'negative_prompt' => $s->negative_prompt,
                ]),
            ];

            return response()->json($data, 200, [
                'Content-Disposition' => "attachment; filename=\"prompt_kartun_{$safeTitle}.json\"",
            ]);
        }

        // Text / Markdown format
        $txt = "================================================================================\n";
        $txt .= "PROMPT STORYBOARD VIDEO KARTUN - " . strtoupper($project->title) . "\n";
        $txt .= "================================================================================\n";
        $txt .= "Target Durasi : {$project->target_duration} Detik (Total Terhitung: {$project->total_duration}s)\n";
        $txt .= "Jumlah Frame  : " . $scenes->count() . " Frame / Aksi\n";
        $txt .= "Gaya Visual   : " . (CartoonPromptService::VISUAL_STYLES[$project->visual_style]['name'] ?? $project->visual_style) . "\n";
        $txt .= "Target Engine : " . (CartoonPromptService::TARGET_ENGINES[$project->target_engine]['name'] ?? $project->target_engine) . "\n";
        $txt .= "Rasio Aspek   : {$project->aspect_ratio}\n";
        $txt .= "Karakter      : {$project->character_description}\n";
        $txt .= "Sinopsis      : {$project->story_premise}\n";
        $txt .= "================================================================================\n\n";

        foreach ($scenes as $scene) {
            $txt .= "--------------------------------------------------------------------------------\n";
            $txt .= "FRAME #" . str_pad((string)$scene->scene_number, 2, '0', STR_PAD_LEFT) . " | ";
            $txt .= $scene->formatted_timestamp . " (Durasi: {$scene->duration}s) | ";
            $txt .= "{$scene->title}\n";
            $txt .= "--------------------------------------------------------------------------------\n";
            $txt .= "[Aksi Karakter] : {$scene->action_description}\n";
            $txt .= "[Kamera / Gerak]: {$scene->camera_movement}\n";
            $txt .= "[Audio / Dialog]: {$scene->dialogue_or_narration}\n";
            $txt .= "[SFX / Suara]   : {$scene->sound_effects}\n";
            $txt .= "[Musik BGM]     : {$scene->music_mood}\n\n";
            $txt .= "[PROMPT VISUAL AI (Salin ke Kling / Runway / Sora / Luma)]:\n";
            $txt .= "{$scene->visual_prompt}\n\n";
            if ($scene->negative_prompt) {
                $txt .= "[Negative Prompt]:\n";
                $txt .= "{$scene->negative_prompt}\n\n";
            }
        }

        $txt .= "================================================================================\n";
        $txt .= "Dihasilkan secara otomatis oleh Ruang Dharma Cartoon Video Prompt Studio.\n";
        $txt .= "================================================================================\n";

        return response($txt, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"prompt_kartun_{$safeTitle}.txt\"",
        ]);
    }

    /**
     * Delete a saved project.
     */
    public function destroy(CartoonPromptProject $project): RedirectResponse
    {
        $this->authorizeProject($project);

        $title = $project->title;
        $project->delete();

        return redirect()->route('cartoon-prompts.index')
            ->with('success', "Proyek kartun \"{$title}\" berhasil dihapus.");
    }

    /**
     * Check project authorization.
     */
    protected function authorizeProject(CartoonPromptProject $project): void
    {
        $userId = Auth::id();
        if ($project->user_id && $project->user_id !== $userId && !Auth::user()?->isAdmin()) {
            abort(403, 'Anda tidak memiliki hak akses ke proyek ini.');
        }
    }
}
