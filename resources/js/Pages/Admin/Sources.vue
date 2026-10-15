<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    BookMarked,
    Plus,
    Edit3,
    Trash2,
    CheckCircle2,
    Clock,
    XCircle,
    ExternalLink,
    FileText,
    ShieldCheck
} from 'lucide-vue-next';

interface SourceItem {
    id: number;
    title: string;
    author_or_institution: string;
    source_type: string;
    reference_section: string | null;
    tradition_context: string;
    region: string;
    verification_status: 'verified' | 'under_review' | 'rejected';
    link_or_document: string | null;
    notes: string | null;
    storyboard_scenes_count: number;
    created_at: string;
}

const props = defineProps<{
    sources: {
        data: SourceItem[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        total: number;
    };
}>();

const showModal = ref(false);
const editingSource = ref<SourceItem | null>(null);

const form = useForm({
    title: '',
    author_or_institution: '',
    source_type: 'Lontar & Teks Kuno',
    reference_section: '',
    tradition_context: 'Tradisi Hindu Bali',
    region: 'Bali',
    verification_status: 'verified',
    link_or_document: '',
    notes: '',
});

const openCreateModal = () => {
    editingSource.value = null;
    form.reset();
    form.source_type = 'Lontar & Teks Kuno';
    form.tradition_context = 'Tradisi Hindu Bali';
    form.region = 'Bali';
    form.verification_status = 'verified';
    showModal.value = true;
};

const openEditModal = (source: SourceItem) => {
    editingSource.value = source;
    form.title = source.title;
    form.author_or_institution = source.author_or_institution;
    form.source_type = source.source_type;
    form.reference_section = source.reference_section || '';
    form.tradition_context = source.tradition_context;
    form.region = source.region;
    form.verification_status = source.verification_status;
    form.link_or_document = source.link_or_document || '';
    form.notes = source.notes || '';
    showModal.value = true;
};

const submitForm = () => {
    if (editingSource.value) {
        form.put(`/admin/sources/${editingSource.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/admin/sources', {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const deleteSource = (source: SourceItem) => {
    if (confirm(`Apakah Anda yakin ingin menghapus rujukan "${source.title}"?`)) {
        router.delete(`/admin/sources/${source.id}`);
    }
};
</script>

<template>
    <AdminLayout title="Sumber Rujukan Hindu">
        <Head title="Sumber Rujukan - Admin Ruang Dharma" />

        <div class="space-y-6">
            <!-- Header bar -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <BookMarked class="w-6 h-6 text-amber-500" />
                        Basis Rujukan & Pustaka Hindu
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Pustaka kitab suci, lontar, dan ketetapan resmi PHDI yang digunakan oleh Gemini AI sebagai referensi sah.
                    </p>
                </div>
                <div>
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-medium text-sm text-white bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 shadow-md transition-all active:scale-95"
                    >
                        <Plus class="w-4 h-4" />
                        Tambah Sumber Rujukan
                    </button>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                <th class="py-4 px-6">Sumber / Judul</th>
                                <th class="py-4 px-6">Penyusun / Lembaga</th>
                                <th class="py-4 px-6">Jenis & Bagian</th>
                                <th class="py-4 px-6">Konteks & Wilayah</th>
                                <th class="py-4 px-6 text-center">Status Verifikasi</th>
                                <th class="py-4 px-6 text-center">Dipakai di Scene</th>
                                <th class="py-4 px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-sm">
                            <tr
                                v-for="source in sources.data"
                                :key="source.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors"
                            >
                                <td class="py-4 px-6">
                                    <div class="font-medium text-slate-900 dark:text-white">{{ source.title }}</div>
                                    <div v-if="source.link_or_document" class="text-xs text-violet-600 dark:text-violet-400 flex items-center gap-1 mt-0.5">
                                        <ExternalLink class="w-3 h-3" />
                                        {{ source.link_or_document }}
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-slate-700 dark:text-slate-300">
                                    {{ source.author_or_institution }}
                                </td>
                                <td class="py-4 px-6">
                                    <div class="text-xs font-medium text-slate-800 dark:text-slate-200">{{ source.source_type }}</div>
                                    <div v-if="source.reference_section" class="text-xs text-slate-500 dark:text-slate-400">{{ source.reference_section }}</div>
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-600 dark:text-slate-400">
                                    <div>{{ source.tradition_context }}</div>
                                    <div class="text-slate-400">Wilayah: {{ source.region }}</div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span
                                        v-if="source.verification_status === 'verified'"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                                    >
                                        <CheckCircle2 class="w-3 h-3" />
                                        Terverifikasi
                                    </span>
                                    <span
                                        v-else-if="source.verification_status === 'under_review'"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-200 dark:border-amber-800"
                                    >
                                        <Clock class="w-3 h-3" />
                                        Ditinjau
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 border border-rose-200 dark:border-rose-800"
                                    >
                                        <XCircle class="w-3 h-3" />
                                        Ditolak
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center font-semibold text-slate-700 dark:text-slate-300">
                                    {{ source.storyboard_scenes_count }}
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openEditModal(source)"
                                            class="p-2 text-slate-500 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 rounded-lg transition-colors"
                                            title="Edit Rujukan"
                                        >
                                            <Edit3 class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="deleteSource(source)"
                                            class="p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors"
                                            title="Hapus Rujukan"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="sources.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-500">
                                    Belum ada sumber rujukan yang didaftarkan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="py-4 px-6 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
                    <div>Menampilkan {{ sources.data.length }} dari {{ sources.total }} rujukan</div>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="sources.prev_page_url"
                            @click="router.visit(sources.prev_page_url)"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            Sebelumnya
                        </button>
                        <button
                            v-if="sources.next_page_url"
                            @click="router.visit(sources.next_page_url)"
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
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-xl w-full p-6 space-y-5 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                    <h3 class="font-bold text-lg text-slate-900 dark:text-white flex items-center gap-2">
                        <ShieldCheck class="w-5 h-5 text-amber-500" />
                        {{ editingSource ? 'Edit Sumber Rujukan' : 'Tambah Sumber Rujukan' }}
                    </h3>
                    <button @click="showModal = false" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                        <XCircle class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Judul Kitab / Naskah / Dokumen</label>
                        <input
                            v-model="form.title"
                            type="text"
                            required
                            placeholder="Contoh: Lontar Sundarigama / Bhagavad Gita"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-violet-500 outline-hidden"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Penyusun / Lembaga Penerbit</label>
                            <input
                                v-model="form.author_or_institution"
                                type="text"
                                required
                                placeholder="Contoh: PHDI Pusat / Ditjen Bimas Hindu"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-violet-500 outline-hidden"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenis Sumber</label>
                            <select
                                v-model="form.source_type"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-violet-500 outline-hidden"
                            >
                                <option value="Lontar & Teks Kuno">Lontar & Teks Kuno</option>
                                <option value="Kitab Suci / Smrti">Kitab Suci / Smrti</option>
                                <option value="Ketetapan PHDI">Ketetapan PHDI</option>
                                <option value="Buku Akademis">Buku Akademis</option>
                                <option value="Jurnal Budaya">Jurnal Budaya</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Bagian / Bab / Sloka</label>
                            <input
                                v-model="form.reference_section"
                                type="text"
                                placeholder="Contoh: Bab IV Sloka 12"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-violet-500 outline-hidden"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Status Verifikasi</label>
                            <select
                                v-model="form.verification_status"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-violet-500 outline-hidden"
                            >
                                <option value="verified">Terverifikasi (Sah)</option>
                                <option value="under_review">Dalam Peninjauan</option>
                                <option value="rejected">Ditolak</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Konteks Tradisi</label>
                            <input
                                v-model="form.tradition_context"
                                type="text"
                                required
                                placeholder="Contoh: Tradisi Hindu Bali"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-violet-500 outline-hidden"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Wilayah</label>
                            <input
                                v-model="form.region"
                                type="text"
                                required
                                placeholder="Contoh: Bali / Nusantara"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-violet-500 outline-hidden"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tautan Web / Dokumen Online (Opsional)</label>
                        <input
                            v-model="form.link_or_document"
                            type="text"
                            placeholder="https://..."
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-violet-500 outline-hidden"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Catatan Verifikasi</label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            placeholder="Keterangan mengenai keabsahan atau konteks kutipan..."
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-violet-500 outline-hidden"
                        ></textarea>
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
                            {{ editingSource ? 'Simpan Perubahan' : 'Tambahkan Sumber' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
