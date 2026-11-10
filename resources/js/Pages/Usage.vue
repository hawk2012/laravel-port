<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Toasts from '@/Components/Toasts.vue';
import StatCard from '@/Components/StatCard.vue';
import Icon from '@/Components/Icon.vue';
import { formatDayShort, formatLatency, formatNumber } from '@/lib/format';
import { TYPE_META } from '@/lib/meta';
import type { GenerationType } from '@/types';

const props = defineProps<{
    days: number;
    summary: {
        generations: number;
        completed: number;
        failed: number;
        credits_spent: number;
        input_tokens: number;
        output_tokens: number;
        avg_latency_ms: number;
        by_type: Record<string, number>;
    };
    daily: Array<{ date: string; credits: number }>;
    projects: { active: number; archived: number; total: number };
}>();

const typeRows = computed(() =>
    Object.entries(props.summary.by_type).map(([type, count]) => ({
        type: type as GenerationType,
        label: TYPE_META[type as GenerationType]?.label ?? type,
        count,
    })),
);

const maxCredits = computed(() => Math.max(1, ...props.daily.map((row) => row.credits)));
</script>

<template>
    <AppLayout>
        <Toasts />

        <div class="mx-auto max-w-6xl space-y-6 px-5 py-6 lg:py-8">
            <header>
                <h1 class="text-xl font-semibold tracking-tight text-white">Использование</h1>
                <p class="mt-1 text-sm text-mist-400">Период: последние {{ days }} дней</p>
            </header>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Генераций" :value="summary.generations" :hint="`${summary.completed} успешно`" icon="spark" />
                <StatCard label="Кредитов" :value="summary.credits_spent" hint="Списано за период" icon="bolt" accent="warn" />
                <StatCard
                    label="Токенов"
                    :value="summary.input_tokens + summary.output_tokens"
                    :hint="`${formatNumber(summary.input_tokens)} вход / ${formatNumber(summary.output_tokens)} выход`"
                    icon="code"
                    accent="accent"
                />
                <StatCard
                    label="Ср. латентность"
                    :value="summary.avg_latency_ms"
                    :hint="formatLatency(summary.avg_latency_ms)"
                    icon="clock"
                    accent="danger"
                    :animate="false"
                />
            </section>

            <div class="grid gap-5 lg:grid-cols-[1.4fr_1fr]">
                <section class="surface-raised p-5">
                    <div class="mb-4">
                        <h2 class="text-sm font-semibold text-white">Кредиты по дням</h2>
                        <p class="mt-0.5 text-[11px] text-mist-400">Пик: {{ formatNumber(maxCredits) }} кр./день</p>
                    </div>

                    <div class="flex items-end gap-1.5" style="height: 150px">
                        <div
                            v-for="row in daily"
                            :key="row.date"
                            class="group flex h-full flex-1 flex-col justify-end"
                            :title="`${row.date}: ${row.credits} кредитов`"
                        >
                            <div
                                class="w-full rounded-t-md bg-gradient-to-t from-accent-500/60 to-accent-400 transition-all duration-500"
                                :style="{ height: `${Math.max(2, Math.round((row.credits / maxCredits) * 150))}px` }"
                            />
                        </div>
                    </div>

                    <div class="mt-2 flex justify-between text-[10px] text-mist-400">
                        <span>{{ daily.length > 0 ? formatDayShort(daily[0]!.date) : '' }}</span>
                        <span>{{ daily.length > 0 ? formatDayShort(daily[daily.length - 1]!.date) : '' }}</span>
                    </div>
                </section>

                <section class="surface-raised p-5">
                    <h2 class="text-sm font-semibold text-white">По типам</h2>
                    <p class="mt-0.5 mb-4 text-[11px] text-mist-400">Списания за период</p>

                    <ul class="space-y-2.5">
                        <li
                            v-for="row in typeRows"
                            :key="row.type"
                            class="flex items-center justify-between rounded-xl border border-white/8 bg-white/3 px-3 py-2.5"
                        >
                            <span class="flex items-center gap-2 text-xs text-mist-200">
                                <Icon :name="row.type" :size="14" />
                                {{ row.label }}
                            </span>
                            <span class="text-sm font-semibold text-white tabular-nums">{{ formatNumber(row.count) }}</span>
                        </li>
                        <li
                            v-if="typeRows.length === 0"
                            class="rounded-xl border border-dashed border-white/10 px-3 py-8 text-center text-xs text-mist-400"
                        >
                            Нет данных за период
                        </li>
                    </ul>

                    <div class="mt-4 grid grid-cols-3 gap-2 border-t border-white/8 pt-4 text-center">
                        <div>
                            <p class="text-lg font-semibold text-white tabular-nums">{{ projects.active }}</p>
                            <p class="text-[10px] text-mist-400">активных</p>
                        </div>
                        <div>
                            <p class="text-lg font-semibold text-white tabular-nums">{{ projects.archived }}</p>
                            <p class="text-[10px] text-mist-400">в архиве</p>
                        </div>
                        <div>
                            <p class="text-lg font-semibold text-white tabular-nums">{{ projects.total }}</p>
                            <p class="text-[10px] text-mist-400">всего</p>
                        </div>
                    </div>
                </section>
            </div>

            <section class="surface-raised p-5">
                <h2 class="mb-4 text-sm font-semibold text-white">Ошибки и успешность</h2>
                <div class="flex flex-wrap items-center gap-6">
                    <div>
                        <p class="text-2xl font-semibold text-success-400 tabular-nums">{{ summary.completed }}</p>
                        <p class="text-[11px] text-mist-400">успешно</p>
                    </div>
                    <div>
                        <p class="text-2xl font-semibold text-danger-400 tabular-nums">{{ summary.failed }}</p>
                        <p class="text-[11px] text-mist-400">с ошибкой</p>
                    </div>
                    <div class="ml-auto max-w-md text-[11px] leading-relaxed text-mist-400">
                        При ошибке провайдера кредиты возвращаются автоматически. Сетевые сбои повторяются
                        с экспоненциальным backoff, поэтому временная недоступность вендора не превращается
                        в потерянные задачи.
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
