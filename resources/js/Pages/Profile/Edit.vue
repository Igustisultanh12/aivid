<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import {
    User as UserIcon,
    Mail,
    Lock,
    Key,
    Shield,
    Check,
    AlertCircle,
    CheckCircle2,
    Trash2,
    Save,
    RefreshCw,
    Sparkles,
    Clock,
    AlertTriangle
} from 'lucide-vue-next';
import type { PageProps } from '@/types';

const props = defineProps<{
    mustVerifyEmail: boolean;
    status?: string | null;
    user: {
        id: number;
        name: string;
        email: string;
        role: string;
        is_admin: boolean;
        quota_total: number;
        quota_used: number;
        remaining_quota: number;
        email_verified_at: string | null;
        is_verified: boolean;
        created_at?: string;
    };
}>();

const page = usePage<PageProps>();

// Form for updating Profile Information (Name & Email)
const profileForm = useForm({
    name: props.user.name,
    email: props.user.email,
});

// Form for updating Password
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Form for deleting account
const deleteAccountForm = useForm({
    password: '',
});

// Modal state
const confirmingUserDeletion = ref(false);
const verificationLinkSent = ref(false);
const resendingVerification = ref(false);

// Submit Profile Information
const updateProfile = () => {
    profileForm.patch('/profile', {
        preserveScroll: true,
        onSuccess: () => {
            // Profile updated successfully
        },
    });
};

// Submit Password Change
const updatePassword = () => {
    passwordForm.put('/profile/password', {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
    });
};

// Resend Verification Email
const resendVerification = () => {
    resendingVerification.value = true;
    useForm({}).post('/email/verification-notification', {
        onSuccess: () => {
            verificationLinkSent.value = true;
            resendingVerification.value = false;
        },
        onFinish: () => {
            resendingVerification.value = false;
        },
    });
};

// Open Delete Account Modal
const confirmDeleteAccount = () => {
    confirmingUserDeletion.value = true;
};

// Close Delete Account Modal
const closeDeleteModal = () => {
    confirmingUserDeletion.value = false;
    deleteAccountForm.reset();
};

// Submit Delete Account
const deleteAccount = () => {
    deleteAccountForm.delete('/profile', {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
        },
        onError: () => {
            // Error handled by form
        },
    });
};
</script>

