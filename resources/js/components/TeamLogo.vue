<template>
    <img
        :src="src"
        :alt="alt"
        class="shrink-0 rounded-full object-contain bg-white p-0.5 ring-1 ring-slate-200 dark:ring-slate-700"
        :class="sizeClass"
        loading="lazy"
    />
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    team: { type: Object, required: true },
    size: { type: String, default: 'md' },
});

const src = computed(() => props.team.logo_url ?? `/logos/${props.team.slug}.png`);
const alt = computed(() => props.team.name ?? props.team.short_name);

const sizeClass = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'h-8 w-8';
        case 'lg':
            return 'h-14 w-14';
        default:
            return 'h-10 w-10';
    }
});
</script>
