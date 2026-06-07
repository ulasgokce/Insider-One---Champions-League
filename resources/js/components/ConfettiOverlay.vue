<template>
    <div
        v-if="active"
        class="pointer-events-none fixed inset-0 z-[100] overflow-hidden"
        aria-hidden="true"
    >
        <span
            v-for="piece in pieces"
            :key="piece.id"
            class="confetti-piece absolute block h-2 w-2 rounded-sm"
            :style="piece.style"
        />
    </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    trigger: { type: Boolean, default: false },
});

const active = ref(false);
const pieces = ref([]);

const colors = ['#10b981', '#06b6d4', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#fbbf24'];

const launch = () => {
    active.value = true;
    pieces.value = Array.from({ length: 80 }, (_, id) => ({
        id,
        style: {
            left: `${Math.random() * 100}%`,
            top: '-8px',
            backgroundColor: colors[Math.floor(Math.random() * colors.length)],
            animationDelay: `${Math.random() * 0.8}s`,
            animationDuration: `${2 + Math.random() * 2}s`,
            transform: `rotate(${Math.random() * 360}deg)`,
        },
    }));

    setTimeout(() => {
        active.value = false;
        pieces.value = [];
    }, 4500);
};

watch(
    () => props.trigger,
    (value, oldValue) => {
        if (value && !oldValue) {
            launch();
        }
    },
);

onMounted(() => {
    if (props.trigger) {
        launch();
    }
});
</script>

<style scoped>
.confetti-piece {
    animation-name: confetti-fall;
    animation-timing-function: cubic-bezier(0.25, 0.46, 0.45, 0.94);
    animation-fill-mode: forwards;
}

@keyframes confetti-fall {
    0% {
        opacity: 1;
        transform: translateY(0) rotate(0deg);
    }
    100% {
        opacity: 0;
        transform: translateY(100vh) rotate(720deg);
    }
}

@media (prefers-reduced-motion: reduce) {
    .confetti-piece {
        animation: none !important;
        opacity: 0;
    }
}
</style>
