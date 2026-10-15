<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Mail, Lock, ArrowRight, Loader2 } from 'lucide-vue-next';

const props = defineProps<{
    token: string;
    email: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/reset-password', {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout title="Atur Ulang Kata Sandi">
        <Head title="Buat Kata Sandi Baru" />

        <div class="mb-6">
            <h2 class="text-xl font-bold font-serif text-slate-900 dark:text-white">
                Atur Kata Sandi Baru
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Masukkan kata sandi baru untuk mengamankan akun Ruang Dharma Anda.
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
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                    />
                </div>
                <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-500 font-medium">
                    {{ form.errors.email }}
                </p>
            </div>

            <div>
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Kata Sandi Baru
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
                        autofocus
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
                    Konfirmasi Kata Sandi Baru
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
                    <span>Perbarui Kata Sandi</span>
                    <ArrowRight class="w-4 h-4" />
                </template>
            </button>
        </form>
    </GuestLayout>
</template>