<template>
    <AppLayout title="Pengaturan Akun & Profil">
        <Head title="Pengaturan Akun & Profil Pengguna" />

        <div class="max-w-4xl mx-auto space-y-8 pb-12">
            <!-- Header Hero Banner -->
            <div class="p-8 sm:p-10 rounded-3xl bg-gradient-to-r from-violet-900 via-purple-900 to-slate-900 text-white shadow-xl relative overflow-hidden flex flex-col md:flex-row items-start md:items-center justify-between gap-6 border border-violet-500/20">
                <div class="absolute -right-10 -bottom-10 w-64 h-64 rounded-full bg-violet-600/20 blur-3xl pointer-events-none"></div>

                <div class="space-y-2 z-10">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-xs font-semibold text-purple-200 backdrop-blur-md">
                        <UserIcon class="w-3.5 h-3.5 text-amber-300" />
                        <span>Kelola Akun Pengguna</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold font-serif">
                        Pengaturan Profil & Keamanan
                    </h1>
                    <p class="text-xs sm:text-sm text-purple-200 leading-relaxed max-w-xl">
                        Perbarui nama lengkap, alamat email, ubah kata sandi, dan pantau status akun Anda secara aman.
                    </p>
                </div>

                <!-- Account Badge Pill -->
                <div class="z-10 flex flex-col items-start md:items-end gap-2 shrink-0">
                    <div class="flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 border border-white/20 text-xs font-bold text-amber-300">
                        <Shield class="w-4 h-4 text-amber-400" />
                        <span class="uppercase tracking-wider">{{ user.role === 'admin' ? 'Administrator' : 'Pengguna Terdaftar' }}</span>
                    </div>
                    <span v-if="user.created_at" class="text-[11px] text-purple-300">
                        Bergabung sejak {{ user.created_at }}
                    </span>
                </div>
            </div>

            <!-- Grid Layout for Settings -->
            <div class="grid grid-cols-1 gap-8">
                <!-- 1. Form Edit Informasi Profil (Nama & Email) -->
                <div class="p-6 sm:p-8 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
                    <div class="flex items-start justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <UserIcon class="w-5 h-5 text-violet-600 dark:text-violet-400" />
                                <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                                    Informasi Profil Pengguna
                                </h2>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                Perbarui nama lengkap dan alamat email utama akun Anda.
                            </p>
                        </div>
                    </div>

                    <form @submit.prevent="updateProfile" class="space-y-5">
                        <!-- Nama Lengkap -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Nama Lengkap
                            </label>
                            <div class="relative">
                                <input
                                    v-model="profileForm.name"
                                    type="text"
                                    required
                                    placeholder="Masukkan nama lengkap Anda"
                                    class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition"
                                />
                            </div>
                            <p v-if="profileForm.errors.name" class="text-xs text-rose-500 mt-1.5 flex items-center gap-1 font-semibold">
                                <AlertCircle class="w-3.5 h-3.5" />
                                {{ profileForm.errors.name }}
                            </p>
                        </div>

                        <!-- Alamat Email -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Alamat Email
                            </label>
                            <div class="relative">
                                <input
                                    v-model="profileForm.email"
                                    type="email"
                                    required
                                    placeholder="nama@email.com"
                                    class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition"
                                />
                            </div>
                            <p v-if="profileForm.errors.email" class="text-xs text-rose-500 mt-1.5 flex items-center gap-1 font-semibold">
                                <AlertCircle class="w-3.5 h-3.5" />
                                {{ profileForm.errors.email }}
                            </p>
                        </div>

                        <!-- Status Verifikasi Email -->
                        <div v-if="mustVerifyEmail" class="p-4 rounded-2xl border"
                            :class="user.is_verified
                                ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800'
                                : 'bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800'"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <CheckCircle2 v-if="user.is_verified" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                    <AlertCircle v-else class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0" />
                                    <div>
                                        <span class="text-xs font-bold" :class="user.is_verified ? 'text-emerald-800 dark:text-emerald-200' : 'text-amber-800 dark:text-amber-200'">
                                            {{ user.is_verified ? 'Email Anda Telah Terverifikasi' : 'Email Anda Belum Terverifikasi' }}
                                        </span>
                                        <p class="text-[11px] text-slate-600 dark:text-slate-300 mt-0.5">
                                            {{ user.is_verified
                                                ? 'Akun Anda memiliki akses penuh ke Studio Produksi Video AI.'
                                                : 'Verifikasi email diperlukan untuk mulai merender video di Studio AI.' }}
                                        </p>
                                    </div>
                                </div>

                                <button
                                    v-if="!user.is_verified"
                                    type="button"
                                    @click="resendVerification"
                                    :disabled="resendingVerification || verificationLinkSent"
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white transition shrink-0 disabled:opacity-50"
                                >
                                    {{ resendingVerification ? 'Mengirim...' : (verificationLinkSent ? 'Tautan Terkirim!' : 'Kirim Ulang Tautan') }}
                                </button>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button
                                type="submit"
                                :disabled="profileForm.processing"
                                class="px-6 py-3 rounded-2xl font-bold text-xs bg-violet-600 hover:bg-violet-700 text-white shadow-md shadow-violet-600/20 inline-flex items-center gap-2 transition disabled:opacity-50 cursor-pointer"
                            >
                                <RefreshCw v-if="profileForm.processing" class="w-4 h-4 animate-spin" />
                                <Save v-else class="w-4 h-4" />
                                <span>{{ profileForm.processing ? 'Menyimpan Perubahan...' : 'Simpan Perubahan Profil' }}</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 2. Form Perbarui Kata Sandi (Change Password) -->
                <div class="p-6 sm:p-8 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
                    <div class="flex items-start justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <Lock class="w-5 h-5 text-amber-500" />
                                <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                                    Perbarui Kata Sandi Akun
                                </h2>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                Pastikan akun Anda menggunakan kata sandi yang panjang dan acak demi keamanan data.
                            </p>
                        </div>
                    </div>

                    <form @submit.prevent="updatePassword" class="space-y-5">
                        <!-- Kata Sandi Saat Ini -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Kata Sandi Saat Ini
                            </label>
                            <input
                                v-model="passwordForm.current_password"
                                type="password"
                                required
                                autocomplete="current-password"
                                placeholder="Masukkan kata sandi lama Anda"
                                class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                            />
                            <p v-if="passwordForm.errors.current_password" class="text-xs text-rose-500 mt-1.5 flex items-center gap-1 font-semibold">
                                <AlertCircle class="w-3.5 h-3.5" />
                                {{ passwordForm.errors.current_password }}
                            </p>
                        </div>

                        <!-- Kata Sandi Baru -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Kata Sandi Baru
                            </label>
                            <input
                                v-model="passwordForm.password"
                                type="password"
                                required
                                autocomplete="new-password"
                                placeholder="Minimal 8 karakter kombinasi"
                                class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                            />
                            <p v-if="passwordForm.errors.password" class="text-xs text-rose-500 mt-1.5 flex items-center gap-1 font-semibold">
                                <AlertCircle class="w-3.5 h-3.5" />
                                {{ passwordForm.errors.password }}
                            </p>
                        </div>

                        <!-- Konfirmasi Kata Sandi Baru -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Konfirmasi Kata Sandi Baru
                            </label>
                            <input
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                placeholder="Ulangi kata sandi baru Anda"
                                class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                            />
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button
                                type="submit"
                                :disabled="passwordForm.processing"
                                class="px-6 py-3 rounded-2xl font-bold text-xs bg-amber-500 hover:bg-amber-600 text-slate-950 shadow-md shadow-amber-500/20 inline-flex items-center gap-2 transition disabled:opacity-50 cursor-pointer"
                            >
                                <RefreshCw v-if="passwordForm.processing" class="w-4 h-4 animate-spin" />
                                <Key v-else class="w-4 h-4" />
                                <span>{{ passwordForm.processing ? 'Memperbarui...' : 'Perbarui Kata Sandi' }}</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 3. Kuota & Informasi Tambahan Akun -->
                <div class="p-6 sm:p-8 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-4">
                        <Sparkles class="w-5 h-5 text-emerald-500" />
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                            Kuota Produksi Video
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60">
                            <span class="text-xs font-bold uppercase text-slate-500">Total Kuota Diberikan</span>
                            <p class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ user.quota_total }} Video</p>
                        </div>
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60">
                            <span class="text-xs font-bold uppercase text-slate-500">Kuota Terpakai</span>
                            <p class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1">{{ user.quota_used }} Video</p>
                        </div>
                        <div class="p-4 rounded-2xl bg-violet-50 dark:bg-violet-950/40 border border-violet-200 dark:border-violet-800">
                            <span class="text-xs font-bold uppercase text-violet-600 dark:text-violet-400">Sisa Kuota Aktif</span>
                            <p class="text-2xl font-extrabold text-violet-700 dark:text-violet-300 mt-1">{{ user.remaining_quota }} Video</p>
                        </div>
                    </div>
                </div>

                <!-- 4. Zona Bahaya (Hapus Akun Pengguna) -->
                <div class="p-6 sm:p-8 rounded-3xl bg-red-50/50 dark:bg-red-950/10 border border-red-200 dark:border-red-900/40 shadow-sm space-y-4">
                    <div class="flex items-center gap-2">
                        <AlertTriangle class="w-5 h-5 text-rose-500" />
                        <h2 class="text-lg font-bold text-rose-700 dark:text-rose-400">
                            Hapus Akun Pengguna
                        </h2>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed max-w-2xl">
                        Setelah akun Anda dihapus, seluruh sumber daya, video yang telah dirender, proyek prompt kartun, dan riwayat aktivitas Anda akan dihapus secara permanen. Sebelum menghapus akun, pastikan Anda telah mengunduh berkas penting Anda.
                    </p>
                    <div class="pt-2">
                        <button
                            type="button"
                            @click="confirmDeleteAccount"
                            class="px-5 py-2.5 rounded-xl font-bold text-xs bg-rose-600 hover:bg-rose-700 text-white shadow-sm inline-flex items-center gap-2 transition cursor-pointer"
                        >
                            <Trash2 class="w-4 h-4" />
                            <span>Hapus Akun Saya</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal for Account Deletion -->
        <Modal :show="confirmingUserDeletion" @close="closeDeleteModal">
            <div class="p-6 space-y-5">
                <div class="flex items-center gap-3 text-rose-600">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-950/60 flex items-center justify-center">
                        <AlertTriangle class="w-5 h-5 text-rose-600 dark:text-rose-400" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                            Apakah Anda Yakin Ingin Menghapus Akun?
                        </h3>
                        <p class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan.</p>
                    </div>
                </div>

                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                    Masukkan kata sandi Anda untuk mengonfirmasi bahwa Anda benar-benar ingin menghapus akun Anda secara permanen beserta seluruh data terkait.
                </p>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                        Kata Sandi Anda
                    </label>
                    <input
                        v-model="deleteAccountForm.password"
                        type="password"
                        required
                        placeholder="Masukkan kata sandi untuk verifikasi"
                        class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                        @keyup.enter="deleteAccount"
                    />
                    <p v-if="deleteAccountForm.errors.password" class="text-xs text-rose-500 mt-1 font-semibold">
                        {{ deleteAccountForm.errors.password }}
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button
                        type="button"
                        @click="closeDeleteModal"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="deleteAccount"
                        :disabled="deleteAccountForm.processing"
                        class="px-5 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-sm inline-flex items-center gap-1.5 transition disabled:opacity-50"
                    >
                        <Trash2 class="w-3.5 h-3.5" />
                        <span>{{ deleteAccountForm.processing ? 'Menghapus...' : 'Konfirmasi Hapus Akun' }}</span>
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
