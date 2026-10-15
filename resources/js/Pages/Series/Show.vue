<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeft, Play, Plus, Video } from 'lucide-vue-next';

defineProps<{
    series: any;
}>();
</script>

<template>
    <AppLayout :title="series.title">
        <Head :title="`${series.title} - Seri Video 30 Detik`" />

        <div class="space-y-6 max-w-5xl mx-auto">
            <Link
                href="/series"
                class="text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white flex items-center gap-1.5 transition"
            >
                <ArrowLeft class="w-4 h-4" />
                <span>Kembali ke Daftar Seri</span>
            </Link>

            <div class="p-8 rounded-3xl bg-gradient-to-r from-violet-900 to-purple-900 text-white shadow-xl space-y-3">
                <span class="text-xs uppercase tracking-widest font-bold text-amber-300">
                    Seri Edukasi Hindu • {{ series.videos?.length || 0 }} / {{ series.total_episodes }} Episode
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold font-serif">
                    {{ series.title }}
                </h1>
                <p class="text-xs sm:text-sm text-purple-200 leading-relaxed max-w-2xl">
                    {{ series.description || 'Kumpulan episode video edukasi 30 detik.' }}
                </p>
            </div>

            <!-- Episodes Playlist -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold font-serif text-slate-900 dark:text-white">
                        Daftar Episode Seri
                    </h2>

                    <Link
                        :href="`/studio`"
                        class="btn-primary px-4 py-2 rounded-xl text-xs font-bold inline-flex items-center gap-1.5"
                    >
                        <Plus class="w-4 h-4" />
                        Tambah Episode
                    </Link>
                </div>

                <div v-if="series.videos && series.videos.length > 0" class="space-y-3">
                    <div
                        v-for="(ep, idx) in series.videos"
                        :key="ep.id"
                        class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 flex items-center justify-between gap-4 shadow-sm"
                    >
                        <div class="flex items-center gap-4">
                            <span class="w-8 h-8 rounded-full bg-violet-100 dark:bg-violet-950 text-violet-600 font-bold text-xs flex items-center justify-center shrink-0">
                                {{ idx + 1 }}
                            </span>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white font-serif">
                                    {{ ep.title }}
                                </h4>
                                <span class="text-[11px] text-slate-500 font-mono">
                                    Durasi 30.00s
                                </span>
                            </div>
                        </div>

                        <Link
                            :href="`/videos/${ep.id}`"
                            class="btn-primary px-4 py-2 rounded-xl text-xs font-bold inline-flex items-center gap-1.5"
                        >
                            <Play class="w-3.5 h-3.5 fill-current" />
                            <span>Putar</span>
                        </Link>
                    </div>
                </div>

                <div v-else class="text-center py-10 text-xs text-slate-500">
                    Belum ada episode yang ditambahkan ke dalam seri ini.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
