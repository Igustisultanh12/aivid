<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Sparkles,
    Clock,
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
    Clapperboard,
    Wand2,
    Volume2,
    Sliders,
    CheckCircle2,
    Camera,
    Music,
    Play
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
const isRegeneratingAll = ref(false);
const regeneratingSceneId = ref<number | null>(null);
const activeTab = ref<'studio' | 'saved'>('studio');
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

// Copy single scene unified prompt
const copyScenePrompt = (scene: CartoonPromptScene) => {
    navigator.clipboard.writeText(scene.visual_prompt);
    copiedFrameId.value = scene.id;
    showToast(`Prompt Lengkap Frame #${scene.scene_number} berhasil disalin!`);
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
    showToast(`Semua ${props.activeProject.scenes.length} prompt berhasil disalin!`);
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
    regeneratingSceneId.value = scene.id;

    router.post(`/cartoon-prompts/${props.activeProject.id}/scenes/${scene.id}/regenerate`, {}, {
        preserveScroll: true,
        onFinish: () => {
            regeneratingSceneId.value = null;
        },
        onSuccess: () => {
            showToast(`Prompt Frame #${scene.scene_number} berhasil diregenerasi.`);
        },
    });
};

// Regenerate entire project (all frames)
const regenerateEntireProject = () => {
    if (!props.activeProject) return;
    if (!confirm(`Regenerasi ulang seluruh ${props.activeProject.scenes?.length || 6} frame naskah untuk proyek "${props.activeProject.title}"?`)) return;

    isRegeneratingAll.value = true;
    router.post(`/cartoon-prompts/${props.activeProject.id}/regenerate`, {}, {
        preserveScroll: true,
        onFinish: () => {
            isRegeneratingAll.value = false;
        },
        onSuccess: () => {
            showToast('Seluruh rangkaian naskah berhasil diregenerasi ulang!');
        },
    });
};

// Add new scene
const addNewScene = () => {
    if (!props.activeProject) return;

    router.post(`/cartoon-prompts/${props.activeProject.id}/scenes`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Frame baru berhasil ditambahkan.');
        },
    });
};

