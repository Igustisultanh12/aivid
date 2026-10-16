<script setup lang="ts">
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Sparkles, Sun, Moon, LayoutDashboard, Video, Film, Bookmark, Calendar, Shield, LogOut, ChevronDown, User as UserIcon, Clapperboard } from 'lucide-vue-next';
import Toast from '@/Components/Toast.vue';
import type { PageProps } from '@/types';

defineProps<{
    title?: string;
}>();

const page = usePage<PageProps>();
const isDark = ref(document.documentElement.classList.contains('dark'));
const userMenuOpen = ref(false);

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
    <div class="min-h-screen flex flex-col bg-[#FAF9F6] dark:bg-[#0B0F19] text-slate-800 dark:text-slate-100 transition-colors">
        <!-- Toast notifications -->
        <Toast />

        <!-- Top Header Navigation -->
        <header class="sticky top-0 z-40 border-b border-slate-200/80 dark:border-slate-800 bg-white/90 dark:bg-[#0B0F19]/90 backdrop-blur-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 flex items-center justify-between">
                <!-- Brand -->
                <div class="flex items-center gap-8">
                    <Link href="/dashboard" class="flex items-center gap-3 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-violet-600 via-purple-600 to-amber-500 p-0.5 shadow-md shadow-violet-500/20 group-hover:scale-105 transition-transform">
                            <div class="w-full h-full bg-white dark:bg-[#0B0F19] rounded-[10px] flex items-center justify-center">
                                <Sparkles class="w-5 h-5 text-violet-600 dark:text-violet-400" />
                            </div>
                        </div>
                        <div>
                            <span class="text-lg font-extrabold tracking-tight text-slate-900 dark:text-white font-serif">
                                Ruang Dharma
                            </span>
                            <span class="block text-[10px] font-bold text-violet-600 dark:text-violet-400 tracking-wider uppercase">
                                Studio Edukasi
                            </span>
                        </div>
                    </Link>

                    <!-- Nav items -->
                    <nav class="hidden md:flex items-center gap-1">
                        <Link
                            href="/dashboard"
                            :class="[
                                'px-3.5 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-2',
                                $page.url.startsWith('/dashboard')
                                    ? 'text-violet-700 dark:text-violet-300 bg-violet-100/60 dark:bg-violet-900/30'
                                    : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'
                            ]"
                        >
                            <LayoutDashboard class="w-4 h-4 text-violet-500" />
                            Dashboard
                        </Link>
                        <Link
                            href="/studio"
                            :class="[
                                'px-3.5 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-2',
                                $page.url.startsWith('/studio')
                                    ? 'text-violet-700 dark:text-violet-300 bg-violet-100/60 dark:bg-violet-900/30'
                                    : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'
                            ]"
                        >
                            <Video class="w-4 h-4 text-orange-500" />
                            Studio Buat Video
                        </Link>
                        <Link
                            href="/cartoon-prompts"
                            :class="[
                                'px-3.5 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-2',
                                $page.url.startsWith('/cartoon-prompts')
                                    ? 'text-violet-700 dark:text-violet-300 bg-violet-100/60 dark:bg-violet-900/30'
                                    : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'
                            ]"
                        >
                            <Clapperboard class="w-4 h-4 text-amber-500" />
                            Prompt Kartun
                        </Link>
                        <Link
                            href="/library"
                            :class="[
                                'px-3.5 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-2',
                                $page.url.startsWith('/library')
                                    ? 'text-violet-700 dark:text-violet-300 bg-violet-100/60 dark:bg-violet-900/30'
                                    : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'
                            ]"
                        >
                            <Film class="w-4 h-4 text-teal-500" />
                            Pustaka Video
                        </Link>
                        <Link
                            href="/series"
                            :class="[
                                'px-3.5 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-2',
                                $page.url.startsWith('/series')
                                    ? 'text-violet-700 dark:text-violet-300 bg-violet-100/60 dark:bg-violet-900/30'
                                    : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'
                            ]"
                        >
                            <Sparkles class="w-4 h-4 text-purple-500" />
                            Seri Video
                        </Link>
                        <Link
                            href="/schedule"
                            :class="[
                                'px-3.5 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-2',
                                $page.url.startsWith('/schedule')
                                    ? 'text-violet-700 dark:text-violet-300 bg-violet-100/60 dark:bg-violet-900/30'
                                    : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'
                            ]"
                        >
                            <Calendar class="w-4 h-4 text-amber-500" />
                            Jadwal
                        </Link>
                    </nav>
                </div>

                <!-- Right Toolbar -->
                <div class="flex items-center gap-3">
                    <!-- Quota Pill -->
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-violet-50 dark:bg-violet-950/60 border border-violet-200 dark:border-violet-800 text-xs font-semibold text-violet-800 dark:text-violet-200">
                        <span class="w-2 h-2 rounded-full bg-violet-500 animate-pulse"></span>
                        <span>Sisa Kuota: {{ page.props.auth?.user?.remaining_quota ?? 0 }}</span>
                    </div>

                    <!-- Dark Mode Toggle -->
                    <button
                        type="button"
                        @click="toggleDarkMode"
                        class="p-2.5 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        aria-label="Mode gelap/terang"
                    >
                        <Sun v-if="isDark" class="w-5 h-5 text-amber-400" />
                        <Moon v-else class="w-5 h-5 text-violet-600" />
                    </button>

                    <!-- Admin Link if Admin -->
                    <Link
                        v-if="page.props.auth?.user?.is_admin"
                        href="/admin/settings/gemini"
                        class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 text-xs font-bold uppercase tracking-wider hover:bg-amber-500/20 transition"
                    >
                        <Shield class="w-3.5 h-3.5" />
                        Admin
                    </Link>

                    <!-- User Dropdown Menu -->
                    <div class="relative">
                        <button
                            type="button"
                            @click="userMenuOpen = !userMenuOpen"
                            class="flex items-center gap-2.5 p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition text-left"
                            aria-label="Menu akun"
                        >
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-violet-600 to-amber-500 flex items-center justify-center text-white font-bold text-xs uppercase shadow-sm">
                                {{ page.props.auth?.user?.name.charAt(0) ?? 'U' }}
                            </div>
                            <span class="hidden md:block text-xs font-semibold text-slate-800 dark:text-slate-200 max-w-[120px] truncate">
                                {{ page.props.auth?.user?.name }}
                            </span>
                            <ChevronDown class="w-3.5 h-3.5 text-slate-400" />
                        </button>

                        <div
                            v-if="userMenuOpen"
                            class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 py-2 z-50 animate-in fade-in slide-in-from-top-2"
                        >
                            <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-800">
                                <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                    {{ page.props.auth?.user?.name }}
                                </p>
                                <p class="text-[11px] text-slate-500 truncate">
                                    {{ page.props.auth?.user?.email }}
                                </p>
                            </div>

                            <Link
                                v-if="page.props.auth?.user?.is_admin"
                                href="/admin/settings/gemini"
                                class="flex items-center gap-2 px-4 py-2.5 text-xs font-semibold text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/30 transition"
                            >
                                <Shield class="w-4 h-4" />
                                Pengaturan Gemini Admin
                            </Link>

                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                class="w-full flex items-center gap-2 px-4 py-2.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition text-left"
                            >
                                <LogOut class="w-4 h-4" />
                                Keluar Akun
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Slot -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <slot />
        </main>
    </div>
</template>
