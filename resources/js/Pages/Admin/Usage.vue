<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    BarChart3,
    Cpu,
    Coins,
    Timer,
    CheckCircle2,
    XCircle,
    Filter,
    Layers,
    FileText,
    Image,
    Volume2,
    Video
} from 'lucide-vue-next';

interface UsageLog {
    id: number;
    request_type: string;
    model_used: string;
    prompt_tokens: number;
    candidates_tokens: number;
    total_tokens: number;
    estimated_cost_usd: number;
    response_time_ms: number;
    status: 'success' | 'failed';
    error_message: string | null;
    created_at: string;
    user?: {
        name: string;
        email: string;
    };
    video?: {
        id: string;
        title: string;
    };
}

const props = defineProps<{
    logs: {
        data: UsageLog[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        total: number;
    };
    stats: {
        total_requests: number;
        total_prompt_tokens: number;
        total_candidate_tokens: number;
        total_cost_usd: number;
        avg_response_time_ms: number;
    };
    filters: {
        type?: string;
    };
}>();

const selectedType = ref(props.filters.type || '');

watch(selectedType, (val) => {
    router.get(
        '/admin/usage',
        { type: val || undefined },
        { preserveState: true, replace: true }
    );
});
</script>

<template>
    <AdminLayout title="Statistik & Biaya AI">
        <Head title="Penggunaan AI - Admin Ruang Dharma" />

        <div class="space-y-6">
            <!-- Header bar -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                            <BarChart3 class="w-6 h-6 text-amber-500" />
                            Statistik & Analisis Biaya Google Gemini
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Pantau metrik konsumsi token API, estimasi pengeluaran USD, dan latensi pemanggilan model secara real-time.
                        </p>
                    </div>

                    <!-- Filter by Request Type -->
                    <div class="flex items-center gap-2">
                        <Filter class="w-4 h-4 text-slate-400" />
                        <select
                            v-model="selectedType"
                            class="px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium focus:ring-2 focus:ring-violet-500 outline-hidden"
                        >
                            <option value="">Semua Jenis Request</option>
                            <option value="script_generation">Naskah (Script)</option>
                            <option value="image_generation">Gambar (Imagen 3)</option>
                            <option value="tts_narration">Suara (TTS)</option>
                            <option value="video_clip">Video Klip</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Request</span>
                        <div class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center text-violet-600">
                            <Cpu class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-2">
                        {{ stats.total_requests.toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Panggilan API tercatat</div>
                </div>

                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Token</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600">
                            <Layers class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-2">
                        {{ (stats.total_prompt_tokens + stats.total_candidate_tokens).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">
                        In: {{ stats.total_prompt_tokens.toLocaleString() }} | Out: {{ stats.total_candidate_tokens.toLocaleString() }}
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Estimasi Biaya</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600">
                            <Coins class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-2">
                        ${{ stats.total_cost_usd.toFixed(4) }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Akumulasi estimasi USD</div>
                </div>

                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Rata-rata Latensi</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-900/30 flex items-center justify-center text-sky-600">
                            <Timer class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-2">
                        {{ stats.avg_response_time_ms }} ms
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">Kecepatan respon Gemini</div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                <th class="py-4 px-6">Waktu</th>
                                <th class="py-4 px-6">Tipe Panggilan</th>
                                <th class="py-4 px-6">Model Gemini</th>
                                <th class="py-4 px-6">Token (In / Out)</th>
                                <th class="py-4 px-6">Biaya (USD)</th>
                                <th class="py-4 px-6">Latensi</th>
                                <th class="py-4 px-6 text-center">Status</th>
                                <th class="py-4 px-6">Pengguna</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-sm">
                            <tr
                                v-for="log in logs.data"
                                :key="log.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                            >
                                <td class="py-4 px-6 text-xs text-slate-500">
                                    {{ new Date(log.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) }}
                                    <div class="text-[10px] text-slate-400">{{ new Date(log.created_at).toLocaleDateString('id-ID') }}</div>
                                </td>
                                <td class="py-4 px-6 font-medium text-slate-800 dark:text-slate-200">
                                    <span class="inline-flex items-center gap-1.5 text-xs">
                                        <FileText v-if="log.request_type === 'script_generation'" class="w-3.5 h-3.5 text-violet-500" />
                                        <Image v-else-if="log.request_type === 'image_generation'" class="w-3.5 h-3.5 text-amber-500" />
                                        <Volume2 v-else-if="log.request_type === 'tts_narration'" class="w-3.5 h-3.5 text-emerald-500" />
                                        <Video v-else class="w-3.5 h-3.5 text-sky-500" />
                                        {{ log.request_type }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-xs font-mono text-slate-600 dark:text-slate-400">
                                    {{ log.model_used }}
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-600 dark:text-slate-400">
                                    {{ log.prompt_tokens }} / {{ log.candidates_tokens }}
                                </td>
                                <td class="py-4 px-6 text-xs font-medium text-slate-800 dark:text-slate-200">
                                    ${{ Number(log.estimated_cost_usd).toFixed(5) }}
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-500">
                                    {{ log.response_time_ms }} ms
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span
                                        v-if="log.status === 'success'"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                                    >
                                        <CheckCircle2 class="w-3 h-3" />
                                        Sukses
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300"
                                        :title="log.error_message || 'Gagal'"
                                    >
                                        <XCircle class="w-3 h-3" />
                                        Gagal
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-600 dark:text-slate-400">
                                    {{ log.user ? log.user.name : 'Sistem' }}
                                </td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-500">
                                    Belum ada log penggunaan API Gemini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="py-4 px-6 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
                    <div>Menampilkan {{ logs.data.length }} dari {{ logs.total }} entri</div>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="logs.prev_page_url"
                            @click="router.visit(logs.prev_page_url)"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            Sebelumnya
                        </button>
                        <button
                            v-if="logs.next_page_url"
                            @click="router.visit(logs.next_page_url)"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            Berikutnya
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
