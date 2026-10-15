<script setup lang="ts">
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Sparkles, Sun, Moon, Menu, X, Compass, Video, BookOpen, Layers } from 'lucide-vue-next';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const mobileMenuOpen = ref(false);

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
    <header class="sticky top-0 z-40 w-full border-b border-slate-200/70 dark:border-slate-800/80 bg-[#FAF9F6]/90 dark:bg-[#0B0F19]/90 backdrop-blur-md transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <!-- Brand Logo -->
            <Link href="/" class="flex items-center gap-3 group">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-violet-600 via-purple-600 to-amber-500 p-0.5 shadow-md shadow-violet-500/20 group-hover:scale-105 transition-transform">
                    <div class="w-full h-full bg-[#FAF9F6] dark:bg-[#0B0F19] rounded-[14px] flex items-center justify-center">
                        <Sparkles class="w-5 h-5 text-violet-600 dark:text-violet-400 group-hover:rotate-12 transition-transform" />
                    </div>
                </div>
                <div>
                    <span class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white font-serif">
                        Ruang Dharma
                    </span>
                    <span class="block text-[11px] font-medium text-violet-600 dark:text-violet-400 uppercase tracking-widest">
                        Cerita 30 Detik
                    </span>
                </div>
            </Link>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center gap-1 lg:gap-2">
                <Link
                    href="/"
                    :class="[
                        'px-4 py-2 rounded-xl text-sm font-semibold transition',
                        $page.component === 'Landing/Index'
                            ? 'text-violet-700 dark:text-violet-300 bg-violet-100/60 dark:bg-violet-900/30'
                            : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'
                    ]"
                >
                    Beranda
                </Link>
                <Link
                    href="/studio"
                    :class="[
                        'px-4 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-1.5',
                        $page.component === 'Studio/Index'
                            ? 'text-violet-700 dark:text-violet-300 bg-violet-100/60 dark:bg-violet-900/30'
                            : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'
                    ]"
                >
                    <Video class="w-4 h-4 text-violet-500" />
                    Studio AI
                </Link>
                <Link
                    href="/gallery"
                    :class="[
                        'px-4 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-1.5',
                        $page.component === 'Gallery/Index'
                            ? 'text-violet-700 dark:text-violet-300 bg-violet-100/60 dark:bg-violet-900/30'
                            : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'
                    ]"
                >
                    <Compass class="w-4 h-4 text-orange-500" />
                    Galeri Komunitas
                </Link>
                <Link
                    href="/glossary"
                    :class="[
                        'px-4 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-1.5',
                        $page.component === 'Glossary/Index'
                            ? 'text-violet-700 dark:text-violet-300 bg-violet-100/60 dark:bg-violet-900/30'
                            : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'
                    ]"
                >
                    <BookOpen class="w-4 h-4 text-teal-500" />
                    Kamus Hindu
                </Link>
            </nav>

            <!-- Actions -->
            <div class="hidden md:flex items-center gap-3">
                <!-- Theme Toggle -->
                <button
                    type="button"
                    @click="toggleDarkMode"
                    class="p-2.5 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    aria-label="Ganti mode gelap/terang"
                >
                    <Sun v-if="isDark" class="w-5 h-5 text-amber-400" />
                    <Moon v-else class="w-5 h-5 text-violet-600" />
                </button>

                <template v-if="page.props.auth?.user">
                    <Link
                        v-if="page.props.auth.user.is_admin"
                        href="/admin/settings/gemini"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 transition"
                    >
                        Panel Admin
                    </Link>
                    <Link
                        href="/dashboard"
                        class="btn-primary px-5 py-2.5 rounded-xl text-sm font-bold shadow-md shadow-violet-600/20"
                    >
                        Dashboard
                    </Link>
                </template>
                <template v-else>
                    <Link
                        href="/login"
                        class="px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                        Masuk
                    </Link>
                    <Link
                        href="/register"
                        class="btn-primary px-5 py-2.5 rounded-xl text-sm font-bold shadow-md shadow-violet-600/20"
                    >
                        Buat Video Pertamamu
                    </Link>
                </template>
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center gap-2 md:hidden">
                <button
                    type="button"
                    @click="toggleDarkMode"
                    class="p-2 rounded-xl text-slate-500 dark:text-slate-400"
                    aria-label="Mode gelap"
                >
                    <Sun v-if="isDark" class="w-5 h-5 text-amber-400" />
                    <Moon v-else class="w-5 h-5 text-violet-600" />
                </button>
                <button
                    type="button"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="p-2 rounded-xl text-slate-700 dark:text-slate-200"
                    aria-label="Buka menu navigasi"
                >
                    <X v-if="mobileMenuOpen" class="w-6 h-6" />
                    <Menu v-else class="w-6 h-6" />
                </button>
            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div v-if="mobileMenuOpen" class="md:hidden border-b border-slate-200 dark:border-slate-800 bg-[#FAF9F6] dark:bg-[#0B0F19] px-4 pt-2 pb-6 space-y-2">
            <Link href="/" class="block px-4 py-3 rounded-xl text-base font-semibold text-slate-800 dark:text-slate-100">
                Beranda
            </Link>
            <Link href="/studio" class="block px-4 py-3 rounded-xl text-base font-semibold text-slate-800 dark:text-slate-100">
                Studio AI
            </Link>
            <Link href="/gallery" class="block px-4 py-3 rounded-xl text-base font-semibold text-slate-800 dark:text-slate-100">
                Galeri Komunitas
            </Link>
            <Link href="/glossary" class="block px-4 py-3 rounded-xl text-base font-semibold text-slate-800 dark:text-slate-100">
                Kamus Hindu
            </Link>

            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex flex-col gap-2">
                <template v-if="page.props.auth?.user">
                    <Link href="/dashboard" class="btn-primary w-full text-center py-3 rounded-xl font-bold">
                        Buka Dashboard
                    </Link>
                </template>
                <template v-else>
                    <Link href="/login" class="w-full text-center py-3 rounded-xl font-semibold border border-slate-300 dark:border-slate-700">
                        Masuk
                    </Link>
                    <Link href="/register" class="btn-primary w-full text-center py-3 rounded-xl font-bold">
                        Buat Video Pertamamu
                    </Link>
                </template>
            </div>
        </div>
    </header>
</template>
