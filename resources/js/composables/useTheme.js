import { onMounted, ref } from 'vue';

export function useTheme() {
    const theme = ref('light');

    const applyTheme = (value) => {
        theme.value = value;
        document.documentElement.classList.toggle('dark', value === 'dark');
        localStorage.setItem('theme', value);
    };

    const toggleTheme = () => {
        applyTheme(theme.value === 'dark' ? 'light' : 'dark');
    };

    onMounted(() => {
        const stored = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyTheme(stored ?? (prefersDark ? 'dark' : 'light'));
    });

    return { theme, toggleTheme, applyTheme };
}
