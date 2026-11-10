<script setup lang="ts">
import { route } from '@/lib/route';
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Icon from '@/Components/Icon.vue';

defineProps<{
    stats: {
        types: Array<{ value: string; label: string; credits: number }>;
    };
}>();

const features = [
    {
        icon: 'spark',
        title: 'Один контракт — четыре модальности',
        text: 'Изображения, видео, аудио и текст идут через общий интерфейс AiProvider. Добавление нового вендора не трогает бизнес-логику.',
    },
    {
        icon: 'layers',
        title: 'Очереди и честные ретраи',
        text: 'Генерация — задача в Redis-очереди. Сетевые сбои повторяются с backoff, финальные ошибки возвращают кредиты.',
    },
    {
        icon: 'shield',
        title: 'Идемпотентность и лимиты',
        text: 'Idempotency-Key защищает от дублей, rate limit — от штормов, а лимит параллельных задач зависит от тарифа.',
    },
    {
        icon: 'chart',
        title: 'Прозрачный usage',
        text: 'Каждая генерация пишет кредиты, токены и латентность. Агрегаты считаются одним SQL-запросом и кешируются.',
    },
    {
        icon: 'code',
        title: 'Слои без магии',
        text: 'Controller → Service → Repository, DTO на входе и выходе, Eloquent-скоупы и индексы под реальные запросы.',
    },
    {
        icon: 'bolt',
        title: 'SSR из коробки',
        text: 'Inertia + Vue 3 рендерится на Node: страница приходит готовым HTML, гидрируется на клиенте без потери интерактивности.',
    },
];

const architecture = [
    { layer: 'HTTP', detail: 'FormRequest → Controller → JsonResource', note: 'Валидация отдельно от логики' },
    { layer: 'Domain', detail: 'Service + DTO + Enum', note: 'Кредиты, статусы, лимиты' },
    { layer: 'Data', detail: 'Repository + Eloquent', note: 'Запросы и индексы в одном месте' },
    { layer: 'Async', detail: 'Job → AiProvider', note: 'Ретраи, таймауты, refund' },
];
</script>

