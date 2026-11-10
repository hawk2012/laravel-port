<script setup lang="ts">
import { computed } from 'vue';
import type { GenerationStatus, GenerationType } from '@/types';
import { STATUS_META, TYPE_META } from '@/lib/meta';

const props = withDefaults(
    defineProps<{
        status?: GenerationStatus;
        type?: GenerationType;
        pulse?: boolean;
    }>(),
    { pulse: false },
);

const statusMeta = computed(() => (props.status ? STATUS_META[props.status] : null));
const typeMeta = computed(() => (props.type ? TYPE_META[props.type] : null));
</script>

<template>
    <span v-if="statusMeta" :class="['chip', statusMeta.chip]">
        <span
            :class="[
                'h-1.5 w-1.5 rounded-full',
                statusMeta.dot,
                pulse && status === 'processing' ? 'animate-pulse' : '',
            ]"
        />
        {{ statusMeta.label }}
    </span>

    <span v-else-if="typeMeta" :class="['chip', typeMeta.accent]">
        {{ typeMeta.label }}
    </span>
</template>
