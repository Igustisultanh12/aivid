<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Users,
    Search,
    Shield,
    UserCheck,
    Edit2,
    CheckCircle2,
    Clock,
    XCircle,
    Video,
    Sparkles
} from 'lucide-vue-next';

interface UserItem {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'user';
    quota_total: number;
    quota_used: number;
    avatar_url: string | null;
    email_verified_at: string | null;
    videos_count: number;
    created_at: string;
}

const props = defineProps<{
    users: {
        data: UserItem[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        total: number;
    };
    filters: {
        search?: string;
    };
}>();

const search = ref(props.filters.search || '');
const showModal = ref(false);
const selectedUser = ref<UserItem | null>(null);

const form = useForm({
    quota_total: 5,
    role: 'user',
});

let searchTimeout: any = null;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            '/admin/users',
            { search: val },
            { preserveState: true, replace: true }
        );
    }, 300);
});

const openEditModal = (user: UserItem) => {
    selectedUser.value = user;
    form.quota_total = user.quota_total;
    form.role = user.role;
    showModal.value = true;
};

const submitForm = () => {
    if (!selectedUser.value) return;
    form.put(`/admin/users/${selectedUser.value.id}/quota`, {
        onSuccess: () => {
            showModal.value = false;
        },
    });
};
</script>

<template>
    <AdminLayout title="Pengguna & Kuota">
        <Head title="Pengguna & Kuota - Admin Ruang Dharma" />

        <div class="space-y-6">
            <!-- Header bar with search -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <Users class="w-6 h-6 text-amber-500" />
                        Pengguna & Alokasi Kuota
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Atur jatah pembuatan video edukasi dan hak akses administrator per pengguna.
                    </p>
                </div>
                <div class="w-full md:w-72 relative">
                    <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari nama atau email..."
                        class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-violet-500 outline-hidden"
                    />
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                <th class="py-4 px-6">Pengguna</th>
                                <th class="py-4 px-6">Peran</th>
                                <th class="py-4 px-6">Verifikasi Email</th>
                                <th class="py-4 px-6">Penggunaan Kuota</th>
                                <th class="py-4 px-6 text-center">Video Dibuat</th>
                                <th class="py-4 px-6">Bergabung</th>
                                <th class="py-4 px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-sm">
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors"
                            >
                                <td class="py-4 px-6">
                                    <div class="font-medium text-slate-900 dark:text-white">{{ user.name }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ user.email }}</div>
                                </td>
                                <td class="py-4 px-6">
                                    <span
                                        v-if="user.role === 'admin'"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-200 dark:border-amber-800"
                                    >
                                        <Shield class="w-3 h-3 text-amber-500" />
                                        Administrator
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    >
                                        Pengguna
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <span
                                        v-if="user.email_verified_at"
                                        class="inline-flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400"
                                    >
                                        <CheckCircle2 class="w-3.5 h-3.5" />
                                        Terverifikasi
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 text-xs text-amber-500"
                                    >
                                        <Clock class="w-3.5 h-3.5" />
                                        Belum Verifikasi
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2">
                                        <div class="w-24 bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                            <div
                                                class="bg-violet-600 h-full rounded-full"
                                                :style="{ width: `${Math.min(100, (user.quota_used / Math.max(1, user.quota_total)) * 100)}%` }"
                                            ></div>
                                        </div>
                                        <span class="text-xs font-medium text-slate-600 dark:text-slate-400">
                                            {{ user.quota_used }} / {{ user.quota_total }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center font-semibold text-slate-700 dark:text-slate-300">
                                    {{ user.videos_count }}
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-500 dark:text-slate-400">
                                    {{ new Date(user.created_at).toLocaleDateString('id-ID') }}
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <button
                                        @click="openEditModal(user)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-violet-700 bg-violet-50 hover:bg-violet-100 dark:bg-violet-900/30 dark:text-violet-300 dark:hover:bg-violet-900/50 transition-colors"
                                    >
                                        <Edit2 class="w-3.5 h-3.5" />
                                        Atur Kuota
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-500">
                                    Tidak ada pengguna ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="py-4 px-6 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
                    <div>Menampilkan {{ users.data.length }} dari {{ users.total }} pengguna</div>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="users.prev_page_url"
                            @click="router.visit(users.prev_page_url)"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            Sebelumnya
                        </button>
                        <button
                            v-if="users.next_page_url"
                            @click="router.visit(users.next_page_url)"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            Berikutnya
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Quota & Role Modal -->
        <div v-if="showModal && selectedUser" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-md w-full p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div>
                        <h3 class="font-bold text-lg text-slate-900 dark:text-white">Atur Hak & Kuota</h3>
                        <p class="text-xs text-slate-500">{{ selectedUser.name }} ({{ selectedUser.email }})</p>
                    </div>
                    <button @click="showModal = false" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                        <XCircle class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Peran Akun</label>
                        <select
                            v-model="form.role"
                            class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-violet-500 outline-hidden"
                        >
                            <option value="user">Pengguna Biasa</option>
                            <option value="admin">Administrator (Akses Penuh)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Total Kuota Video</label>
                        <input
                            v-model.number="form.quota_total"
                            type="number"
                            min="0"
                            max="1000"
                            required
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-violet-500 outline-hidden"
                        />
                        <p class="text-xs text-slate-500 mt-1">Kuota terpakai saat ini: {{ selectedUser.quota_used }} video</p>
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
                            Perbarui Pengguna
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
