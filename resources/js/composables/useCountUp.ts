import { onMounted, onUnmounted, ref } from 'vue';

/**
 * Длительность анимации чисел на дашборде — с уважением к prefers-reduced-motion.
 */
export function useCountUp(target: number, duration = 900) {
    const display = ref(0);
    const prefersReduced =
        typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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
        if (prefersReduced || duration === 0) {
            display.value = target;

            return;
        }

        frame = requestAnimationFrame(step);
    });

    onUnmounted(() => cancelAnimationFrame(frame));

    return display;
}
