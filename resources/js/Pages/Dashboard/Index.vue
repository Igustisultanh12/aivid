<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Video,
    Plus,
    Clock,
    Sparkles,
    CheckCircle2,
    AlertCircle,
    ArrowRight,
    Play,
    Loader2,
    Calendar,
    Layers,
    Compass
} from 'lucide-vue-next';
import type { Video as VideoType, Topic } from '@/types';

defineProps<{
    stats: {
        total_videos: number;
        completed_videos: number;
        quota_remaining: number;
        quota_total: number;
        quota_used: number;
    };
    recentVideos: VideoType[];
    activeVideos: VideoType[];
    recommendedTopics: Topic[];
}>();
</script>

<template>
    <AppLayout title="Dashboard">
        <Head title="Dashboard Edukasi" />

        <div class="space-y-8">
            <!-- Header Banner -->
            <div class="p-8 rounded-3xl bg-gradient-to-r from-violet-900 via-purple-900 to-slate-900 text-white shadow-xl relative overflow-hidden flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <!-- Background accent circles -->
                <div class="absolute -right-10 -bottom-10 w-64 h-64 rounded-full bg-violet-600/20 blur-3xl pointer-events-none"></div>

                <div class="space-y-2 max-w-xl z-10">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-xs font-semibold text-purple-200 backdrop-blur-md">
                        <Sparkles class="w-3.5 h-3.5 text-amber-300" />
                        <span>Ruang Studio Dharma</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold font-serif">
                        Selamat Datang di Ruang Dharma
                    </h1>
                    <p class="text-xs sm:text-sm text-purple-200 leading-relaxed">
                        Ubah ketertarikan filosofi Hindu menjadi cerita video edukasi 30 detik yang memikat generasi muda.
                    </p>
                </div>

                <div class="z-10">
                    <Link
                        href="/studio"
                        class="btn-coral px-6 py-3.5 rounded-2xl font-bold text-sm inline-flex items-center gap-2 shadow-lg shadow-orange-600/30 whitespace-nowrap"
                    >
                        <Plus class="w-5 h-5" />
                        <span>Buat Video Sekarang</span>
                    </Link>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0">
                        <Video class="w-6 h-6" />
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Video</span>
                        <p class="text-2xl font-extrabold text-slate-900 dark:text-white mt-0.5">
                            {{ stats.total_videos }}
                        </p>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <CheckCircle2 class="w-6 h-6" />
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Selesai 30s</span>
                        <p class="text-2xl font-extrabold text-slate-900 dark:text-white mt-0.5">
                            {{ stats.completed_videos }}
                        </p>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                        <Sparkles class="w-6 h-6" />
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Sisa Kuota</span>
                        <p class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 mt-0.5">
                            {{ stats.quota_remaining }} <span class="text-xs font-normal text-slate-500">/ {{ stats.quota_total }}</span>
                        </p>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-orange-100 dark:bg-orange-950/60 text-orange-600 dark:text-orange-400 flex items-center justify-center shrink-0">
                        <Clock class="w-6 h-6" />
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Sedang Proses</span>
                        <p class="text-2xl font-extrabold text-slate-900 dark:text-white mt-0.5">
                            {{ activeVideos.length }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Active Processing Jobs (Real-time tracking) -->
            <div v-if="activeVideos.length > 0" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold font-serif text-slate-900 dark:text-white flex items-center gap-2">
                        <Loader2 class="w-5 h-5 text-violet-600 animate-spin" />
                        <span>Proses Pembuatan Video Berjalan</span>
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="item in activeVideos"
                        :key="item.id"
                        class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-violet-300 dark:border-violet-800/80 shadow-md flex flex-col justify-between"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-violet-600 dark:text-violet-400 uppercase tracking-wider">
                                    {{ item.topic_name || 'Ide Khusus' }}
                                </span>
                                <span class="text-xs font-bold font-mono text-slate-500">
                                    {{ item.progress_percent }}%
                                </span>
                            </div>

                            <h3 class="text-base font-bold text-slate-900 dark:text-white truncate">
                                {{ item.title }}
                            </h3>

                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-violet-500 animate-pulse"></span>
                                {{ item.current_stage_label }}
                            </p>

                            <!-- Real Progress Bar -->
                            <div class="w-full h-2.5 bg-slate-100 dark:bg-slate-800 rounded-full mt-4 overflow-hidden">
                                <div
                                    class="h-full bg-gradient-to-r from-violet-600 to-amber-500 transition-all duration-300 rounded-full"
                                    :style="{ width: `${item.progress_percent}%` }"
                                ></div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <Link
                                :href="`/studio?video_id=${item.id}`"
                                class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1"
                            >
                                Buka di Studio &rarr;
                            </Link>

                            <Link
                                :href="`/studio/${item.id}/cancel`"
                                method="post"
                                as="button"
                                class="text-xs font-medium text-rose-500 hover:underline"
                            >
                                Batalkan
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Videos -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold font-serif text-slate-900 dark:text-white">
                        Video Terbaru Anda
                    </h2>
                    <Link href="/library" class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">
                        Lihat Semua Video &rarr;
                    </Link>
                </div>

                <div v-if="recentVideos.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="v in recentVideos"
                        :key="v.id"
                        class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm hover:shadow-lg transition group flex flex-col justify-between"
                    >
                        <div>
                            <!-- Thumbnail -->
                            <div class="aspect-[16/9] bg-slate-950 relative overflow-hidden flex items-center justify-center">
                                <img
                                    v-if="v.thumbnail_path"
                                    :src="`/storage/${v.thumbnail_path}`"
                                    :alt="v.title"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                />
                                <div v-else class="text-slate-500 flex flex-col items-center justify-center">
                                    <Video class="w-8 h-8 text-violet-400" />
                                </div>

                                <div class="absolute top-3 left-3 px-2 py-0.5 rounded-full bg-slate-900/80 text-[10px] font-bold text-amber-300 backdrop-blur-md">
                                    30s
                                </div>

                                <Link
                                    v-if="v.status === 'completed'"
                                    :href="`/videos/${v.id}`"
                                    class="absolute inset-0 flex items-center justify-center bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <div class="w-12 h-12 rounded-full bg-violet-600 text-white flex items-center justify-center shadow-lg">
                                        <Play class="w-6 h-6 fill-current ml-0.5" />
                                    </div>
                                </Link>
                            </div>

                            <div class="p-5">
                                <span class="text-[10px] font-bold text-violet-600 dark:text-violet-400 uppercase tracking-wider">
                                    {{ v.topic?.title || v.topic_name || 'Edukasi Hindu' }}
                                </span>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white mt-1 line-clamp-1 font-serif">
                                    {{ v.title }}
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                                    {{ v.summary_text || 'Video edukasi filosofi Hindu 30 detik.' }}
                                </p>
                            </div>
                        </div>

                        <div class="px-5 pb-5 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                            <span :class="[
                                'px-2 py-0.5 rounded-full font-bold text-[10px]',
                                v.status === 'completed' ? 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300' :
                                v.status === 'failed' ? 'bg-rose-100 text-rose-700' :
                                'bg-violet-100 text-violet-700'
                            ]">
                                {{ v.status === 'completed' ? 'Selesai' : v.status }}
                            </span>

                            <Link
                                :href="`/videos/${v.id}`"
                                class="font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1"
                            >
                                Tonton &rarr;
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-else class="p-10 rounded-3xl bg-white dark:bg-slate-900 border border-dashed border-slate-300 dark:border-slate-800 text-center space-y-3">
                    <Video class="w-10 h-10 text-slate-400 mx-auto" />
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">
                        Belum Ada Video yang Dihasilkan
                    </h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">
                        Mulai eksplorasi pertama Anda dengan memilih topik yang menarik di Studio Ruang Dharma.
                    </p>
                    <Link
                        href="/studio"
                        class="btn-primary px-5 py-2.5 rounded-xl font-bold text-xs inline-flex items-center gap-1.5"
                    >
                        <Plus class="w-4 h-4" />
                        Buat Video Pertamamu
                    </Link>
                </div>
            </div>

            <!-- Recommended Topics -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold font-serif text-slate-900 dark:text-white">
                        Rekomendasi Topik Pilihan
                    </h2>
                    <Link href="/studio" class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">
                        Buka Semua Topik &rarr;
                    </Link>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Link
                        v-for="t in recommendedTopics"
                        :key="t.id"
                        :href="`/studio?topic_id=${t.id}`"
                        class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-violet-500 hover:shadow-md transition group"
                    >
                        <span class="text-[10px] font-bold text-orange-600 dark:text-orange-400 uppercase tracking-wider">
                            {{ t.category }}
                        </span>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white mt-1 group-hover:text-violet-600 dark:group-hover:text-violet-400 transition font-serif line-clamp-1">
                            {{ t.title }}
                        </h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-snug">
                            {{ t.description }}
                        </p>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
