<script setup lang="ts">
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Sparkles,
    Settings,
    Layers,
    BookMarked,
    Users,
    ShieldAlert,
    BarChart3,
    ArrowLeft,
    Sun,
    Moon,
    LogOut,
    Activity
} from 'lucide-vue-next';
import Toast from '@/Components/Toast.vue';
import type { PageProps } from '@/types';

defineProps<{
    title?: string;
}>();

const page = usePage<PageProps>();
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

const navItems = [
    { name: 'Integrasi Google Gemini', href: '/admin/settings/gemini', icon: Settings },
    { name: 'Manajemen Topik', href: '/admin/topics', icon: Layers },
    { name: 'Sumber Rujukan Hindu', href: '/admin/sources', icon: BookMarked },
    { name: 'Pengguna & Kuota', href: '/admin/users', icon: Users },
    { name: 'Moderasi Komunitas', href: '/admin/moderation', icon: ShieldAlert },
    { name: 'Statistik & Biaya AI', href: '/admin/usage', icon: BarChart3 },
];
</script>

<template>
    <div class="min-h-screen flex bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <Toast />

        <!-- Sidebar -->
        <aside class="w-72 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col shrink-0">
            <!-- Brand -->
            <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-violet-600 p-0.5 shadow-md">
                        <div class="w-full h-full bg-white dark:bg-slate-900 rounded-[10px] flex items-center justify-center">
                            <Sparkles class="w-5 h-5 text-amber-500" />
                        </div>
                    </div>
                    <div>
                        <span class="text-base font-extrabold text-slate-900 dark:text-white font-serif">
                            Ruang Dharma
                        </span>
                        <span class="block text-[10px] font-bold text-amber-500 uppercase tracking-widest">
                            Panel Administrator
                        </span>
                    </div>
                </Link>
            </div>

            <!-- Nav Links -->
            <nav class="flex-1 p-4 space-y-1.5 overflow-y-auto">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs font-bold transition',
                        $page.url.startsWith(item.href)
                            ? 'bg-violet-600 text-white shadow-md shadow-violet-600/30'
                            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'
                    ]"
                >
                    <component :is="item.icon" class="w-4 h-4 shrink-0" />
                    <span>{{ item.name }}</span>
                </Link>

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 mt-4">
                    <a
                        href="/horizon"
                        target="_blank"
                        class="flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 transition"
                    >
                        <Activity class="w-4 h-4 text-emerald-500" />
                        <span>Laravel Horizon (Antrean)</span>
                    </a>
                </div>
            </nav>

            <!-- Bottom User & Back -->
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 space-y-2">
                <Link
                    href="/dashboard"
                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition"
                >
                    <ArrowLeft class="w-4 h-4" />
                    Kembali ke Aplikasi
                </Link>

                <div class="flex items-center justify-between pt-2">
                    <button
                        type="button"
                        @click="toggleDarkMode"
                        class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        aria-label="Mode gelap"
                    >
                        <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
                        <Moon v-else class="w-4 h-4 text-violet-600" />
                    </button>

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="p-2 rounded-xl text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition text-xs font-bold flex items-center gap-1.5"
                    >
                        <LogOut class="w-4 h-4" />
                        Keluar
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-y-auto">
            <header class="h-18 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-8 flex items-center justify-between sticky top-0 z-30">
                <h1 class="text-xl font-bold font-serif text-slate-900 dark:text-white">
                    {{ title || 'Dasbor Administrator' }}
                </h1>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-300/40">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Sistem Aktif
                    </span>
                </div>
            </header>

            <main class="flex-1 p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
