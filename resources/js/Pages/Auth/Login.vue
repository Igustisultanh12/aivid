<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Mail, Lock, ArrowRight, Loader2 } from 'lucide-vue-next';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <GuestLayout title="Masuk">
        <Head title="Masuk ke Akun Anda" />

        <div class="mb-6">
            <h2 class="text-xl font-bold font-serif text-slate-900 dark:text-white">
                Masuk ke Ruang Dharma
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Lanjutkan perjalanan edukasi visual filosofi Hindu Anda.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
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
                        autofocus
                        autocomplete="username"
                        placeholder="nama@email.com"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                    />
                </div>
                <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-500 font-medium">
                    {{ form.errors.email }}
                </p>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Kata Sandi
                    </label>
                    <Link
                        href="/forgot-password"
                        class="text-xs font-semibold text-violet-600 dark:text-violet-400 hover:underline"
                    >
                        Lupa kata sandi?
                    </Link>
                </div>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <Lock class="w-4 h-4" />
                    </div>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                    />
                </div>
                <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-500 font-medium">
                    {{ form.errors.password }}
                </p>
            </div>

            <div class="flex items-center">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="w-4 h-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500 dark:bg-slate-800 dark:border-slate-700"
                    />
                    <span class="text-xs text-slate-600 dark:text-slate-400 font-medium">
                        Ingat saya di perangkat ini
                    </span>
                </label>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="btn-primary w-full py-3 rounded-xl font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-violet-600/25 disabled:opacity-50 mt-2"
            >
                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                <template v-else>
                    <span>Masuk ke Akun</span>
                    <ArrowRight class="w-4 h-4" />
                </template>
            </button>

            <div class="pt-4 text-center border-t border-slate-100 dark:border-slate-800">
                <p class="text-xs text-slate-600 dark:text-slate-400">
                    Belum memiliki akun?
                    <Link href="/register" class="font-bold text-violet-600 dark:text-violet-400 hover:underline ml-1">
                        Daftar sekarang
                    </Link>
                </p>
            </div>
        </form>
    </GuestLayout>
</template>
