<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Toasts from '@/Components/Toasts.vue';
import Icon from '@/Components/Icon.vue';
import AssetPreview from '@/Components/AssetPreview.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { TYPE_META } from '@/lib/meta';
import { api, extractError } from '@/lib/api';
import { formatLatency, relativeTime } from '@/lib/format';
import { useGenerationPolling } from '@/composables/useGenerationPolling';
import type { Generation, GenerationType, Project } from '@/types';

const props = defineProps<{
    projects: { data: Project[] };
    recent: Generation[];
    typeOptions: GenerationType[];
}>();

const projectList = computed(() => props.projects.data ?? []);

const type = ref<GenerationType>('image');
const prompt = ref('');
const negativePrompt = ref('');
const projectId = ref<number | null>(projectList.value[0]?.id ?? null);
const size = ref('1024x1024');
const temperature = ref(0.7);
const submitting = ref(false);
const error = ref<string | null>(null);
const active = ref<Generation | null>(null);

const { generation: polled, polling, start } = useGenerationPolling();

watch(polled, (value) => {
    if (value) {
        active.value = value;
    }
});

const cost = computed(() => TYPE_META[type.value].credits);
const canSubmit = computed(() => prompt.value.trim().length >= 3 && projectId.value !== null && !submitting.value);

async function submit(): Promise<void> {
    if (!canSubmit.value || projectId.value === null) {
        return;
    }

    submitting.value = true;
    error.value = null;

    try {
        const payload: Record<string, unknown> = {
            type: type.value,
            prompt: prompt.value.trim(),
            params: type.value === 'image' ? { size: size.value } : { temperature: temperature.value },
        };

        if (negativePrompt.value.trim() !== '') {
            payload.negative_prompt = negativePrompt.value.trim();
        }

        const created = await api.createGeneration(projectId.value, payload);
        active.value = created;
        start(created.uuid);
        prompt.value = '';
    } catch (e) {
        const parsed = extractError(e);
        error.value = parsed.message;
    } finally {
        submitting.value = false;
    }
}

function refreshPage(): void {
    router.reload({ only: ['recent'] });
}
</script>

