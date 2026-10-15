<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Calendar, Plus, Clock, Trash2, Globe, Sparkles } from 'lucide-vue-next';
import type { Topic } from '@/types';

defineProps<{
    schedules: {
        data: any[];
        links: any[];
        total: number;
    };
    topics: Topic[];
}>();

const createModalOpen = ref(false);

const form = useForm({
    topic_id: null as number | null,
    custom_idea: '',
    target_audience: 'Anak Muda',
    tone: 'Inspiratif & Hangat',
    production_mode: 'illustration_motion',
    visual_style: 'modern_editorial',
    aspect_ratio: '9:16',
    scheduled_at: '',
    timezone: 'Asia/Makassar',
});

const submit = () => {
    form.post('/schedule', {
        onSuccess: () => {
            createModalOpen.value = false;
            form.reset();
        }
    });
};

const deleteSchedule = (id: number) => {
    if (confirm('Batalkan jadwal produksi video ini?')) {
        router.delete(`/schedule/${id}`);
    }
};
</script>

<template>
    <AppLayout title="Jadwal Produksi Otomatis">
        <Head title="Jadwal Produksi Video Otomatis" />

        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold font-serif text-slate-900 dark:text-white">
                        Jadwal Produksi Video
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Atur jadwal otomatis agar video edukasi 30 detik diproduksi menjelang hari raya atau waktu tertentu.
                    </p>
                </div>

                <button
                    type="button"
                    @click="createModalOpen = true"
                    class="btn-primary px-5 py-2.5 rounded-xl font-bold text-xs inline-flex items-center gap-2 shadow-md shadow-violet-600/20"
                >
                    <Plus class="w-4 h-4" />
                    <span>Jadwalkan Video Baru</span>
                </button>
            </div>

            <div v-if="schedules.data.length > 0" class="space-y-3">
                <div
                    v-for="item in schedules.data"
                    :key="item.id"
                    class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                >
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-violet-100 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 uppercase tracking-wider">
                                {{ item.topic?.title || item.topic_name }}
                            </span>
                            <span class="text-xs font-mono font-semibold text-slate-500 flex items-center gap-1">
                                <Clock class="w-3.5 h-3.5" />
                                {{ new Date(item.scheduled_at).toLocaleString('id-ID') }} ({{ item.timezone }})
                            </span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white font-serif">
                            {{ item.custom_idea || item.topic?.title }}
                        </h3>
                        <p class="text-xs text-slate-500">
                            Mode: {{ item.production_mode === 'illustration_motion' ? 'Ilustrasi Bergerak' : 'Video Generatif' }} • Rasio {{ item.aspect_ratio }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <span :class="[
                            'px-3 py-1 rounded-full text-xs font-bold capitalize',
                            item.status === 'completed' ? 'bg-emerald-100 text-emerald-700' :
                            item.status === 'running' ? 'bg-amber-100 text-amber-700' :
                            'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'
                        ]">
                            {{ item.status === 'scheduled' ? 'Terjadwal' : item.status }}
                        </span>

                        <button
                            type="button"
                            @click="deleteSchedule(item.id)"
                            class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition"
                            title="Hapus Jadwal"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="p-12 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-center space-y-4">
                <Calendar class="w-12 h-12 text-slate-400 mx-auto" />
                <h3 class="text-base font-bold text-slate-900 dark:text-white font-serif">
                    Belum Ada Jadwal Produksi
                </h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    Jadwalkan pembuatan video untuk menyambut Hari Raya Galungan, Kuningan, Saraswati, atau Nyepi secara otomatis.
                </p>
                <button
                    type="button"
                    @click="createModalOpen = true"
                    class="btn-primary px-6 py-2.5 rounded-xl font-bold text-xs inline-flex items-center gap-2"
                >
                    <Plus class="w-4 h-4" />
                    Buat Jadwal Pertama
                </button>
            </div>
        </div>

        <!-- MODAL JADWAL -->
        <Modal :show="createModalOpen" title="Jadwalkan Produksi Video 30 Detik" @close="createModalOpen = false">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Pilih Topik
                    </label>
                    <select
                        v-model="form.topic_id"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs"
                    >
                        <option :value="null">-- Gunakan Ide Khusus Saja --</option>
                        <option v-for="t in topics" :key="t.id" :value="t.id">
                            {{ t.title }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Ide / Pertanyaan Khusus
                    </label>
                    <textarea
                        v-model="form.custom_idea"
                        rows="2"
                        placeholder="Tuliskan gagasan spesifik jika tidak memilih topik..."
                        class="w-full p-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs"
                    ></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Waktu Eksekusi
                        </label>
                        <input
                            v-model="form.scheduled_at"
                            type="datetime-local"
                            required
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Zona Waktu
                        </label>
                        <select
                            v-model="form.timezone"
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs"
                        >
                            <option value="Asia/Makassar">WITA (Bali / Makassar)</option>
                            <option value="Asia/Jakarta">WIB (Jakarta / Jawa)</option>
                            <option value="Asia/Jayapura">WIT (Jayapura / Timur)</option>
                        </select>
                    </div>
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
                        Simpan Jadwal
                    </button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
