<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Sparkles, Heart } from 'lucide-vue-next';
</script>

<template>
    <footer class="border-t border-slate-200/80 dark:border-slate-800/80 bg-white dark:bg-[#0B0F19] transition-colors mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10">
                <!-- Brand col -->
                <div class="md:col-span-2 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-violet-600 to-amber-500 p-0.5 shadow-md">
                            <div class="w-full h-full bg-white dark:bg-[#0B0F19] rounded-[10px] flex items-center justify-center">
                                <Sparkles class="w-4 h-4 text-violet-600 dark:text-violet-400" />
                            </div>
                        </div>
                        <span class="text-xl font-bold font-serif text-slate-900 dark:text-white">
                            Ruang Dharma
                        </span>
                    </div>

                    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed max-w-md">
                        Platform edukasi video AI 30 detik untuk menjelajahi keindahan filosofi, etika, dan tradisi Hindu di Indonesia dan Bali. Dirancang untuk generasi muda dengan penyampaian hangat dan penuh rasa hormat.
                    </p>

                    <div class="text-xs text-slate-500 dark:text-slate-500 pt-2 flex items-center gap-1.5">
                        <span>Dibangun dengan rasa hormat pada kearifan leluhur Nusantara</span>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-slate-100 mb-4 font-serif">
                        Eksplorasi
                    </h4>
                    <ul class="space-y-2.5 text-sm">
                        <li>
                            <Link href="/studio" class="text-slate-600 dark:text-slate-400 hover:text-violet-600 dark:hover:text-violet-400 transition">
                                Studio Pembuatan
                            </Link>
                        </li>
                        <li>
                            <Link href="/gallery" class="text-slate-600 dark:text-slate-400 hover:text-violet-600 dark:hover:text-violet-400 transition">
                                Galeri Komunitas
                            </Link>
                        </li>
                        <li>
                            <Link href="/glossary" class="text-slate-600 dark:text-slate-400 hover:text-violet-600 dark:hover:text-violet-400 transition">
                                Kamus Istilah Hindu
                            </Link>
                        </li>
                        <li>
                            <Link href="/library" class="text-slate-600 dark:text-slate-400 hover:text-violet-600 dark:hover:text-violet-400 transition">
                                Pustaka Video Saya
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Technical & Compliance -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-slate-100 mb-4 font-serif">
                        Integrasi & Etika
                    </h4>
                    <ul class="space-y-2.5 text-sm text-slate-600 dark:text-slate-400">
                        <li>Google Gemini REST API</li>
                        <li>FFmpeg High-Definition Render</li>
                        <li>Sumber Rujukan PHDI & Lontar</li>
                        <li>Enkripsi Kredensial Database</li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-slate-200/60 dark:border-slate-800/60 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <p>
                    &copy; 2026 Ruang Dharma. Semua hak dilindungi.
                </p>
                <p class="text-slate-400">
                    Cerita bermakna dalam 30 detik.
                </p>
            </div>
        </div>
    </footer>
</template>
