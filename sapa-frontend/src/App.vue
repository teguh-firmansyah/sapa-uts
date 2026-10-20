<script setup>
import { Toaster } from "vue-sonner";
import "vue-sonner/style.css";
import { onMounted } from "vue";
import { RouterView } from "vue-router";
import { useTheme } from "@/composables/useTheme";
import { useAuthStore } from "@/stores/auth";
import { applySeoSettings } from "@/composables/useSeo";

const authStore = useAuthStore();
const { initTheme } = useTheme();

onMounted(() => {
  authStore.fetchCurrentUser();
  initTheme();
  applySeoSettings();
});
</script>

<template>
  <Toaster
    theme="dark"
    position="top-right"
    richColors
    closeButton
    :toastOptions="{
      style: {
        background: '#1e293b',
        borderColor: '#334155',
        color: '#f8fafc',
      },
      classNames: {
        toast: 'border rounded-xl shadow-xl font-sans',
        title: 'text-sm font-semibold',
        description: 'text-xs text-slate-400',
        actionButton:
          'bg-emerald-500 text-slate-950 font-medium text-xs rounded-lg px-3 py-1.5',
        cancelButton:
          'bg-slate-700 text-slate-300 text-xs rounded-lg px-3 py-1.5',
        closeButton:
          'bg-slate-700 text-slate-400 hover:text-white border-slate-600',
      },
    }"
  />
  <router-view />
</template>
