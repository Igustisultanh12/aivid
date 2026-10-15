<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import VideoPlayer from '@/Components/VideoPlayer.vue';
import {
    Download,
    Bookmark,
    Share2,
    BookOpen,
    Layers,
    FileText,
    HelpCircle,
    CheckCircle2,
    AlertCircle,
    ArrowLeft,
    Trash2,
    Globe,
    Lock
} from 'lucide-vue-next';
import type { Video as VideoType } from '@/types';

const props = defineProps<{
    video: VideoType;
    isBookmarked: boolean;
    isOwner: boolean;
    streamUrl: string;
}>();

const activeTab = ref<'transcript' | 'sources' | 'scenes' | 'quiz'>('transcript');
const bookmarked = ref(props.isBookmarked);

// Quiz interaction state
const userAnswers = ref<Record<number, number>>({});
const quizSubmitted = ref(false);
const quizResults = ref<any>(null);
const quizLoading = ref(false);

const toggleBookmark = async () => {
    try {
        const res = await fetch(`/videos/${props.video.id}/bookmark`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                'Content-Type': 'application/json',
            }
        });
        if (res.ok) {
            const data = await res.json();
            bookmarked.value = data.bookmarked;
        }
    } catch (e) {
        // silent error
    }
};

const togglePublicStatus = () => {
    router.post(`/videos/${props.video.id}/toggle-public`);
};

const deleteVideo = () => {
    if (confirm('Apakah Anda yakin ingin menghapus video ini secara permanen?')) {
        router.delete(`/videos/${props.video.id}`);
    }
};

const selectOption = (quizId: number, optionIdx: number) => {
    if (quizSubmitted.value) return;
    userAnswers.value[quizId] = optionIdx;
};

const submitQuizAnswers = async () => {
    quizLoading.value = true;
    try {
        const res = await fetch(`/videos/${props.video.id}/quiz/submit`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ answers: userAnswers.value }),
        });
        if (res.ok) {
            quizResults.value = await res.json();
            quizSubmitted.value = true;
        }
    } finally {
        quizLoading.value = false;
    }
};

const resetQuiz = () => {
    userAnswers.value = {};
    quizSubmitted.value = false;
    quizResults.value = null;
};
</script>

