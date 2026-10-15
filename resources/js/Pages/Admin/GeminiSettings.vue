<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Sparkles,
    Key,
    CheckCircle2,
    AlertCircle,
    Cpu,
    Video,
    Image,
    Volume2,
    Type,
    RefreshCw,
    Trash2,
    Save,
    ExternalLink,
    HelpCircle,
    Info,
    Loader2
} from 'lucide-vue-next';

const props = defineProps<{
    settings: {
        is_configured: boolean;
        masked_key: string | null;
        text_model: string;
        image_model: string;
        tts_model: string;
        video_model: string;
        voice_name: string;
        max_video_per_user: number;
        daily_render_limit: number;
        last_tested_at: string | null;
        last_test_result: any;
    };
    modelOptions: {
        text: Array<{ id: string; name: string }>;
        image: Array<{ id: string; name: string }>;
        tts: Array<{ id: string; name: string }>;
        video: Array<{ id: string; name: string }>;
        voices: Array<{ id: string; name: string }>;
    };
}>();

// Form to save/update API Key
const keyForm = useForm({
    api_key: '',
});

// Form to update Models & Limits
const modelForm = useForm({
    text_model: props.settings.text_model,
    image_model: props.settings.image_model,
    tts_model: props.settings.tts_model,
    video_model: props.settings.video_model,
    voice_name: props.settings.voice_name,
    max_video_per_user: props.settings.max_video_per_user,
    daily_render_limit: props.settings.daily_render_limit,
});

// Separate Test States
const testingConnection = ref(false);
const testConnectionResult = ref<any>(props.settings.last_test_result);

const testingText = ref(false);
const testTextResult = ref<any>(null);

const testingImage = ref(false);
const testImageResult = ref<any>(null);

const testingVideo = ref(false);
const testVideoResult = ref<any>(null);

const submitKey = () => {
    keyForm.post('/admin/settings/gemini/key', {
        onSuccess: () => {
            keyForm.reset();
        }
    });
};

const removeKey = () => {
    if (confirm('Apakah Anda yakin ingin memutus koneksi Google Gemini API? Fitur AI akan beralih ke mode offline.')) {
        router.delete('/admin/settings/gemini/key');
    }
};

const submitModels = () => {
    modelForm.post('/admin/settings/gemini/models');
};

const runTestConnection = async () => {
    testingConnection.value = true;
    testConnectionResult.value = null;
    try {
        const res = await fetch('/admin/settings/gemini/test-connection', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                'Content-Type': 'application/json',
            }
        });
        testConnectionResult.value = await res.json();
    } finally {
        testingConnection.value = false;
    }
};

const runTestText = async () => {
    testingText.value = true;
    testTextResult.value = null;
    try {
        const res = await fetch('/admin/settings/gemini/test-text', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                'Content-Type': 'application/json',
            }
        });
        testTextResult.value = await res.json();
    } finally {
        testingText.value = false;
    }
};

const runTestImage = async () => {
    if (!confirm('Uji pembuatan gambar Imagen 3 dapat mengonsumsi kuota atau biaya kredit Google AI Studio. Lanjutkan pengujian?')) {
        return;
    }
    testingImage.value = true;
    testImageResult.value = null;
    try {
        const res = await fetch('/admin/settings/gemini/test-image', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                'Content-Type': 'application/json',
            }
        });
        testImageResult.value = await res.json();
    } finally {
        testingImage.value = false;
    }
};

const runTestVideo = async () => {
    testingVideo.value = true;
    testVideoResult.value = null;
    try {
        const res = await fetch('/admin/settings/gemini/test-video', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                'Content-Type': 'application/json',
            }
        });
        testVideoResult.value = await res.json();
    } finally {
        testingVideo.value = false;
    }
};
</script>

