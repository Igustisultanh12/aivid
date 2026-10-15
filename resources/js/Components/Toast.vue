<script setup lang="ts">
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, AlertCircle, Info, X } from 'lucide-vue-next';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const visible = ref(false);
const message = ref('');
const type = ref<'success' | 'error' | 'info'>('info');
let timeout: any = null;

const showToast = (msg: string, toastType: 'success' | 'error' | 'info') => {
    message.value = msg;
    type.value = toastType;
    visible.value = true;
    if (timeout) clearTimeout(timeout);
    timeout = setTimeout(() => {
        visible.value = false;
    }, 5000);
};

const close = () => {
    visible.value = false;
    if (timeout) clearTimeout(timeout);
};

watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        showToast(flash.success, 'success');
    } else if (flash?.error) {
        showToast(flash.error, 'error');
    } else if (flash?.info) {
        showToast(flash.info, 'info');
    }
}, { deep: true, immediate: true });
</script>

<template>
    <div
        v-if="visible"
        class="fixed bottom-6 right-6 z-50 max-w-md w-full transition-all duration-300 transform translate-y-0"
    >
        <div
            :class="[
                'p-4 rounded-2xl shadow-xl border flex items-start gap-3 backdrop-blur-md',
                type === 'success' ? 'bg-emerald-50/95 dark:bg-emerald-950/90 border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-100' :
                type === 'error' ? 'bg-rose-50/95 dark:bg-rose-950/90 border-rose-200 dark:border-rose-800 text-rose-900 dark:text-rose-100' :
                'bg-violet-50/95 dark:bg-violet-950/90 border-violet-200 dark:border-violet-800 text-violet-900 dark:text-violet-100'
            ]"
        >
            <CheckCircle2 v-if="type === 'success'" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
            <AlertCircle v-else-if="type === 'error'" class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" />
            <Info v-else class="w-5 h-5 text-violet-600 dark:text-violet-400 shrink-0 mt-0.5" />

            <div class="flex-1 text-sm font-medium leading-relaxed">
                {{ message }}
            </div>

            <button
                @click="close"
                type="button"
                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors rounded-lg p-1"
                aria-label="Tutup notifikasi"
            >
                <X class="w-4 h-4" />
            </button>
        </div>
    </div>
</template>
