import { ref, onUnmounted } from 'vue';
import { api, extractError } from '@/lib/api';
import type { Generation } from '@/types';

interface Options {
    intervalMs?: number;
    maxAttempts?: number;
}

/**
 * Поллинг статуса генерации. Останавливается автоматически при финальном
 * статусе или исчерпании попыток, а также при размонтировании компонента.
 */
export function useGenerationPolling(options: Options = {}) {
    const { intervalMs = 1500, maxAttempts = 60 } = options;

    const generation = ref<Generation | null>(null);
    const polling = ref(false);
    const error = ref<string | null>(null);

    let timer: ReturnType<typeof setTimeout> | null = null;
    let attempts = 0;
    let active = false;

    function stop(): void {
        active = false;
        polling.value = false;

        if (timer !== null) {
            clearTimeout(timer);
            timer = null;
        }
    }

    async function tick(uuid: string): Promise<void> {
        if (!active) {
            return;
        }

        try {
            const result = await api.generation(uuid);
            generation.value = result;

            if (result.status === 'completed' || result.status === 'failed' || result.status === 'cancelled') {
                stop();

                return;
            }
        } catch (e) {
            error.value = extractError(e).message;
            stop();

            return;
        }

        attempts += 1;

        if (attempts >= maxAttempts) {
            stop();

            return;
        }

        timer = setTimeout(() => void tick(uuid), intervalMs);
    }

    function start(uuid: string): void {
        stop();
        attempts = 0;
        error.value = null;
        active = true;
        polling.value = true;
        void tick(uuid);
    }

    onUnmounted(stop);

    return { generation, polling, error, start, stop };
}
