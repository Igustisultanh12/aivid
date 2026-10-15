<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import VideoPlayer from '@/Components/VideoPlayer.vue';
import {
    Sparkles,
    ArrowRight,
    Play,
    Clock,
    ShieldCheck,
    Cpu,
    BookOpen,
    Layers,
    Share2,
    CheckCircle2,
    HelpCircle,
    ChevronDown,
    Flower2,
    Sun,
    Flame,
    Moon,
    HeartHandshake,
    Scale,
    Video as VideoIcon
} from 'lucide-vue-next';
import type { Topic, Video } from '@/types';
import gsap from 'gsap';

const props = defineProps<{
    popularTopics: Topic[];
    demoVideo?: Video | null;
    faqs: Array<{ question: string; answer: string }>;
}>();

const activeFaq = ref<number | null>(0);

const toggleFaq = (idx: number) => {
    activeFaq.value = activeFaq.value === idx ? null : idx;
};

const getTopicIcon = (iconName: string) => {
    switch (iconName) {
        case 'Flower2': return Flower2;
        case 'Sun': return Sun;
        case 'Flame': return Flame;
        case 'Moon': return Moon;
        case 'HeartHandshake': return HeartHandshake;
        case 'Scale': return Scale;
        case 'BookOpen': return BookOpen;
        default: return Sparkles;
    }
};

onMounted(() => {
    // Subtle GSAP entrance animations
    gsap.from('.hero-headline', {
        opacity: 0,
        y: 30,
        duration: 0.9,
        ease: 'power3.out'
    });
    gsap.from('.hero-subheadline', {
        opacity: 0,
        y: 20,
        duration: 0.8,
        delay: 0.2,
        ease: 'power3.out'
    });
    gsap.from('.hero-cta', {
        opacity: 0,
        y: 15,
        duration: 0.8,
        delay: 0.4,
        ease: 'power3.out'
    });
});
</script>

