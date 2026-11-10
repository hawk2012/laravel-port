<script setup lang="ts">
import { route } from '@/lib/route';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/Components/Icon.vue';
import type { SharedProps } from '@/types';

const page = usePage<SharedProps>();
const user = computed(() => page.props.auth.user);

const links = [
    { label: 'Возможности', href: '#features' },
    { label: 'Архитектура', href: '#architecture' },
    { label: 'API', href: '#api' },
];
</script>

<template>
    <div class="relative min-h-screen overflow-hidden">
        <div class="glow-brand pointer-events-none absolute inset-x-0 top-0 h-[520px]" />
        <div class="grid-lines pointer-events-none absolute inset-0 opacity-40 [mask-image:radial-gradient(ellipse_at_top,black,transparent_72%)]" />

        <header class="relative z-10 mx-auto flex max-w-6xl items-center justify-between px-5 py-6">
            <Link :href="route('landing')" class="flex items-center gap-2.5">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-500 text-white shadow-lg shadow-brand-500/30">
                    <Icon name="bolt" :size="18" />
                </span>
                <span class="text-[15px] font-semibold text-white">
                    Kontur<span class="text-brand-400">AI</span>
                </span>
            </Link>

            <nav class="hidden items-center gap-7 text-sm text-mist-300 md:flex" aria-label="Навигация по лендингу">
                <a v-for="link in links" :key="link.href" :href="link.href" class="transition hover:text-white">
                    {{ link.label }}
                </a>
            </nav>

            <div class="flex items-center gap-2">
                <template v-if="user">
                    <Link :href="route('dashboard')" class="btn-primary">
                        Открыть дашборд
                        <Icon name="arrow-right" :size="16" />
                    </Link>
                </template>
                <template v-else>
                    <Link :href="route('login')" class="btn-quiet">Войти</Link>
                    <Link :href="route('register')" class="btn-primary">Начать бесплатно</Link>
                </template>
            </div>
        </header>

        <slot />

        <footer class="relative z-10 border-t border-white/8 py-8">
            <div class="mx-auto flex max-w-6xl flex-col gap-2 px-5 text-xs text-mist-400 sm:flex-row sm:items-center sm:justify-between">
                <p>Kontur AI — демонстрационный проект: Laravel 12 + Vue 3 + TypeScript + Inertia SSR.</p>
                <div class="flex gap-4">
                    <a href="/docs/openapi.yaml" class="transition hover:text-white">OpenAPI</a>
                    <a href="https://github.com/hawk2012/laravel-port" class="transition hover:text-white">GitHub</a>
                </div>
            </div>
        </footer>
    </div>
</template>
