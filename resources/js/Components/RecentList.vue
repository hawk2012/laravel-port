<script setup lang="ts">
import Icon from '@/Components/Icon.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Generation } from '@/types';
import { formatLatency, relativeTime } from '@/lib/format';

defineProps<{ items: Generation[] }>();
</script>

<template>
    <ul class="divide-y divide-white/6">
        <li v-for="item in items" :key="item.uuid" class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
            <span
                :class="[
                    'mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-white/8 bg-white/5',
                    item.type === 'image'
                        ? 'text-brand-400'
                        : item.type === 'video'
                          ? 'text-danger-400'
                          : item.type === 'audio'
                            ? 'text-accent-400'
                            : 'text-warn-400',
                ]"
            >
                <Icon :name="item.type" :size="15" />
            </span>

            <div class="min-w-0 flex-1">
                <p class="truncate text-sm text-mist-100">{{ item.prompt }}</p>
                <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-mist-400">
                    <span>{{ relativeTime(item.created_at) }}</span>
                    <span v-if="item.project" class="truncate">· {{ item.project.name }}</span>
                    <span v-if="item.latency_ms">· {{ formatLatency(item.latency_ms) }}</span>
                </div>
            </div>

            <StatusBadge :status="item.status" pulse />
        </li>
    </ul>
</template>
