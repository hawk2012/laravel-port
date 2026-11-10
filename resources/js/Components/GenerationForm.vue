<script setup lang="ts">
import { route } from '@/lib/route';
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';
import { TYPE_META, TYPE_OPTIONS } from '@/lib/meta';
import type { GenerationType, Project } from '@/types';

const props = withDefaults(
    defineProps<{
        projects: Project[];
        typeOptions?: GenerationType[];
        compact?: boolean;
    }>(),
    { compact: false },
);

const type = ref<GenerationType>('image');
const prompt = ref('');
const projectId = ref<number | null>(props.projects[0]?.id ?? null);
const submitting = ref(false);
const errors = ref<Record<string, string>>({});

const cost = computed(() => TYPE_META[type.value].credits);
const available = computed(() => props.typeOptions ?? TYPE_OPTIONS);
const canSubmit = computed(() => prompt.value.trim().length >= 3 && projectId.value !== null && !submitting.value);

const placeholder = computed(() => {
    switch (type.value) {
        case 'image':
            return 'Промпт для изображения: «неоновый киберпанк-город под дождём, отражения в лужах, 35мм»';
        case 'video':
            return 'Опишите сцену: «пролёт камеры над горным озером на рассвете, туман, плавно»';
        case 'audio':
            return 'Опишите звук: «спокойный эмбиент с мягким пэдом и редкими колокольчиками»';
        default:
            return 'Что нужно написать: «пост для Telegram о запуске новой AI-функции, 600 знаков, дружелюбно»';
    }
});

function submit(): void {
    if (!canSubmit.value || projectId.value === null) {
        return;
    }

    submitting.value = true;
    errors.value = {};

    router.post(
        route('dashboard.generate'),
        { project_id: projectId.value, type: type.value, prompt: prompt.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                prompt.value = '';
            },
            onError: (e) => {
                errors.value = e as Record<string, string>;
            },
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
}
</script>

<template>
    <form class="surface-raised p-5" @submit.prevent="submit">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-white">Новая генерация</h2>
                <p class="mt-0.5 text-[11px] text-mist-400">
                    Задача уходит в очередь, кредиты списываются сразу и возвращаются при ошибке
                </p>
            </div>
            <span class="chip border-brand-500/25 bg-brand-500/10 text-brand-400">
                <Icon name="bolt" :size="13" />
                {{ cost }} кредитов
            </span>
        </div>

        <div class="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-4" role="radiogroup" aria-label="Тип генерации">
            <button
                v-for="option in available"
                :key="option"
                type="button"
                role="radio"
                :aria-checked="type === option"
                :class="[
                    'flex flex-col items-start gap-1 rounded-xl border px-3 py-2.5 text-left transition',
                    type === option
                        ? 'border-brand-500/60 bg-brand-500/12 text-white'
                        : 'border-white/8 bg-white/3 text-mist-300 hover:border-white/16 hover:bg-white/6',
                ]"
                @click="type = option"
            >
                <Icon :name="option" :size="16" />
                <span class="text-xs font-medium">{{ TYPE_META[option].label }}</span>
            </button>
        </div>

        <label class="label" for="prompt">Промпт</label>
        <textarea
            id="prompt"
            v-model="prompt"
            :placeholder="placeholder"
            :rows="compact ? 3 : 4"
            maxlength="2000"
            class="field resize-none"
            :aria-invalid="Boolean(errors.prompt)"
        />
        <div class="mt-1.5 flex items-center justify-between text-[11px] text-mist-400">
            <span :class="errors.prompt ? 'text-danger-400' : ''">{{ errors.prompt || 'Минимум 3 символа' }}</span>
            <span class="tabular-nums">{{ prompt.length }}/2000</span>
        </div>

        <div class="mt-4 flex flex-col gap-2.5 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label class="label" for="project">Проект</label>
                <select id="project" v-model="projectId" class="field">
                    <option v-if="projects.length === 0" :value="null">Сначала создайте проект</option>
                    <option v-for="project in projects" :key="project.id" :value="project.id">
                        {{ project.name }}
                    </option>
                </select>
            </div>

            <button type="submit" class="btn-primary h-[42px] sm:w-44" :disabled="!canSubmit">
                <Icon v-if="!submitting" name="spark" :size="16" />
                <span v-if="submitting" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                {{ submitting ? 'Отправка' : 'Сгенерировать' }}
            </button>
        </div>

        <p v-if="errors.project_id" class="mt-2 text-[11px] text-danger-400">{{ errors.project_id }}</p>
        <p v-if="projects.length === 0" class="mt-2 text-[11px] text-warn-400">
            Создайте проект в разделе «Проекты» — генерации привязаны к проекту.
        </p>
    </form>
</template>
