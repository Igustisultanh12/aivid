<script setup lang="ts">
import { ref, computed, nextTick } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Sparkles,
    Clock,
    Timer,
    Video,
    Film,
    Copy,
    Check,
    Download,
    RefreshCw,
    Plus,
    Trash2,
    Edit3,
    Save,
    X,
    ChevronDown,
    ChevronUp,
    Layers,
    Clapperboard,
    Wand2,
    Play,
    Volume2,
    Camera,
    Share2,
    Sliders,
    ArrowRight,
    AlertCircle,
    CheckCircle2
} from 'lucide-vue-next';
import type { CartoonPromptProject, CartoonPromptScene } from '@/types';

interface ThemeItem {
    id: string;
    name: string;
    description: string;
    default_title: string;
    default_character: string;
    default_premise: string;
}

interface VisualStyleItem {
    id: string;
    name: string;
    badge: string;
    description: string;
    style_prompt: string;
    negative: string;
}

interface TargetEngineItem {
    id: string;
    name: string;
    description: string;
    suffix: string;
}

const props = defineProps<{
    activeProject: CartoonPromptProject | null;
    savedProjects: Array<{
        id: string;
        title: string;
        theme: string;
        target_duration: number;
        visual_style: string;
        scene_count: number;
        created_at: string;
    }>;
    themes: ThemeItem[];
    visualStyles: VisualStyleItem[];
    targetEngines: TargetEngineItem[];
    cameraMotions: Record<string, string>;
    geminiConfigured: boolean;
}>();

// Form for generating new cartoon prompts (Defaults to authentic Hindu stories)
const form = useForm({
    title: '',
    theme: 'ramayana_mahabharata',
    custom_theme_title: '',
    story_premise: '',
    target_duration: 30 as 30 | 60,
    visual_style: 'pixar_3d',
    target_engine: 'universal',
    aspect_ratio: '9:16' as '9:16' | '16:9' | '1:1',
    character_description: '',
});

// UI states
const isGenerating = ref(false);
const activeTab = ref<'studio' | 'saved'>(props.activeProject ? 'studio' : 'studio');
const copiedFrameId = ref<number | null>(null);
const copiedDialogueId = ref<number | null>(null);
const copiedAll = ref(false);
const copiedNegativeId = ref<number | null>(null);
const toastMessage = ref<string | null>(null);
const showAdvancedOptions = ref(false);

// Editing single scene
const editingSceneId = ref<number | null>(null);
const editSceneForm = useForm({
    title: '',
    action_description: '',
    visual_prompt: '',
    camera_movement: '',
    dialogue_or_narration: '',
    sound_effects: '',
    music_mood: '',
    negative_prompt: '',
    duration: 5,
});

// Initialize form defaults based on Hindu theme
const onThemeChange = (newThemeId: string) => {
    form.theme = newThemeId;
    const selectedTheme = props.themes.find(t => t.id === newThemeId);
    if (selectedTheme && newThemeId !== 'custom' && newThemeId !== 'custom_hindu') {
        form.title = selectedTheme.default_title;
        form.character_description = selectedTheme.default_character;
        form.story_premise = selectedTheme.default_premise;
    }
};

// Set duration
const setDuration = (duration: 30 | 60) => {
    form.target_duration = duration;
};

// Start generation
const submitGenerate = () => {
    isGenerating.value = true;
    form.post('/cartoon-prompts/generate', {
        preserveScroll: false,
        onFinish: () => {
            isGenerating.value = false;
        },
    });
};

// Copy single scene prompt
const copyScenePrompt = (scene: CartoonPromptScene) => {
    navigator.clipboard.writeText(scene.visual_prompt);
    copiedFrameId.value = scene.id;
    showToast(`Prompt Frame #${scene.scene_number} berhasil disalin!`);
    setTimeout(() => {
        if (copiedFrameId.value === scene.id) {
            copiedFrameId.value = null;
        }
    }, 2500);
};

// Copy negative prompt
const copyNegativePrompt = (scene: CartoonPromptScene) => {
    if (!scene.negative_prompt) return;
    navigator.clipboard.writeText(scene.negative_prompt);
    copiedNegativeId.value = scene.id;
    showToast(`Negative Prompt Frame #${scene.scene_number} disalin!`);
    setTimeout(() => {
        if (copiedNegativeId.value === scene.id) {
            copiedNegativeId.value = null;
        }
    }, 2500);
};

// Copy dialogue or narration
const copyDialogue = (scene: CartoonPromptScene) => {
    if (!scene.dialogue_or_narration) return;
    navigator.clipboard.writeText(scene.dialogue_or_narration);
    copiedDialogueId.value = scene.id;
    showToast(`Dialog Frame #${scene.scene_number} berhasil disalin!`);
    setTimeout(() => {
        if (copiedDialogueId.value === scene.id) {
            copiedDialogueId.value = null;
        }
    }, 2500);
};

// Copy all visual prompts
const copyAllPrompts = () => {
    if (!props.activeProject?.scenes) return;

    const formatted = props.activeProject.scenes
        .map(scene => `--- FRAME #${scene.scene_number} (${scene.start_second}s - ${scene.end_second}s: ${scene.title}) ---\n${scene.visual_prompt}\n`)
        .join('\n');

    navigator.clipboard.writeText(formatted);
    copiedAll.value = true;
    showToast(`Semua ${props.activeProject.scenes.length} prompt berhasil disalin ke clipboard!`);
    setTimeout(() => {
        copiedAll.value = false;
    }, 3000);
};

