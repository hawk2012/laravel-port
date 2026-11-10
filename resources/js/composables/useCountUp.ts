import { onMounted, onUnmounted, ref } from 'vue';

/**
 * Анимация числа от 0 к целевому значению.
 *
 * Стартовое значение равно target, чтобы серверный HTML содержал настоящее
 * число (иначе SSR отдаёт нули и это ломает и SEO, и первый рендер).
 * Анимация запускается только после монтирования, когда window уже доступен.
 */
export function useCountUp(target: number, duration = 900) {
    const display = ref(target);

    let frame = 0;
    let startedAt = 0;

    function step(timestamp: number): void {
        if (startedAt === 0) {
            startedAt = timestamp;
        }

        const progress = Math.min((timestamp - startedAt) / duration, 1);
        const eased = 1 - (1 - progress) ** 3;
        display.value = Math.round(target * eased);

        if (progress < 1) {
            frame = requestAnimationFrame(step);
        }
    }

    onMounted(() => {
        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (prefersReduced || duration === 0 || target === 0) {
            display.value = target;

            return;
        }

        display.value = 0;
        frame = requestAnimationFrame(step);
    });

    onUnmounted(() => cancelAnimationFrame(frame));

    return display;
}
