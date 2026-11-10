<script setup lang="ts">
import { computed } from 'vue';
import Icon from '@/Components/Icon.vue';
import type { Asset } from '@/types';

const props = defineProps<{ asset: Asset; prompt?: string }>();

const isText = computed(() => props.asset.kind === 'text');
const aspect = computed(() => {
    if (props.asset.width && props.asset.height) {
        return `${props.asset.width} / ${props.asset.height}`;
    }

    return '1 / 1';
});
</script>

<template>
    <div
        v-if="isText"
        class="flex h-full flex-col gap-2 rounded-xl border border-white/8 bg-ink-850/70 p-3.5"
    >
        <div class="flex items-center gap-1.5 text-xs font-semibold text-warn-400">
            <Icon name="text" :size="14" />
            Текст
        </div>
        <p class="line-clamp-6 text-xs leading-relaxed whitespace-pre-line text-mist-200">
            {{ asset.text || prompt || 'Текстовый результат' }}
        </p>
    </div>

    <div v-else-if="asset.preview_url" class="overflow-hidden rounded-xl border border-white/8 bg-ink-850">
        <img
            :src="asset.preview_url"
            :alt="prompt || 'Сгенерированный ассет'"
            :style="{ aspectRatio: aspect }"
            class="h-full w-full object-cover transition duration-500 hover:scale-[1.03]"
            loading="lazy"
        />
    </div>

    <div
        v-else
        class="flex items-center justify-center rounded-xl border border-white/8 bg-ink-850 text-mist-400"
        :style="{ aspectRatio: aspect }"
    >
        <Icon :name="asset.kind === 'video' ? 'video' : asset.kind === 'audio' ? 'audio' : 'image'" :size="28" />
    </div>
</template>
