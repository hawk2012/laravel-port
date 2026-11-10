<script setup lang="ts">
import { route } from '@/lib/route';
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import ActivityChart from '@/Components/ActivityChart.vue';
import RecentList from '@/Components/RecentList.vue';
import GenerationForm from '@/Components/GenerationForm.vue';
import Icon from '@/Components/Icon.vue';
import Toasts from '@/Components/Toasts.vue';
import { formatLatency, formatNumber } from '@/lib/format';
import { TYPE_META } from '@/lib/meta';
import type { DashboardStats, GenerationType, Project } from '@/types';

const props = defineProps<{
    stats: DashboardStats;
    projects: { data: Project[] };
    typeOptions: GenerationType[];
    statusOptions: string[];
}>();

const projectList = computed(() => props.projects.data ?? []);
const completed = computed(() => props.stats.status_counts.completed ?? 0);
const failed = computed(() => props.stats.status_counts.failed ?? 0);
const running = computed(() => (props.stats.status_counts.queued ?? 0) + (props.stats.status_counts.processing ?? 0));
const successRate = computed(() => {
    const total = completed.value + failed.value;

    return total === 0 ? 100 : Math.round((completed.value / total) * 100);
});

const typeBreakdown = computed(() =>
    (Object.keys(TYPE_META) as GenerationType[]).map((type) => ({
        type,
        label: TYPE_META[type].label,
        count: props.stats.type_counts[type] ?? 0,
    })),
);

const maxType = computed(() => Math.max(1, ...typeBreakdown.value.map((row) => row.count)));
</script>

<template>
    <AppLayout>
        <Toasts />

        <div class="mx-auto max-w-6xl space-y-6 px-5 py-6 lg:py-8">
            <header class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold tracking-tight text-white">Дашборд</h1>
                    <p class="mt-1 text-sm text-mist-400">
                        Сводка за 30 дней · {{ formatNumber(stats.usage.generations) }} генераций,
                        {{ formatNumber(stats.usage.credits_spent) }} кредитов
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('studio')" class="btn-ghost">
                        <Icon name="spark" :size="16" />
                        Студия
                    </Link>
                    <Link :href="route('history')" class="btn-primary">
                        История
                        <Icon name="arrow-right" :size="16" />
                    </Link>
                </div>
            </header>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ключевые метрики">
                <StatCard
                    label="Генераций"
                    :value="stats.usage.generations"
                    :hint="`${completed} успешно`"
                    icon="spark"
                    accent="brand"
                />
                <StatCard
                    label="В работе"
                    :value="running"
                    hint="Очередь и обработка"
                    icon="clock"
                    accent="warn"
                />
                <StatCard
                    label="Успешность"
                    :value="successRate"
                    hint="Доля завершённых без ошибок"
                    icon="shield"
                    accent="accent"
                />
                <StatCard
                    label="Ср. время"
                    :value="stats.usage.avg_latency_ms"
                    :hint="formatLatency(stats.usage.avg_latency_ms)"
                    icon="bolt"
                    accent="danger"
                    :animate="false"
                />
            </section>

            <div class="grid gap-5 lg:grid-cols-[1.35fr_1fr]">
                <section class="surface-raised p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-white">Активность</h2>
                            <p class="mt-0.5 text-[11px] text-mist-400">Генерации по дням за 14 дней</p>
                        </div>
                        <span class="chip">{{ stats.project_stats.active }} проектов</span>
                    </div>
                    <ActivityChart :series="stats.daily" :height="140" />
                </section>

                <section class="surface-raised p-5">
                    <h2 class="text-sm font-semibold text-white">Распределение по типам</h2>
                    <p class="mt-0.5 mb-4 text-[11px] text-mist-400">Всего за всё время</p>

                    <ul class="space-y-3">
                        <li v-for="row in typeBreakdown" :key="row.type">
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="flex items-center gap-1.5 text-mist-200">
                                    <Icon :name="row.type" :size="13" />
                                    {{ row.label }}
                                </span>
                                <span class="font-semibold text-white tabular-nums">{{ formatNumber(row.count) }}</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-white/8">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-brand-600 to-accent-400 transition-all duration-700"
                                    :style="{ width: `${Math.round((row.count / maxType) * 100)}%` }"
                                />
                            </div>
                        </li>
                    </ul>
                </section>
            </div>

            <div class="grid gap-5 lg:grid-cols-[1fr_1.35fr]">
                <GenerationForm :projects="projectList" :type-options="typeOptions" compact />

                <section class="surface-raised p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-white">Последние генерации</h2>
                        <Link :href="route('history')" class="text-[11px] font-medium text-brand-400 hover:text-brand-400/80">
                            Вся история →
                        </Link>
                    </div>

                    <RecentList v-if="stats.recent.length > 0" :items="stats.recent" />
                    <p v-else class="py-8 text-center text-xs text-mist-400">
                        Пока нет генераций. Запустите первую — результат появится здесь.
                    </p>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
