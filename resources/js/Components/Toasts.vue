<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';
import type { SharedProps } from '@/types';

const page = usePage<SharedProps>();

interface Toast {
    id: number;
    level: 'success' | 'error';
    message: string;
}

const toasts = ref<Toast[]>([]);
let counter = 0;

function push(level: 'success' | 'error', message: string): void {
    const id = ++counter;
    toasts.value = [...toasts.value, { id, level, message }];
    setTimeout(() => dismiss(id), 5000);
}

function dismiss(id: number): void {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
}

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            push('success', flash.success);
        }
        if (flash?.error) {
            push('error', flash.error);
        }
    },
    { immediate: true, deep: true },
);

const iconFor = computed(() => (level: 'success' | 'error') => (level === 'success' ? 'check' : 'alert'));
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex flex-col items-center gap-2 px-4">
        <TransitionGroup
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            leave-active-class="transition duration-200 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                :class="[
                    'pointer-events-auto flex w-full max-w-md items-start gap-2.5 rounded-xl border px-4 py-3 text-sm shadow-xl backdrop-blur',
                    toast.level === 'success'
                        ? 'border-success-400/30 bg-success-400/10 text-success-400'
                        : 'border-danger-400/30 bg-danger-400/10 text-danger-400',
                ]"
                role="status"
            >
                <Icon :name="iconFor(toast.level)" :size="16" class="mt-0.5 shrink-0" />
                <p class="flex-1 text-mist-100">{{ toast.message }}</p>
                <button type="button" class="text-mist-400 transition hover:text-white" @click="dismiss(toast.id)">
                    <Icon name="close" :size="14" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
