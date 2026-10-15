export interface User {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'user';
    is_admin: boolean;
    quota_total: number;
    quota_used: number;
    remaining_quota: number;
    email_verified_at: string | null;
    is_verified: boolean;
}

export interface Topic {
    id: number;
    slug: string;
    title: string;
    description: string;
    category: string;
    cultural_context: string;
    is_popular: boolean;
    suggested_prompts?: string[];
    icon_name: string;
    videos_count?: number;
}

export interface Source {
    id: number;
    title: string;
    author_or_institution: string;
    source_type: string;
    reference_section: string | null;
    tradition_context: string;
    region: string;
    verification_status: 'verified' | 'under_review' | 'rejected';
    link_or_document?: string | null;
    notes?: string | null;
    storyboard_scenes_count?: number;
}

export interface StoryboardScene {
    id?: number;
    video_id?: string;
    scene_order: number;
    start_time: number;
    end_time: number;
    duration: number;
    narration_text: string;
    onscreen_text: string | null;
    visual_prompt: string;
    camera_motion: 'zoom_in' | 'zoom_out' | 'pan_left' | 'pan_right' | 'tilt_up';
    transition_type: 'fade' | 'cut' | 'wipe';
    source_citation?: string | null;
    image_path?: string | null;
    audio_path?: string | null;
    status?: string;
}

export interface VideoQuiz {
    id: number;
    video_id: string;
    question: string;
    options: string[];
    correct_option_index: number;
    explanation: string;
}

export interface Video {
    id: string;
    user_id: number;
    title: string;
    slug: string;
    hook_text?: string;
    reflection_text?: string;
    summary_text?: string;
    topic_id?: number | null;
    topic_name?: string;
    target_audience: string;
    tone: string;
    production_mode: 'illustration_motion' | 'generative_video';
    visual_style: string;
    aspect_ratio: '9:16' | '16:9' | '1:1';
    voice_id: string;
    has_subtitles: boolean;
    status: 'draft' | 'queued' | 'preparing_references' | 'generating_script' | 'waiting_script_approval' | 'generating_narration' | 'generating_visuals' | 'rendering' | 'completed' | 'needs_review' | 'failed' | 'cancelled';
    progress_percent: number;
    current_stage_label: string;
    error_message?: string | null;
    duration_seconds: number;
    video_path?: string | null;
    thumbnail_path?: string | null;
    subtitles_srt_path?: string | null;
    subtitles_vtt_path?: string | null;
    transcript_text?: string | null;
    is_public: boolean;
    is_featured: boolean;
    moderation_status: 'approved' | 'pending' | 'flagged' | 'rejected';
    views_count: number;
    downloads_count: number;
    metadata?: Record<string, any>;
    created_at: string;
    updated_at: string;
    topic?: Topic;
    user?: User;
    scenes?: StoryboardScene[];
    quizzes?: VideoQuiz[];
}

export interface GlossaryTerm {
    id: number;
    term: string;
    sanskrit_or_balinese_origin?: string | null;
    meaning: string;
    context?: string | null;
    related_traditions?: string | null;
    phonetic_pronunciation?: string | null;
}

export interface PageProps {
    auth: {
        user: User | null;
    };
    flash: {
        success?: string | null;
        error?: string | null;
        info?: string | null;
    };
    gemini_configured: boolean;
    app_name: string;
    [key: string]: any;
}
