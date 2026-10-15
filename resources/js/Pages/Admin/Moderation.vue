<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    ShieldAlert,
    CheckCircle2,
    XCircle,
    Eye,
    MessageSquare,
    Video,
    User,
    Calendar,
    AlertTriangle,
    Check,
    X
} from 'lucide-vue-next';

interface PendingVideo {
    id: string;
    title: string;
    description: string | null;
    aspect_ratio: string;
    thumbnail_url: string | null;
    video_url: string | null;
    user: {
        id: number;
        name: string;
        email: string;
    };
    topic?: {
        title: string;
    };
    created_at: string;
}

interface PendingReport {
    id: number;
    reason: string;
    details: string | null;
    user: {
        id: number;
        name: string;
        email: string;
    };
    video: {
        id: string;
        title: string;
        user: {
            name: string;
        };
    };
    created_at: string;
}

const props = defineProps<{
    pendingReports: PendingReport[];
    pendingVideos: PendingVideo[];
}>();

const activeTab = ref<'videos' | 'reports'>('videos');
const selectedVideo = ref<PendingVideo | null>(null);

const approveVideo = (video: PendingVideo) => {
    router.post(`/admin/moderation/videos/${video.id}/approve`);
};

const rejectVideo = (video: PendingVideo) => {
    if (confirm(`Tolak dan kembalikan video "${video.title}" ke status privat?`)) {
        router.post(`/admin/moderation/videos/${video.id}/reject`);
    }
};

const resolveReport = (report: PendingReport, action: 'action_taken' | 'dismissed') => {
    const reasonPrompt = action === 'action_taken'
        ? prompt('Masukkan catatan tindakan (opsional):', 'Melanggar panduan komunitas')
        : 'Laporan tidak terbukti';

    if (reasonPrompt !== null) {
        router.post(`/admin/moderation/reports/${report.id}/resolve`, {
            action,
            notes: reasonPrompt,
        });
    }
};
</script>

