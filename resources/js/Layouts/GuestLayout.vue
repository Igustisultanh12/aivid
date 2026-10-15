<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Sparkles, Sun, Moon } from 'lucide-vue-next';
import { ref } from 'vue';
import Toast from '@/Components/Toast.vue';

defineProps<{
    title?: string;
}>();

const isDark = ref(document.documentElement.classList.contains('dark'));

const toggleDarkMode = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.theme = 'dark';
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.theme = 'light';
    }
};
</script>

<template>
    <div class="min-h-screen flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8 bg-[#FAF9F6] dark:bg-[#0B0F19] bg-mesh-purple transition-colors relative">
        <Toast />

        <!-- Top toggle -->
        <div class="absolute top-6 right-6">
            <button
                type="button"
                @click="toggleDarkMode"
                class="p-2.5 rounded-2xl bg-white/80 dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-800 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white transition shadow-sm backdrop-blur-md"
                aria-label="Mode gelap"
            >
                <Sun v-if="isDark" class="w-5 h-5 text-amber-400" />
                <Moon v-else class="w-5 h-5 text-violet-600" />
            </button>
        </div>

        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center mb-8">
            <Link href="/" class="inline-flex items-center gap-3 group">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-violet-600 via-purple-600 to-amber-500 p-0.5 shadow-lg shadow-violet-500/25 group-hover:scale-105 transition-transform">
                    <div class="w-full h-full bg-white dark:bg-slate-900 rounded-[14px] flex items-center justify-center">
                        <Sparkles class="w-6 h-6 text-violet-600 dark:text-violet-400 group-hover:rotate-12 transition-transform" />
                    </div>
                </div>
                <div class="text-left">
                    <span class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white font-serif">
                        Ruang Dharma
                    </span>
                    <span class="block text-xs font-semibold text-violet-600 dark:text-violet-400 uppercase tracking-widest">
                        Cerita Bermakna 30 Detik
                    </span>
                </div>
            </Link>
        </div>

        <div class="w-full sm:max-w-md bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl py-10 px-6 sm:px-10 shadow-2xl rounded-3xl border border-slate-200/80 dark:border-slate-800">
            <slot />
        </div>

        <div class="mt-8 text-center text-xs text-slate-500 dark:text-slate-500">
            <Link href="/" class="hover:text-violet-600 dark:hover:text-violet-400 font-medium transition">
                &larr; Kembali ke Beranda
            </Link>
        </div>
    </div>
</template>
