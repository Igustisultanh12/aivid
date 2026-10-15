<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Layers,
    Plus,
    Edit3,
    Trash2,
    CheckCircle2,
    XCircle,
    Star,
    Sparkles,
    Search,
    BookOpen
} from 'lucide-vue-next';

interface TopicItem {
    id: number;
    title: string;
    slug: string;
    description: string;
    category: string;
    cultural_context: string;
    is_popular: boolean;
    icon_name: string;
    videos_count: number;
    created_at: string;
}

const props = defineProps<{
    topics: {
        data: TopicItem[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        total: number;
    };
}>();

const showModal = ref(false);
const editingTopic = ref<TopicItem | null>(null);

const form = useForm({
    title: '',
    description: '',
    category: 'Upacara & Tradisi',
    cultural_context: 'Tradisi Bali & Nusantara',
    is_popular: false,
    icon_name: 'Sparkles',
});

const openCreateModal = () => {
    editingTopic.value = null;
    form.reset();
    form.category = 'Upacara & Tradisi';
    form.cultural_context = 'Tradisi Bali & Nusantara';
    form.icon_name = 'Sparkles';
    form.is_popular = false;
    showModal.value = true;
};

const openEditModal = (topic: TopicItem) => {
    editingTopic.value = topic;
    form.title = topic.title;
    form.description = topic.description;
    form.category = topic.category;
    form.cultural_context = topic.cultural_context;
    form.is_popular = Boolean(topic.is_popular);
    form.icon_name = topic.icon_name || 'Sparkles';
    showModal.value = true;
};

const submitForm = () => {
    if (editingTopic.value) {
        form.put(`/admin/topics/${editingTopic.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/admin/topics', {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const deleteTopic = (topic: TopicItem) => {
    if (confirm(`Apakah Anda yakin ingin menghapus topik "${topic.title}"?`)) {
        router.delete(`/admin/topics/${topic.id}`);
    }
};
</script>

<template>
    <AdminLayout title="Manajemen Topik Edukasi">
        <Head title="Manajemen Topik - Admin Ruang Dharma" />

        <div class="space-y-6">
            <!-- Header bar -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <Layers class="w-6 h-6 text-amber-500" />
                        Manajemen Topik Edukasi
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Kelola katalog topik filosofi dan tradisi Hindu yang tersedia untuk generasi video AI.
                    </p>
                </div>
                <div>
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-medium text-sm text-white bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 shadow-md transition-all active:scale-95"
                    >
                        <Plus class="w-4 h-4" />
                        Tambah Topik Baru
                    </button>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                <th class="py-4 px-6">Topik</th>
                                <th class="py-4 px-6">Kategori</th>
                                <th class="py-4 px-6">Konteks Budaya</th>
                                <th class="py-4 px-6 text-center">Video Dibuat</th>
                                <th class="py-4 px-6 text-center">Status Populer</th>
                                <th class="py-4 px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-sm">
                            <tr
                                v-for="topic in topics.data"
                                :key="topic.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors"
                            >
                                <td class="py-4 px-6">
                                    <div class="font-medium text-slate-900 dark:text-white">{{ topic.title }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1 mt-0.5">{{ topic.description }}</div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-violet-50 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300 border border-violet-200 dark:border-violet-800">
                                        {{ topic.category }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-slate-600 dark:text-slate-300 text-xs">
                                    {{ topic.cultural_context }}
                                </td>
                                <td class="py-4 px-6 text-center font-semibold text-slate-700 dark:text-slate-300">
                                    {{ topic.videos_count }}
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span
                                        v-if="topic.is_popular"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-200 dark:border-amber-800"
                                    >
                                        <Star class="w-3 h-3 fill-amber-400 text-amber-500" />
                                        Populer
                                    </span>
                                    <span v-else class="text-xs text-slate-400">-</span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openEditModal(topic)"
                                            class="p-2 text-slate-500 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 rounded-lg transition-colors"
                                            title="Edit Topik"
                                        >
                                            <Edit3 class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="deleteTopic(topic)"
                                            class="p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors"
                                            title="Hapus Topik"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="topics.data.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-500">
                                    Belum ada topik edukasi yang didaftarkan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="py-4 px-6 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
                    <div>Menampilkan {{ topics.data.length }} dari {{ topics.total }} topik</div>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="topics.prev_page_url"
                            @click="router.visit(topics.prev_page_url)"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            Sebelumnya
                        </button>
                        <button
                            v-if="topics.next_page_url"
                            @click="router.visit(topics.next_page_url)"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            Berikutnya
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                    <h3 class="font-bold text-lg text-slate-900 dark:text-white flex items-center gap-2">
                        <BookOpen class="w-5 h-5 text-amber-500" />
                        {{ editingTopic ? 'Edit Topik Edukasi' : 'Tambah Topik Baru' }}
                    </h3>
                    <button @click="showModal = false" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                        <XCircle class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Judul Topik</label>
                        <input
                            v-model="form.title"
                            type="text"
                            required
                            placeholder="Contoh: Makna Canang Sari"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-violet-500 outline-hidden"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Deskripsi Singkat</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            required
                            placeholder="Penjelasan inti mengenai filosofi topik ini..."
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-violet-500 outline-hidden"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Kategori</label>
                            <select
                                v-model="form.category"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-violet-500 outline-hidden"
                            >
                                <option value="Upacara & Tradisi">Upacara & Tradisi</option>
                                <option value="Hari Suci">Hari Suci</option>
                                <option value="Filosofi Kehidupan">Filosofi Kehidupan</option>
                                <option value="Etika & Susila">Etika & Susila</option>
                                <option value="Simbolisme Sakral">Simbolisme Sakral</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Konteks Budaya</label>
                            <input
                                v-model="form.cultural_context"
                                type="text"
                                required
                                placeholder="Contoh: Tradisi Hindu Bali"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-violet-500 outline-hidden"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Ikon Lucide</label>
                            <input
                                v-model="form.icon_name"
                                type="text"
                                placeholder="Sparkles / Flame / Sun"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-violet-500 outline-hidden"
                            />
                        </div>
                        <div class="flex items-center pt-5">
                            <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700 dark:text-slate-300">
                                <input
                                    v-model="form.is_popular"
                                    type="checkbox"
                                    class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500 border-slate-300"
                                />
                                Tandai sebagai Topik Populer
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 rounded-xl text-sm font-medium text-white bg-violet-600 hover:bg-violet-700 shadow-md transition-all disabled:opacity-50"
                        >
                            {{ editingTopic ? 'Simpan Perubahan' : 'Tambahkan Topik' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
