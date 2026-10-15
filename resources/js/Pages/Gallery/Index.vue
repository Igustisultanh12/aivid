<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import Modal from '@/Components/Modal.vue';
import {
    Compass,
    Search,
    Play,
    ShieldAlert,
    Eye,
    Filter,
    Sparkles
} from 'lucide-vue-next';
import type { Video, Topic } from '@/types';

const props = defineProps<{
    videos: {
        data: Video[];
        links: any[];
        total: number;
    };
    topics: Topic[];
    filters: {
        search?: string;
        topic_id?: string;
    };
}>();

const search = ref(props.filters.search || '');
const topicId = ref(props.filters.topic_id || '');

const reportModalOpen = ref(false);
const reportingVideo = ref<Video | null>(null);

const reportForm = useForm({
    reason: 'Ketidaksesuaian Sumber Agama',
    notes: '',
});

let searchTimeout: any = null;
const applyFilter = () => {
    router.get(
        '/gallery',
        {
            search: search.value || undefined,
            topic_id: topicId.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

watch(search, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilter, 400);
});

watch(topicId, () => {
    applyFilter();
});

const openReport = (video: Video) => {
    reportingVideo.value = video;
    reportModalOpen.value = true;
};

const submitReport = () => {
    if (!reportingVideo.value) return;
    reportForm.post(`/gallery/${reportingVideo.value.id}/report`, {
        onSuccess: () => {
            reportModalOpen.value = false;
            reportForm.reset();
        }
    });
};
</script>

<template>
    <div class="min-h-screen flex flex-col bg-[#FAF9F6] dark:bg-[#0B0F19] text-slate-800 dark:text-slate-100 transition-colors">
        <Head title="Galeri Komunitas - Video Edukasi Filosofi Hindu 30 Detik" />

        <Navbar />

        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
            <!-- Header Banner -->
            <div class="text-center max-w-2xl mx-auto space-y-3">
                <span class="text-xs font-bold uppercase tracking-wider text-orange-600 dark:text-orange-400 font-serif">
                    Karya Pilihan Terkurasi
                </span>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white font-serif">
                    Galeri Komunitas Ruang Dharma
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    Koleksi video edukasi 30 detik yang diterbitkan oleh pengguna untuk berbagi kebijaksanaan filosofi Hindu dan keindahan tradisi Bali.
                </p>
            </div>

            <!-- Search and Filter Bar -->
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <Search class="w-4 h-4 absolute left-3 top-3 text-slate-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari video di galeri komunitas..."
                        class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                    />
                </div>

                <select
                    v-model="topicId"
                    class="w-full sm:w-64 px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                >
                    <option value="">Semua Topik</option>
                    <option v-for="t in topics" :key="t.id" :value="t.id">
                        {{ t.title }}
                    </option>
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
                                <Compass class="w-8 h-8 text-orange-400" />
                            </div>

                            <div class="absolute top-3 left-3 px-2 py-0.5 rounded-full bg-slate-900/80 text-[10px] font-bold text-amber-300 backdrop-blur-md">
                                30s
                            </div>

                            <Link
                                :href="`/videos/${v.id}`"
                                class="absolute inset-0 flex items-center justify-center bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity"
                            >
                                <div class="w-12 h-12 rounded-full bg-violet-600 text-white flex items-center justify-center shadow-lg">
                                    <Play class="w-6 h-6 fill-current ml-0.5" />
                                </div>
                            </Link>
                        </div>

                        <!-- Info -->
                        <div class="p-5">
                            <span class="text-[10px] font-bold text-orange-600 dark:text-orange-400 uppercase tracking-wider">
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

                    <!-- Footer Details -->
                    <div class="px-5 pb-5 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
                        <span class="flex items-center gap-1 font-mono text-[11px]">
                            <Eye class="w-3.5 h-3.5" />
                            {{ v.views_count }} tayang
                        </span>

                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="openReport(v)"
                                class="text-slate-400 hover:text-rose-500 transition text-[11px]"
                                title="Laporkan konten jika melanggar nilai tradisi"
                            >
                                Laporkan
                            </button>

                            <Link
                                :href="`/videos/${v.id}`"
                                class="font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1"
                            >
                                Tonton &rarr;
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="p-12 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-center space-y-4">
                <Compass class="w-12 h-12 text-slate-400 mx-auto" />
                <h3 class="text-base font-bold text-slate-900 dark:text-white font-serif">
                    Belum Ada Video Publik Sesuai Kriteria
                </h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    Jadilah yang pertama membagikan video edukasi 30 detik karya Anda ke Galeri Komunitas.
                </p>
                <Link
                    href="/studio"
                    class="btn-primary px-6 py-2.5 rounded-xl font-bold text-xs inline-flex items-center gap-2"
                >
                    Buat Video Sekarang
                </Link>
            </div>
        </main>

        <!-- REPORT CONTENT MODAL -->
        <Modal :show="reportModalOpen" title="Laporkan Konten Video" @close="reportModalOpen = false">
            <form @submit.prevent="submitReport" class="space-y-4">
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                    Ruang Dharma sangat menjaga integritas keagamaan dan kehormatan tradisi Hindu. Jika video ini memuat kekeliruan kutipan suci atau narasi yang tidak pantas, silakan beritahu kami.
                </p>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Alasan Pelaporan
                    </label>
                    <select
                        v-model="reportForm.reason"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs"
                    >
                        <option value="Ketidaksesuaian Sumber Agama">Ketidaksesuaian Sumber / Rujukan Agama</option>
                        <option value="Kutipan Kitab Tidak Akurat">Kutipan Kitab / Mantra Tidak Akurat</option>
                        <option value="Simbol Visual Tidak Pantas">Penggunaan Simbol Visual Tidak Pantas</option>
                        <option value="Ujaran Kebencian / Sara">Ujaran Kebencian / Menyinggung SARA</option>
                        <option value="Lainnya">Alasan Lainnya</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Catatan Tambahan (Opsional)
                    </label>
                    <textarea
                        v-model="reportForm.notes"
                        rows="3"
                        placeholder="Jelaskan bagian adegan atau naskah yang perlu ditinjau ulang..."
                        class="w-full p-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs"
                    ></textarea>
                </div>

                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
                    <button
                        type="button"
                        @click="reportModalOpen = false"
                        class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-semibold"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="reportForm.processing"
                        class="px-5 py-2 rounded-xl bg-rose-600 text-white font-bold text-xs"
                    >
                        Kirim Laporan
                    </button>
                </div>
            </form>
        </Modal>

        <Footer />
    </div>
</template>
