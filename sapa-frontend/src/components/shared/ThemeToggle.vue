<script setup>
import { Sun, Moon } from "lucide-vue-next";
import { useTheme } from "@/composables/useTheme";

const { theme, allowOverride, toggleTheme } = useTheme();
</script>

<template>
  <button
    v-if="allowOverride"
    type="button"
    @click="toggleTheme"
    :title="theme === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'"
    class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/80 text-slate-400 transition-all duration-200 hover:border-slate-700 hover:text-slate-200 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
  >
    <Sun v-if="theme === 'dark'" class="h-4 w-4" />
    <Moon v-else class="h-4 w-4" />
  </button>
</template>
