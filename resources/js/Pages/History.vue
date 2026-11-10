<script setup lang="ts">
import { route } from '@/lib/route';
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Toasts from '@/Components/Toasts.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AssetPreview from '@/Components/AssetPreview.vue';
import Icon from '@/Components/Icon.vue';
import { STATUS_META, TYPE_META, STATUS_OPTIONS, TYPE_OPTIONS } from '@/lib/meta';
import { formatLatency, formatNumber, relativeTime } from '@/lib/format';
import type { Generation, GenerationType } from '@/types';

const props = defineProps<{
    generations: Generation[];
    meta: { current_page: number; per_page: number; total: number; last_page: number };
    filters: { status?: string; type?: string; search?: string };
    typeOptions: GenerationType[];
}>();

const search = ref(props.filters.search ?? '');
const status = ref<string>(props.filters.status ?? '');
const type = ref<string>(props.filters.type ?? '');

let debounce: ReturnType<typeof setTimeout> | null = null;

function applyFilters(): void {
    router.get(
        route('history'),
        {
            ...(search.value ? { search: search.value } : {}),
            ...(status.value ? { status: status.value } : {}),
            ...(type.value ? { type: type.value } : {}),
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

watch(search, () => {
    if (debounce) {
        clearTimeout(debounce);
    }
    debounce = setTimeout(applyFilters, 350);
});

watch([status, type], applyFilters);

const totalPages = computed(() => Math.max(1, props.meta.last_page));

function goToPage(page: number): void {
    router.get(route('history'), { ...props.filters, page }, { preserveScroll: true, preserveState: true });
}
</script>

<template>
    <AppLayout>
        <Toasts />

        <div class="mx-auto max-w-6xl space-y-5 px-5 py-6 lg:py-8">
            <header class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold tracking-tight text-white">История</h1>
                    <p class="mt-1 text-sm text-mist-400">{{ formatNumber(meta.total) }} генераций</p>
                </div>
            </header>

            <div class="surface flex flex-col gap-2.5 p-3 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <Icon name="search" :size="15" class="pointer-events-none absolute top-3 left-3 text-mist-400" />
                    <input
                        v-model="search"
                        class="field pl-9"
                        placeholder="Поиск по промпту"
                        aria-label="Поиск по промпту"
                    />
                </div>

                <select v-model="status" class="field sm:w-44" aria-label="Статус">
                    <option value="">Все статусы</option>
                    <option v-for="option in STATUS_OPTIONS" :key="option" :value="option">
                        {{ STATUS_META[option].label }}
                    </option>
                </select>

                <select v-model="type" class="field sm:w-44" aria-label="Тип">
                    <option value="">Все типы</option>
                    <option v-for="option in TYPE_OPTIONS" :key="option" :value="option">
                        {{ TYPE_META[option].label }}
                    </option>
                </select>
            </div>

            <div v-if="generations.length > 0" class="space-y-2.5">
                <article
                    v-for="item in generations"
                    :key="item.uuid"
                    class="surface-raised grid gap-4 p-4 sm:grid-cols-[auto_1fr_auto] sm:items-start"
                >
                    <div class="grid grid-cols-2 gap-2 sm:w-32 sm:grid-cols-1">
                        <AssetPreview
                            v-for="asset in item.assets.slice(0, 1)"
                            :key="asset.uuid"
                            :asset="asset"
                            :prompt="item.prompt"
                        />
                        <div
                            v-if="item.assets.length === 0"
                            class="flex aspect-square items-center justify-center rounded-xl border border-white/8 bg-white/3 text-mist-400"
                        >
                            <Icon :name="item.type" :size="20" />
                        </div>
                    </div>

                    <div class="min-w-0">
                        <div class="mb-1.5 flex flex-wrap items-center gap-2">
                            <StatusBadge :status="item.status" pulse />
                            <span class="chip">{{ TYPE_META[item.type].label }}</span>
                            <span v-if="item.project" class="chip">{{ item.project.name }}</span>
                        </div>

                        <p class="line-clamp-2 text-sm leading-relaxed text-mist-100">{{ item.prompt }}</p>

                        <p
                            v-if="item.error_message"
                            class="mt-1.5 line-clamp-1 text-[11px] text-danger-400"
                        >
                            {{ item.error_message }}
                        </p>

                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-mist-400">
                            <span>{{ relativeTime(item.created_at) }}</span>
                            <span>· {{ item.credits_used }} кр.</span>
                            <span v-if="item.latency_ms">· {{ formatLatency(item.latency_ms) }}</span>
                            <span v-if="item.model" class="font-mono">· {{ item.model }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 sm:flex-col sm:items-end">
                        <a
                            v-if="item.assets.length > 0"
                            :href="`/api/v1/assets/${item.assets[0]?.uuid}/download`"
                            class="btn-ghost !px-2.5 !py-2"
                            aria-label="Скачать ассет"
                        >
                            <Icon name="download" :size="15" />
                        </a>
                        <span class="chip font-mono text-[10px]">{{ item.uuid.slice(0, 8) }}</span>
                    </div>
                </article>
            </div>

            <div v-else class="surface-raised py-16 text-center">
                <p class="text-sm text-mist-300">Ничего не найдено</p>
                <p class="mt-1 text-xs text-mist-400">Измените фильтры или запустите первую генерацию</p>
            </div>

            <nav v-if="totalPages > 1" class="flex items-center justify-center gap-1.5" aria-label="Постраничная навигация">
                <button
                    type="button"
                    class="btn-ghost !px-3 !py-2"
                    :disabled="meta.current_page <= 1"
                    @click="goToPage(meta.current_page - 1)"
                >
                    Назад
                </button>
                <span class="px-3 text-xs text-mist-400">{{ meta.current_page }} / {{ totalPages }}</span>
                <button
                    type="button"
                    class="btn-ghost !px-3 !py-2"
                    :disabled="meta.current_page >= totalPages"
                    @click="goToPage(meta.current_page + 1)"
                >
                    Вперёд
                </button>
            </nav>
        </div>
    </AppLayout>
</template>
