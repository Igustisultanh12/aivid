<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Play, Pause, Volume2, VolumeX, Maximize2, Minimize2, Subtitles, RotateCcw } from 'lucide-vue-next';

const props = withDefaults(defineProps<{
    src: string;
    poster?: string;
    subtitlesUrl?: string;
    aspectRatio?: '9:16' | '16:9' | '1:1';
    autoPlay?: boolean;
}>(), {
    aspectRatio: '9:16',
    autoPlay: false,
});

const videoRef = ref<HTMLVideoElement | null>(null);
const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(30.0);
const isMuted = ref(false);
const volume = ref(1.0);
const showSubtitles = ref(true);
const isFullscreen = ref(false);
const playbackRate = ref(1.0);
const containerRef = ref<HTMLDivElement | null>(null);

const formattedCurrentTime = computed(() => formatTime(currentTime.value));
const formattedDuration = computed(() => formatTime(duration.value || 30.0));
const progressPercent = computed(() => {
    return duration.value > 0 ? (currentTime.value / duration.value) * 100 : 0;
});

const formatTime = (secs: number) => {
    const m = Math.floor(secs / 60);
    const s = Math.floor(secs % 60);
    return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
};

const togglePlay = () => {
    if (!videoRef.value) return;
    if (videoRef.value.paused) {
        videoRef.value.play();
        isPlaying.value = true;
    } else {
        videoRef.value.pause();
        isPlaying.value = false;
    }
};

const handleTimeUpdate = () => {
    if (!videoRef.value) return;
    currentTime.value = videoRef.value.currentTime;
};

const handleLoadedMetadata = () => {
    if (!videoRef.value) return;
    duration.value = videoRef.value.duration || 30.0;
};

const handleSeek = (e: MouseEvent) => {
    const target = e.currentTarget as HTMLElement;
    const rect = target.getBoundingClientRect();
    const pos = (e.clientX - rect.left) / rect.width;
    if (videoRef.value && duration.value) {
        videoRef.value.currentTime = pos * duration.value;
    }
};

const toggleMute = () => {
    if (!videoRef.value) return;
    isMuted.value = !isMuted.value;
    videoRef.value.muted = isMuted.value;
};

const handleVolumeChange = (e: Event) => {
    const val = parseFloat((e.target as HTMLInputElement).value);
    volume.value = val;
    if (videoRef.value) {
        videoRef.value.volume = val;
        videoRef.value.muted = val === 0;
        isMuted.value = val === 0;
    }
};

const toggleSubtitles = () => {
    showSubtitles.value = !showSubtitles.value;
    if (videoRef.value && videoRef.value.textTracks.length > 0) {
        videoRef.value.textTracks[0].mode = showSubtitles.value ? 'showing' : 'hidden';
    }
};

const toggleFullscreen = () => {
    if (!containerRef.value) return;
    if (!document.fullscreenElement) {
        containerRef.value.requestFullscreen().catch(() => {});
        isFullscreen.value = true;
    } else {
        document.exitFullscreen().catch(() => {});
        isFullscreen.value = false;
    }
};

const changeRate = () => {
    const rates = [1.0, 1.25, 1.5];
    const nextIdx = (rates.indexOf(playbackRate.value) + 1) % rates.length;
    playbackRate.value = rates[nextIdx];
    if (videoRef.value) {
        videoRef.value.playbackRate = playbackRate.value;
    }
};

const restart = () => {
    if (!videoRef.value) return;
    videoRef.value.currentTime = 0;
    videoRef.value.play();
    isPlaying.value = true;
};
</script>

