<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import { BookOpen, Search, Sparkles, Volume2 } from 'lucide-vue-next';
import type { GlossaryTerm } from '@/types';

const props = defineProps<{
    terms: {
        data: GlossaryTerm[];
        links: any[];
        total: number;
    };
    filters: {
        search?: string;
    };
}>();

const search = ref(props.filters.search || '');
let searchTimeout: any = null;

const applySearch = () => {
    router.get(
        '/glossary',
        { search: search.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

watch(search, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applySearch, 400);
});
</script>

<template>
    <div class="min-h-screen flex flex-col bg-[#FAF9F6] dark:bg-[#0B0F19] text-slate-800 dark:text-slate-100 transition-colors">
        <Head title="Kamus Istilah Filosofi & Tradisi Hindu - Ruang Dharma" />

        <Navbar />

        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
            <div class="text-center max-w-2xl mx-auto space-y-3">
                <span class="text-xs font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400 font-serif">
                    Glosarium & Etimologi
                </span>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white font-serif">
                    Kamus Istilah Hindu
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    Panduan istilah Sansekerta dan Kawi/Bali mengenai filosofi, kosmologi, etika moral, dan sarana tradisi Hindu di Indonesia.
                </p>
            </div>

            <!-- Search input -->
            <div class="max-w-xl mx-auto">
                <div class="relative">
                    <Search class="w-4 h-4 absolute left-3.5 top-3.5 text-slate-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari istilah: Dharma, Karma, Canang, Tirtha..."
                        class="w-full pl-10 pr-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500 shadow-sm"
                    />
                </div>
            </div>

            <!-- Terms Grid -->
            <div v-if="terms.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="term in terms.data"
                    :key="term.id"
                    class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-xl transition-all flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold font-mono text-teal-600 dark:text-teal-400 uppercase tracking-wider">
                                {{ term.phonetic_pronunciation || term.term }}
                            </span>
                            <span class="text-[10px] text-slate-400">
                                Istilah Otentik
                            </span>
                        </div>

                        <h3 class="text-xl font-bold font-serif text-slate-900 dark:text-white">
                            {{ term.term }}
                        </h3>

                        <div v-if="term.sanskrit_or_balinese_origin" class="text-xs text-amber-600 dark:text-amber-400 font-medium mt-1">
                            {{ term.sanskrit_or_balinese_origin }}
                        </div>

                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-3 leading-relaxed">
                            {{ term.meaning }}
                        </p>

                        <div v-if="term.context" class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 leading-snug">
                            <strong class="text-slate-700 dark:text-slate-300">Konteks:</strong> {{ term.context }}
                        </div>
                    </div>

                    <div v-if="term.related_traditions" class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-[10px] text-violet-600 dark:text-violet-400 font-semibold">
                        Terkait: {{ term.related_traditions }}
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-12 text-slate-500 text-xs">
                Istilah yang Anda cari belum ditemukan di kamus.
            </div>
        </main>

        <Footer />
    </div>
</template>