// Delete scene
const deleteScene = (scene: CartoonPromptScene) => {
    if (!props.activeProject) return;
    if (!confirm(`Hapus Frame #${scene.scene_number} dari naskah?`)) return;

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
    <AppLayout title="Generator Prompt Video Kartun Hindu">
        <Head title="Generator Prompt Video Kartun Hindu (30s & 60s)" />

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
                class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl bg-slate-900/95 text-white shadow-2xl border border-slate-700/60 backdrop-blur-md"
            >
                <div class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                    <Check class="w-3.5 h-3.5" />
                </div>
                <span class="text-xs font-semibold">{{ toastMessage }}</span>
            </div>
        </transition>

        <div class="space-y-6 pb-16 max-w-7xl mx-auto">
            <!-- Studio Hero Banner -->
            <div class="card-glass rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/80 dark:border-slate-800 relative overflow-hidden">
                <div class="absolute -right-20 -top-20 w-80 h-80 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>
                <div class="absolute -left-20 -bottom-20 w-80 h-80 rounded-full bg-violet-600/10 blur-3xl pointer-events-none"></div>

                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 relative z-10">
                    <div class="space-y-2.5 max-w-2xl">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-xs font-bold text-amber-800 dark:text-amber-300">
                            <Sparkles class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                            <span>Studio Video Kartun Hindu Nusantara</span>
                            <span class="px-2 py-0.5 rounded-full bg-amber-500 text-slate-950 text-[10px] font-extrabold uppercase">30s & 60s</span>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-extrabold font-serif tracking-tight text-slate-900 dark:text-white leading-tight">
                            Generator Prompt Video Kartun Hindu
                        </h1>

                        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                            Rancang rangkaian prompt video AI per-frame berdurasi tepat <strong>30 detik (6 frame)</strong> atau <strong>1 menit (12 frame)</strong> dalam <strong>Bahasa Indonesia</strong> khusus kisah suci Hindu Nusantara. Setiap frame menyatukan aksi visual, arah kamera, dialog, SFX, musik, dan negative prompt menjadi <strong>satu prompt utuh</strong> dengan alur berlanjut mulus untuk <em>Kling AI, Runway Gen-3, Luma, Sora, & Pika</em>.
                        </p>

                        <div class="pt-1 flex flex-wrap items-center gap-2 text-xs">
                            <span v-if="geminiConfigured" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                <CheckCircle2 class="w-3.5 h-3.5 text-emerald-500" />
                                Gemini 3.8 Flash Terhubung
                            </span>
                            <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                <Wand2 class="w-3.5 h-3.5 text-amber-500" />
                                Procedural Story Engine Siap
                            </span>
                        </div>
                    </div>

                    <!-- Navigation Pills -->
                    <div class="flex items-center gap-2 p-1.5 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 shrink-0 self-stretch sm:self-auto justify-center">
                        <button
                            type="button"
                            @click="activeTab = 'studio'"
                            class="px-4 py-2.5 rounded-xl font-bold text-xs inline-flex items-center gap-2 transition"
                            :class="activeTab === 'studio'
                                ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        >
                            <Wand2 class="w-4 h-4 text-amber-500" />
                            <span>Studio Naskah</span>
                        </button>

                        <button
                            v-if="savedProjects.length > 0"
                            type="button"
                            @click="activeTab = 'saved'"
                            class="px-4 py-2.5 rounded-xl font-bold text-xs inline-flex items-center gap-2 transition"
                            :class="activeTab === 'saved'
                                ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        >
                            <Film class="w-4 h-4 text-violet-500" />
                            <span>Naskah Tersimpan ({{ savedProjects.length }})</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Saved Projects Drawer (if tab active) -->
            <div v-if="activeTab === 'saved' && savedProjects.length > 0" class="card-glass rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200/80 dark:border-slate-800">
                    <div>
                        <h2 class="text-lg font-bold font-serif text-slate-900 dark:text-white">Daftar Naskah Video Kartun Tersimpan</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pilih salah satu proyek untuk melihat dan menyalin prompt per-frame.</p>
                    </div>
                    <button
                        type="button"
                        @click="activeTab = 'studio'"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                    <div
                        v-for="item in savedProjects"
                        :key="item.id"
                        class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-violet-500 dark:hover:border-violet-400 transition bg-slate-50/50 dark:bg-slate-800/40 flex flex-col justify-between gap-3 group cursor-pointer"
                        @click="router.visit(`/cartoon-prompts?project_id=${item.id}`); activeTab = 'studio';"
                    >
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase font-mono"
                                    :class="item.target_duration === 30 ? 'bg-amber-100 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300' : 'bg-violet-100 dark:bg-violet-950/70 text-violet-700 dark:text-violet-300'">
                                    ⏱️ {{ item.target_duration }}s
                                </span>
                                <span class="text-[11px] text-slate-400 font-medium">{{ item.scene_count }} Frame</span>
                            </div>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white group-hover:text-violet-600 dark:group-hover:text-violet-400 transition line-clamp-2">
                                {{ item.title }}
                            </h3>
                        </div>
                        <div class="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-200 dark:border-slate-800">
                            <span class="truncate max-w-[180px]">{{ item.visual_style }}</span>
                            <span class="text-violet-600 dark:text-violet-400 font-bold group-hover:translate-x-1 transition-transform">Buka →</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Project Storyboard (Per-Frame Breakdown) -->
            <div v-if="activeProject" class="space-y-6">
                <!-- Project Ribbon Bar -->
                <div class="card-glass rounded-3xl p-6 sm:p-7 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase font-mono"
                                    :class="activeProject.target_duration === 30 ? 'bg-amber-500 text-slate-950' : 'bg-violet-600 text-white'"
                                >
                                    ⏱️ {{ activeProject.target_duration }} DETIK ({{ activeProject.scene_count }} FRAME)
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    📐 {{ activeProject.aspect_ratio }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300">
                                    🎨 {{ activeProject.visual_style }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-orange-100 dark:bg-orange-950/60 text-orange-700 dark:text-orange-300">
                                    🚀 {{ activeProject.target_engine }}
                                </span>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-extrabold font-serif text-slate-900 dark:text-white pt-0.5">
                                {{ activeProject.title }}
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                <strong>Karakter Anchor:</strong> {{ activeProject.character_description || 'Karakter Utama Animasi' }}
                            </p>
                        </div>

                        <!-- Action Toolbar -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Copy All Prompts (Primary) -->
                            <button
                                type="button"
                                @click="copyAllPrompts"
                                class="px-4 py-2.5 rounded-xl font-bold text-xs inline-flex items-center gap-2 shadow-sm transition"
                                :class="copiedAll ? 'bg-emerald-600 text-white' : 'btn-primary'"
                            >
                                <Check v-if="copiedAll" class="w-4 h-4" />
                                <Copy v-else class="w-4 h-4" />
                                <span>{{ copiedAll ? 'Semua Tersalin!' : 'Salin Semua Prompt' }}</span>
                            </button>

                            <!-- Regenerate All Prompts (Right next to Salin Semua Prompt, per Gambar 2) -->
                            <button
                                type="button"
                                @click="regenerateEntireProject"
                                :disabled="isRegeneratingAll"
                                class="px-4 py-2.5 rounded-xl font-bold text-xs inline-flex items-center gap-2 border border-amber-300 dark:border-amber-700/80 bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-200 hover:bg-amber-100 dark:hover:bg-amber-900/40 transition shadow-sm disabled:opacity-50"
                                title="Regenerasi seluruh rangkaian prompt naskah proyek ini"
                            >
                                <RefreshCw class="w-4 h-4 text-amber-600 dark:text-amber-400" :class="{ 'animate-spin': isRegeneratingAll }" />
                                <span>{{ isRegeneratingAll ? 'Meregenerasi...' : 'Regenerasi Naskah' }}</span>
                            </button>

                            <!-- Export TXT -->
                            <a
                                :href="`/cartoon-prompts/${activeProject.id}/export/txt`"
                                target="_blank"
                                class="px-3 py-2.5 rounded-xl text-xs font-semibold border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 inline-flex items-center gap-1.5 transition"
                                title="Unduh storyboard format TXT"
                            >
                                <Download class="w-3.5 h-3.5 text-slate-500" />
                                <span>TXT</span>
                            </a>

                            <!-- Export JSON -->
                            <a
                                :href="`/cartoon-prompts/${activeProject.id}/export/json`"
                                target="_blank"
                                class="px-3 py-2.5 rounded-xl text-xs font-semibold border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 inline-flex items-center gap-1.5 transition"
                                title="Unduh format JSON"
                            >
                                <Download class="w-3.5 h-3.5 text-slate-500" />
                                <span>JSON</span>
                            </a>

                            <!-- Add Scene -->
                            <button
                                type="button"
                                @click="addNewScene"
                                class="px-3 py-2.5 rounded-xl text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 hover:bg-emerald-100 transition inline-flex items-center gap-1"
                            >
                                <Plus class="w-3.5 h-3.5" />
                                <span>+ Frame</span>
                            </button>

                            <!-- Delete Project -->
                            <button
                                type="button"
                                @click="deleteProject"
                                class="p-2.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-slate-200 dark:border-slate-800 transition"
                                title="Hapus Proyek"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Timeline Progress Track -->
                    <div class="pt-3 border-t border-slate-200/80 dark:border-slate-800">
                        <div class="flex items-center justify-between text-xs font-semibold text-slate-500 mb-2">
                            <span class="flex items-center gap-1.5">
                                <Clock class="w-3.5 h-3.5 text-violet-500" />
                                Timeline Adegan (Total: {{ activeProject.total_duration }} Detik)
                            </span>
                            <span class="text-xs text-slate-400">Klik frame untuk melompat</span>
                        </div>

                        <div class="grid gap-1.5 p-1 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60"
                            :style="`grid-template-columns: repeat(${activeProject.scenes?.length || 6}, minmax(0, 1fr));`"
                        >
                            <button
                                v-for="scene in activeProject.scenes"
                                :key="scene.id"
                                type="button"
                                @click="scrollToScene(scene.scene_number)"
                                class="py-1.5 px-1 rounded-xl text-center transition hover:bg-white dark:hover:bg-slate-700 flex flex-col items-center justify-center border"
                                :class="activeProject.target_duration === 30
                                    ? 'border-amber-500/20 text-amber-900 dark:text-amber-200'
                                    : 'border-violet-500/20 text-violet-900 dark:text-violet-200'"
                            >
                                <span class="text-[11px] font-extrabold uppercase font-mono">F{{ scene.scene_number }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">{{ scene.duration }}s</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Per-Frame Cards List -->
                <div class="space-y-4">
                    <div
                        v-for="scene in activeProject.scenes"
                        :key="scene.id"
                        :id="`scene-card-${scene.scene_number}`"
                        class="card-glass rounded-3xl p-5 sm:p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4 transition-all duration-300"
                    >
                        <!-- Scene Card Header -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200/80 dark:border-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-violet-600 text-white font-extrabold flex items-center justify-center text-sm shadow-sm shrink-0">
                                    {{ scene.scene_number }}
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold font-mono">
                                            ⏱️ {{ scene.start_second }}s - {{ scene.end_second }}s ({{ scene.duration }}s)
                                        </span>
                                        <span v-if="scene.scene_number > 1" class="px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 text-xs font-semibold flex items-center gap-1 border border-emerald-200 dark:border-emerald-800/60">
                                            <CheckCircle2 class="w-3 h-3 text-emerald-500" />
                                            <span>Menyambung Frame {{ scene.scene_number - 1 }}</span>
                                        </span>
                                        <span v-else class="px-2 py-0.5 rounded-full bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 text-xs font-semibold flex items-center gap-1 border border-amber-200 dark:border-amber-800/60">
                                            <Sparkles class="w-3 h-3 text-amber-500" />
                                            <span>Opening Scene</span>
                                        </span>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-900 dark:text-white mt-0.5">
                                        {{ scene.title }}
                                    </h3>
                                </div>
                            </div>

                            <!-- Scene Actions -->
                            <div class="flex items-center gap-1.5 self-end sm:self-auto">
                                <!-- Copy Single Unified Prompt -->
                                <button
                                    type="button"
                                    @click="copyScenePrompt(scene)"
                                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 transition shadow-sm"
                                    :class="copiedFrameId === scene.id
                                        ? 'bg-emerald-600 text-white'
                                        : 'btn-primary'"
                                    title="Salin Satu Prompt Lengkap Frame Ini (Aksi + Kamera + Dialog + SFX + BGM + Negative)"
                                >
                                    <Check v-if="copiedFrameId === scene.id" class="w-3.5 h-3.5" />
                                    <Copy v-else class="w-3.5 h-3.5" />
                                    <span>{{ copiedFrameId === scene.id ? 'Tersalin!' : 'Salin Prompt' }}</span>
                                </button>

                                <!-- Regenerate Scene (Right next to Salin Prompt) -->
                                <button
                                    type="button"
                                    @click="regenerateScene(scene)"
                                    :disabled="regeneratingSceneId === scene.id"
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 transition border border-amber-300 dark:border-amber-700/70 bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-200 hover:bg-amber-100 dark:hover:bg-amber-900/40 disabled:opacity-50"
                                    title="Regenerasi prompt frame ini dengan variasi baru"
                                >
                                    <RefreshCw class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" :class="{ 'animate-spin': regeneratingSceneId === scene.id }" />
                                    <span>{{ regeneratingSceneId === scene.id ? 'Memproses...' : 'Regenerate' }}</span>
                                </button>

                                <!-- Copy Dialogue -->
                                <button
                                    v-if="scene.dialogue_or_narration"
                                    type="button"
                                    @click="copyDialogue(scene)"
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 transition border border-emerald-300 dark:border-emerald-700/70 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/40"
                                    title="Salin Kalimat Dialog"
                                >
                                    <Check v-if="copiedDialogueId === scene.id" class="w-3.5 h-3.5 text-emerald-500" />
                                    <Volume2 v-else class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                    <span>{{ copiedDialogueId === scene.id ? 'Tersalin!' : 'Salin Dialog' }}</span>
                                </button>

                                <!-- Edit Scene -->
                                <button
                                    type="button"
                                    @click="startEditScene(scene)"
                                    class="p-2 rounded-xl text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                    title="Edit Frame"
                                >
                                    <Edit3 class="w-3.5 h-3.5 text-violet-500" />
                                </button>

                                <!-- Delete Scene -->
                                <button
                                    type="button"
                                    @click="deleteScene(scene)"
                                    class="p-2 rounded-xl text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                    title="Hapus Frame"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Read Mode: SATU PROMPT UTUH (Bahasa Indonesia) -->
                        <div v-if="editingSceneId !== scene.id" class="space-y-3">
                            <!-- Unified Complete AI Video Prompt (Bahasa Indonesia) -->
                            <div class="space-y-1.5">
                                <div class="flex flex-wrap items-center justify-between gap-2 px-1 text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-violet-700 dark:text-violet-300 flex items-center gap-1.5 text-xs uppercase tracking-wider">
                                            <Wand2 class="w-3.5 h-3.5 text-violet-500" />
                                            Prompt Lengkap Video AI (Bahasa Indonesia)
                                        </span>
                                        <span class="px-2 py-0.5 rounded-full bg-violet-100 dark:bg-violet-950/70 text-violet-700 dark:text-violet-300 text-[10px] font-bold">
                                            Satu Prompt Utuh
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="copyScenePrompt(scene)"
                                        class="text-[11px] font-bold text-violet-600 dark:text-violet-400 hover:underline inline-flex items-center gap-1"
                                    >
                                        <Check v-if="copiedFrameId === scene.id" class="w-3 h-3 text-emerald-500" />
                                        <Copy v-else class="w-3 h-3" />
                                        <span>{{ copiedFrameId === scene.id ? 'Tersalin ke Clipboard!' : 'Salin Seluruh Prompt' }}</span>
                                    </button>
                                </div>

                                <div class="p-4 sm:p-5 rounded-2xl bg-slate-950 text-slate-100 font-mono text-xs sm:text-[13px] leading-relaxed border border-slate-800 shadow-inner relative group select-all whitespace-pre-wrap">
                                    {{ scene.visual_prompt }}
                                </div>
                            </div>

                            <!-- Quick Summary Pills -->
                            <div class="flex flex-wrap items-center gap-2 pt-1 text-[11px]">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium">
                                    <Camera class="w-3 h-3 text-slate-500" />
                                    <strong class="font-bold">Kamera:</strong> {{ scene.camera_movement || 'Cinematic tracking shot' }}
                                </span>
                                <span v-if="scene.dialogue_or_narration" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-200 font-medium">
                                    <Volume2 class="w-3 h-3 text-emerald-500" />
                                    <strong class="font-bold">Dialog:</strong> "{{ scene.dialogue_or_narration }}"
                                </span>
                                <span v-if="scene.sound_effects" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-200 font-medium">
                                    <Play class="w-3 h-3 text-amber-500" />
                                    <strong class="font-bold">SFX:</strong> {{ scene.sound_effects }}
                                </span>
                                <span v-if="scene.music_mood" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-violet-50 dark:bg-violet-950/40 text-violet-800 dark:text-violet-200 font-medium">
                                    <Music class="w-3 h-3 text-violet-500" />
                                    <strong class="font-bold">BGM:</strong> {{ scene.music_mood }}
                                </span>
                            </div>
                        </div>

                        <!-- Inline Edit Mode -->
                        <div v-else class="space-y-3.5 p-4 rounded-2xl bg-violet-50/50 dark:bg-violet-950/20 border border-violet-200 dark:border-violet-800">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Judul Frame</label>
                                    <input
                                        v-model="editSceneForm.title"
                                        type="text"
                                        class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
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
                                        class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Deskripsi Aksi Karakter (Bahasa Indonesia)</label>
                                <textarea
                                    v-model="editSceneForm.action_description"
                                    rows="2"
                                    class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Prompt Lengkap AI Video (Bahasa Indonesia)</label>
                                <textarea
                                    v-model="editSceneForm.visual_prompt"
                                    rows="6"
                                    class="w-full px-3 py-2 rounded-xl text-xs font-mono border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                ></textarea>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Arah & Gerak Kamera</label>
                                    <input
                                        v-model="editSceneForm.camera_movement"
                                        type="text"
                                        class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Dialog / Narasi</label>
                                    <input
                                        v-model="editSceneForm.dialogue_or_narration"
                                        type="text"
                                        class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                    />
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    @click="cancelEditScene"
                                    class="px-3.5 py-1.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    @click="saveEditedScene(scene)"
                                    class="px-4 py-1.5 rounded-xl text-xs font-bold btn-primary inline-flex items-center gap-1.5 shadow-sm"
                                >
                                    <Save class="w-3.5 h-3.5" />
                                    <span>Simpan Perubahan</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Creation Studio Form Section (Simplified, Modern & 100% Hindu-Exclusive) -->
            <div class="card-glass rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-6">
                <!-- Form Section Header -->
                <div class="border-b border-slate-200/80 dark:border-slate-800 pb-4">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <h2 class="text-xl font-bold font-serif text-slate-900 dark:text-white">
                            Buat Naskah Prompt Baru
                        </h2>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Pilih durasi dan tema suci Hindu. Prompt visual per-frame dengan alur menyambung runtut akan disusun otomatis.
                    </p>
                </div>

                <form @submit.prevent="submitGenerate" class="space-y-6">
                    <!-- LANGKAH 1: Pilihan Durasi Video -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            1. Pilihan Durasi Video
                        </label>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <!-- 30 Detik -->
                            <div
                                @click="setDuration(30)"
                                class="p-4 rounded-2xl border-2 cursor-pointer transition flex items-center justify-between gap-3"
                                :class="form.target_duration === 30
                                    ? 'border-violet-600 bg-violet-50/70 dark:bg-violet-950/30 ring-1 ring-violet-500 shadow-sm'
                                    : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40'"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-extrabold text-lg shrink-0"
                                        :class="form.target_duration === 30 ? 'bg-amber-500 text-slate-950' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300'"
                                    >
                                        ⚡
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-extrabold text-sm text-slate-900 dark:text-white">30 Detik</h3>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-amber-500/20 text-amber-700 dark:text-amber-400">6 Frame</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Format kilat untuk TikTok, Reels, & Shorts</p>
                                    </div>
                                </div>
                                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0"
                                    :class="form.target_duration === 30 ? 'border-violet-600 bg-violet-600' : 'border-slate-300 dark:border-slate-600'"
                                >
                                    <div v-if="form.target_duration === 30" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                </div>
                            </div>

                            <!-- 1 Menit -->
                            <div
                                @click="setDuration(60)"
                                class="p-4 rounded-2xl border-2 cursor-pointer transition flex items-center justify-between gap-3"
                                :class="form.target_duration === 60
                                    ? 'border-violet-600 bg-violet-50/70 dark:bg-violet-950/30 ring-1 ring-violet-500 shadow-sm'
                                    : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40'"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-extrabold text-lg shrink-0"
                                        :class="form.target_duration === 60 ? 'bg-violet-600 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300'"
                                    >
                                        🎬
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-extrabold text-sm text-slate-900 dark:text-white">1 Menit (60 Detik)</h3>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-violet-500/20 text-violet-700 dark:text-violet-400">12 Frame</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Struktur cerita 3-babak komprehensif</p>
                                    </div>
                                </div>
                                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0"
                                    :class="form.target_duration === 60 ? 'border-violet-600 bg-violet-600' : 'border-slate-300 dark:border-slate-600'"
                                >
                                    <div v-if="form.target_duration === 60" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- LANGKAH 2: Tema Cerita Hindu -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                2. Tema Cerita Hindu (Otomatis Mengisi Judul & Tokoh)
                            </label>
                            <span class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold">Khusus Kisah & Nilai Dharma</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div
                                v-for="t in themes"
                                :key="t.id"
                                @click="onThemeChange(t.id)"
                                class="p-3.5 rounded-2xl border-2 cursor-pointer transition flex flex-col justify-between gap-2"
                                :class="form.theme === t.id
                                    ? 'border-violet-600 bg-violet-50/70 dark:bg-violet-950/30 shadow-sm'
                                    : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40'"
                            >
                                <div>
                                    <div class="flex items-start justify-between gap-2 mb-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-base shrink-0">
                                                {{ t.id === 'ramayana_mahabharata' ? '🏹' : (t.id === 'dharma_philosophy' ? '🪷' : (t.id === 'hindu_traditions' ? '🎋' : (t.id === 'deva_wisdom' ? '🐘' : '📜'))) }}
                                            </span>
                                            <h4 class="font-extrabold text-xs text-slate-900 dark:text-white leading-snug">{{ t.name }}</h4>
                                        </div>
                                        <div class="w-3.5 h-3.5 rounded-full border-2 flex items-center justify-center shrink-0 mt-0.5"
                                            :class="form.theme === t.id ? 'border-violet-600 bg-violet-600' : 'border-slate-300 dark:border-slate-600'"
                                        >
                                            <div v-if="form.theme === t.id" class="w-1 h-1 rounded-full bg-white"></div>
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-normal line-clamp-2 pl-6">{{ t.description }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Custom Topic Input if Custom selected -->
                        <div v-if="form.theme === 'custom' || form.theme === 'custom_hindu'" class="pt-2">
                            <input
                                v-model="form.custom_theme_title"
                                type="text"
                                placeholder="Tuliskan topik / judul cerita Hindu pilihan Anda..."
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-medium"
                            />
                        </div>
                    </div>

                    <!-- LANGKAH 3: Gaya Visual & Rasio Layar (Kompak & Rapi) -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 pt-1">
                        <!-- Gaya Visual -->
                        <div class="lg:col-span-2 space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                3. Gaya Visual Animasi
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <button
                                    v-for="style in visualStyles"
                                    :key="style.id"
                                    type="button"
                                    @click="form.visual_style = style.id"
                                    class="py-2.5 px-2 rounded-xl border text-xs font-bold transition text-center truncate"
                                    :class="form.visual_style === style.id
                                        ? 'border-violet-600 bg-violet-50/80 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300'
                                        : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
                                >
                                    {{ style.badge }}
                                </button>
                            </div>
                        </div>

                        <!-- Rasio Layar -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                Rasio Layar
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    @click="form.aspect_ratio = '9:16'"
                                    class="py-2.5 px-2 rounded-xl border text-xs font-bold transition text-center"
                                    :class="form.aspect_ratio === '9:16'
                                        ? 'border-violet-600 bg-violet-50/80 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300'
                                        : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
                                >
                                    9:16
                                </button>
                                <button
                                    type="button"
                                    @click="form.aspect_ratio = '16:9'"
                                    class="py-2.5 px-2 rounded-xl border text-xs font-bold transition text-center"
                                    :class="form.aspect_ratio === '16:9'
                                        ? 'border-violet-600 bg-violet-50/80 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300'
                                        : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
                                >
                                    16:9
                                </button>
                                <button
                                    type="button"
                                    @click="form.aspect_ratio = '1:1'"
                                    class="py-2.5 px-2 rounded-xl border text-xs font-bold transition text-center"
                                    :class="form.aspect_ratio === '1:1'
                                        ? 'border-violet-600 bg-violet-50/80 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300'
                                        : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
                                >
                                    1:1
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- KUSTOMISASI DETAIL (OPSIONAL) -->
                    <div class="pt-2 border-t border-slate-200/80 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showAdvancedOptions = !showAdvancedOptions"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-violet-600 dark:hover:text-violet-400 transition"
                        >
                            <Sliders class="w-3.5 h-3.5" />
                            <span>{{ showAdvancedOptions ? 'Sembunyikan Pengaturan Detail' : 'Sesuaikan Judul & Karakter Tertentu (Opsional)' }}</span>
                            <ChevronUp v-if="showAdvancedOptions" class="w-3.5 h-3.5" />
                            <ChevronDown v-else class="w-3.5 h-3.5" />
                        </button>

                        <div v-if="showAdvancedOptions" class="mt-3 p-4 rounded-2xl bg-slate-50/60 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Judul Cerita Kartun
                                </label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    placeholder="Tuliskan judul kartun..."
                                    class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Karakter Utama (Visual Anchor Konsisten)
                                </label>
                                <textarea
                                    v-model="form.character_description"
                                    rows="2"
                                    placeholder="Deskripsi karakter utama..."
                                    class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs"
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Sinopsis / Alur Cerita
                                </label>
                                <textarea
                                    v-model="form.story_premise"
                                    rows="2"
                                    placeholder="Alur ringkas cerita..."
                                    class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs"
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Model Target AI Video
                                </label>
                                <select
                                    v-model="form.target_engine"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-semibold"
                                >
                                    <option v-for="eng in targetEngines" :key="eng.id" :value="eng.id">
                                        {{ eng.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Generate Utama -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="isGenerating || form.processing"
                            class="w-full py-3.5 px-6 rounded-2xl font-bold text-sm btn-primary flex items-center justify-center gap-2 cursor-pointer shadow-lg disabled:opacity-50"
                        >
                            <RefreshCw v-if="isGenerating || form.processing" class="w-4 h-4 animate-spin" />
                            <Wand2 v-else class="w-4 h-4 text-amber-300" />
                            <span>
                                {{ isGenerating || form.processing
                                    ? `Menyusun Naskah ${form.target_duration} Detik (${form.target_duration === 30 ? '6' : '12'} Frame)...`
                                    : `✨ Hasilkan Prompt Video Kartun Hindu (${form.target_duration} Detik • ${form.target_duration === 30 ? '6 Frame' : '12 Frame'})`
                                }}
                            </span>
                        </button>
                        <p class="text-center text-[11px] text-slate-400 mt-2">
                            Alur adegan dari Frame 1 sampai {{ form.target_duration === 30 ? '6' : '12' }} dijamin menyambung mulus dengan dialog dan prompt visual AI.
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
