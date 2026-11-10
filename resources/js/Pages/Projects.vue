<script setup lang="ts">
import { route } from '@/lib/route';
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Toasts from '@/Components/Toasts.vue';
import Icon from '@/Components/Icon.vue';
import { formatNumber, relativeTime } from '@/lib/format';
import type { Project } from '@/types';

const props = defineProps<{
    projects: Project[];
    meta: { current_page: number; per_page: number; total: number; last_page: number };
    filters: { status?: string; search?: string };
    stats: { active: number; archived: number; total: number };
}>();

const showForm = ref(false);
const search = ref(props.filters.search ?? '');

const form = useForm({
    name: '',
    description: '',
});

function submit(): void {
    form.post(route('projects.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
}

function archive(project: Project): void {
    if (!confirm(`Архивировать проект «${project.name}»?`)) {
        return;
    }

    router.patch(route('projects.update', project.id), { status: 'archived' }, { preserveScroll: true });
}

let debounce: ReturnType<typeof setTimeout> | null = null;
function onSearch(): void {
    if (debounce) {
        clearTimeout(debounce);
    }
    debounce = setTimeout(() => {
        router.get(route('projects'), search.value ? { search: search.value } : {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 350);
}
</script>

<template>
    <AppLayout>
        <Toasts />

        <div class="mx-auto max-w-6xl space-y-5 px-5 py-6 lg:py-8">
            <header class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold tracking-tight text-white">Проекты</h1>
                    <p class="mt-1 text-sm text-mist-400">
                        {{ formatNumber(stats.active) }} активных · {{ formatNumber(stats.archived) }} в архиве
                    </p>
                </div>

                <button type="button" class="btn-primary" @click="showForm = !showForm">
                    <Icon :name="showForm ? 'close' : 'plus'" :size="16" />
                    {{ showForm ? 'Отмена' : 'Новый проект' }}
                </button>
            </header>

            <form v-if="showForm" class="surface-raised space-y-3 p-5" @submit.prevent="submit">
                <div>
                    <label class="label" for="project-name">Название</label>
                    <input id="project-name" v-model="form.name" class="field" maxlength="120" placeholder="Кампания «Осень»" />
                    <p v-if="form.errors.name" class="mt-1 text-[11px] text-danger-400">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="label" for="project-desc">Описание</label>
                    <textarea
                        id="project-desc"
                        v-model="form.description"
                        rows="2"
                        maxlength="500"
                        class="field resize-none"
                        placeholder="Для чего этот проект"
                    />
                </div>

                <button type="submit" class="btn-primary" :disabled="form.processing || form.name.length < 2">
                    Создать
                </button>
            </form>

            <div class="relative">
                <Icon name="search" :size="15" class="pointer-events-none absolute top-3 left-3 text-mist-400" />
                <input
                    v-model="search"
                    class="field pl-9"
                    placeholder="Поиск по проектам"
                    aria-label="Поиск по проектам"
                    @input="onSearch"
                />
            </div>

            <div v-if="projects.length > 0" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <article v-for="project in projects" :key="project.id" class="surface-raised flex flex-col p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h2 class="truncate text-sm font-semibold text-white">{{ project.name }}</h2>
                            <p class="mt-0.5 truncate font-mono text-[10px] text-mist-400">{{ project.slug }}</p>
                        </div>
                        <span
                            :class="[
                                'chip shrink-0',
                                project.status === 'active'
                                    ? 'border-success-400/30 bg-success-400/10 text-success-400'
                                    : 'border-white/10 bg-white/5 text-mist-300',
                            ]"
                        >
                            {{ project.status === 'active' ? 'активен' : 'архив' }}
                        </span>
                    </div>

                    <p class="mt-2.5 line-clamp-2 min-h-[32px] text-xs leading-relaxed text-mist-400">
                        {{ project.description || 'Без описания' }}
                    </p>

                    <div class="mt-3 flex items-center justify-between border-t border-white/8 pt-3">
                        <div class="text-[11px] text-mist-400">
                            <span class="font-semibold text-mist-200">{{ project.generations_count ?? 0 }}</span> генераций
                            <span class="mx-1">·</span>
                            {{ relativeTime(project.created_at) }}
                        </div>

                        <button
                            v-if="project.status === 'active'"
                            type="button"
                            class="btn-quiet !px-2 !py-1.5 text-[11px]"
                            @click="archive(project)"
                        >
                            <Icon name="trash" :size="13" />
                            В архив
                        </button>
                    </div>
                </article>
            </div>

            <div v-else class="surface-raised py-16 text-center">
                <p class="text-sm text-mist-300">Проектов пока нет</p>
                <p class="mt-1 text-xs text-mist-400">Создайте проект — к нему привязываются генерации</p>
            </div>
        </div>
    </AppLayout>
</template>