<template>
    <div class="min-h-screen flex flex-col bg-[#FAF9F6] dark:bg-[#0B0F19] text-slate-800 dark:text-slate-100 transition-colors">
        <Head title="Cerita Bermakna dalam 30 Detik - Video Edukasi Filosofi Hindu AI" />

        <Navbar />

        <!-- HERO SECTION -->
        <section class="relative pt-12 pb-24 lg:pt-20 lg:pb-32 overflow-hidden bg-mesh-purple">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                    <!-- Left Hero Copy -->
                    <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                        <!-- Cultural Badge -->
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-violet-100/80 dark:bg-violet-950/60 border border-violet-200 dark:border-violet-800 text-xs font-bold text-violet-800 dark:text-violet-300 shadow-sm backdrop-blur-sm">
                            <Sparkles class="w-3.5 h-3.5 text-violet-600 dark:text-violet-400" />
                            <span>Kearifan Tradisi Hindu Bali & Nusantara</span>
                        </div>

                        <!-- Headline -->
                        <h1 class="hero-headline text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight font-serif text-slate-900 dark:text-white leading-[1.12]">
                            Cerita bermakna dalam <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-600 via-purple-600 to-amber-500">30 detik.</span>
                        </h1>

                        <!-- Subheadline -->
                        <p class="hero-subheadline text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                            Jelajahi filosofi Hindu dan ubah rasa ingin tahumu menjadi cerita visual yang bermakna. Diproduksi secara presisi dengan kecerdasan Google Gemini dan sentuhan estetika budaya yang menghormati tradisi.
                        </p>

                        <!-- CTA Group -->
                        <div class="hero-cta flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-4">
                            <Link
                                href="/register"
                                class="btn-primary w-full sm:w-auto px-8 py-4 rounded-2xl font-bold text-base flex items-center justify-center gap-2.5 shadow-xl shadow-violet-600/30 group"
                            >
                                <span>Buat Video Pertamamu</span>
                                <ArrowRight class="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                            </Link>

                            <Link
                                href="/gallery"
                                class="w-full sm:w-auto px-6 py-4 rounded-2xl font-bold text-sm bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition text-slate-700 dark:text-slate-200 flex items-center justify-center gap-2 shadow-sm"
                            >
                                <Play class="w-4 h-4 text-orange-500 fill-current" />
                                <span>Lihat Karya Komunitas</span>
                            </Link>
                        </div>

                        <!-- Trust Features -->
                        <div class="pt-6 grid grid-cols-3 gap-4 border-t border-slate-200/60 dark:border-slate-800/60 text-left">
                            <div class="space-y-1">
                                <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1">
                                    <Clock class="w-3.5 h-3.5 text-violet-500" /> Tepat 30s
                                </span>
                                <p class="text-[11px] text-slate-500 leading-snug">Durasi ideal untuk media sosial anak muda</p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1">
                                    <BookOpen class="w-3.5 h-3.5 text-orange-500" /> Sumber Valid
                                </span>
                                <p class="text-[11px] text-slate-500 leading-snug">Berlandaskan lontar dan pedoman PHDI</p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1">
                                    <Cpu class="w-3.5 h-3.5 text-teal-500" /> Google Gemini
                                </span>
                                <p class="text-[11px] text-slate-500 leading-snug">REST API resmi terkonfigurasi mandiri</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Demo Video Card -->
                    <div class="lg:col-span-5 flex justify-center">
                        <div class="w-full max-w-sm relative">
                            <!-- Glow decorative backdrop -->
                            <div class="absolute -inset-2 bg-gradient-to-r from-violet-600 via-orange-500 to-amber-400 rounded-[34px] blur-xl opacity-30 dark:opacity-40 -z-10"></div>

                            <div class="card-glass rounded-3xl p-3 shadow-2xl border border-white/80 dark:border-slate-800">
                                <div class="flex items-center justify-between px-3 py-2 border-b border-slate-100 dark:border-slate-800 mb-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                            Contoh Hasil Video
                                        </span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 uppercase tracking-wider">
                                        Demo Resmi
                                    </span>
                                </div>

                                <!-- Custom Player Component -->
                                <VideoPlayer
                                    v-if="demoVideo"
                                    :src="route('videos.stream', demoVideo.id)"
                                    :poster="demoVideo.thumbnail_path ? `/storage/${demoVideo.thumbnail_path}` : undefined"
                                    :subtitles-url="route('videos.download', { video: demoVideo.id, type: 'vtt' })"
                                    aspect-ratio="9:16"
                                />
                                <div v-else class="aspect-[9/16] bg-slate-900 rounded-2xl flex flex-col items-center justify-center p-6 text-center text-slate-400">
                                    <VideoIcon class="w-12 h-12 text-violet-500 mb-3" />
                                    <p class="text-xs">Video percontohan sedang disiapkan.</p>
                                </div>

                                <div class="mt-3 px-2 py-1">
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate">
                                        {{ demoVideo?.title ?? 'Makna Canang Sari: Ketulusan Hati' }}
                                    </h3>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2 leading-relaxed">
                                        {{ demoVideo?.summary_text ?? 'Membahas satu gagasan utama ketulusan dan harmoni lima indera dalam 30 detik.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- POPULAR TOPICS SECTION -->
        <section class="py-20 bg-white dark:bg-[#0B0F19] border-y border-slate-200/70 dark:border-slate-800/70">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-14">
                    <span class="text-xs font-bold uppercase tracking-wider text-violet-600 dark:text-violet-400 font-serif">
                        Tema Populer
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white mt-2 font-serif">
                        Satu Ide Inti, Penjelasan Jernih
                    </h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-3 leading-relaxed">
                        Setiap video fokus menyingkap satu gagasan utama agar mudah dipahami, relevan dengan dinamika anak muda, dan tetap menjunjung tinggi kehormatan agama.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="topic in popularTopics"
                        :key="topic.id"
                        class="p-6 rounded-3xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800/80 hover:border-violet-500/50 hover:shadow-xl hover:-translate-y-1 transition duration-200 flex flex-col justify-between group"
                    >
                        <div>
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-violet-600 to-orange-500 p-0.5 shadow-sm mb-4">
                                <div class="w-full h-full bg-white dark:bg-slate-900 rounded-[14px] flex items-center justify-center text-violet-600 dark:text-violet-400">
                                    <component :is="getTopicIcon(topic.icon_name)" class="w-6 h-6" />
                                </div>
                            </div>

                            <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">
                                {{ topic.category }}
                            </span>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mt-1 group-hover:text-violet-600 dark:group-hover:text-violet-400 transition font-serif">
                                {{ topic.title }}
                            </h3>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-2 leading-relaxed">
                                {{ topic.description }}
                            </p>
                        </div>

                        <div class="pt-5 mt-4 border-t border-slate-200/60 dark:border-slate-800/60 flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-slate-500">
                                {{ topic.cultural_context }}
                            </span>
                            <Link
                                :href="`/studio?topic_id=${topic.id}`"
                                class="text-xs font-bold text-violet-600 dark:text-violet-400 group-hover:underline flex items-center gap-1"
                            >
                                Buat Video Ini &rarr;
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- HOW IT WORKS TIMELINE -->
        <section class="py-24 bg-[#FAF9F6] dark:bg-slate-950/60 relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <span class="text-xs font-bold uppercase tracking-wider text-orange-600 dark:text-orange-400 font-serif">
                        Alur Kerja Studio
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white mt-2 font-serif">
                        Dari Ide Menjadi Cerita 30 Detik
                    </h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">
                        Alur produksi transparan dengan kendali penuh sebelum render final.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm relative">
                        <div class="w-8 h-8 rounded-full bg-violet-600 text-white font-bold text-xs flex items-center justify-center mb-4">
                            1
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white font-serif">
                            Pilih Topik & Atur Gaya
                        </h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-2 leading-relaxed">
                            Pilih tema tradisi Hindu atau masukkan ide khusus. Tentukan target penonton, nada suara narator, dan rasio video (9:16, 16:9, 1:1).
                        </p>
                    </div>

                    <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm relative">
                        <div class="w-8 h-8 rounded-full bg-orange-600 text-white font-bold text-xs flex items-center justify-center mb-4">
                            2
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white font-serif">
                            Naskah & Storyboard Terstruktur
                        </h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-2 leading-relaxed">
                            Gemini menyusun naskah 30 detik (Hook 0-3s, Inti 3-23s, Refleksi 23-30s). Anda dapat meninjau dan mengedit teks adegan terlebih dahulu.
                        </p>
                    </div>

                    <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm relative">
                        <div class="w-8 h-8 rounded-full bg-teal-600 text-white font-bold text-xs flex items-center justify-center mb-4">
                            3
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white font-serif">
                            Produksi Visual & Narasi
                        </h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-2 leading-relaxed">
                            AI menghasilkan gambar bernuansa seni Bali dan audio narasi bahasa Indonesia yang selaras dengan durasi setiap adegan.
                        </p>
                    </div>

                    <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm relative">
                        <div class="w-8 h-8 rounded-full bg-amber-600 text-white font-bold text-xs flex items-center justify-center mb-4">
                            4
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white font-serif">
                            Render & Unduh Video
                        </h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-2 leading-relaxed">
                            FFmpeg menggabungkan motion visual, audio, dan subtitle kontras. Periksa durasi dengan FFprobe, lalu unduh MP4 dan transkrip.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ SECTION -->
        <section class="py-20 bg-white dark:bg-[#0B0F19] border-t border-slate-200/70 dark:border-slate-800/70">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-xs font-bold uppercase tracking-wider text-violet-600 dark:text-violet-400 font-serif">
                        Pertanyaan Umum
                    </span>
                    <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white mt-1 font-serif">
                        Seputar Ruang Dharma
                    </h2>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(faq, idx) in faqs"
                        :key="idx"
                        class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden transition"
                    >
                        <button
                            type="button"
                            @click="toggleFaq(idx)"
                            class="w-full px-6 py-4 text-left flex items-center justify-between gap-4 font-bold text-sm sm:text-base text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-900/50 transition"
                        >
                            <span>{{ faq.question }}</span>
                            <ChevronDown
                                :class="[
                                    'w-5 h-5 text-slate-400 transition-transform duration-200 shrink-0',
                                    activeFaq === idx ? 'rotate-180 text-violet-600' : ''
                                ]"
                            />
                        </button>
                        <div
                            v-if="activeFaq === idx"
                            class="px-6 pb-5 pt-1 text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed border-t border-slate-100 dark:border-slate-800/60"
                        >
                            {{ faq.answer }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FINAL CTA SECTION -->
        <section class="py-20 bg-gradient-to-tr from-violet-900 via-purple-900 to-slate-950 text-white text-center relative overflow-hidden">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 space-y-6">
                <h2 class="text-3xl sm:text-4xl font-extrabold font-serif">
                    Mulai Cerita Bermakna Pertamamu Hari Ini
                </h2>
                <p class="text-sm sm:text-base text-purple-200 max-w-xl mx-auto leading-relaxed">
                    Satu ide filosofi, 30 detik yang mengubah pandangan. Buat video edukasimu sekarang secara gratis.
                </p>
                <div class="pt-2">
                    <Link
                        href="/register"
                        class="btn-coral px-8 py-4 rounded-2xl font-bold text-base inline-flex items-center gap-2 shadow-2xl shadow-orange-600/40"
                    >
                        <span>Buat Video Pertamamu</span>
                        <ArrowRight class="w-5 h-5" />
                    </Link>
                </div>
            </div>
        </section>

        <Footer />
    </div>
</template>