// Toast notification helper
const showToast = (msg: string) => {
    toastMessage.value = msg;
    setTimeout(() => {
        if (toastMessage.value === msg) {
            toastMessage.value = null;
        }
    }, 3000);
};

// Scroll to scene from timeline
const scrollToScene = (sceneNumber: number) => {
    const el = document.getElementById(`scene-card-${sceneNumber}`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('ring-2', 'ring-violet-500');
        setTimeout(() => {
            el.classList.remove('ring-2', 'ring-violet-500');
        }, 1500);
    }
};

// Start editing scene
const startEditScene = (scene: CartoonPromptScene) => {
    editingSceneId.value = scene.id;
    editSceneForm.title = scene.title;
    editSceneForm.action_description = scene.action_description;
    editSceneForm.visual_prompt = scene.visual_prompt;
    editSceneForm.camera_movement = scene.camera_movement || '';
    editSceneForm.dialogue_or_narration = scene.dialogue_or_narration || '';
    editSceneForm.sound_effects = scene.sound_effects || '';
    editSceneForm.music_mood = scene.music_mood || '';
    editSceneForm.negative_prompt = scene.negative_prompt || '';
    editSceneForm.duration = scene.duration;
};

// Cancel editing scene
const cancelEditScene = () => {
    editingSceneId.value = null;
};

// Save edited scene
const saveEditedScene = (scene: CartoonPromptScene) => {
    if (!props.activeProject) return;

    editSceneForm.put(`/cartoon-prompts/${props.activeProject.id}/scenes/${scene.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingSceneId.value = null;
            showToast(`Perubahan Frame #${scene.scene_number} disimpan.`);
        },
    });
};

// Regenerate single scene prompt
const regenerateScene = (scene: CartoonPromptScene) => {
    if (!props.activeProject) return;

    router.post(`/cartoon-prompts/${props.activeProject.id}/scenes/${scene.id}/regenerate`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showToast(`Prompt Frame #${scene.scene_number} berhasil diregenerasi.`);
        },
    });
};

// Add new scene
const addNewScene = () => {
    if (!props.activeProject) return;

    router.post(`/cartoon-prompts/${props.activeProject.id}/scenes`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Frame baru berhasil ditambahkan ke timeline.');
        },
    });
};

// Delete scene
const deleteScene = (scene: CartoonPromptScene) => {
    if (!props.activeProject) return;
    if (!confirm(`Hapus Frame #${scene.scene_number} dari rangkaian prompt?`)) return;

    router.delete(`/cartoon-prompts/${props.activeProject.id}/scenes/${scene.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showToast(`Frame #${scene.scene_number} berhasil dihapus.`);
        },
    });
};

// Delete entire project
const deleteProject = () => {
    if (!props.activeProject) return;
    if (!confirm(`Apakah Anda yakin ingin menghapus seluruh proyek "${props.activeProject.title}"?`)) return;

    router.delete(`/cartoon-prompts/${props.activeProject.id}`, {
        onSuccess: () => {
            showToast('Proyek berhasil dihapus.');
        },
    });
};

// Auto-fill initial values if form is empty
if (!form.title) {
    onThemeChange('ramayana_mahabharata');
}
</script>

