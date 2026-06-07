<template>
    <button
        type="button"
        class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full border border-slate-200 bg-white px-3 text-lg transition-colors duration-200 hover:bg-slate-100 active:scale-95 dark:border-slate-700 dark:bg-slate-900 dark:hover:bg-slate-800"
        :aria-label="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
        @click="toggleTheme"
    >
        <span v-if="theme === 'dark'">☀️</span>
        <span v-else>🌙</span>
    </button>
</template>

<script setup>
import { useTheme } from '../composables/useTheme';

const { theme, toggleTheme } = useTheme();
</script>
