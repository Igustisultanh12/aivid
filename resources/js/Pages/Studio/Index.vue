<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import {
    Sparkles,
    Video,
    Wand2,
    CheckCircle2,
    AlertCircle,
    ArrowRight,
    ArrowLeft,
    Loader2,
    RefreshCw,
    Edit3,
    Film,
    Volume2,
    Ratio,
    ShieldAlert,
    X,
    Eye,
    Play,
    Info,
    RotateCcw
} from 'lucide-vue-next';
import type { Topic, Video as VideoType, StoryboardScene } from '@/types';

const props = defineProps<{
    topics: Topic[];
    selectedTopicId?: number | null;
    activeVideo?: VideoType | null;
    voices: Array<{ id: string; name: string; gender: string }>;
    visualStyles: Array<{ id: string; name: string; desc: string }>;
    userQuota: { total: number; used: number; remaining: number };
    geminiConfigured: boolean;
}>();

// Wizard state (Step 1 to 7)
const currentStep = ref(1);

const form = useForm({
    topic_id: props.selectedTopicId ?? null,
    custom_idea: '',
    target_audience: 'Anak Muda',
    tone: 'Inspiratif & Hangat',
    production_mode: 'illustration_motion', // illustration_motion, generative_video
    visual_style: 'modern_editorial',
    aspect_ratio: '9:16',
    voice_id: 'Pari',
    has_subtitles: true,
    review_script_first: false,
});

// Scene Editing Modal state
const editingScene = ref<StoryboardScene | null>(null);
const sceneModalOpen = ref(false);
const sceneEditForm = useForm({
    narration_text: '',
    onscreen_text: '',
    visual_prompt: '',
    camera_motion: 'zoom_in' as 'zoom_in' | 'zoom_out' | 'pan_left' | 'pan_right' | 'tilt_up',
});

// Polling interval for live progress tracking
let pollTimer: any = null;
const liveVideo = ref<VideoType | null>(props.activeVideo ?? null);

const startPolling = (videoId: string) => {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(async () => {
        try {
            const res = await fetch(`/api/videos/${videoId}/status`);
            if (res.ok) {
                const data = await res.json();
                if (liveVideo.value) {
                    liveVideo.value.status = data.status;
                    liveVideo.value.progress_percent = data.progress_percent;
                    liveVideo.value.current_stage_label = data.current_stage_label;
                    liveVideo.value.error_message = data.error_message;
                    liveVideo.value.duration_seconds = data.duration_seconds;

                    if (['completed', 'failed', 'cancelled', 'waiting_script_approval'].includes(data.status)) {
                        clearInterval(pollTimer);
                        // Refresh Inertia props to sync scenes and details
                        router.reload({ only: ['activeVideo'] });
                    }
                }
            }
        } catch (e) {
            // silent polling error
        }
    }, 3000);
};

onMounted(() => {
    if (props.activeVideo && !['completed', 'failed', 'cancelled'].includes(props.activeVideo.status)) {
        startPolling(props.activeVideo.id);
    }
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});

watch(() => props.activeVideo, (newVal) => {
    liveVideo.value = newVal ?? null;
    if (newVal && !['completed', 'failed', 'cancelled'].includes(newVal.status)) {
        startPolling(newVal.id);
    }
}, { deep: true });

const selectedTopic = computed(() => {
    return props.topics.find(t => t.id === form.topic_id);
});

const selectTopic = (id: number) => {
    form.topic_id = id;
    form.custom_idea = '';
};

const submitGenerate = () => {
    form.post('/studio/generate', {
        onSuccess: () => {
            currentStep.value = 1;
        }
    });
};

const openEditScene = (scene: StoryboardScene) => {
    editingScene.value = scene;
    sceneEditForm.narration_text = scene.narration_text;
    sceneEditForm.onscreen_text = scene.onscreen_text || '';
    sceneEditForm.visual_prompt = scene.visual_prompt;
    sceneEditForm.camera_motion = scene.camera_motion;
    sceneModalOpen.value = true;
};

const saveSceneEdit = () => {
    if (!liveVideo.value || !editingScene.value?.id) return;
    sceneEditForm.put(`/studio/${liveVideo.value.id}/scenes/${editingScene.value.id}`, {
        onSuccess: () => {
            sceneModalOpen.value = false;
        }
    });
};

