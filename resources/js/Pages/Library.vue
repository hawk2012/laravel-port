<script setup lang="ts">
import { route } from '@/lib/route';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Toasts from '@/Components/Toasts.vue';
import AssetPreview from '@/Components/AssetPreview.vue';
import Icon from '@/Components/Icon.vue';
import { TYPE_META, TYPE_OPTIONS } from '@/lib/meta';
import { formatBytes, formatNumber } from '@/lib/format';
import type { Asset, AssetKind } from '@/types';

const props = defineProps<{
    assets: Asset[];
    meta: { current_page: number; per_page: number; total: number; last_page: number };
    filters: { kind?: string; project_id?: number };
    kindOptions: AssetKind[];
}>();

const kind = ref(props.filters.kind ?? '');

watch(kind, () => {
    router.get(route('library'), kind.value ? { kind: kind.value } : {}, { preserveState: true, preserveScroll: true });
});

function goToPage(page: number): void {
    router.get(route('library'), { ...props.filters, page }, { preserveScroll: true, preserveState: true });
}
</script>

<template>
    <AppLayout>
        <Toasts />

        <div class="mx-auto max-w-6xl space-y-5 px-5 py-6 lg:py-8">
            <header class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold tracking-tight text-white">Медиатека</h1>
                    <p class="mt-1 text-sm text-mist-400">{{ formatNumber(meta.total) }} ассетов</p>
                </div>

                <div class="flex flex-wrap gap-1.5" role="tablist" aria-label="Фильтр по типу">
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="kind === ''"
                        :class="['chip transition', kind === '' ? 'border-brand-500/40 bg-brand-500/12 text-brand-400' : 'hover:bg-white/10']"
                        @click="kind = ''"
                    >
                        Все
                    </button>
                    <button
                        v-for="option in TYPE_OPTIONS"
                        :key="option"
                        type="button"
                        role="tab"
                        :aria-selected="kind === option"
                        :class="['chip transition', kind === option ? 'border-brand-500/40 bg-brand-500/12 text-brand-400' : 'hover:bg-white/10']"
                        @click="kind = option"
                    >
                        {{ TYPE_META[option].label }}
                    </button>
                </div>
            </header>

            <div v-if="assets.length > 0" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <article v-for="asset in assets" :key="asset.uuid" class="surface-raised overflow-hidden">
                    <AssetPreview :asset="asset" />

                    <div class="flex items-center justify-between gap-2 p-3">
                        <div class="min-w-0">
                            <p class="truncate text-[11px] font-medium text-mist-200">
                                {{ TYPE_META[asset.kind].label }}
                            </p>
                            <p class="text-[10px] text-mist-400">
                                {{ formatBytes(asset.size_bytes) }}
                                <span v-if="asset.width"> · {{ asset.width }}×{{ asset.height }}</span>
                            </p>
                        </div>

                        <a
                            :href="route('media.show', asset.uuid)"
                            class="btn-quiet !px-2 !py-2"
                            :aria-label="`Скачать ${asset.kind}`"
                        >
                            <Icon name="download" :size="15" />
                        </a>
                    </div>
                </article>
            </div>

            <div v-else class="surface-raised py-16 text-center">
                <p class="text-sm text-mist-300">Медиатека пуста</p>
                <p class="mt-1 text-xs text-mist-400">Сгенерируйте первый ассет в студии</p>
            </div>

            <nav v-if="meta.last_page > 1" class="flex items-center justify-center gap-1.5" aria-label="Постраничная навигация">
                <button
                    type="button"
                    class="btn-ghost !px-3 !py-2"
                    :disabled="meta.current_page <= 1"
                    @click="goToPage(meta.current_page - 1)"
                >
                    Назад
                </button>
                <span class="px-3 text-xs text-mist-400">{{ meta.current_page }} / {{ meta.last_page }}</span>
                <button
                    type="button"
                    class="btn-ghost !px-3 !py-2"
                    :disabled="meta.current_page >= meta.last_page"
                    @click="goToPage(meta.current_page + 1)"
                >
                    Вперёд
                </button>
            </nav>
        </div>
    </AppLayout>
</template>
