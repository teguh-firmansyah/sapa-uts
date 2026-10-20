import { ref, watch } from "vue";
import settingService from "@/services/settingService";

const theme = ref("dark");
const allowOverride = ref(true);
const isInitialized = ref(false);

const STORAGE_KEY = "sapa_theme_preference";

function applyThemeToDocument(value) {
  document.documentElement.setAttribute("data-theme", value);
}

async function initTheme() {
  if (isInitialized.value) return;

  try {
    const settings = await settingService.getAppearanceSettings();
    allowOverride.value = settings.allow_user_override;

    const userPref = localStorage.getItem(STORAGE_KEY);

    if (userPref && settings.allow_user_override) {
      theme.value = userPref;
    } else {
      theme.value = settings.default_theme;
    }
  } catch {
    theme.value = localStorage.getItem(STORAGE_KEY) || "dark";
  }

  applyThemeToDocument(theme.value);
  isInitialized.value = true;
}

function toggleTheme() {
  if (!allowOverride.value) return;
  theme.value = theme.value === "dark" ? "light" : "dark";
  localStorage.setItem(STORAGE_KEY, theme.value);
  applyThemeToDocument(theme.value);
}

function setTheme(value) {
  theme.value = value;
  localStorage.setItem(STORAGE_KEY, value);
  applyThemeToDocument(value);
}

export function useTheme() {
  return {
    theme,
    allowOverride,
    isInitialized,
    initTheme,
    toggleTheme,
    setTheme,
  };
}
