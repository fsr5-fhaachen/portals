import { ref } from "vue";

export default () => {
  type ColorMode = "light" | "dark";

  const currentColorMode = ref<ColorMode>(localStorage.theme || "light");

  const setColorModeClass = (colorMode: ColorMode) => {
    if (colorMode === "light") {
      document.documentElement.classList.remove("dark");
    } else {
      document.documentElement.classList.add("dark");
    }
  };

  const setColorMode = (colorMode: ColorMode) => {
    currentColorMode.value = colorMode;
    setColorModeClass(colorMode);
    localStorage.theme = colorMode;
  };

  const getColorMode = () => {
    return currentColorMode.value;
  };

  const initColorMode = () => {
    if (
      localStorage.theme === "dark" ||
      (!("theme" in localStorage) &&
        window.matchMedia("(prefers-color-scheme: dark)").matches)
    ) {
      setColorMode("dark");
    } else {
      setColorMode("light");
    }
  };

  const toggleColorMode = () => {
    if (getColorMode() === "dark") {
      setColorMode("light");
    } else {
      setColorMode("dark");
    }
  };

  return {
    initColorMode,
    getColorMode,
    toggleColorMode,
  };
};