const regenerateVisual = (scene: StoryboardScene) => {
    if (!liveVideo.value || !scene.id) return;
    router.post(`/studio/${liveVideo.value.id}/scenes/${scene.id}/regenerate-visual`, {}, {
        preserveScroll: true,
    });
};

const approveScriptAndRender = () => {
    if (!liveVideo.value) return;
    router.post(`/studio/${liveVideo.value.id}/approve-script`, {}, {
        onSuccess: () => {
            startPolling(liveVideo.value!.id);
        }
    });
};

const retryVideo = () => {
    if (!liveVideo.value) return;
    router.post(`/studio/${liveVideo.value.id}/retry`, {}, {
        onSuccess: () => {
            startPolling(liveVideo.value!.id);
        }
    });
};

const cancelVideo = () => {
    if (!liveVideo.value) return;
    router.post(`/studio/${liveVideo.value.id}/cancel`, {}, {
        onSuccess: () => {
            if (pollTimer) clearInterval(pollTimer);
        }
    });
};
</script>

<template>
    <AppLayout title="Studio AI">
        <Head title="Studio Pembuatan Video 30 Detik" />

        <div class="space-y-8">
            <!-- Warning if Gemini API Key not set -->
            <div
                v-if="!geminiConfigured"
                class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/50 border border-amber-300 dark:border-amber-800 text-amber-900 dark:text-amber-200 flex items-start gap-3 shadow-sm"
            >
                <ShieldAlert class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                <div class="flex-1 text-xs leading-relaxed">
                    <span class="font-bold">Perhatian: API Key Google Gemini belum dikonfigurasi.</span>
                    Aplikasi akan menggunakan naskah kurasi aman dan visual cadangan. Administrator dapat memasukkan API Key melalui
                    <Link href="/admin/settings/gemini" class="font-bold underline hover:text-amber-700 ml-1">
                        Pengaturan Integrasi AI Gemini
                    </Link>.
                </div>
            </div>

            <!-- ACTIVE / MONITORING PROGRESS SECTION -->
            <div
                v-if="liveVideo && liveVideo.status !== 'draft'"
                class="card-glass rounded-3xl p-6 sm:p-8 shadow-xl border border-violet-200 dark:border-violet-900/60 relative overflow-hidden"
            >
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 pb-6 border-b border-slate-200/80 dark:border-slate-800">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-violet-100 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300 uppercase tracking-wider">
                                {{ liveVideo.topic_name || 'Edukasi Hindu' }}
                            </span>
                            <span class="text-xs font-mono font-bold text-amber-500">
                                30 DETIK
                            </span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white mt-1 font-serif">
                            {{ liveVideo.title }}
                        </h2>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- If waiting approval -->
                        <button
                            v-if="liveVideo.status === 'waiting_script_approval'"
                            @click="approveScriptAndRender"
                            type="button"
                            class="btn-coral px-5 py-2.5 rounded-xl font-bold text-xs flex items-center gap-1.5 shadow-md shadow-orange-500/25"
                        >
                            <CheckCircle2 class="w-4 h-4" />
                            <span>Setujui Naskah & Lanjutkan Render</span>
                        </button>

                        <!-- If failed -->
                        <button
                            v-if="liveVideo.status === 'failed'"
                            @click="retryVideo"
                            type="button"
                            class="btn-primary px-4 py-2 rounded-xl font-bold text-xs flex items-center gap-1.5"
                        >
                            <RotateCcw class="w-4 h-4" />
                            <span>Coba Lagi Tahap Gagal</span>
                        </button>

                        <!-- If completed -->
                        <Link
                            v-if="liveVideo.status === 'completed'"
                            :href="`/videos/${liveVideo.id}`"
                            class="btn-primary px-5 py-2.5 rounded-xl font-bold text-xs flex items-center gap-1.5 shadow-md"
                        >
                            <Play class="w-4 h-4 fill-current" />
                            <span>Tonton Video Final</span>
                        </Link>

                        <!-- Cancel -->
                        <button
                            v-if="!['completed', 'failed', 'cancelled'].includes(liveVideo.status)"
                            @click="cancelVideo"
                            type="button"
                            class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition"
                        >
                            Batalkan
                        </button>
                    </div>
                </div>

                <!-- Progress Bar & Stage Indicator -->
                <div class="py-6 space-y-3">
                    <div class="flex items-center justify-between text-xs font-bold">
                        <span class="flex items-center gap-2 text-violet-700 dark:text-violet-300">
                            <Loader2 v-if="!['completed', 'failed', 'cancelled'].includes(liveVideo.status)" class="w-4 h-4 animate-spin text-violet-600" />
                            <CheckCircle2 v-else-if="liveVideo.status === 'completed'" class="w-4 h-4 text-emerald-500" />
                            <AlertCircle v-else class="w-4 h-4 text-rose-500" />
                            {{ liveVideo.current_stage_label }}
                        </span>
                        <span class="font-mono text-slate-500 text-sm">
                            {{ liveVideo.progress_percent }}%
                        </span>
                    </div>

                    <div class="w-full h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden shadow-inner">
                        <div
                            class="h-full bg-gradient-to-r from-violet-600 via-purple-600 to-amber-500 transition-all duration-500 rounded-full"
                            :style="{ width: `${liveVideo.progress_percent}%` }"
                        ></div>
                    </div>

                    <p v-if="liveVideo.error_message" class="text-xs text-rose-500 font-medium">
                        {{ liveVideo.error_message }}
                    </p>
                </div>

                <!-- Storyboard Scenes Review / Edit -->
                <div v-if="liveVideo.scenes && liveVideo.scenes.length > 0" class="pt-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 font-serif">
                            Adegan Storyboard 30 Detik ({{ liveVideo.scenes.length }} Adegan)
                        </h3>
                        <span class="text-xs text-slate-500">
                            Klik 'Ubah Naskah' untuk menyempurnakan narasi
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="scene in liveVideo.scenes"
                            :key="scene.id"
                            class="p-4 rounded-2xl bg-white/70 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800 flex flex-col justify-between space-y-3"
                        >
                            <div>
                                <div class="flex items-center justify-between text-[11px] font-bold text-slate-500 mb-1.5">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        Adegan {{ scene.scene_order }}
                                    </span>
                                    <span class="font-mono text-amber-600 dark:text-amber-400">
                                        {{ scene.start_time }}s - {{ scene.end_time }}s ({{ scene.duration }}s)
                                    </span>
                                </div>

                                <p class="text-xs font-semibold text-slate-900 dark:text-white leading-relaxed">
                                    "{{ scene.narration_text }}"
                                </p>

                                <div v-if="scene.onscreen_text" class="mt-2 text-[11px] text-violet-600 dark:text-violet-400 font-medium">
                                    Teks Layar: <span class="font-bold">{{ scene.onscreen_text }}</span>
                                </div>

                                <p class="text-[10px] text-slate-500 mt-1 italic line-clamp-2">
                                    Prompt: {{ scene.visual_prompt }}
                                </p>
                            </div>

                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                                <button
                                    type="button"
                                    @click="openEditScene(scene)"
                                    class="text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1 font-semibold"
                                >
                                    <Edit3 class="w-3.5 h-3.5" />
                                    <span>Ubah Naskah</span>
                                </button>

                                <button
                                    type="button"
                                    @click="regenerateVisual(scene)"
                                    class="text-slate-500 hover:text-orange-500 flex items-center gap-1 font-medium text-[11px]"
                                    title="Buat ulang gambar adegan ini"
                                >
                                    <RefreshCw class="w-3 h-3" />
                                    <span>Visual Baru</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- NEW VIDEO CREATION WIZARD (7 Steps) -->
            <div class="card-glass rounded-3xl p-6 sm:p-10 shadow-xl border border-slate-200/80 dark:border-slate-800">
                <!-- Wizard Progress Indicator -->
                <div class="mb-8">
                    <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">
                        <span>Langkah {{ currentStep }} dari 7</span>
                        <span class="text-violet-600 dark:text-violet-400 font-serif">
                            {{
                                currentStep === 1 ? '1. Topik atau Ide' :
                                currentStep === 2 ? '2. Sasaran & Nada Bicara' :
                                currentStep === 3 ? '3. Mode Produksi Media' :
                                currentStep === 4 ? '4. Gaya Visual & Suara' :
                                currentStep === 5 ? '5. Subtitle & Rasio' :
                                currentStep === 6 ? '6. Estimasi Penggunaan' :
                                '7. Konfirmasi & Mulai'
                            }}
                        </span>
                    </div>

                    <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div
                            class="h-full bg-gradient-to-r from-violet-600 to-amber-500 transition-all duration-300"
                            :style="{ width: `${(currentStep / 7) * 100}%` }"
                        ></div>
                    </div>
                </div>

                <!-- STEP 1: Topik atau Ide -->
                <div v-if="currentStep === 1" class="space-y-6">
                    <div>
                        <h2 class="text-xl font-bold font-serif text-slate-900 dark:text-white">
                            Pilih Topik Filosofi Hindu atau Tuliskan Ide Anda
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Pilih satu topik terverifikasi di bawah atau ketikkan pertanyaan/ide filosofis Anda sendiri.
                        </p>
                    </div>

                    <!-- Topics Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 max-h-[360px] overflow-y-auto p-1">
                        <div
                            v-for="t in topics"
                            :key="t.id"
                            @click="selectTopic(t.id)"
                            :class="[
                                'p-4 rounded-2xl border cursor-pointer transition-all flex flex-col justify-between text-left',
                                form.topic_id === t.id
                                    ? 'bg-violet-50/90 dark:bg-violet-950/40 border-violet-600 dark:border-violet-500 shadow-md ring-2 ring-violet-500/20'
                                    : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-violet-300'
                            ]"
                        >
                            <div>
                                <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">
                                    {{ t.category }}
                                </span>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white mt-0.5 font-serif line-clamp-1">
                                    {{ t.title }}
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                                    {{ t.description }}
                                </p>
                            </div>
                            <span class="text-[10px] text-slate-400 font-medium mt-3">
                                {{ t.cultural_context }}
                            </span>
                        </div>
                    </div>

                    <!-- Custom Idea Input -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                        <label for="custom_idea" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Atau Masukkan Ide / Pertanyaan Khusus
                        </label>
                        <textarea
                            id="custom_idea"
                            v-model="form.custom_idea"
                            rows="2"
                            placeholder="Contoh: Mengapa pada hari Saraswati kita dilarang membaca atau menulis buku yang diupacarai?"
                            class="w-full p-3.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                        ></textarea>
                    </div>
                </div>

                <!-- STEP 2: Target Penonton & Gaya Penyampaian -->
                <div v-else-if="currentStep === 2" class="space-y-6">
                    <div>
                        <h2 class="text-xl font-bold font-serif text-slate-900 dark:text-white">
                            Target Penonton & Gaya Penyampaian
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Sesuaikan sudut pandang naskah agar pesan filosofis relevan dan mudah diserap.
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                            Sasaran Penonton
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <button
                                v-for="aud in ['Anak Muda', 'Remaja', 'Umum']"
                                :key="aud"
                                type="button"
                                @click="form.target_audience = aud"
                                :class="[
                                    'p-4 rounded-2xl border text-center font-bold text-sm transition',
                                    form.target_audience === aud
                                        ? 'bg-violet-600 text-white border-violet-600 shadow-md'
                                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-violet-300 text-slate-800 dark:text-slate-200'
                                ]"
                            >
                                {{ aud }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                            Gaya / Nada Penyampaian
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <button
                                v-for="t in ['Inspiratif & Hangat', 'Edukatif & Santai', 'Mendalam & Kontemplatif']"
                                :key="t"
                                type="button"
                                @click="form.tone = t"
                                :class="[
                                    'p-4 rounded-2xl border text-center font-bold text-sm transition',
                                    form.tone === t
                                        ? 'bg-orange-600 text-white border-orange-600 shadow-md'
                                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-orange-300 text-slate-800 dark:text-slate-200'
                                ]"
                            >
                                {{ t }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Mode Produksi -->
                <div v-else-if="currentStep === 3" class="space-y-6">
                    <div>
                        <h2 class="text-xl font-bold font-serif text-slate-900 dark:text-white">
                            Pilih Mode Produksi Media
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Pilih bagaimana visual video 30 detik Anda disusun dan dirender.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Mode A -->
                        <div
                            @click="form.production_mode = 'illustration_motion'"
                            :class="[
                                'p-6 rounded-3xl border-2 cursor-pointer transition-all flex flex-col justify-between space-y-4',
                                form.production_mode === 'illustration_motion'
                                    ? 'bg-violet-50/80 dark:bg-violet-950/40 border-violet-600 shadow-xl'
                                    : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-violet-300'
                            ]"
                        >
                            <div class="space-y-2">
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-violet-100 dark:bg-violet-900/60 text-violet-700 dark:text-violet-300 inline-block">
                                    Rekomendasi Utama
                                </span>
                                <h3 class="text-lg font-bold font-serif text-slate-900 dark:text-white">
                                    Ilustrasi Bergerak (Motion Graphic)
                                </h3>
                                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                    Menggunakan gambar beresolusi tinggi yang dihasilkan melalui Gemini/Imagen API, digerakkan dengan dinamika kamera Ken Burns (pan/zoom), narasi suara jernih, subtitle kontras, dan transisi halus via FFmpeg.
                                </p>
                            </div>

                            <div class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                                <CheckCircle2 class="w-4 h-4" />
                                <span>Didukung penuh & siap pakai di semua akun</span>
                            </div>
                        </div>

                        <!-- Mode B -->
                        <div
                            @click="form.production_mode = 'generative_video'"
                            :class="[
                                'p-6 rounded-3xl border-2 cursor-pointer transition-all flex flex-col justify-between space-y-4',
                                form.production_mode === 'generative_video'
                                    ? 'bg-orange-50/80 dark:bg-orange-950/40 border-orange-600 shadow-xl'
                                    : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-orange-300'
                            ]"
                        >
                            <div class="space-y-2">
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-orange-100 dark:bg-orange-900/60 text-orange-700 dark:text-orange-300 inline-block">
                                    Fitur Khusus
                                </span>
                                <h3 class="text-lg font-bold font-serif text-slate-900 dark:text-white">
                                    Video Generatif (Google Veo)
                                </h3>
                                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                    Memanfaatkan model video generative Google (Veo 2.0 / Omni Flash). Memerlukan proyek Google AI Studio dengan akses model video aktif dan kuota komputasi video.
                                </p>
                            </div>

                            <div class="text-[11px] font-semibold text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                                <Info class="w-4 h-4" />
                                <span>Otomatis beralih ke Ilustrasi Bergerak jika akses Veo belum aktif</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 4: Gaya Visual & Suara -->
                <div v-else-if="currentStep === 4" class="space-y-6">
                    <div>
                        <h2 class="text-xl font-bold font-serif text-slate-900 dark:text-white">
                            Gaya Visual & Pilihan Suara Narator
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Pilih estetika gambar seni dan karakter suara narator bahasa Indonesia.
                        </p>
                    </div>

                    <!-- Visual Styles -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                            Gaya Visual Ilustrasi
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div
                                v-for="s in visualStyles"
                                :key="s.id"
                                @click="form.visual_style = s.id"
                                :class="[
                                    'p-4 rounded-2xl border cursor-pointer transition text-left',
                                    form.visual_style === s.id
                                        ? 'bg-violet-50 dark:bg-violet-950/40 border-violet-600 dark:border-violet-500 shadow-sm'
                                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800'
                                ]"
                            >
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white font-serif">
                                    {{ s.name }}
                                </h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    {{ s.desc }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Voice Selection -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                            Karakter Suara Narasi (TTS)
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div
                                v-for="v in voices"
                                :key="v.id"
                                @click="form.voice_id = v.id"
                                :class="[
                                    'p-4 rounded-2xl border cursor-pointer transition flex items-center justify-between',
                                    form.voice_id === v.id
                                        ? 'bg-orange-50 dark:bg-orange-950/40 border-orange-600 dark:border-orange-500 shadow-sm'
                                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800'
                                ]"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-950 text-orange-600 flex items-center justify-center">
                                        <Volume2 class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-900 dark:text-white">
                                            {{ v.name }}
                                        </h4>
                                        <span class="text-[10px] text-slate-500">Bahasa Indonesia Resmi</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 5: Subtitle, Musik, dan Rasio -->
                <div v-else-if="currentStep === 5" class="space-y-6">
                    <div>
                        <h2 class="text-xl font-bold font-serif text-slate-900 dark:text-white">
                            Format Rasio Layar & Opsi Subtitle
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Sesuaikan target platform media sosial yang ingin Anda tuju.
                        </p>
                    </div>

                    <!-- Aspect Ratio -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                            Rasio Aspek Video
                        </label>
                        <div class="grid grid-cols-3 gap-4">
                            <button
                                v-for="r in [
                                    { id: '9:16', name: '9:16 Vertikal', desc: 'TikTok, Reels, Shorts (1080x1920)' },
                                    { id: '16:9', name: '16:9 Lanskap', desc: 'YouTube & Layar Desktop (1920x1080)' },
                                    { id: '1:1', name: '1:1 Persegi', desc: 'Feed Instagram (1080x1080)' }
                                ]"
                                :key="r.id"
                                type="button"
                                @click="form.aspect_ratio = r.id as '9:16' | '16:9' | '1:1'"
                                :class="[
                                    'p-4 rounded-2xl border text-center transition flex flex-col items-center justify-center space-y-1',
                                    form.aspect_ratio === r.id
                                        ? 'bg-violet-600 text-white border-violet-600 shadow-md'
                                        : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200'
                                ]"
                            >
                                <span class="text-sm font-bold">{{ r.name }}</span>
                                <span class="text-[10px] opacity-80">{{ r.desc }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Options Toggles -->
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 space-y-3">
                        <label class="flex items-center justify-between cursor-pointer">
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white block">Sertakan Subtitle Dinamis</span>
                                <span class="text-[11px] text-slate-500">Membakar subtitle kontras tinggi di area aman video + menghasilkan file .srt dan .vtt</span>
                            </div>
                            <input
                                v-model="form.has_subtitles"
                                type="checkbox"
                                class="w-5 h-5 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                            />
                        </label>

                        <div class="border-t border-slate-200 dark:border-slate-800 pt-3">
                            <label class="flex items-center justify-between cursor-pointer">
                                <div>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white block">Tinjau Naskah Sebelum Produksi Media</span>
                                    <span class="text-[11px] text-slate-500">Jeda setelah naskah & storyboard dibuat agar Anda dapat menyunting kalimat adegan sebelum rendering</span>
                                </div>
                                <input
                                    v-model="form.review_script_first"
                                    type="checkbox"
                                    class="w-5 h-5 rounded border-slate-300 text-orange-600 focus:ring-orange-500"
                                />
                            </label>
                        </div>
                    </div>
                </div>

                <!-- STEP 6: Tinjau Estimasi Penggunaan -->
                <div v-else-if="currentStep === 6" class="space-y-6">
                    <div>
                        <h2 class="text-xl font-bold font-serif text-slate-900 dark:text-white">
                            Tinjau Estimasi Kuota & Sumber Rujukan
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Periksa rincian konfigurasi sebelum memulai siklus produksi video.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 space-y-3 text-xs">
                            <h4 class="font-bold text-slate-900 dark:text-white uppercase tracking-wider text-[11px]">
                                Ringkasan Spesifikasi Video
                            </h4>
                            <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-800/60">
                                <span class="text-slate-500">Topik / Ide:</span>
                                <span class="font-bold text-right max-w-[200px] truncate">{{ selectedTopic?.title || form.custom_idea || 'Ide Khusus' }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-800/60">
                                <span class="text-slate-500">Durasi Target:</span>
                                <span class="font-bold font-mono text-amber-600">30.00 detik (+/- 0.5s)</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-800/60">
                                <span class="text-slate-500">Mode Produksi:</span>
                                <span class="font-bold">{{ form.production_mode === 'illustration_motion' ? 'Ilustrasi Bergerak (FFmpeg)' : 'Video Generatif (Veo)' }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-800/60">
                                <span class="text-slate-500">Rasio Aspek:</span>
                                <span class="font-bold font-mono">{{ form.aspect_ratio }}</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-slate-500">Mode Alur:</span>
                                <span class="font-bold text-orange-600">{{ form.review_script_first ? 'Tinjau Naskah Dulu' : 'Produksi Otomatis' }}</span>
                            </div>
                        </div>

                        <div class="p-5 rounded-2xl bg-violet-50/70 dark:bg-violet-950/40 border border-violet-200 dark:border-violet-900 space-y-3 text-xs">
                            <h4 class="font-bold text-violet-900 dark:text-violet-200 uppercase tracking-wider text-[11px]">
                                Estimasi Pemakaian Kuota
                            </h4>
                            <div class="flex justify-between py-1 border-b border-violet-200/60 dark:border-violet-800/60">
                                <span class="text-slate-600 dark:text-slate-400">Kuota Akun Anda:</span>
                                <span class="font-bold font-mono">{{ userQuota.remaining }} tersisa dari {{ userQuota.total }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-violet-200/60 dark:border-violet-800/60">
                                <span class="text-slate-600 dark:text-slate-400">Potongan Kuota:</span>
                                <span class="font-bold font-mono text-violet-700 dark:text-violet-300">1 Video</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-violet-200/60 dark:border-violet-800/60">
                                <span class="text-slate-600 dark:text-slate-400">Estimasi Token AI:</span>
                                <span class="font-bold font-mono">~1.200 Token Teks + 5 Visual</span>
                            </div>
                            <p class="text-[11px] text-slate-500 pt-1 leading-snug">
                                Kuota aplikasi hanya dipotong jika video berhasil diproduksi secara utuh.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- STEP 7: Mulai Generate -->
                <div v-else-if="currentStep === 7" class="space-y-6 text-center py-6">
                    <div class="w-16 h-16 rounded-3xl bg-gradient-to-tr from-violet-600 via-purple-600 to-amber-500 p-0.5 mx-auto shadow-xl">
                        <div class="w-full h-full bg-white dark:bg-slate-900 rounded-[22px] flex items-center justify-center">
                            <Wand2 class="w-8 h-8 text-violet-600 dark:text-violet-400" />
                        </div>
                    </div>

                    <div class="max-w-md mx-auto space-y-2">
                        <h2 class="text-2xl font-bold font-serif text-slate-900 dark:text-white">
                            Siap Memulai Produksi Video
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                            Kecerdasan buatan Google Gemini akan menyusun naskah 30 detik berdasarkan sumber rujukan Hindu yang sahih dan memproses aset visual serta audio secara otomatis.
                        </p>
                    </div>

                    <div class="pt-4">
                        <button
                            type="button"
                            @click="submitGenerate"
                            :disabled="form.processing"
                            class="btn-primary px-8 py-4 rounded-2xl font-bold text-base inline-flex items-center gap-2.5 shadow-2xl shadow-violet-600/30 disabled:opacity-50"
                        >
                            <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin" />
                            <template v-else>
                                <Sparkles class="w-5 h-5 text-amber-300" />
                                <span>Mulai Produksi Video 30 Detik</span>
                            </template>
                        </button>
                    </div>
                </div>

                <!-- Wizard Navigation Buttons -->
                <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <button
                        v-if="currentStep > 1"
                        type="button"
                        @click="currentStep--"
                        class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition flex items-center gap-1.5"
                    >
                        <ArrowLeft class="w-4 h-4" />
                        Kembali
                    </button>
                    <div v-else></div>

                    <button
                        v-if="currentStep < 7"
                        type="button"
                        @click="currentStep++"
                        :disabled="currentStep === 1 && !form.topic_id && !form.custom_idea"
                        class="btn-primary px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-1.5 disabled:opacity-50"
                    >
                        Lanjutkan
                        <ArrowRight class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- SCENE EDIT MODAL -->
        <Modal :show="sceneModalOpen" title="Sunting Naskah & Teks Adegan" @close="sceneModalOpen = false">
            <form @submit.prevent="saveSceneEdit" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Teks Narasi Suara
                    </label>
                    <textarea
                        v-model="sceneEditForm.narration_text"
                        rows="3"
                        required
                        class="w-full p-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-violet-500"
                    ></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Teks Singkat Pada Layar (Onscreen)
                    </label>
                    <input
                        v-model="sceneEditForm.onscreen_text"
                        type="text"
                        placeholder="Contoh: Rahasia Selembar Janur"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-violet-500"
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Prompt Visual Gambar
                    </label>
                    <textarea
                        v-model="sceneEditForm.visual_prompt"
                        rows="2"
                        required
                        class="w-full p-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-violet-500"
                    ></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Gerakan Kamera
                    </label>
                    <select
                        v-model="sceneEditForm.camera_motion"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-violet-500"
                    >
                        <option value="zoom_in">Perbesar Perlahan (Zoom In)</option>
                        <option value="zoom_out">Perkecil Perlahan (Zoom Out)</option>
                        <option value="pan_left">Geser ke Kiri (Pan Left)</option>
                        <option value="pan_right">Geser ke Kanan (Pan Right)</option>
                        <option value="tilt_up">Sorot ke Atas (Tilt Up)</option>
                    </select>
                </div>

                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
                    <button
                        type="button"
                        @click="sceneModalOpen = false"
                        class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-semibold"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="sceneEditForm.processing"
                        class="btn-primary px-5 py-2 rounded-xl text-xs font-bold"
                    >
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