<template>
    <AdminLayout title="Integrasi Google Gemini API">
        <Head title="Pengaturan Google Gemini API" />

        <div class="space-y-8 max-w-5xl">
            <!-- Header Card with Connection Status -->
            <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div :class="[
                        'w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 shadow-md',
                        settings.is_configured
                            ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600'
                            : 'bg-amber-100 dark:bg-amber-950/60 text-amber-600'
                    ]">
                        <Key class="w-7 h-7" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span :class="[
                                'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                settings.is_configured
                                    ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300'
                                    : 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300'
                            ]">
                                {{ settings.is_configured ? 'Terkoneksi & Terenkripsi' : 'Belum Dikonfigurasi' }}
                            </span>
                            <span v-if="settings.last_tested_at" class="text-[11px] text-slate-400">
                                Diuji: {{ new Date(settings.last_tested_at).toLocaleString('id-ID') }}
                            </span>
                        </div>
                        <h2 class="text-xl font-bold font-serif text-slate-900 dark:text-white mt-1">
                            Google Gemini REST API
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Kunci API disimpan di database menggunakan enkripsi AES-256 Laravel. Tidak memerlukan berkas .env.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto">
                    <button
                        type="button"
                        @click="runTestConnection"
                        :disabled="testingConnection || !settings.is_configured"
                        class="btn-primary px-4 py-2.5 rounded-xl font-bold text-xs inline-flex items-center gap-2 shadow-md disabled:opacity-50"
                    >
                        <Loader2 v-if="testingConnection" class="w-4 h-4 animate-spin" />
                        <RefreshCw v-else class="w-4 h-4" />
                        <span>Uji Koneksi API</span>
                    </button>

                    <button
                        v-if="settings.is_configured"
                        type="button"
                        @click="removeKey"
                        class="px-3.5 py-2.5 rounded-xl border border-rose-300 dark:border-rose-900 text-rose-600 text-xs font-semibold hover:bg-rose-50 dark:hover:bg-rose-950/30 transition"
                        title="Putus Koneksi"
                    >
                        <Trash2 class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- SECTION 1: Kunci API & Masked View -->
            <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold font-serif text-slate-900 dark:text-white">
                            Kunci Akses API (API Key)
                        </h3>
                        <p class="text-xs text-slate-500">
                            Dapatkan API Key gratis di Google AI Studio (aistudio.google.com).
                        </p>
                    </div>
                    <a
                        href="https://aistudio.google.com/app/apikey"
                        target="_blank"
                        rel="noopener"
                        class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1"
                    >
                        <span>Buka Google AI Studio</span>
                        <ExternalLink class="w-3.5 h-3.5" />
                    </a>
                </div>

                <!-- Display Masked Key if configured -->
                <div v-if="settings.is_configured" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Kunci Aktif Saat Ini</span>
                        <span class="font-mono text-sm font-bold text-slate-900 dark:text-white">{{ settings.masked_key }}</span>
                    </div>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                        <CheckCircle2 class="w-4 h-4" />
                        Tersimpan Aman
                    </span>
                </div>

                <!-- Input to save or replace key -->
                <form @submit.prevent="submitKey" class="space-y-3">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        {{ settings.is_configured ? 'Ganti dengan Kunci API Baru' : 'Masukkan API Key Gemini' }}
                    </label>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input
                            v-model="keyForm.api_key"
                            type="password"
                            required
                            placeholder="AIzaSy..."
                            class="flex-1 px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-mono focus:ring-2 focus:ring-violet-500"
                        />
                        <button
                            type="submit"
                            :disabled="keyForm.processing"
                            class="btn-primary px-6 py-2.5 rounded-xl font-bold text-xs whitespace-nowrap disabled:opacity-50"
                        >
                            <Loader2 v-if="keyForm.processing" class="w-4 h-4 animate-spin inline mr-1" />
                            Simpan & Verifikasi Kunci
                        </button>
                    </div>
                    <p v-if="keyForm.errors.api_key" class="text-xs text-rose-500 font-medium">
                        {{ keyForm.errors.api_key }}
                    </p>
                </form>

                <!-- Connection Test Result Banner -->
                <div
                    v-if="testConnectionResult"
                    :class="[
                        'p-4 rounded-2xl border text-xs leading-relaxed space-y-2',
                        testConnectionResult.success
                            ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 text-emerald-900 dark:text-emerald-200'
                            : 'bg-rose-50 dark:bg-rose-950/40 border-rose-300 text-rose-900 dark:text-rose-200'
                    ]"
                >
                    <div class="flex items-center gap-2 font-bold">
                        <CheckCircle2 v-if="testConnectionResult.success" class="w-4 h-4 text-emerald-600" />
                        <AlertCircle v-else class="w-4 h-4 text-rose-600" />
                        <span>{{ testConnectionResult.message }}</span>
                        <span v-if="testConnectionResult.response_time_ms" class="font-mono text-[10px] opacity-80">
                            ({{ testConnectionResult.response_time_ms }} ms)
                        </span>
                    </div>

                    <div v-if="testConnectionResult.capabilities" class="grid grid-cols-3 gap-2 pt-2 border-t border-emerald-200/60 dark:border-emerald-800/60 text-[11px]">
                        <span>Teks (Gemini): <strong>{{ testConnectionResult.capabilities.text_generation ? 'Aktif' : 'Nonaktif' }}</strong></span>
                        <span>Gambar (Imagen): <strong>{{ testConnectionResult.capabilities.image_generation ? 'Aktif' : 'Perlu Aktivasi' }}</strong></span>
                        <span>Video (Veo): <strong>{{ testConnectionResult.capabilities.video_generation ? 'Tersedia' : 'Akses Khusus' }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Pengujian Mandiri Terpisah (Text, Image, Video) -->
            <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                <div>
                    <h3 class="text-base font-bold font-serif text-slate-900 dark:text-white">
                        Pengujian Mandiri Kapabilitas Media
                    </h3>
                    <p class="text-xs text-slate-500">
                        Uji masing-masing kemampuan secara terpisah untuk memverifikasi kesiapan model pada akun Google Anda.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Test Text -->
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 flex flex-col justify-between space-y-3">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 text-violet-600 font-bold text-xs">
                                <Type class="w-4 h-4" />
                                <span>Pembuatan Teks (Naskah)</span>
                            </div>
                            <p class="text-[11px] text-slate-500">
                                Menguji pemanggilan prompt Gemini 2.5/Flash untuk pembuatan naskah 30 detik.
                            </p>
                        </div>
                        <div>
                            <button
                                type="button"
                                @click="runTestText"
                                :disabled="testingText || !settings.is_configured"
                                class="w-full py-2 rounded-xl bg-violet-600 text-white font-bold text-xs disabled:opacity-50"
                            >
                                <Loader2 v-if="testingText" class="w-3.5 h-3.5 animate-spin inline mr-1" />
                                Uji Pembuatan Teks
                            </button>
                            <p v-if="testTextResult" class="text-[10px] mt-2 font-mono p-2 rounded bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300">
                                {{ testTextResult.message }} {{ testTextResult.output ? `"${testTextResult.output}"` : '' }}
                            </p>
                        </div>
                    </div>

                    <!-- Test Image -->
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 flex flex-col justify-between space-y-3">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 text-teal-600 font-bold text-xs">
                                <Image class="w-4 h-4" />
                                <span>Pembuatan Gambar (Imagen)</span>
                            </div>
                            <p class="text-[11px] text-slate-500">
                                Menghasilkan 1 sampel visual. Memerlukan kuota Imagen 3 aktif.
                            </p>
                        </div>
                        <div>
                            <button
                                type="button"
                                @click="runTestImage"
                                :disabled="testingImage || !settings.is_configured"
                                class="w-full py-2 rounded-xl bg-teal-600 text-white font-bold text-xs disabled:opacity-50"
                            >
                                <Loader2 v-if="testingImage" class="w-3.5 h-3.5 animate-spin inline mr-1" />
                                Uji Pembuatan Gambar
                            </button>
                            <p v-if="testImageResult" class="text-[10px] mt-2 font-mono p-2 rounded bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300">
                                {{ testImageResult.message }}
                            </p>
                        </div>
                    </div>

                    <!-- Test Video -->
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 flex flex-col justify-between space-y-3">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 text-orange-600 font-bold text-xs">
                                <Video class="w-4 h-4" />
                                <span>Pembuatan Video (Veo)</span>
                            </div>
                            <p class="text-[11px] text-slate-500">
                                Memeriksa ketersediaan model video generatif pada proyek Google Anda.
                            </p>
                        </div>
                        <div>
                            <button
                                type="button"
                                @click="runTestVideo"
                                :disabled="testingVideo || !settings.is_configured"
                                class="w-full py-2 rounded-xl bg-orange-600 text-white font-bold text-xs disabled:opacity-50"
                            >
                                <Loader2 v-if="testingVideo" class="w-3.5 h-3.5 animate-spin inline mr-1" />
                                Cek Akses Model Video
                            </button>
                            <p v-if="testVideoResult" class="text-[10px] mt-2 font-mono p-2 rounded bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300">
                                {{ testVideoResult.message }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: Pemilihan Model & Batasan Aplikasi -->
            <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
                <div>
                    <h3 class="text-base font-bold font-serif text-slate-900 dark:text-white">
                        Konfigurasi Model & Batasan Penggunaan
                    </h3>
                    <p class="text-xs text-slate-500">
                        Perubahan langsung disimpan ke database dan aktif seketika tanpa perlu rebuild frontend.
                    </p>
                </div>

                <form @submit.prevent="submitModels" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Model Naskah & Storyboard (Teks)
                            </label>
                            <div class="space-y-1.5">
                                <select
                                    v-model="modelForm.text_model"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white"
                                >
                                    <option v-for="m in modelOptions.text" :key="m.id" :value="m.id">
                                        {{ m.name }}
                                    </option>
                                    <option value="custom">Model Khusus / Ketik Manual...</option>
                                </select>
                                <input
                                    v-if="modelForm.text_model === 'custom' || !modelOptions.text.some(m => m.id === modelForm.text_model)"
                                    v-model="modelForm.text_model"
                                    type="text"
                                    placeholder="Contoh: gemini-3.0-flash atau gemini-3.8..."
                                    class="w-full px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono text-slate-900 dark:text-white"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Model Ilustrasi Adegan (Gambar)
                            </label>
                            <select
                                v-model="modelForm.image_model"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white"
                            >
                                <option v-for="m in modelOptions.image" :key="m.id" :value="m.id">
                                    {{ m.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Model Narasi Suara (TTS)
                            </label>
                            <select
                                v-model="modelForm.tts_model"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white"
                            >
                                <option v-for="m in modelOptions.tts" :key="m.id" :value="m.id">
                                    {{ m.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Suara Narator Bawaan
                            </label>
                            <select
                                v-model="modelForm.voice_name"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white"
                            >
                                <option v-for="v in modelOptions.voices" :key="v.id" :value="v.id">
                                    {{ v.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Model Video Generatif
                            </label>
                            <select
                                v-model="modelForm.video_model"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white"
                            >
                                <option v-for="m in modelOptions.video" :key="m.id" :value="m.id">
                                    {{ m.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Batas Kuota Default per Pengguna
                            </label>
                            <input
                                v-model.number="modelForm.max_video_per_user"
                                type="number"
                                min="1"
                                max="500"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                                Batas Render Harian Seluruh Aplikasi
                            </label>
                            <input
                                v-model.number="modelForm.daily_render_limit"
                                type="number"
                                min="1"
                                max="1000"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-900 dark:text-white"
                            />
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                        <button
                            type="submit"
                            :disabled="modelForm.processing"
                            class="btn-primary px-6 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 shadow-md disabled:opacity-50"
                        >
                            <Save class="w-4 h-4" />
                            <span>Simpan Konfigurasi Model</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