<template>
    <AppLayout>
        <Toasts />

        <div class="mx-auto max-w-6xl space-y-6 px-5 py-6 lg:py-8">
            <header>
                <h1 class="text-xl font-semibold tracking-tight text-white">Студия</h1>
                <p class="mt-1 text-sm text-mist-400">
                    Запрос уходит в очередь, статус обновляется автоматически
                </p>
            </header>

            <div class="grid gap-5 lg:grid-cols-[1.1fr_1fr]">
                <form class="surface-raised space-y-4 p-5" @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4" role="radiogroup" aria-label="Тип">
                        <button
                            v-for="option in typeOptions"
                            :key="option"
                            type="button"
                            role="radio"
                            :aria-checked="type === option"
                            :class="[
                                'flex flex-col items-start gap-1 rounded-xl border px-3 py-2.5 text-left transition',
                                type === option
                                    ? 'border-brand-500/60 bg-brand-500/12 text-white'
                                    : 'border-white/8 bg-white/3 text-mist-300 hover:border-white/16',
                            ]"
                            @click="type = option"
                        >
                            <Icon :name="option" :size="16" />
                            <span class="text-xs font-medium">{{ TYPE_META[option].label }}</span>
                        </button>
                    </div>

                    <div>
                        <label class="label" for="studio-prompt">Промпт</label>
                        <textarea
                            id="studio-prompt"
                            v-model="prompt"
                            rows="5"
                            maxlength="2000"
                            class="field resize-none"
                            placeholder="Опишите, что нужно сгенерировать"
                        />
                    </div>

                    <div>
                        <label class="label" for="studio-negative">Негативный промпт</label>
                        <input
                            id="studio-negative"
                            v-model="negativePrompt"
                            maxlength="500"
                            class="field"
                            placeholder="Что исключить: размытие, текст, водяные знаки"
                        />
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="label" for="studio-project">Проект</label>
                            <select id="studio-project" v-model="projectId" class="field">
                                <option v-if="projectList.length === 0" :value="null">Нет проектов</option>
                                <option v-for="project in projectList" :key="project.id" :value="project.id">
                                    {{ project.name }}
                                </option>
                            </select>
                        </div>

                        <div v-if="type === 'image'">
                            <label class="label" for="studio-size">Размер</label>
                            <select id="studio-size" v-model="size" class="field">
                                <option value="1024x1024">1024 × 1024</option>
                                <option value="1024x576">1024 × 576</option>
                                <option value="576x1024">576 × 1024</option>
                            </select>
                        </div>

                        <div v-else>
                            <label class="label" for="studio-temp">Температура: {{ temperature.toFixed(1) }}</label>
                            <input
                                id="studio-temp"
                                v-model.number="temperature"
                                type="range"
                                min="0"
                                max="2"
                                step="0.1"
                                class="mt-3 w-full accent-brand-500"
                            />
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-white/8 pt-4">
                        <span class="chip border-brand-500/25 bg-brand-500/10 text-brand-400">
                            <Icon name="bolt" :size="13" />
                            {{ cost }} кредитов
                        </span>
                        <button type="submit" class="btn-primary sm:w-48" :disabled="!canSubmit">
                            <Icon v-if="!submitting" name="spark" :size="16" />
                            <span
                                v-if="submitting"
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                            />
                            {{ submitting ? 'Отправка' : 'Сгенерировать' }}
                        </button>
                    </div>

                    <p v-if="error" class="rounded-xl border border-danger-400/30 bg-danger-400/10 px-3 py-2 text-xs text-danger-400">
                        {{ error }}
                    </p>
                </form>

                <section class="surface-raised flex flex-col p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-white">Результат</h2>
                        <span v-if="polling" class="chip border-brand-500/25 bg-brand-500/10 text-brand-400">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-brand-400" />
                            обновляется
                        </span>
                    </div>

                    <div v-if="active" class="flex flex-1 flex-col gap-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <StatusBadge :status="active.status" pulse />
                            <span class="chip">{{ TYPE_META[active.type].label }}</span>
                            <span v-if="active.model" class="chip font-mono">{{ active.model }}</span>
                            <span v-if="active.latency_ms" class="chip">{{ formatLatency(active.latency_ms) }}</span>
                        </div>

                        <p class="rounded-xl border border-white/8 bg-white/3 p-3 text-xs leading-relaxed text-mist-200">
                            {{ active.prompt }}
                        </p>

                        <div v-if="active.assets.length > 0" class="grid gap-3 sm:grid-cols-2">
                            <AssetPreview
                                v-for="asset in active.assets"
                                :key="asset.uuid"
                                :asset="asset"
                                :prompt="active.prompt"
                            />
                        </div>

                        <div
                            v-else-if="active.status === 'queued' || active.status === 'processing'"
                            class="flex flex-1 flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-white/10 py-14"
                        >
                            <span class="h-9 w-9 animate-spin rounded-full border-2 border-brand-500/30 border-t-brand-400" />
                            <p class="text-xs text-mist-400">Генерация выполняется…</p>
                        </div>

                        <p
                            v-else-if="active.status === 'failed'"
                            class="rounded-xl border border-danger-400/30 bg-danger-400/10 p-3 text-xs text-danger-400"
                        >
                            {{ active.error_message || 'Генерация не удалась. Кредиты возвращены.' }}
                        </p>

                        <a
                            v-if="active.assets.length > 0"
                            :href="`/api/v1/assets/${active.assets[0]?.uuid}/download`"
                            class="btn-ghost mt-auto self-start"
                        >
                            <Icon name="download" :size="15" />
                            Скачать
                        </a>
                    </div>

                    <div v-else class="flex flex-1 flex-col items-center justify-center gap-2 py-16 text-center">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl border border-white/8 bg-white/5 text-mist-400">
                            <Icon name="spark" :size="20" />
                        </span>
                        <p class="text-xs text-mist-400">Здесь появится результат генерации</p>
                    </div>
                </section>
            </div>

            <section class="surface-raised p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-white">Недавние</h2>
                    <button type="button" class="btn-quiet !px-2.5 !py-1.5 text-[11px]" @click="refreshPage">
                        <Icon name="refresh" :size="13" />
                        Обновить
                    </button>
                </div>

                <div v-if="recent.length > 0" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <button
                        v-for="item in recent"
                        :key="item.uuid"
                        type="button"
                        class="group overflow-hidden rounded-xl border border-white/8 bg-white/3 p-2.5 text-left transition hover:border-white/16"
                        @click="active = item; start(item.uuid)"
                    >
                        <div class="mb-2 flex items-center justify-between">
                            <StatusBadge :status="item.status" />
                            <span class="text-[10px] text-mist-400">{{ relativeTime(item.created_at) }}</span>
                        </div>
                        <p class="line-clamp-2 text-[11px] leading-relaxed text-mist-300">{{ item.prompt }}</p>
                    </button>
                </div>
                <p v-else class="py-6 text-center text-xs text-mist-400">Пока пусто</p>
            </section>
        </div>
    </AppLayout>
</template>
