import { onMounted, ref } from 'vue';

export function useReducedMotion() {
    const reducedMotion = ref(false);

    onMounted(() => {
        const media = window.matchMedia('(prefers-reduced-motion: reduce)');
        reducedMotion.value = media.matches;

        const handler = (event) => {
            reducedMotion.value = event.matches;
        };

        media.addEventListener('change', handler);

        return () => media.removeEventListener('change', handler);
    });

    return { reducedMotion };
}
