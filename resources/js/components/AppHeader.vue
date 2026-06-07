<template>
    <header class="flex items-center justify-between gap-4 px-4 py-4 md:px-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">
                Insider One
            </p>
            <h1 class="text-xl font-bold md:text-2xl">Champions League Simulator</h1>
        </div>
        <ThemeToggle />
    </header>
</template>

<script setup>
import ThemeToggle from './ThemeToggle.vue';
</script>