<template>
    <AppLayout title="Generator Prompt Video Kartun">
        <Head title="Generator Prompt Video Kartun (30s & 60s)" />

        <!-- Floating Toast Notification -->
        <transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="toastMessage"
                class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-5 py-3.5 rounded-2xl bg-slate-900/95 dark:bg-slate-800/95 text-white shadow-2xl border border-violet-500/30 backdrop-blur-md"
            >
                <div class="w-7 h-7 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                    <Check class="w-4 h-4" />
                </div>
                <span class="text-sm font-semibold">{{ toastMessage }}</span>
            </div>
        </transition>

        <div class="space-y-8 pb-12">
            <!-- Studio Hero Banner -->
            <div class="p-8 sm:p-10 rounded-3xl bg-gradient-to-r from-violet-900 via-purple-900 to-amber-950 text-white shadow-2xl relative overflow-hidden flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8 border border-violet-500/20">
                <!-- Glowing decorative elements -->
                <div class="absolute -right-16 -bottom-16 w-80 h-80 rounded-full bg-amber-500/15 blur-3xl pointer-events-none"></div>
                <div class="absolute left-1/3 -top-20 w-72 h-72 rounded-full bg-violet-600/20 blur-3xl pointer-events-none"></div>

                <div class="space-y-3 max-w-2xl z-10">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/20 text-xs font-bold text-amber-300 backdrop-blur-md">
                        <Clapperboard class="w-4 h-4 text-amber-300 animate-pulse" />
                        <span>Cartoon Video Prompt Generator • Cerita Hindu</span>
                        <span class="px-2 py-0.5 rounded-full bg-amber-400 text-slate-950 text-[10px] font-extrabold uppercase">30s & 60s</span>
                    </div>

                    <h1 class="text-3xl sm:text-4xl font-extrabold font-serif tracking-tight text-white leading-tight">
                        Generator Prompt Video Kartun Cerita Hindu
                    </h1>

                    <p class="text-sm sm:text-base text-purple-200 leading-relaxed">
                        Hasilkan rangkaian prompt video AI per-frame/per-aksi berdurasi tepat <strong>30 Detik (6 Frame)</strong> atau <strong>1 Menit (12 Frame)</strong> khusus cerita dan nilai luhur Hindu Nusantara. Alur frame-ke-frame mengalir runtut, saling menyambung, dengan sinkronisasi dialog dan konsistensi visual utuh untuk AI Video Generator (<em>Kling AI, Runway Gen-3, Luma Dream Machine, Sora, Pika</em>).
                    </p>

                    <!-- Engine Status Badges -->
                    <div class="pt-2 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-semibold bg-white/10 text-purple-200 border border-white/10">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            AI Prompt Engine Siap Pakai
                        </span>
                        <span v-if="geminiConfigured" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            <Sparkles class="w-3.5 h-3.5 text-emerald-400" />
                            Google Gemini 3.8 Flash Terhubung
                        </span>
                        <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                            <Wand2 class="w-3.5 h-3.5 text-amber-400" />
                            Procedural Creative Engine Aktif
                        </span>
                    </div>
                </div>

                <!-- Fast Actions -->
                <div class="z-10 flex flex-col sm:flex-row lg:flex-col gap-3 w-full sm:w-auto">
                    <button
                        type="button"
                        @click="activeTab = 'studio'"
                        :class="[
                            'px-6 py-3.5 rounded-2xl font-bold text-sm inline-flex items-center justify-center gap-2.5 shadow-lg transition',
                            activeTab === 'studio'
                                ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 shadow-orange-500/30 font-extrabold scale-102'
                                : 'bg-white/10 hover:bg-white/20 text-white border border-white/20'
                        ]"
                    >
                        <Wand2 class="w-4 h-4" />
                        <span>Studio Generator Baru</span>
                    </button>

                    <button
                        v-if="savedProjects.length > 0"
                        type="button"
                        @click="activeTab = 'saved'"
                        :class="[
                            'px-6 py-3.5 rounded-2xl font-bold text-sm inline-flex items-center justify-center gap-2.5 shadow-lg transition',
                            activeTab === 'saved'
                                ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 shadow-orange-500/30 font-extrabold scale-102'
                                : 'bg-white/10 hover:bg-white/20 text-white border border-white/20'
                        ]"
                    >
                        <Film class="w-4 h-4" />
                        <span>Proyek Tersimpan ({{ savedProjects.length }})</span>
                    </button>
                </div>
            </div>

            <!-- Saved Projects Drawer (if tab active) -->
            <div v-if="activeTab === 'saved' && savedProjects.length > 0" class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-md space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold font-serif text-slate-900 dark:text-white">Proyek Prompt Kartun Tersimpan</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pilih proyek terdahulu untuk melihat, menyalin, atau mengedit naskah.</p>
                    </div>
                    <button
                        type="button"
                        @click="activeTab = 'studio'"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="item in savedProjects"
                        :key="item.id"
                        class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-violet-500 dark:hover:border-violet-400 transition bg-slate-50/50 dark:bg-slate-800/40 flex flex-col justify-between gap-3 group cursor-pointer"
                        @click="router.visit(`/cartoon-prompts?project_id=${item.id}`); activeTab = 'studio';"
                    >
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase"
                                    :class="item.target_duration === 30 ? 'bg-amber-100 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300' : 'bg-violet-100 dark:bg-violet-950/70 text-violet-700 dark:text-violet-300'">
                                    ⏱️ {{ item.target_duration }} Detik
                                </span>
                                <span class="text-xs text-slate-400">{{ item.scene_count }} Frame</span>
                            </div>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-violet-600 dark:group-hover:text-violet-400 transition line-clamp-2">
                                {{ item.title }}
                            </h3>
                        </div>
                        <div class="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-200 dark:border-slate-800">
                            <span>Gaya: {{ item.visual_style }}</span>
                            <span class="text-violet-600 dark:text-violet-400 font-bold group-hover:translate-x-1 transition-transform">Buka →</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Project Showcase (Per-Frame / Per-Action Prompts Breakdown) -->
            <div v-if="activeProject" class="space-y-6">
                <!-- Master Project Ribbon -->
                <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-md space-y-5">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider"
                                    :class="activeProject.target_duration === 30 ? 'bg-amber-500 text-slate-950' : 'bg-violet-600 text-white'"
                                >
                                    ⏱️ {{ activeProject.target_duration }} DETIK ({{ activeProject.scene_count }} FRAME)
                                </span>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    📐 Rasio {{ activeProject.aspect_ratio }}
                                </span>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300">
                                    🎨 {{ activeProject.visual_style }}
                                </span>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 dark:bg-orange-950/60 text-orange-700 dark:text-orange-300">
                                    🚀 Engine: {{ activeProject.target_engine }}
                                </span>
                            </div>
                            <h2 class="text-2xl font-extrabold font-serif text-slate-900 dark:text-white pt-1">
                                {{ activeProject.title }}
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <strong>Karakter Konsisten:</strong> {{ activeProject.character_description || 'Karakter Utama Animasi' }}
                            </p>
                        </div>

                        <!-- Action Toolbar -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Copy All Prompts -->
                            <button
                                type="button"
                                @click="copyAllPrompts"
                                class="px-4 py-2.5 rounded-xl font-bold text-xs inline-flex items-center gap-2 shadow-sm transition"
                                :class="copiedAll ? 'bg-emerald-600 text-white' : 'bg-violet-600 hover:bg-violet-700 text-white'"
                            >
                                <Check v-if="copiedAll" class="w-4 h-4" />
                                <Copy v-else class="w-4 h-4" />
                                <span>{{ copiedAll ? 'Semua Tersalin!' : 'Salin Semua Prompt' }}</span>
                            </button>

                            <!-- Export TXT -->
                            <a
                                :href="`/cartoon-prompts/${activeProject.id}/export/txt`"
                                target="_blank"
                                class="px-3.5 py-2.5 rounded-xl text-xs font-semibold border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 inline-flex items-center gap-1.5 transition"
                                title="Unduh naskah storyboard dalam format TXT"
                            >
                                <Download class="w-3.5 h-3.5 text-slate-500" />
                                <span>Unduh TXT</span>
                            </a>

                            <!-- Export JSON -->
                            <a
                                :href="`/cartoon-prompts/${activeProject.id}/export/json`"
                                target="_blank"
                                class="px-3.5 py-2.5 rounded-xl text-xs font-semibold border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 inline-flex items-center gap-1.5 transition"
                                title="Unduh format JSON untuk otomasi AI"
                            >
                                <Download class="w-3.5 h-3.5 text-slate-500" />
                                <span>JSON</span>
                            </a>

                            <!-- Add Scene -->
                            <button
                                type="button"
                                @click="addNewScene"
                                class="px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 hover:bg-emerald-100 transition inline-flex items-center gap-1.5"
                            >
                                <Plus class="w-3.5 h-3.5" />
                                <span>+ Frame</span>
                            </button>

                            <!-- Delete Project -->
                            <button
                                type="button"
                                @click="deleteProject"
                                class="p-2.5 rounded-xl text-red-500 hover:bg-red-50 dark:hover:bg-red-950/40 border border-transparent hover:border-red-200 transition"
                                title="Hapus Proyek"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Interactive Timeline Ribbon -->
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-800">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-500 mb-2">
                            <span class="flex items-center gap-1.5">
                                <Clock class="w-3.5 h-3.5 text-violet-500" />
                                Visual Timeline Sequence (Total: {{ activeProject.total_duration }} Detik)
                            </span>
                            <span class="text-violet-600 dark:text-violet-400">Klik frame untuk melompat</span>
                        </div>

                        <!-- Progress Track -->
                        <div class="grid gap-1.5 p-1.5 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60"
                            :style="`grid-template-columns: repeat(${activeProject.scenes?.length || 6}, minmax(0, 1fr));`"
                        >
                            <button
                                v-for="scene in activeProject.scenes"
                                :key="scene.id"
                                type="button"
                                @click="scrollToScene(scene.scene_number)"
                                class="p-2 rounded-xl text-center transition group hover:scale-102 flex flex-col items-center justify-center gap-0.5 border"
                                :class="activeProject.target_duration === 30
                                    ? 'bg-amber-500/10 hover:bg-amber-500/25 border-amber-500/30 text-amber-900 dark:text-amber-200'
                                    : 'bg-violet-500/10 hover:bg-violet-500/25 border-violet-500/30 text-violet-900 dark:text-violet-200'"
                            >
                                <span class="text-[11px] font-extrabold uppercase">F{{ scene.scene_number }}</span>
                                <span class="text-[10px] font-semibold opacity-75">{{ scene.duration }}s</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Per-Frame Cards List -->
                <div class="space-y-6">
                    <div
                        v-for="scene in activeProject.scenes"
                        :key="scene.id"
                        :id="`scene-card-${scene.scene_number}`"
                        class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-md space-y-4 transition-all duration-300"
                    >
                        <!-- Scene Card Header -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-violet-600 to-amber-500 text-white font-extrabold flex items-center justify-center text-sm shadow-md shadow-violet-500/20 shrink-0">
                                    {{ scene.scene_number }}
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2.5 py-0.5 rounded-full bg-violet-100 dark:bg-violet-950/70 text-violet-700 dark:text-violet-300 text-xs font-bold font-mono">
                                            ⏱️ {{ scene.start_second }}s - {{ scene.end_second }}s (Durasi: {{ scene.duration }}s)
                                        </span>
                                        <span v-if="scene.scene_number > 1" class="px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 text-xs font-semibold flex items-center gap-1">
                                            <CheckCircle2 class="w-3 h-3 text-emerald-500" />
                                            <span>Menyambung Frame {{ scene.scene_number - 1 }}</span>
                                        </span>
                                        <span v-else class="px-2.5 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300 text-xs font-semibold flex items-center gap-1">
                                            <Sparkles class="w-3 h-3 text-amber-500" />
                                            <span>Opening / Hook Frame 1</span>
                                        </span>
                                    </div>
                                    <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white mt-0.5">
                                        {{ scene.title }}
                                    </h3>
                                </div>
                            </div>

                            <!-- Scene Actions -->
                            <div class="flex items-center gap-2 self-end sm:self-auto">
                                <!-- Copy Single Prompt -->
                                <button
                                    type="button"
                                    @click="copyScenePrompt(scene)"
                                    class="px-3.5 py-2 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 transition shadow-sm"
                                    :class="copiedFrameId === scene.id
                                        ? 'bg-emerald-600 text-white'
                                        : 'bg-violet-600 hover:bg-violet-700 text-white'"
                                    title="Salin Prompt Visual Frame Ini"
                                >
                                    <Check v-if="copiedFrameId === scene.id" class="w-3.5 h-3.5" />
                                    <Copy v-else class="w-3.5 h-3.5" />
                                    <span>{{ copiedFrameId === scene.id ? 'Tersalin!' : 'Salin Prompt' }}</span>
                                </button>

                                <!-- Copy Dialogue -->
                                <button
                                    v-if="scene.dialogue_or_narration"
                                    type="button"
                                    @click="copyDialogue(scene)"
                                    class="px-3 py-2 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 transition border border-emerald-300 dark:border-emerald-700/70 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/40"
                                    title="Salin Kalimat Dialog / Narasi Frame Ini"
                                >
                                    <Check v-if="copiedDialogueId === scene.id" class="w-3.5 h-3.5 text-emerald-500" />
                                    <Volume2 v-else class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                    <span>{{ copiedDialogueId === scene.id ? 'Dialog Tersalin!' : 'Salin Dialog' }}</span>
                                </button>

                                <!-- Regenerate Scene -->
                                <button
                                    type="button"
                                    @click="regenerateScene(scene)"
                                    class="p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 transition"
                                    title="Regenerasi Prompt Frame Ini dengan Variasi Baru"
                                >
                                    <RefreshCw class="w-4 h-4 text-amber-500" />
                                </button>

                                <!-- Edit Scene -->
                                <button
                                    type="button"
                                    @click="startEditScene(scene)"
                                    class="p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 transition"
                                    title="Edit Detail Frame"
                                >
                                    <Edit3 class="w-4 h-4 text-violet-500" />
                                </button>

                                <!-- Delete Scene -->
                                <button
                                    type="button"
                                    @click="deleteScene(scene)"
                                    class="p-2 rounded-xl text-red-500 hover:bg-red-50 dark:hover:bg-red-950/40 border border-slate-200 dark:border-slate-700 transition"
                                    title="Hapus Frame Ini"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Read Mode -->
                        <div v-if="editingSceneId !== scene.id" class="space-y-4">
                            <!-- Action Description (Indonesian) -->
                            <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-slate-800 dark:text-amber-100 text-sm">
                                <span class="font-bold text-amber-800 dark:text-amber-300 block text-xs uppercase tracking-wider mb-1">
                                    🎬 Aksi & Peristiwa Karakter:
                                </span>
                                {{ scene.action_description }}
                            </div>

                            <!-- Visual AI Prompt (English, Ready for Kling / Runway / Sora / Luma) -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs font-bold text-slate-500">
                                    <span class="flex items-center gap-1.5 text-violet-600 dark:text-violet-400">
                                        <Wand2 class="w-3.5 h-3.5" />
                                        PROMPT VISUAL AI (Salin ke Kling / Runway / Luma / Sora):
                                    </span>
                                    <span class="text-[11px] font-mono text-slate-400">Format: English Optimized</span>
                                </div>
                                <div class="p-4 rounded-2xl bg-slate-950 text-slate-100 font-mono text-xs sm:text-sm leading-relaxed border border-slate-800 shadow-inner relative group select-all">
                                    {{ scene.visual_prompt }}
                                </div>
                            </div>

                            <!-- Context Details Grid (Camera, Audio, SFX, BGM) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60">
                                    <span class="font-bold text-slate-500 uppercase block mb-1 flex items-center gap-1">
                                        <Camera class="w-3 h-3 text-blue-500" />
                                        Arah Gerak Kamera:
                                    </span>
                                    <span class="text-slate-800 dark:text-slate-200 font-semibold">{{ scene.camera_movement || 'Cinematic tracking shot' }}</span>
                                </div>

                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60">
                                    <span class="font-bold text-slate-500 uppercase block mb-1 flex items-center gap-1">
                                        <Volume2 class="w-3 h-3 text-emerald-500" />
                                        Dialog / Narasi:
                                    </span>
                                    <span class="text-slate-800 dark:text-slate-200 italic">"{{ scene.dialogue_or_narration || '-' }}"</span>
                                </div>

                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60">
                                    <span class="font-bold text-slate-500 uppercase block mb-1 flex items-center gap-1">
                                        <Play class="w-3 h-3 text-amber-500" />
                                        Efek Suara (SFX):
                                    </span>
                                    <span class="text-slate-800 dark:text-slate-200 font-semibold">{{ scene.sound_effects || '-' }}</span>
                                </div>

                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60">
                                    <span class="font-bold text-slate-500 uppercase block mb-1 flex items-center gap-1">
                                        <Sliders class="w-3 h-3 text-purple-500" />
                                        Musik Pengiring:
                                    </span>
                                    <span class="text-slate-800 dark:text-slate-200 font-semibold">{{ scene.music_mood || '-' }}</span>
                                </div>
                            </div>

                            <!-- Negative Prompt (collapsible) -->
                            <div v-if="scene.negative_prompt" class="pt-1 flex items-center justify-between text-xs text-slate-500">
                                <span class="truncate max-w-xl">
                                    <strong>Negative Prompt:</strong> {{ scene.negative_prompt }}
                                </span>
                                <button
                                    type="button"
                                    @click="copyNegativePrompt(scene)"
                                    class="text-violet-600 dark:text-violet-400 hover:underline font-semibold shrink-0 ml-2"
                                >
                                    {{ copiedNegativeId === scene.id ? 'Tersalin!' : 'Salin Negative' }}
                                </button>
                            </div>
                        </div>

                        <!-- Inline Edit Mode -->
                        <div v-else class="space-y-4 p-4 rounded-2xl bg-violet-50/50 dark:bg-violet-950/20 border border-violet-200 dark:border-violet-800">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Judul Frame</label>
                                    <input
                                        v-model="editSceneForm.title"
                                        type="text"
                                        class="w-full px-3 py-2 rounded-xl text-sm border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Durasi (Detik)</label>
                                    <input
                                        v-model.number="editSceneForm.duration"
                                        type="number"
                                        step="0.5"
                                        min="1"
                                        max="30"
                                        class="w-full px-3 py-2 rounded-xl text-sm border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Deskripsi Aksi Karakter (Bahasa Indonesia)</label>
                                <textarea
                                    v-model="editSceneForm.action_description"
                                    rows="2"
                                    class="w-full px-3 py-2 rounded-xl text-sm border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Visual Prompt untuk AI Video (Bahasa Inggris)</label>
                                <textarea
                                    v-model="editSceneForm.visual_prompt"
                                    rows="4"
                                    class="w-full px-3 py-2 rounded-xl text-sm font-mono border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                ></textarea>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Gerakan Kamera</label>
                                    <input
                                        v-model="editSceneForm.camera_movement"
                                        type="text"
                                        class="w-full px-3 py-2 rounded-xl text-sm border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Dialog / Narasi</label>
                                    <input
                                        v-model="editSceneForm.dialogue_or_narration"
                                        type="text"
                                        class="w-full px-3 py-2 rounded-xl text-sm border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                    />
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    @click="cancelEditScene"
                                    class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    @click="saveEditedScene(scene)"
                                    class="px-5 py-2 rounded-xl text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white shadow-sm inline-flex items-center gap-1.5 transition"
                                >
                                    <Save class="w-3.5 h-3.5" />
                                    <span>Simpan Perubahan</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Creation Studio Form Section (Simplified & 100% Hindu-Exclusive) -->
            <div class="p-6 sm:p-10 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl space-y-8">
                <!-- Section Header -->
                <div class="border-b border-slate-200 dark:border-slate-800 pb-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100 dark:bg-amber-950/70 text-amber-800 dark:text-amber-300 text-xs font-bold mb-2">
                            <Sparkles class="w-3.5 h-3.5 text-amber-500" />
                            <span>Khusus Cerita Hindu & Nilai Dharma</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-extrabold font-serif text-slate-900 dark:text-white">
                            Form Request Prompt Video Kartun Hindu
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Pilih durasi dan tema cerita Hindu. Mesin akan merancang prompt frame-demi-frame yang <strong>saling menyambung runtut (kontinuitas ketat)</strong> lengkap dengan dialog dan visual prompt AI.
                        </p>
                    </div>

                    <div class="flex items-center gap-2 text-xs text-slate-500 bg-slate-50 dark:bg-slate-800/60 px-4 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700/60 self-start md:self-auto">
                        <CheckCircle2 class="w-4 h-4 text-emerald-500 shrink-0" />
                        <span>Prompt siap pakai untuk <strong>Kling AI, Runway, Luma & Sora</strong></span>
                    </div>
                </div>

                <form @submit.prevent="submitGenerate" class="space-y-8">
                    <!-- LANGKAH 1: Pilihan Durasi Video (30 Detik vs 1 Menit) -->
                    <div class="space-y-3">
                        <label class="block text-sm font-extrabold text-slate-900 dark:text-white flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <Clock class="w-4 h-4 text-amber-500" />
                                1. Pilihan Durasi Video (Wajib)
                            </span>
                            <span class="text-xs text-violet-600 dark:text-violet-400 font-semibold">Tentukan panjang video</span>
                        </label>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- 30 Detik Card -->
                            <div
                                @click="setDuration(30)"
                                class="p-5 rounded-2xl border-2 cursor-pointer transition-all duration-200 flex items-start gap-4 relative overflow-hidden group"
                                :class="form.target_duration === 30
                                    ? 'border-amber-500 bg-amber-50/70 dark:bg-amber-950/30 shadow-lg shadow-amber-500/10'
                                    : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40'"
                            >
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 font-extrabold text-xl shadow-md"
                                    :class="form.target_duration === 30
                                        ? 'bg-amber-500 text-slate-950'
                                        : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300'"
                                >
                                    ⚡
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-extrabold text-lg text-slate-900 dark:text-white">30 Detik (Video Kilat)</h3>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-amber-500 text-slate-950">6 Frame</span>
                                    </div>
                                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                                        Format video ringkas untuk TikTok, Reels, dan YouTube Shorts. Menghasilkan <strong>6 Prompt Frame/Aksi</strong> (@ 5 detik per adegan) dengan hook kuat dan akhir tuntas di detik ke-30.
                                    </p>
                                    <div class="pt-1 flex items-center gap-2 text-[11px] font-semibold text-amber-700 dark:text-amber-400">
                                        <span>✓ 6 Prompt Kontinu</span>
                                        <span>•</span>
                                        <span>✓ Detik 0 s.d 30 Tuntas</span>
                                    </div>
                                </div>
                            </div>

                            <!-- 1 Menit Card -->
                            <div
                                @click="setDuration(60)"
                                class="p-5 rounded-2xl border-2 cursor-pointer transition-all duration-200 flex items-start gap-4 relative overflow-hidden group"
                                :class="form.target_duration === 60
                                    ? 'border-violet-500 bg-violet-50/70 dark:bg-violet-950/30 shadow-lg shadow-violet-500/10'
                                    : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40'"
                            >
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 font-extrabold text-xl shadow-md"
                                    :class="form.target_duration === 60
                                        ? 'bg-violet-600 text-white'
                                        : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300'"
                                >
                                    🎬
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-extrabold text-lg text-slate-900 dark:text-white">1 Menit (Cerita Utuh)</h3>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-violet-600 text-white">12 Frame</span>
                                    </div>
                                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                                        Format cerita kartun mendalam. Menghasilkan <strong>12 Prompt Frame/Aksi</strong> (@ 5 detik per adegan) dengan alur utuh: Pengenalan, Masalah, Tantangan, Puncak, dan Resolusi Shanti.
                                    </p>
                                    <div class="pt-1 flex items-center gap-2 text-[11px] font-semibold text-violet-700 dark:text-violet-400">
                                        <span>✓ 12 Prompt Kontinu</span>
                                        <span>•</span>
                                        <span>✓ Detik 0 s.d 60 Tuntas</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- LANGKAH 2: Pilihan Tema Cerita Hindu -->
                    <div class="space-y-3">
                        <label class="block text-sm font-extrabold text-slate-900 dark:text-white flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <Sparkles class="w-4 h-4 text-purple-500" />
                                2. Pilih Tema Cerita Hindu (Otomatis Mengisi Judul & Karakter)
                            </span>
                            <span class="text-xs text-amber-600 dark:text-amber-400 font-semibold">Khusus cerita bernilai Dharma</span>
                        </label>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div
                                v-for="t in themes"
                                :key="t.id"
                                @click="onThemeChange(t.id)"
                                class="p-4 rounded-2xl border-2 cursor-pointer transition-all duration-200 flex flex-col justify-between gap-2 relative overflow-hidden group"
                                :class="form.theme === t.id
                                    ? 'border-violet-600 bg-violet-50/80 dark:bg-violet-950/40 text-violet-950 dark:text-violet-100 shadow-md shadow-violet-500/10'
                                    : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40'"
                            >
                                <div>
                                    <div class="flex items-center justify-between gap-2 mb-1.5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg">
                                                {{ t.id === 'ramayana_mahabharata' ? '🏹' : (t.id === 'dharma_philosophy' ? '🪷' : (t.id === 'hindu_traditions' ? '🎋' : (t.id === 'deva_wisdom' ? '🐘' : '📜'))) }}
                                            </span>
                                            <h4 class="font-extrabold text-sm text-slate-900 dark:text-white leading-tight">{{ t.name }}</h4>
                                        </div>
                                        <div class="w-3 h-3 rounded-full border-2 flex items-center justify-center shrink-0" :class="form.theme === t.id ? 'border-violet-600 bg-violet-600' : 'border-slate-300 dark:border-slate-600'">
                                            <div v-if="form.theme === t.id" class="w-1 h-1 rounded-full bg-white"></div>
                                        </div>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-normal line-clamp-3">{{ t.description }}</p>
                                </div>

                                <div class="pt-2 border-t border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between text-[11px]">
                                    <span class="text-slate-400 font-medium truncate max-w-[190px]">Contoh: {{ t.default_title }}</span>
                                    <span v-if="form.theme === t.id" class="text-violet-600 dark:text-violet-400 font-bold shrink-0">Terpilih ✓</span>
                                </div>
                            </div>
                        </div>

                        <!-- Custom Theme Title input if custom selected -->
                        <div v-if="form.theme === 'custom' || form.theme === 'custom_hindu'" class="pt-2 p-4 rounded-2xl bg-amber-50/60 dark:bg-amber-950/20 border border-amber-300 dark:border-amber-800/60 space-y-2">
                            <label class="block text-xs font-bold text-amber-900 dark:text-amber-300">Tuliskan Topik / Judul Cerita Hindu Pilihan Anda</label>
                            <input
                                v-model="form.custom_theme_title"
                                type="text"
                                placeholder="Contoh: Kisah Bhisma Mengajarkan Keadilan dan Dharma kepada Pandawa"
                                class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                            />
                        </div>
                    </div>

                    <!-- LANGKAH 3: Gaya Visual & Rasio Layar (Kompak & Simpel) -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pt-1">
                        <!-- Gaya Visual Animasi (2 Kolom) -->
                        <div class="lg:col-span-2 space-y-2">
                            <label class="block text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                <Sliders class="w-4 h-4 text-teal-500" />
                                3. Gaya Visual Animasi
                            </label>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                <div
                                    v-for="style in visualStyles"
                                    :key="style.id"
                                    @click="form.visual_style = style.id"
                                    class="p-3 rounded-2xl border-2 cursor-pointer transition flex flex-col justify-between gap-1 text-center"
                                    :class="form.visual_style === style.id
                                        ? 'border-violet-600 bg-violet-50/80 dark:bg-violet-950/50 shadow-sm'
                                        : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40'"
                                >
                                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ style.badge }}</span>
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ style.name }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Format Rasio Layar (1 Kolom) -->
                        <div class="space-y-2">
                            <label class="block text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                <Film class="w-4 h-4 text-emerald-500" />
                                Format Rasio Aspek
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    @click="form.aspect_ratio = '9:16'"
                                    class="py-2.5 px-2 rounded-xl border-2 text-xs font-bold transition text-center"
                                    :class="form.aspect_ratio === '9:16'
                                        ? 'border-violet-600 bg-violet-100/60 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300'
                                        : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300'"
                                >
                                    📱 9:16
                                </button>
                                <button
                                    type="button"
                                    @click="form.aspect_ratio = '16:9'"
                                    class="py-2.5 px-2 rounded-xl border-2 text-xs font-bold transition text-center"
                                    :class="form.aspect_ratio === '16:9'
                                        ? 'border-violet-600 bg-violet-100/60 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300'
                                        : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300'"
                                >
                                    🖥️ 16:9
                                </button>
                                <button
                                    type="button"
                                    @click="form.aspect_ratio = '1:1'"
                                    class="py-2.5 px-2 rounded-xl border-2 text-xs font-bold transition text-center"
                                    :class="form.aspect_ratio === '1:1'
                                        ? 'border-violet-600 bg-violet-100/60 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300'
                                        : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300'"
                                >
                                    ⏹️ 1:1
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- LANGKAH 4 (OPSIONAL): Sesuaikan Judul & Detail Karakter -->
                    <div class="pt-2 border-t border-slate-200/80 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showAdvancedOptions = !showAdvancedOptions"
                            class="inline-flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition py-1"
                        >
                            <Sliders class="w-3.5 h-3.5" />
                            <span>{{ showAdvancedOptions ? 'Sembunyikan Kustomisasi Detail' : '⚙️ Sesuaikan Judul, Karakter & Alur (Opsional)' }}</span>
                            <ChevronUp v-if="showAdvancedOptions" class="w-3.5 h-3.5" />
                            <ChevronDown v-else class="w-3.5 h-3.5" />
                        </button>

                        <div v-if="showAdvancedOptions" class="mt-4 p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Judul Cerita Kartun (Sudah Diisi Otomatis Sesuai Tema)
                                </label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    placeholder="Tuliskan judul kartun Hindu..."
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 flex items-center justify-between">
                                    <span>Karakter Utama (Visual Anchor Konsisten Tiap Frame)</span>
                                    <span class="text-[11px] text-violet-600 dark:text-violet-400">Diulang di setiap prompt visual</span>
                                </label>
                                <textarea
                                    v-model="form.character_description"
                                    rows="2"
                                    placeholder="Contoh: Sang Hanuman cilik berbulu putih salju kemilau mengenakan mahkota emas permata..."
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Sinopsis / Alur Cerita Singkat
                                </label>
                                <textarea
                                    v-model="form.story_premise"
                                    rows="2"
                                    placeholder="Ceritakan gambaran alur yang ingin dipecah per-frame..."
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Target AI Video Generator (Optimasi Sintaks)
                                </label>
                                <select
                                    v-model="form.target_engine"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-semibold"
                                >
                                    <option v-for="eng in targetEngines" :key="eng.id" :value="eng.id">
                                        {{ eng.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Generate Utama (Satu Klik Langsung Jadi!) -->
                    <div class="pt-4">
                        <button
                            type="submit"
                            :disabled="isGenerating || form.processing"
                            class="w-full py-4 px-8 rounded-2xl font-extrabold text-base bg-gradient-to-r from-amber-500 via-orange-500 to-violet-600 text-white shadow-xl shadow-orange-500/25 hover:opacity-95 transition-all transform hover:-translate-y-0.5 disabled:opacity-50 disabled:pointer-events-none flex items-center justify-center gap-3 cursor-pointer"
                        >
                            <RefreshCw v-if="isGenerating || form.processing" class="w-5 h-5 animate-spin" />
                            <Wand2 v-else class="w-5 h-5 text-amber-200" />
                            <span>
                                {{ isGenerating || form.processing
                                    ? `Sedang Menyusun Naskah Runtut ${form.target_duration} Detik (${form.target_duration === 30 ? '6' : '12'} Frame)...`
                                    : `✨ Hasilkan Prompt Video Kartun Hindu (${form.target_duration} Detik • ${form.target_duration === 30 ? '6 Frame' : '12 Frame'})`
                                }}
                            </span>
                        </button>
                        <p class="text-center text-xs text-slate-500 dark:text-slate-400 mt-2">
                            Alur adegan dari Frame 1 sampai {{ form.target_duration === 30 ? '6' : '12' }} dijamin menyambung mulus dengan sinkronisasi dialog, SFX, dan musik gamelan/spiritual Hindu.
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
