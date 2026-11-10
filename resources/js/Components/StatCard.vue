<script setup lang="ts">
import { formatNumber } from '@/lib/format';
import { useCountUp } from '@/composables/useCountUp';
import Icon from '@/Components/Icon.vue';

const props = defineProps<{
    label: string;
    value: number;
    hint?: string;
    icon?: string;
    accent?: 'brand' | 'accent' | 'warn' | 'danger';
    animate?: boolean;
}>();

const display = useCountUp(props.value);

const accents: Record<string, string> = {
    brand: 'text-brand-400 bg-brand-500/12 border-brand-500/20',
    accent: 'text-accent-400 bg-accent-500/12 border-accent-500/20',
    warn: 'text-warn-400 bg-warn-400/12 border-warn-400/20',
    danger: 'text-danger-400 bg-danger-500/12 border-danger-500/20',
};
</script>

<template>
    <div class="surface-raised p-4.5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold tracking-wide text-mist-400 uppercase">{{ label }}</p>
                <p class="mt-1.5 text-2xl font-semibold tracking-tight text-white tabular-nums">
                    {{ animate === false ? formatNumber(value) : formatNumber(display) }}
                </p>
                <p v-if="hint" class="mt-1 truncate text-[11px] text-mist-400">{{ hint }}</p>
            </div>
            <span
                v-if="icon"
                :class="['grid h-9 w-9 shrink-0 place-items-center rounded-xl border', accents[accent ?? 'brand']]"
            >
                <Icon :name="icon" :size="17" />
            </span>
        </div>
    </div>
</template>