<template>
    <AdminLayout title="Moderasi Komunitas">
        <Head title="Moderasi Komunitas - Admin Ruang Dharma" />

        <div class="space-y-6">
            <!-- Header bar -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                            <ShieldAlert class="w-6 h-6 text-amber-500" />
                            Pusat Moderasi & Kurasi
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Verifikasi kelayakan video publik dan tangani laporan komunitas terkait kepatuhan etika tradisi Hindu.
                        </p>
                    </div>

                    <!-- Tabs -->
                    <div class="flex bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
                        <button
                            @click="activeTab = 'videos'"
                            :class="[
                                'px-4 py-2 rounded-lg text-xs font-semibold flex items-center gap-2 transition-all',
                                activeTab === 'videos'
                                    ? 'bg-white dark:bg-slate-700 text-violet-700 dark:text-violet-300 shadow-xs'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                            ]"
                        >
                            <Video class="w-4 h-4" />
                            Pengajuan Galeri Publik
                            <span
                                v-if="pendingVideos.length > 0"
                                class="w-5 h-5 rounded-full bg-violet-600 text-white text-[10px] flex items-center justify-center font-bold"
                            >
                                {{ pendingVideos.length }}
                            </span>
                        </button>
                        <button
                            @click="activeTab = 'reports'"
                            :class="[
                                'px-4 py-2 rounded-lg text-xs font-semibold flex items-center gap-2 transition-all',
                                activeTab === 'reports'
                                    ? 'bg-white dark:bg-slate-700 text-rose-600 dark:text-rose-400 shadow-xs'
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                            ]"
                        >
                            <AlertTriangle class="w-4 h-4" />
                            Laporan Pelanggaran
                            <span
                                v-if="pendingReports.length > 0"
                                class="w-5 h-5 rounded-full bg-rose-600 text-white text-[10px] flex items-center justify-center font-bold"
                            >
                                {{ pendingReports.length }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab 1: Pending Videos -->
            <div v-if="activeTab === 'videos'" class="space-y-4">
                <div v-if="pendingVideos.length === 0" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-12 text-center">
                    <CheckCircle2 class="w-12 h-12 text-emerald-500 mx-auto mb-3" />
                    <h3 class="text-base font-semibold text-slate-800 dark:text-slate-200">Semua Pengajuan Selesai Diperiksa</h3>
                    <p class="text-xs text-slate-500 mt-1">Tidak ada video yang sedang menunggu persetujuan kurasi publik.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="video in pendingVideos"
                        :key="video.id"
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden flex flex-col justify-between"
                    >
                        <div>
                            <!-- Thumbnail / aspect -->
                            <div class="relative bg-slate-950 aspect-video flex items-center justify-center overflow-hidden">
                                <img
                                    v-if="video.thumbnail_url"
                                    :src="video.thumbnail_url"
                                    :alt="video.title"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="text-slate-600 text-xs flex flex-col items-center">
                                    <Video class="w-8 h-8 mb-1" />
                                    Thumbnail tidak tersedia
                                </div>
                                <span class="absolute top-2 right-2 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-black/60 text-white">
                                    {{ video.aspect_ratio }}
                                </span>
                            </div>

                            <div class="p-5 space-y-2">
                                <div class="text-xs text-violet-600 dark:text-violet-400 font-medium">
                                    {{ video.topic?.title || 'Topik Umum' }}
                                </div>
                                <h3 class="font-bold text-slate-900 dark:text-white line-clamp-2">
                                    {{ video.title }}
                                </h3>
                                <p class="text-xs text-slate-500 line-clamp-2">
                                    {{ video.description || 'Tidak ada deskripsi.' }}
                                </p>
                                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-400 flex items-center justify-between">
                                    <span>Oleh: {{ video.user.name }}</span>
                                    <span>{{ new Date(video.created_at).toLocaleDateString('id-ID') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-50/70 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2">
                            <a
                                :href="`/videos/${video.id}`"
                                target="_blank"
                                class="flex-1 text-center py-2 px-3 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-100"
                            >
                                Pratinjau
                            </a>
                            <button
                                @click="approveVideo(video)"
                                class="flex-1 py-2 px-3 rounded-xl text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 shadow-xs flex items-center justify-center gap-1"
                            >
                                <Check class="w-3.5 h-3.5" />
                                Setujui
                            </button>
                            <button
                                @click="rejectVideo(video)"
                                class="flex-1 py-2 px-3 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/30 dark:hover:bg-rose-900/50 flex items-center justify-center gap-1"
                            >
                                <X class="w-3.5 h-3.5" />
                                Tolak
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Pending Reports -->
            <div v-if="activeTab === 'reports'" class="space-y-4">
                <div v-if="pendingReports.length === 0" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-12 text-center">
                    <CheckCircle2 class="w-12 h-12 text-emerald-500 mx-auto mb-3" />
                    <h3 class="text-base font-semibold text-slate-800 dark:text-slate-200">Tidak Ada Laporan Tertunda</h3>
                    <p class="text-xs text-slate-500 mt-1">Komunitas berjalan harmonis. Tidak ada pelanggaran yang belum diproses.</p>
                </div>

                <div v-else class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    <th class="py-4 px-6">Video Dilaporkan</th>
                                    <th class="py-4 px-6">Pelapor</th>
                                    <th class="py-4 px-6">Alasan Laporan</th>
                                    <th class="py-4 px-6">Detail / Catatan</th>
                                    <th class="py-4 px-6">Tanggal</th>
                                    <th class="py-4 px-6 text-right">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-sm">
                                <tr
                                    v-for="report in pendingReports"
                                    :key="report.id"
                                    class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                                >
                                    <td class="py-4 px-6">
                                        <div class="font-medium text-slate-900 dark:text-white">{{ report.video.title }}</div>
                                        <div class="text-xs text-slate-500">Pemilik: {{ report.video.user.name }}</div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="text-slate-800 dark:text-slate-200">{{ report.user.name }}</div>
                                        <div class="text-xs text-slate-500">{{ report.user.email }}</div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                            {{ report.reason }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-xs text-slate-600 dark:text-slate-300 max-w-xs">
                                        {{ report.details || '-' }}
                                    </td>
                                    <td class="py-4 px-6 text-xs text-slate-500">
                                        {{ new Date(report.created_at).toLocaleDateString('id-ID') }}
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button
                                                @click="resolveReport(report, 'action_taken')"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/30 dark:text-rose-300"
                                            >
                                                Tarik Video
                                            </button>
                                            <button
                                                @click="resolveReport(report, 'dismissed')"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300"
                                            >
                                                Abaikan
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