<template>
    <PublicLayout>
        <section class="relative z-10 mx-auto max-w-6xl px-5 pt-14 pb-20">
            <div class="max-w-3xl">
                <span class="chip animate-in border-brand-500/30 bg-brand-500/10 text-brand-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand-400" />
                    Laravel 12 · Vue 3 · TypeScript · Inertia SSR
                </span>

                <h1 class="animate-in mt-5 text-4xl leading-[1.05] font-semibold tracking-tight text-white text-balance sm:text-5xl lg:text-6xl">
                    Платформа генерации контента,
                    <span class="bg-gradient-to-r from-brand-400 via-brand-500 to-accent-400 bg-clip-text text-transparent">
                        собранная как продукт
                    </span>
                </h1>

                <p class="animate-in mt-5 max-w-2xl text-base leading-relaxed text-mist-300">
                    Не CRUD-пример, а рабочий контур: очередь задач, AI-провайдеры за общим контрактом,
                    списание и возврат кредитов, идемпотентность, лимиты и метрики. Всё, что нужно,
                    чтобы продукт жил под нагрузкой.
                </p>

                <div class="animate-in mt-8 flex flex-wrap items-center gap-3">
                    <Link :href="route('register')" class="btn-primary px-5 py-3">
                        Попробовать
                        <Icon name="arrow-right" :size="16" />
                    </Link>
                    <a href="#architecture" class="btn-ghost px-5 py-3">Как устроено</a>
                    <a
                        href="https://github.com/hawk2012/laravel-port"
                        class="btn-quiet px-3 py-3 text-mist-300"
                        target="_blank"
                        rel="noopener"
                    >
                        Исходный код
                    </a>
                </div>
            </div>

            <div class="mt-14 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="type in stats.types"
                    :key="type.value"
                    class="surface group p-4 transition hover:border-white/16 hover:bg-ink-850/90"
                >
                    <div class="flex items-center justify-between">
                        <span
                            :class="[
                                'grid h-9 w-9 place-items-center rounded-xl border border-white/8 bg-white/5',
                                type.value === 'image'
                                    ? 'text-brand-400'
                                    : type.value === 'video'
                                      ? 'text-danger-400'
                                      : type.value === 'audio'
                                        ? 'text-accent-400'
                                        : 'text-warn-400',
                            ]"
                        >
                            <Icon :name="type.value" :size="17" />
                        </span>
                        <span class="text-[11px] text-mist-400">{{ type.credits }} кр.</span>
                    </div>
                    <p class="mt-3 text-sm font-medium text-white">{{ type.label }}</p>
                    <p class="mt-0.5 text-[11px] text-mist-400">через единый AiProvider</p>
                </div>
            </div>
        </section>

        <section id="features" class="relative z-10 mx-auto max-w-6xl px-5 py-16">
            <h2 class="text-2xl font-semibold tracking-tight text-white">Что внутри</h2>
            <p class="mt-2 max-w-2xl text-sm text-mist-400">
                Решения, которые обычно появляются после первого инцидента в проде — здесь сделаны сразу.
            </p>

            <div class="mt-8 grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                <article v-for="feature in features" :key="feature.title" class="surface-raised p-5">
                    <span class="grid h-9 w-9 place-items-center rounded-xl border border-brand-500/20 bg-brand-500/12 text-brand-400">
                        <Icon :name="feature.icon" :size="17" />
                    </span>
                    <h3 class="mt-3.5 text-sm font-semibold text-white">{{ feature.title }}</h3>
                    <p class="mt-1.5 text-xs leading-relaxed text-mist-400">{{ feature.text }}</p>
                </article>
            </div>
        </section>

        <section id="architecture" class="relative z-10 mx-auto max-w-6xl px-5 py-16">
            <div class="grid gap-8 lg:grid-cols-[1fr_1.1fr] lg:items-center">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight text-white">Слои и границы</h2>
                    <p class="mt-2 text-sm leading-relaxed text-mist-400">
                        Контроллер не знает про Eloquent, сервис не знает про HTTP, репозиторий не знает
                        про бизнес-правила. Провайдер AI — интерфейс, поэтому stub в тестах и реальный
                        вендор в проде подменяются без правок.
                    </p>

                    <ul class="mt-6 space-y-2">
                        <li
                            v-for="item in architecture"
                            :key="item.layer"
                            class="flex items-start gap-3 rounded-xl border border-white/8 bg-white/3 p-3"
                        >
                            <span class="chip shrink-0 border-white/10 bg-white/5 text-mist-200">{{ item.layer }}</span>
                            <div class="min-w-0">
                                <p class="font-mono text-xs text-mist-100">{{ item.detail }}</p>
                                <p class="mt-0.5 text-[11px] text-mist-400">{{ item.note }}</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="surface-raised overflow-hidden">
                    <div class="flex items-center gap-2 border-b border-white/8 px-4 py-2.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-danger-500/70" />
                        <span class="h-2.5 w-2.5 rounded-full bg-warn-400/70" />
                        <span class="h-2.5 w-2.5 rounded-full bg-success-400/70" />
                        <span class="ml-2 font-mono text-[11px] text-mist-400">POST /api/v1/projects/1/generations</span>
                    </div>
                    <pre class="overflow-x-auto p-4 font-mono text-[11.5px] leading-relaxed text-mist-300"><code>{
  "type": "image",
  "prompt": "неоновый город под дождём",
  "params": { "size": "1024x1024" },
  "idempotency_key": "draft-42"
}

<span class="text-mist-500">// 202 Accepted</span>
{
  "data": {
    "uuid": "9f1c…",
    "status": "queued",
    "credits_used": 5,
    "assets": []
  }
}</code></pre>
                </div>
            </div>
        </section>

        <section id="api" class="relative z-10 mx-auto max-w-6xl px-5 py-16">
            <div class="surface-raised flex flex-col items-start justify-between gap-5 p-7 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-white">Открытое API</h2>
                    <p class="mt-1.5 max-w-xl text-sm text-mist-400">
                        Полная OpenAPI-спека в репозитории: аутентификация, проекты, генерации, ассеты, usage.
                        Sanctum-токены и коды ошибок описаны явно.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="/docs/openapi.yaml" class="btn-ghost" download>
                        <Icon name="download" :size="16" />
                        openapi.yaml
                    </a>
                    <Link :href="route('register')" class="btn-primary">Создать аккаунт</Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
