<script setup lang="ts">
import { route } from '@/lib/route';
import { Link, useForm } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit(): void {
    form.post(route('login.store'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-5 py-10">
        <div class="glow-brand pointer-events-none absolute inset-x-0 top-0 h-[420px]" />
        <div class="grid-lines pointer-events-none absolute inset-0 opacity-30" />

        <div class="animate-in relative z-10 w-full max-w-sm">
            <Link :href="route('landing')" class="mb-8 flex items-center justify-center gap-2.5">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-500 text-white shadow-lg shadow-brand-500/30">
                    <Icon name="bolt" :size="18" />
                </span>
                <span class="text-[15px] font-semibold text-white">Kontur<span class="text-brand-400">AI</span></span>
            </Link>

            <div class="surface-raised p-6">
                <h1 class="text-lg font-semibold text-white">Вход</h1>
                <p class="mt-1 text-xs text-mist-400">Продолжите работу с проектами и генерациями</p>

                <form class="mt-5 space-y-3.5" @submit.prevent="submit">
                    <div>
                        <label class="label" for="email">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            required
                            class="field"
                            placeholder="dev@example.com"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-[11px] text-danger-400">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="label" for="password">Пароль</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="field"
                            placeholder="••••••••"
                        />
                        <p v-if="form.errors.password" class="mt-1 text-[11px] text-danger-400">{{ form.errors.password }}</p>
                    </div>

                    <label class="flex cursor-pointer items-center gap-2 text-xs text-mist-300">
                        <input v-model="form.remember" type="checkbox" class="h-3.5 w-3.5 rounded border-white/20 bg-ink-850 accent-brand-500" />
                        Запомнить меня
                    </label>

                    <button type="submit" class="btn-primary w-full" :disabled="form.processing">
                        <span v-if="form.processing" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                        Войти
                    </button>
                </form>

                <p class="mt-4 text-center text-xs text-mist-400">
                    Нет аккаунта?
                    <Link :href="route('register')" class="font-medium text-brand-400 hover:text-brand-400/80">Регистрация</Link>
                </p>
            </div>
        </div>
    </div>
</template>
