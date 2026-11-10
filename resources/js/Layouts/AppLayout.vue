<script setup lang="ts">
import { route } from '@/lib/route';
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';
import type { SharedProps } from '@/types';
import { formatNumber } from '@/lib/format';

const page = usePage<SharedProps>();
const user = computed(() => page.props.auth.user);

const nav = [
    { label: 'Дашборд', route: 'dashboard', icon: 'grid' },
    { label: 'Студия', route: 'studio', icon: 'spark' },
    { label: 'История', route: 'history', icon: 'clock' },
    { label: 'Медиатека', route: 'library', icon: 'layers' },
    { label: 'Проекты', route: 'projects', icon: 'code' },
    { label: 'Использование', route: 'usage', icon: 'chart' },
];

const current = computed(() => page.url.split('?')[0]);
const creditsPercent = computed(() => {
    if (!user.value) {
        return 0;
    }

    const cap = user.value.plan === 'free' ? 500 : user.value.plan === 'pro' ? 10_000 : 100_000;

    return Math.min(100, Math.round((user.value.credits_balance / cap) * 100));
});
</script>

<template>
    <div class="min-h-screen lg:grid lg:grid-cols-[264px_1fr]">
        <aside class="border-b border-white/8 bg-ink-900/60 lg:sticky lg:top-0 lg:h-screen lg:border-r lg:border-b-0">
            <div class="flex h-full flex-col gap-6 p-5">
                <Link :href="route('dashboard')" class="flex items-center gap-2.5">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-500 text-white shadow-lg shadow-brand-500/30">
                        <Icon name="bolt" :size="18" />
                    </span>
                    <span class="text-[15px] leading-tight font-semibold text-white">
                        Kontur<span class="text-brand-400">AI</span>
                        <span class="block text-[11px] font-normal tracking-wide text-mist-400">content engine</span>
                    </span>
                </Link>

                <nav class="flex flex-wrap gap-1 lg:flex-col" aria-label="Основная навигация">
                    <Link
                        v-for="item in nav"
                        :key="item.route"
                        :href="route(item.route)"
                        :class="[
                            'flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                            current === route(item.route, undefined, false)
                                ? 'bg-white/8 text-white'
                                : 'text-mist-300 hover:bg-white/5 hover:text-white',
                        ]"
                    >
                        <Icon :name="item.icon" :size="17" />
                        {{ item.label }}
                    </Link>
                </nav>

                <div v-if="user" class="mt-auto space-y-3">
                    <div class="surface p-3.5">
                        <div class="mb-2 flex items-baseline justify-between">
                            <span class="text-[11px] font-semibold tracking-wide text-mist-400 uppercase">Кредиты</span>
                            <span class="text-sm font-semibold text-white">{{ formatNumber(user.credits_balance) }}</span>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-white/10">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-brand-500 to-accent-400 transition-all duration-700"
                                :style="{ width: `${creditsPercent}%` }"
                            />
                        </div>
                        <p class="mt-2 text-[11px] text-mist-400">
                            Тариф {{ user.plan_label }} · до {{ user.concurrent_limit }} задач параллельно
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5 rounded-xl border border-white/8 bg-white/3 p-2.5">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-500/20 text-xs font-bold text-brand-400">
                            {{ user.name.slice(0, 2).toUpperCase() }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold text-white">{{ user.name }}</p>
                            <p class="truncate text-[11px] text-mist-400">{{ user.email }}</p>
                        </div>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="btn-quiet !px-2 !py-2"
                            aria-label="Выйти"
                        >
                            <Icon name="logout" :size="15" />
                        </Link>
                    </div>
                </div>
            </div>
        </aside>

        <main class="min-w-0">
            <slot />
        </main>
    </div>
</template>