<template>
    <div
        ref="containerRef"
        :class="[
            'relative group overflow-hidden rounded-3xl bg-slate-950 shadow-2xl flex items-center justify-center border border-slate-800',
            aspectRatio === '9:16' ? 'aspect-[9/16] max-h-[640px] mx-auto' :
            aspectRatio === '1:1' ? 'aspect-square max-w-[500px] mx-auto' :
            'aspect-video w-full'
        ]"
    >
        <video
            ref="videoRef"
            :src="src"
            :poster="poster"
            class="w-full h-full object-cover"
            playsinline
            @timeupdate="handleTimeUpdate"
            @loadedmetadata="handleLoadedMetadata"
            @ended="isPlaying = false"
            @click="togglePlay"
        >
            <track
                v-if="subtitlesUrl"
                :src="subtitlesUrl"
                kind="subtitles"
                srclang="id"
                label="Bahasa Indonesia"
                :default="showSubtitles"
            />
        </video>

        <!-- Big Play Button Overlay on Pause -->
        <button
            v-if="!isPlaying"
            @click="togglePlay"
            type="button"
            class="absolute z-20 w-16 h-16 rounded-full bg-violet-600/90 text-white flex items-center justify-center shadow-xl hover:scale-110 active:scale-95 transition-transform backdrop-blur-sm"
            aria-label="Putar video"
        >
            <Play class="w-8 h-8 fill-current ml-1" />
        </button>

        <!-- 30-Second Badge Indicator -->
        <div class="absolute top-4 left-4 z-20 px-3 py-1 rounded-full bg-slate-950/70 border border-slate-700/60 text-[11px] font-bold text-amber-400 backdrop-blur-md flex items-center gap-1.5 shadow-sm">
            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
            30 DETIK
        </div>

        <!-- Custom Controls Bar (Fade-in on hover) -->
        <div class="absolute inset-x-0 bottom-0 z-30 p-4 bg-gradient-to-t from-slate-950/95 via-slate-950/70 to-transparent opacity-90 sm:opacity-0 group-hover:opacity-100 transition-opacity">
            <!-- Scrubber Track -->
            <div
                class="w-full h-2 bg-slate-700/60 rounded-full cursor-pointer relative mb-3 overflow-hidden group/scrub"
                @click="handleSeek"
            >
                <div
                    class="h-full bg-gradient-to-r from-violet-500 to-amber-400 rounded-full transition-all duration-75 relative"
                    :style="{ width: `${progressPercent}%` }"
                ></div>
            </div>

            <div class="flex items-center justify-between text-white text-xs">
                <!-- Play/Pause & Time -->
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="togglePlay"
                        class="p-1.5 rounded-lg hover:bg-white/10 transition"
                        :aria-label="isPlaying ? 'Jeda' : 'Putar'"
                    >
                        <Pause v-if="isPlaying" class="w-5 h-5 fill-current" />
                        <Play v-else class="w-5 h-5 fill-current" />
                    </button>

                    <button
                        type="button"
                        @click="restart"
                        class="p-1.5 rounded-lg hover:bg-white/10 transition text-slate-300"
                        title="Ulangi dari awal"
                    >
                        <RotateCcw class="w-4 h-4" />
                    </button>

                    <span class="font-mono text-slate-300 select-none">
                        {{ formattedCurrentTime }} / {{ formattedDuration }}
                    </span>
                </div>

                <!-- Right Actions: Volume, Subtitles, Speed, Fullscreen -->
                <div class="flex items-center gap-2">
                    <!-- Volume -->
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            @click="toggleMute"
                            class="p-1.5 rounded-lg hover:bg-white/10 transition text-slate-300"
                            :aria-label="isMuted ? 'Nyalakan suara' : 'Bisukan suara'"
                        >
                            <VolumeX v-if="isMuted" class="w-4 h-4 text-rose-400" />
                            <Volume2 v-else class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Subtitles Toggle -->
                    <button
                        v-if="subtitlesUrl"
                        type="button"
                        @click="toggleSubtitles"
                        :class="[
                            'p-1.5 rounded-lg transition text-xs font-bold',
                            showSubtitles ? 'bg-violet-600 text-white' : 'hover:bg-white/10 text-slate-400'
                        ]"
                        title="Tampilkan / Sembunyikan Subtitle"
                    >
                        <Subtitles class="w-4 h-4" />
                    </button>

                    <!-- Playback Speed -->
                    <button
                        type="button"
                        @click="changeRate"
                        class="px-2 py-1 rounded-lg hover:bg-white/10 text-slate-300 font-mono font-bold text-[11px]"
                    >
                        {{ playbackRate }}x
                    </button>

                    <!-- Fullscreen -->
                    <button
                        type="button"
                        @click="toggleFullscreen"
                        class="p-1.5 rounded-lg hover:bg-white/10 transition text-slate-300"
                        :aria-label="isFullscreen ? 'Keluar layar penuh' : 'Layar penuh'"
                    >
                        <Minimize2 v-if="isFullscreen" class="w-4 h-4" />
                        <Maximize2 v-else class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
