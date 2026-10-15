<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Search,
    Filter,
    Play,
    Download,
    Trash2,
    Video,
    Plus,
    Clock,
    Sparkles,
    Calendar
} from 'lucide-vue-next';
import type { Video as VideoType, Topic } from '@/types';

const props = defineProps<{
    videos: {
        data: VideoType[];
        links: any[];
        total: number;
    };
    topics: Topic[];
    filters: {
        search?: string;
        topic_id?: string;
        status?: string;
        sort?: string;
    };
}>();

const search = ref(props.filters.search || '');
const topicId = ref(props.filters.topic_id || '');
const status = ref(props.filters.status || '');
const sort = ref(props.filters.sort || 'newest');

let searchDebounce: any = null;

const applyFilters = () => {
    router.get(
        '/library',
        {
            search: search.value || undefined,
            topic_id: topicId.value || undefined,
            status: status.value || undefined,
            sort: sort.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

watch(search, () => {
    if (searchDebounce) clearTimeout(searchDebounce);
    searchDebounce = setTimeout(applyFilters, 400);
});

watch([topicId, status, sort], () => {
    applyFilters();
});

const deleteVideo = (id: string, title: string) => {
    if (confirm(`Apakah Anda yakin ingin menghapus video "${title}"? Berkas media akan dihapus permanen.`)) {
        router.delete(`/videos/${id}`);
    }
};
</script>

<template>
    <AppLayout title="Pustaka Video">
        <Head title="Pustaka Video Edukasi Saya" />

        <div class="space-y-6">
            <!-- Header bar with Create CTA -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold font-serif text-slate-900 dark:text-white">
                        Pustaka Video Saya
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Daftar seluruh karya video edukasi 30 detik yang telah Anda rancang dan hasilkan.
                    </p>
                </div>

                <Link
                    href="/studio"
                    class="btn-primary px-5 py-2.5 rounded-xl font-bold text-xs inline-flex items-center gap-2 shadow-md shadow-violet-600/20 self-start sm:self-auto"
                >
                    <Plus class="w-4 h-4" />
                    <span>Buat Video Baru</span>
                </Link>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search -->
                <div class="relative">
                    <Search class="w-4 h-4 absolute left-3 top-3 text-slate-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari judul atau topik..."
                        class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                    />
                </div>

                <!-- Topic Filter -->
                <select
                    v-model="topicId"
                    class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                >
                    <option value="">Semua Topik</option>
                    <option v-for="t in topics" :key="t.id" :value="t.id">
                        {{ t.title }}
                    </option>
                </select>

                <!-- Status Filter -->
                <select
                    v-model="status"
                    class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                >
                    <option value="">Semua Status</option>
                    <option value="completed">Selesai</option>
                    <option value="queued">Dalam Antrean</option>
                    <option value="rendering">Sedang Render</option>
                    <option value="waiting_script_approval">Menunggu Persetujuan</option>
                    <option value="failed">Gagal</option>
                </select>

                <!-- Sort -->
                <select
                    v-model="sort"
                    class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                >
                    <option value="newest">Terbaru Dibuat</option>
                    <option value="oldest">Terlama Dibuat</option>
                    <option value="duration">Durasi Terpanjang</option>
                </select>
            </div>

            <!-- Video Grid -->
            <div v-if="videos.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="v in videos.data"
                    :key="v.id"
                    class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm hover:shadow-xl transition-all flex flex-col justify-between group"
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

                        <!-- Details -->
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

                    <!-- Footer actions -->
                    <div class="px-5 pb-5 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                        <span :class="[
                            'px-2.5 py-0.5 rounded-full font-bold text-[10px]',
                            v.status === 'completed' ? 'bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300' :
                            v.status === 'failed' ? 'bg-rose-100 text-rose-700' :
                            'bg-violet-100 text-violet-700'
                        ]">
                            {{ v.status === 'completed' ? 'Selesai' : v.status }}
                        </span>

                        <div class="flex items-center gap-2">
                            <Link
                                v-if="v.status === 'completed'"
                                :href="`/videos/${v.id}/download`"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                title="Unduh Video MP4"
                            >
                                <Download class="w-4 h-4" />
                            </Link>

                            <Link
                                :href="`/videos/${v.id}`"
                                class="font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1"
                            >
                                Tonton &rarr;
                            </Link>

                            <button
                                type="button"
                                @click="deleteVideo(v.id, v.title)"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition"
                                title="Hapus Video"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="p-12 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-center space-y-4">
                <Video class="w-12 h-12 text-slate-400 mx-auto" />
                <h3 class="text-base font-bold text-slate-900 dark:text-white font-serif">
                    Tidak Ditemukan Video Sesuai Kriteria
                </h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto">
                    Coba atur ulang kata kunci pencarian atau buat video edukasi baru menggunakan topik favorit Anda di Studio Ruang Dharma.
                </p>
                <Link
                    href="/studio"
                    class="btn-primary px-6 py-2.5 rounded-xl font-bold text-xs inline-flex items-center gap-2"
                >
                    <Plus class="w-4 h-4" />
                    Buat Video Baru
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
