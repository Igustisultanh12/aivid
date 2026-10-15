<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { User, Mail, Lock, ArrowRight, Loader2, Sparkles } from 'lucide-vue-next';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout title="Daftar Akun">
        <Head title="Daftar Akun Baru" />

        <div class="mb-6">
            <h2 class="text-xl font-bold font-serif text-slate-900 dark:text-white">
                Buat Akun Ruang Dharma
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Dapatkan 10 kuota pembuatan video edukasi filosofi Hindu 30 detik gratis.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Nama Lengkap
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <User class="w-4 h-4" />
                    </div>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Contoh: Putu Bagus"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                    />
                </div>
                <p v-if="form.errors.name" class="mt-1.5 text-xs text-rose-500 font-medium">
                    {{ form.errors.name }}
                </p>
            </div>

            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Alamat Email
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <Mail class="w-4 h-4" />
                    </div>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="nama@email.com"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                    />
                </div>
                <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-500 font-medium">
                    {{ form.errors.email }}
                </p>
            </div>

            <div>
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Kata Sandi (Min. 8 karakter)
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <Lock class="w-4 h-4" />
                    </div>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                    />
                </div>
                <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-500 font-medium">
                    {{ form.errors.password }}
                </p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Konfirmasi Kata Sandi
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <Lock class="w-4 h-4" />
                    </div>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                    />
                </div>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="btn-primary w-full py-3 rounded-xl font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-violet-600/25 disabled:opacity-50 mt-4"
            >
                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                <template v-else>
                    <Sparkles class="w-4 h-4" />
                    <span>Daftar & Dapatkan Kuota</span>
                </template>
            </button>

            <div class="pt-4 text-center border-t border-slate-100 dark:border-slate-800">
                <p class="text-xs text-slate-600 dark:text-slate-400">
                    Sudah memiliki akun?
                    <Link href="/login" class="font-bold text-violet-600 dark:text-violet-400 hover:underline ml-1">
                        Masuk di sini
                    </Link>
                </p>
            </div>
        </form>
    </GuestLayout>
</template>
