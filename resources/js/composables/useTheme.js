import { ref } from "vue";

const isDark = ref(false);
const darkMode = "dark";
const lightMode = "light";

export function useTheme() {
    const toggleTheme = () => {
        isDark.value = !isDark.value;
        updateTheme();
    };

    const updateTheme = () => {
        const html = document.documentElement;
        if (isDark.value) {
            html.classList.add(darkMode);
        } else {
            html.classList.remove(darkMode);
        }
        localStorage.setItem("theme", isDark.value ? darkMode : lightMode);
    };

    const initTheme = () => {
        const savedTheme = localStorage.getItem("theme");
        const prefersDark = window.matchMedia(
            `(prefers-color-scheme: ${darkMode})`
        ).matches;

        if (savedTheme) {
            isDark.value = savedTheme === darkMode;
        } else {
            isDark.value = prefersDark;
        }

        updateTheme();
    };

    return {
        isDark,
        toggleTheme,
        initTheme,
    };
}