<template>
    <AppLayout :title="video.title">
        <Head :title="`${video.title} - Video Edukasi 30 Detik`" />

        <div class="space-y-8 max-w-5xl mx-auto">
            <!-- Back & Action Bar -->
            <div class="flex items-center justify-between">
                <Link
                    href="/library"
                    class="text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white flex items-center gap-1.5 transition"
                >
                    <ArrowLeft class="w-4 h-4" />
                    <span>Kembali ke Pustaka</span>
                </Link>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="toggleBookmark"
                        :class="[
                            'p-2 rounded-xl border text-xs font-semibold flex items-center gap-1.5 transition',
                            bookmarked
                                ? 'bg-amber-50 dark:bg-amber-950/60 border-amber-400 text-amber-700 dark:text-amber-300'
                                : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300'
                        ]"
                        title="Simpan ke Bookmark"
                    >
                        <Bookmark :class="['w-4 h-4', bookmarked ? 'fill-current text-amber-500' : '']" />
                        <span class="hidden sm:inline">{{ bookmarked ? 'Tersimpan' : 'Bookmark' }}</span>
                    </button>

                    <button
                        v-if="isOwner"
                        type="button"
                        @click="togglePublicStatus"
                        :class="[
                            'px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 border transition',
                            video.is_public
                                ? 'bg-emerald-50 dark:bg-emerald-950/50 border-emerald-300 text-emerald-700 dark:text-emerald-300'
                                : 'bg-slate-100 dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300'
                        ]"
                    >
                        <Globe v-if="video.is_public" class="w-3.5 h-3.5" />
                        <Lock v-else class="w-3.5 h-3.5" />
                        <span>{{ video.is_public ? 'Publik (Galeri)' : 'Pribadi' }}</span>
                    </button>

                    <button
                        v-if="isOwner"
                        type="button"
                        @click="deleteVideo"
                        class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-rose-50 dark:hover:bg-rose-950/30 text-slate-400 hover:text-rose-600 transition"
                        title="Hapus Video Ini"
                    >
                        <Trash2 class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Video Player Centerpiece -->
            <div class="card-glass rounded-3xl p-4 sm:p-6 shadow-2xl border border-slate-200/80 dark:border-slate-800">
                <VideoPlayer
                    :src="streamUrl"
                    :poster="video.thumbnail_path ? `/storage/${video.thumbnail_path}` : undefined"
                    :subtitles-url="`/videos/${video.id}/download/vtt`"
                    :aspect-ratio="video.aspect_ratio"
                />

                <!-- Title & Meta Header -->
                <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-violet-100 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300 uppercase tracking-wider">
                                {{ video.topic?.title || video.topic_name || 'Edukasi Hindu' }}
                            </span>
                            <span class="text-xs font-mono font-bold text-amber-500">
                                30.00 DETIK
                            </span>
                            <span v-if="video.metadata?.is_demo" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800">
                                CONTOH RESMI
                            </span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white font-serif">
                            {{ video.title }}
                        </h1>
                    </div>

                    <!-- Download Dropdown / Buttons -->
                    <div class="flex items-center gap-2">
                        <a
                            :href="`/videos/${video.id}/download`"
                            class="btn-primary px-4 py-2.5 rounded-xl font-bold text-xs inline-flex items-center gap-1.5 shadow-md shadow-violet-600/20"
                        >
                            <Download class="w-4 h-4" />
                            <span>Unduh Video (MP4)</span>
                        </a>

                        <a
                            :href="`/videos/${video.id}/download/srt`"
                            class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 transition"
                            title="Unduh Subtitle SRT"
                        >
                            SRT
                        </a>

                        <a
                            :href="`/videos/${video.id}/download/vtt`"
                            class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 transition"
                            title="Unduh Subtitle VTT"
                        >
                            VTT
                        </a>

                        <a
                            :href="`/videos/${video.id}/download/thumbnail`"
                            class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 transition"
                            title="Unduh Thumbnail"
                        >
                            Cover
                        </a>
                    </div>
                </div>

                <p v-if="video.summary_text" class="text-xs text-slate-600 dark:text-slate-400 mt-3 leading-relaxed">
                    {{ video.summary_text }}
                </p>
            </div>

            <!-- Tabs: Transkrip, Sumber Rujukan, Storyboard, Kuis -->
            <div class="card-glass rounded-3xl p-6 shadow-xl border border-slate-200/80 dark:border-slate-800 space-y-6">
                <!-- Tab Buttons -->
                <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3 overflow-x-auto">
                    <button
                        type="button"
                        @click="activeTab = 'transcript'"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap',
                            activeTab === 'transcript'
                                ? 'bg-violet-600 text-white shadow-sm'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        <FileText class="w-4 h-4" />
                        <span>Transkrip Narasi</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'sources'"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap',
                            activeTab === 'sources'
                                ? 'bg-violet-600 text-white shadow-sm'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        <BookOpen class="w-4 h-4" />
                        <span>Sumber Rujukan Hindu</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'scenes'"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap',
                            activeTab === 'scenes'
                                ? 'bg-violet-600 text-white shadow-sm'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        <Layers class="w-4 h-4" />
                        <span>Storyboard Adegan</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'quiz'"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 whitespace-nowrap',
                            activeTab === 'quiz'
                                ? 'bg-violet-600 text-white shadow-sm'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        <HelpCircle class="w-4 h-4" />
                        <span>Kuis Pemahaman</span>
                    </button>
                </div>

                <!-- TAB 1: Transkrip Narasi -->
                <div v-if="activeTab === 'transcript'" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 font-serif">
                            Transkrip Audio Lengkap
                        </h3>
                        <a
                            :href="`/videos/${video.id}/download/transcript`"
                            class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1"
                        >
                            <Download class="w-3.5 h-3.5" />
                            Unduh Teks (.txt)
                        </a>
                    </div>

                    <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 text-sm leading-relaxed text-slate-800 dark:text-slate-200 whitespace-pre-line font-serif">
                        {{ video.transcript_text || 'Transkrip narasi disesuaikan dengan adegan storyboard 30 detik.' }}
                    </div>
                </div>

                <!-- TAB 2: Sumber Rujukan Hindu -->
                <div v-else-if="activeTab === 'sources'" class="space-y-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 font-serif">
                        Landasan Rujukan Faktual & Kitab
                    </h3>

                    <div class="space-y-3">
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800">
                            <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Rujukan Otoritatif</span>
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white mt-0.5">
                                Pedoman Resmi Parisada Hindu Dharma Indonesia (PHDI) & Lontar Sundarigama
                            </h4>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                                Narasi ini merujuk pada tafsir ajaran Panca Yadnya dan sastra klasik Bali mengenai pemaknaan sarana upakara dan budi pekerti luhur.
                            </p>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800">
                            <span class="text-[10px] font-bold text-violet-600 uppercase tracking-wider">Kaidah Filosofis</span>
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white mt-0.5">
                                Bhagavad Gita & Sarasamuccaya
                            </h4>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                                Mengedepankan nilai Lascarya (ketulusan tanpa pamrih) serta hukum Karma Phala sebagai pedoman hidup generasi muda.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: Storyboard Adegan -->
                <div v-else-if="activeTab === 'scenes'" class="space-y-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 font-serif">
                        Rincian Adegan 30 Detik
                    </h3>

                    <div class="space-y-3">
                        <div
                            v-for="s in video.scenes"
                            :key="s.id"
                            class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-start justify-between gap-3 text-xs"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded font-bold bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-[10px]">
                                        Adegan {{ s.scene_order }}
                                    </span>
                                    <span class="font-bold text-violet-600 dark:text-violet-400 font-mono">
                                        {{ s.start_time }}s - {{ s.end_time }}s ({{ s.duration }}s)
                                    </span>
                                </div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                    "{{ s.narration_text }}"
                                </p>
                                <p v-if="s.onscreen_text" class="text-slate-500">
                                    Teks Layar: <strong class="text-amber-600">{{ s.onscreen_text }}</strong>
                                </p>
                            </div>
                            <span class="px-2 py-1 rounded-md bg-white dark:bg-slate-800 text-slate-500 font-mono text-[10px] whitespace-nowrap">
                                {{ s.camera_motion }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: Kuis Pemahaman -->
                <div v-else-if="activeTab === 'quiz'" class="space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 font-serif">
                                Uji Pemahaman Anda
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Jawab pertanyaan singkat berdasarkan video edukasi 30 detik ini.
                            </p>
                        </div>

                        <button
                            v-if="quizSubmitted"
                            type="button"
                            @click="resetQuiz"
                            class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline"
                        >
                            Ulangi Kuis
                        </button>
                    </div>

                    <!-- Score Result banner -->
                    <div
                        v-if="quizSubmitted && quizResults"
                        class="p-4 rounded-2xl bg-gradient-to-r from-violet-600 to-amber-500 text-white flex items-center justify-between"
                    >
                        <div>
                            <span class="text-xs uppercase tracking-wider opacity-90 font-bold">Hasil Kuis Anda</span>
                            <h4 class="text-xl font-extrabold font-serif">
                                Skor: {{ quizResults.score }} dari {{ quizResults.total }} ({{ quizResults.percentage }}%)
                            </h4>
                        </div>
                        <CheckCircle2 class="w-8 h-8 text-amber-200" />
                    </div>

                    <!-- Questions List -->
                    <div v-if="video.quizzes && video.quizzes.length > 0" class="space-y-6">
                        <div
                            v-for="(q, qIdx) in video.quizzes"
                            :key="q.id"
                            class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 space-y-3"
                        >
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                                {{ qIdx + 1 }}. {{ q.question }}
                            </h4>

                            <div class="space-y-2">
                                <div
                                    v-for="(opt, optIdx) in q.options"
                                    :key="optIdx"
                                    @click="selectOption(q.id, optIdx)"
                                    :class="[
                                        'p-3 rounded-xl border text-xs cursor-pointer transition flex items-center justify-between',
                                        userAnswers[q.id] === optIdx
                                            ? 'bg-violet-50 dark:bg-violet-950/60 border-violet-600 font-semibold text-violet-900 dark:text-violet-200'
                                            : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:border-violet-300',
                                        quizSubmitted && optIdx === q.correct_option_index
                                            ? '!bg-emerald-50 dark:!bg-emerald-950/60 !border-emerald-500 !text-emerald-900 dark:!text-emerald-200 font-bold'
                                            : '',
                                        quizSubmitted && userAnswers[q.id] === optIdx && optIdx !== q.correct_option_index
                                            ? '!bg-rose-50 dark:!bg-rose-950/60 !border-rose-500 !text-rose-900 font-medium'
                                            : ''
                                    ]"
                                >
                                    <span>{{ opt }}</span>
                                    <span v-if="quizSubmitted && optIdx === q.correct_option_index" class="text-emerald-600 font-bold">
                                        Benar
                                    </span>
                                </div>
                            </div>

                            <!-- Explanation after submit -->
                            <div
                                v-if="quizSubmitted"
                                class="p-3 rounded-xl bg-violet-100/50 dark:bg-violet-950/30 text-xs text-violet-900 dark:text-violet-200 leading-relaxed"
                            >
                                <span class="font-bold">Penjelasan:</span> {{ q.explanation }}
                            </div>
                        </div>

                        <button
                            v-if="!quizSubmitted"
                            type="button"
                            @click="submitQuizAnswers"
                            :disabled="quizLoading || Object.keys(userAnswers).length === 0"
                            class="btn-primary w-full py-3 rounded-xl font-bold text-xs flex items-center justify-center gap-2 shadow-md disabled:opacity-50"
                        >
                            <span>Periksa Jawaban Kuis</span>
                        </button>
                    </div>

                    <div v-else class="text-center py-6 text-xs text-slate-500">
                        Kuis untuk materi video ini sedang disiapkan.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
