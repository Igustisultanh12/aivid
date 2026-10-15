<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { MailCheck, Loader2, LogOut } from 'lucide-vue-next';

const form = useForm({});

const submit = () => {
    form.post('/email/verification-notification');
};
</script>

<template>
    <GuestLayout title="Verifikasi Email">
        <Head title="Verifikasi Alamat Email Anda" />

        <div class="text-center mb-6">
            <div class="w-14 h-14 rounded-2xl bg-violet-100 dark:bg-violet-900/40 text-violet-600 dark:text-violet-400 mx-auto flex items-center justify-center mb-4">
                <MailCheck class="w-7 h-7" />
            </div>
            <h2 class="text-xl font-bold font-serif text-slate-900 dark:text-white">
                Verifikasi Alamat Email
            </h2>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-2 leading-relaxed">
                Terima kasih telah mendaftar di Ruang Dharma! Sebelum memproduksi video, silakan klik tautan verifikasi yang kami kirimkan ke email Anda.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <button
                type="submit"
                :disabled="form.processing"
                class="btn-primary w-full py-3 rounded-xl font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-violet-600/25 disabled:opacity-50"
            >
                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                <span>Kirim Ulang Tautan Verifikasi</span>
            </button>

            <div class="pt-4 flex items-center justify-between border-t border-slate-100 dark:border-slate-800 text-xs">
                <Link href="/dashboard" class="text-violet-600 dark:text-violet-400 font-semibold hover:underline">
                    Ke Dashboard Sementara
                </Link>

                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="text-slate-500 hover:text-rose-500 flex items-center gap-1 transition"
                >
                    <LogOut class="w-3.5 h-3.5" />
                    Keluar
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
