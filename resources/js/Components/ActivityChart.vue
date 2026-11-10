<script setup lang="ts">
import { computed } from 'vue';
import { formatDayShort, formatNumber } from '@/lib/format';

const props = defineProps<{
    series: Array<{ date: string; total: number; failed: number }>;
    height?: number;
}>();

const barHeight = computed(() => props.height ?? 120);
const max = computed(() => Math.max(1, ...props.series.map((point) => point.total)));

function barPx(value: number): number {
    return Math.round((value / max.value) * barHeight.value);
}
</script>

<template>
    <div class="w-full">
        <div class="flex items-end gap-1.5" :style="{ height: `${barHeight + 22}px` }">
            <div
                v-for="point in series"
                :key="point.date"
                class="group flex h-full flex-1 flex-col items-center justify-end gap-1.5"
            >
                <div class="relative flex w-full flex-col justify-end" :style="{ height: `${barHeight}px` }">
                    <div
                        class="w-full rounded-t-md bg-gradient-to-t from-brand-600/70 to-brand-400 transition-all duration-500 group-hover:from-brand-500 group-hover:to-brand-400"
                        :style="{ height: `${Math.max(barPx(point.total), point.total > 0 ? 3 : 1)}px` }"
                        :title="`${point.date}: ${point.total} генераций`"
                    />
                    <div
                        v-if="point.failed > 0"
                        class="absolute inset-x-0 bottom-0 rounded-t-md bg-danger-500/80"
                        :style="{ height: `${Math.max(barPx(point.failed), 2)}px` }"
                    />
                </div>
                <span class="text-[10px] text-mist-400">{{ formatDayShort(point.date) }}</span>
            </div>
        </div>
        <div class="mt-3 flex items-center gap-4 text-[11px] text-mist-400">
            <span class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-sm bg-brand-400" /> Всего
            </span>
            <span class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-sm bg-danger-500" /> Ошибки
            </span>
            <span class="ml-auto">Пик: {{ formatNumber(max) }}/день</span>
        </div>
    </div>
</template>
