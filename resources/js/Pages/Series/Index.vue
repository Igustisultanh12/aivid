<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Sparkles, Plus, Layers, ArrowRight, Video } from 'lucide-vue-next';

defineProps<{
    series: {
        data: any[];
        links: any[];
        total: number;
    };
}>();

const createModalOpen = ref(false);

const form = useForm({
    title: '',
    description: '',
    total_episodes: 3,
});

const submit = () => {
    form.post('/series', {
        onSuccess: () => {
            createModalOpen.value = false;
            form.reset();
        }
    });
};
</script>

<template>
    <AppLayout title="Seri Video Edukasi">
        <Head title="Seri Video Edukasi 30 Detik" />

        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold font-serif text-slate-900 dark:text-white">
                        Seri Video Edukasi
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Kumpulan beberapa episode video 30 detik untuk mengupas tuntas satu topik filosofi yang mendalam.
                    </p>
                </div>

                <button
                    type="button"
                    @click="createModalOpen = true"
                    class="btn-primary px-5 py-2.5 rounded-xl font-bold text-xs inline-flex items-center gap-2 shadow-md shadow-violet-600/20"
                >
                    <Plus class="w-4 h-4" />
                    <span>Buat Seri Baru</span>
                </button>
            </div>

            <!-- Series Grid -->
            <div v-if="series.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="s in series.data"
                    :key="s.id"
                    class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-xl transition-all flex flex-col justify-between group"
                >
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300">
                                {{ s.videos_count }} / {{ s.total_episodes }} Episode
                            </span>
                            <span class="text-xs font-mono font-bold text-amber-500">
                                30s per Episode
                            </span>
                        </div>

                        <h3 class="text-lg font-bold text-slate-900 dark:text-white font-serif group-hover:text-violet-600 transition">
                            {{ s.title }}
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 line-clamp-3 leading-relaxed">
                            {{ s.description || 'Seri edukasi bertahap untuk pemahaman yang komprehensif.' }}
                        </p>
                    </div>

                    <div class="pt-4 mt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <Link
                            :href="`/series/${s.id}`"
                            class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1"
                        >
                            <span>Buka Playlist Seri</span>
                            <ArrowRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>
            </div>

            <div v-else class="p-12 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-center space-y-4">
                <Layers class="w-12 h-12 text-slate-400 mx-auto" />
                <h3 class="text-base font-bold text-slate-900 dark:text-white font-serif">
                    Belum Memiliki Seri Video
                </h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    Gabungkan beberapa video 30 detik menjadi satu alur edukasi berkelanjutan (contoh: Seri Tri Hita Karana - 3 Episode).
                </p>
                <button
                    type="button"
                    @click="createModalOpen = true"
                    class="btn-primary px-6 py-2.5 rounded-xl font-bold text-xs inline-flex items-center gap-2"
                >
                    <Plus class="w-4 h-4" />
                    Buat Seri Pertama
                </button>
            </div>
        </div>

        <!-- CREATE SERIES MODAL -->
        <Modal :show="createModalOpen" title="Buat Seri Video Baru" @close="createModalOpen = false">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Judul Seri
                    </label>
                    <input
                        v-model="form.title"
                        type="text"
                        required
                        placeholder="Contoh: Seri Filosofi Tri Hita Karana"
                        class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-violet-500"
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Deskripsi Seri
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder="Ringkasan apa yang akan dipelajari dari seri ini..."
                        class="w-full p-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-violet-500"
                    ></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Rencana Jumlah Episode (Masing-masing 30 Detik)
                    </label>
                    <input
                        v-model.number="form.total_episodes"
                        type="number"
                        min="1"
                        max="10"
                        required
                        class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-violet-500"
                    />
                </div>

                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
                    <button
                        type="button"
                        @click="createModalOpen = false"
                        class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-semibold"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="btn-primary px-5 py-2 rounded-xl text-xs font-bold"
                    >
                        Simpan Seri Baru
                    </button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
