<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Mail, ArrowRight, Loader2 } from 'lucide-vue-next';

const form = useForm({
    email: '',
});

const submit = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <GuestLayout title="Lupa Kata Sandi">
        <Head title="Pemulihan Kata Sandi" />

        <div class="mb-6">
            <h2 class="text-xl font-bold font-serif text-slate-900 dark:text-white">
                Lupa Kata Sandi?
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                Masukkan email Anda dan kami akan mengirimkan tautan untuk mengatur ulang kata sandi akun Anda.
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
                        placeholder="nama@email.com"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                    />
                </div>
                <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-500 font-medium">
                    {{ form.errors.email }}
                </p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="btn-primary w-full py-3 rounded-xl font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-violet-600/25 disabled:opacity-50 mt-2"
            >
                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                <template v-else>
                    <span>Kirim Tautan Pemulihan</span>
                    <ArrowRight class="w-4 h-4" />
                </template>
            </button>

            <div class="pt-4 text-center border-t border-slate-100 dark:border-slate-800">
                <Link href="/login" class="text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-violet-600 transition">
                    &larr; Kembali ke halaman Masuk
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
