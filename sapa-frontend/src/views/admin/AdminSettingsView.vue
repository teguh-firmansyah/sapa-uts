<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { toast } from "vue-sonner";
import { useAuthStore } from "@/stores/auth";
import settingService from "@/services/settingService";
import NotificationBell from "@/components/shared/NotificationBell.vue";
import ThemeToggle from "@/components/shared/ThemeToggle.vue";
import { useTheme } from "@/composables/useTheme";
import {
  BarChart3,
  FileText,
  Users,
  LogOut,
  Settings,
  Lightbulb,
  GraduationCap,
  School,
  Image as ImageIcon,
  Check,
  Loader2,
  Upload,
  X,
  Moon,
  Sun,
  MapPin,
  Phone,
  Mail,
  Globe,
  Search,
  ShieldCheck,
  Lock,
} from "lucide-vue-next";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { setTheme } = useTheme();

const logoFailed = ref(false);
const isLoading = ref(true);
const isSaving = ref(false);

/* ---------------------------------- */
/* Navigasi panel (existing)          */
/* ---------------------------------- */
const navItems = [
  { label: "Analitik", to: "/admin/dashboard", icon: BarChart3 },
  { label: "Semua Laporan", to: "/admin/reports", icon: FileText },
  { label: "Aspirasi", to: "/admin/aspirations", icon: Lightbulb },
  { label: "Kelas", to: "/admin/classes", icon: GraduationCap },
  { label: "Manajemen User", to: "/admin/users", icon: Users },
  { label: "Pengaturan", to: "/admin/settings", icon: Settings },
];

const isActive = (item) =>
  route.path === item.to || route.path.startsWith(item.to + "/");

const activeTab = ref("general");
const settingTabs = [
  { value: "general", label: "Umum", icon: School },
  { value: "appearance", label: "Tampilan", icon: ImageIcon },
  { value: "seo", label: "SEO", icon: FileText },
];

/* ---------------------------------- */
/* Pengaturan umum (existing)         */
/* ---------------------------------- */
const form = ref({
  school_name: "",
  school_address: "",
  school_phone: "",
  school_email: "",
});
const currentLogoUrl = ref(null);
const logoFile = ref(null);
const logoPreview = ref(null);
const logoInputRef = ref(null);
const formErrors = ref({});

async function loadSettings() {
  isLoading.value = true;
  try {
    const data = await settingService.getGeneralSettings();
    form.value = {
      school_name: data.school_name,
      school_address: data.school_address,
      school_phone: data.school_phone,
      school_email: data.school_email,
    };
    currentLogoUrl.value = data.logo_url;
  } catch {
    toast.error("Gagal memuat pengaturan.");
  } finally {
    isLoading.value = false;
  }
}

function handleLogoChange(e) {
  const file = e.target.files[0];
  e.target.value = "";
  if (!file) return;
  if (!file.type.startsWith("image/")) {
    toast.error("Format berkas tidak didukung. Unggah PNG, JPG, atau SVG.");
    return;
  }
  if (file.size > 1024 * 1024) {
    toast.error("Ukuran logo maksimal 1MB.");
    return;
  }
  if (logoPreview.value?.startsWith("blob:"))
    URL.revokeObjectURL(logoPreview.value);
  logoFile.value = file;
  logoPreview.value = URL.createObjectURL(file);
}

function cancelLogoPreview() {
  if (logoPreview.value?.startsWith("blob:"))
    URL.revokeObjectURL(logoPreview.value);
  logoFile.value = null;
  logoPreview.value = null;
}

function validateForm() {
  const e = {};
  if (!form.value.school_name.trim())
    e.school_name = "Nama sekolah wajib diisi";
  if (
    form.value.school_email &&
    !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.school_email)
  ) {
    e.school_email = "Format email tidak valid";
  }
  return e;
}

async function handleSaveGeneral() {
  formErrors.value = validateForm();
  if (Object.keys(formErrors.value).length > 0) {
    toast.error("Mohon lengkapi data yang belum valid.");
    return;
  }

  isSaving.value = true;
  try {
    await settingService.updateGeneralSettings({
      ...form.value,
      logo: logoFile.value,
    });
    toast.success("Pengaturan umum berhasil disimpan.");
    logoFile.value = null;
    await loadSettings(); // reload supaya logo_url terbaru
  } catch (error) {
    const validationErrors = error?.response?.data?.errors;
    if (validationErrors) {
      formErrors.value = Object.fromEntries(
        Object.entries(validationErrors).map(([k, v]) => [k, v[0]]),
      );
    }
    toast.error(
      error?.response?.data?.message || "Gagal menyimpan pengaturan.",
    );
  } finally {
    isSaving.value = false;
  }
}

/* Bersihkan error saat field diedit */
watch(
  () => [form.value.school_name, form.value.school_email],
  () => {
    for (const key of ["school_name", "school_email"]) {
      if (formErrors.value[key]) formErrors.value[key] = "";
    }
  },
);

/* ---------------------------------- */
/* Pengaturan tampilan (existing)     */
/* ---------------------------------- */
const appearanceForm = ref({
  default_theme: "dark",
  allow_user_override: true,
});
const isSavingAppearance = ref(false);

async function loadAppearance() {
  try {
    const data = await settingService.getAppearanceSettings();
    appearanceForm.value = {
      default_theme: data.default_theme,
      allow_user_override: data.allow_user_override,
    };
  } catch {
    toast.error("Gagal memuat pengaturan tampilan.");
  }
}

async function handleSaveAppearance() {
  isSavingAppearance.value = true;
  try {
    await settingService.updateAppearanceSettings(appearanceForm.value);
    toast.success("Pengaturan tampilan berhasil disimpan.");
    setTheme(appearanceForm.value.default_theme);
  } catch (error) {
    toast.error(
      error?.response?.data?.message || "Gagal menyimpan pengaturan tampilan.",
    );
  } finally {
    isSavingAppearance.value = false;
  }
}

/* ---------------------------------- */
/* Pengaturan SEO (existing +         */
/* pratinjau langsung)                */
/* ---------------------------------- */
const seoForm = ref({
  meta_title: "",
  meta_description: "",
  meta_keywords: "",
});
const currentOgImageUrl = ref(null);
const currentFaviconUrl = ref(null);
const ogImageFile = ref(null);
const ogImagePreview = ref(null);
const ogImageInputRef = ref(null);
const faviconFile = ref(null);
const faviconPreview = ref(null);
const faviconInputRef = ref(null);
const isSavingSeo = ref(false);
const seoErrors = ref({});

async function loadSeo() {
  try {
    const data = await settingService.getSeoSettings();
    seoForm.value = {
      meta_title: data.meta_title,
      meta_description: data.meta_description,
      meta_keywords: data.meta_keywords,
    };
    currentOgImageUrl.value = data.og_image_url;
    currentFaviconUrl.value = data.favicon_url;
  } catch {
    toast.error("Gagal memuat pengaturan SEO.");
  }
}

function handleOgImageChange(e) {
  const file = e.target.files[0];
  e.target.value = "";
  if (!file) return;
  if (!file.type.startsWith("image/")) {
    toast.error("Format berkas tidak didukung. Unggah PNG, JPG, atau JPEG.");
    return;
  }
  if (file.size > 1024 * 1024) {
    toast.error("Ukuran gambar maksimal 1MB.");
    return;
  }
  if (ogImagePreview.value?.startsWith("blob:"))
    URL.revokeObjectURL(ogImagePreview.value);
  ogImageFile.value = file;
  ogImagePreview.value = URL.createObjectURL(file);
}

function cancelOgPreview() {
  if (ogImagePreview.value?.startsWith("blob:"))
    URL.revokeObjectURL(ogImagePreview.value);
  ogImageFile.value = null;
  ogImagePreview.value = null;
}

function handleFaviconChange(e) {
  const file = e.target.files[0];
  e.target.value = "";
  if (!file) return;
  const isFaviconLike =
    file.type.startsWith("image/") || file.name.toLowerCase().endsWith(".ico");
  if (!isFaviconLike) {
    toast.error("Format favicon tidak didukung. Unggah .ico atau .png.");
    return;
  }
  if (file.size > 512 * 1024) {
    toast.error("Ukuran favicon maksimal 512KB.");
    return;
  }
  if (faviconPreview.value?.startsWith("blob:"))
    URL.revokeObjectURL(faviconPreview.value);
  faviconFile.value = file;
  faviconPreview.value = URL.createObjectURL(file);
}

function cancelFaviconPreview() {
  if (faviconPreview.value?.startsWith("blob:"))
    URL.revokeObjectURL(faviconPreview.value);
  faviconFile.value = null;
  faviconPreview.value = null;
}

function validateSeoForm() {
  const e = {};
  if (!seoForm.value.meta_title.trim()) e.meta_title = "Judul meta wajib diisi";
  else if (seoForm.value.meta_title.length > 70)
    e.meta_title = "Maksimal 70 karakter";
  if (
    seoForm.value.meta_description &&
    seoForm.value.meta_description.length > 160
  ) {
    e.meta_description = "Maksimal 160 karakter";
  }
  return e;
}

async function handleSaveSeo() {
  seoErrors.value = validateSeoForm();
  if (Object.keys(seoErrors.value).length > 0) return;

  isSavingSeo.value = true;
  try {
    await settingService.updateSeoSettings({
      ...seoForm.value,
      og_image: ogImageFile.value,
      favicon: faviconFile.value,
    });
    toast.success("Pengaturan SEO berhasil disimpan.");
    ogImageFile.value = null;
    faviconFile.value = null;
    await loadSeo();
  } catch (error) {
    const validationErrors = error?.response?.data?.errors;
    if (validationErrors) {
      seoErrors.value = Object.fromEntries(
        Object.entries(validationErrors).map(([k, v]) => [k, v[0]]),
      );
    }
    toast.error(
      error?.response?.data?.message || "Gagal menyimpan pengaturan SEO.",
    );
  } finally {
    isSavingSeo.value = false;
  }
}

watch(
  () => [seoForm.value.meta_title, seoForm.value.meta_description],
  () => {
    for (const key of ["meta_title", "meta_description"]) {
      if (seoErrors.value[key]) seoErrors.value[key] = "";
    }
  },
);

/* ---------- Turunan pratinjau SEO ---------- */
const previewDomain = computed(() => {
  const domain = (form.value.school_email || "").split("@")[1];
  return domain || "sekolah.sch.id";
});

const previewTitle = computed(
  () =>
    seoForm.value.meta_title?.trim() ||
    "SAPA — Sistem Aspirasi dan Pengaduan Sekolah",
);

const previewDesc = computed(
  () =>
    seoForm.value.meta_description?.trim() ||
    "Platform pelaporan aspirasi, fasilitas, dan pengaduan bullying bagi warga sekolah.",
);

const ogSrc = computed(() => ogImagePreview.value || currentOgImageUrl.value);
const faviconSrc = computed(
  () => faviconPreview.value || currentFaviconUrl.value,
);

const titleCountClass = computed(() =>
  seoForm.value.meta_title.length > 70
    ? "text-red-400"
    : seoForm.value.meta_title.length > 0
      ? "text-emerald-500/80"
      : "text-slate-600",
);

const descCountClass = computed(() =>
  (seoForm.value.meta_description?.length || 0) > 160
    ? "text-red-400"
    : (seoForm.value.meta_description?.length || 0) > 0
      ? "text-emerald-500/80"
      : "text-slate-600",
);

const formatFileSize = (bytes) => {
  if (!bytes) return "";
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

/* ---------------------------------- */
/* Inisialisasi & logout (existing)   */
/* ---------------------------------- */
onMounted(() => {
  loadSettings();
  loadAppearance();
  loadSeo();
});

const handleLogout = async () => {
  await authStore.logout();
  toast.success("Berhasil keluar dari sistem.");
  router.push({ name: "login" });
};

const currentYear = new Date().getFullYear();
</script>

<template>
  <div
    class="sapa-root flex min-h-screen flex-col bg-slate-950 font-sans text-slate-100 antialiased selection:bg-emerald-500/25"
  >
    <!-- ============ Bar atas ============ -->
    <header
      class="sticky top-0 z-50 border-b border-slate-800/80 bg-slate-950/85 backdrop-blur-md"
    >
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div
          class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 py-3 md:h-16 md:py-0"
        >
          <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
            <div
              class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-700 bg-slate-800 ring-1 ring-emerald-500/20"
            >
              <img
                v-if="!logoFailed"
                src="@/assets/logo/logo sapa.jpeg"
                alt="Logo SAPA"
                class="h-full w-full object-cover"
                @error="logoFailed = true"
              />
              <span v-else class="text-sm font-extrabold text-emerald-400"
                >S</span
              >
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <p
                  class="text-[15px] font-extrabold leading-none tracking-tight text-white"
                >
                  SAPA
                </p>
                <span
                  class="rounded border border-blue-500/30 bg-blue-500/10 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-blue-400"
                  >Admin</span
                >
              </div>
              <p
                class="mt-1 hidden truncate text-[9px] font-medium uppercase leading-none tracking-[0.16em] text-slate-500 lg:block"
              >
                Pengaturan &amp; Konfigurasi Sistem
              </p>
            </div>
          </div>

          <nav
            class="no-scrollbar order-3 -mx-1 flex w-full items-center gap-1 overflow-x-auto pb-1 md:order-2 md:mx-0 md:w-auto md:border-l md:border-slate-800/80 md:pb-0 md:pl-6"
            aria-label="Navigasi utama"
          >
            <router-link
              v-for="item in navItems"
              :key="item.to"
              :to="item.to"
              :aria-current="isActive(item) ? 'page' : undefined"
              class="flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg border px-3 py-1.5 text-xs font-semibold transition-all duration-200"
              :class="
                isActive(item)
                  ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                  : 'border-transparent text-slate-400 hover:bg-slate-900 hover:text-slate-200'
              "
            >
              <component :is="item.icon" class="h-3.5 w-3.5" />
              <span>{{ item.label }}</span>
            </router-link>
          </nav>

          <div class="order-2 flex items-center gap-2 md:order-3">
            <ThemeToggle />
            <NotificationBell detail-route-name="admin-report-detail" />

            <button
              type="button"
              @click="handleLogout"
              title="Keluar dari akun"
              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/80 text-slate-500 transition-all duration-200 hover:border-rose-500/30 hover:bg-rose-500/10 hover:text-rose-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/50"
            >
              <LogOut class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- ============ Konten ============ -->
    <main
      class="mx-auto w-full max-w-7xl flex-1 space-y-8 px-4 py-8 sm:px-6 lg:px-8 lg:py-10"
    >
      <!-- Kepala halaman -->
      <section class="fade-up">
        <div class="flex items-center gap-2.5">
          <span
            class="h-2 w-2 rounded-full bg-emerald-500"
            aria-hidden="true"
          ></span>
          <p
            class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-300/90"
          >
            Panel Admin · Pengaturan
          </p>
        </div>
        <h1
          class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl"
        >
          Pengaturan Sistem
        </h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-400">
          Kelola identitas sekolah, tampilan, dan konfigurasi sistem SAPA.
        </p>
      </section>

      <!-- Tab pengaturan -->
      <nav
        class="no-scrollbar fade-up -mx-1 flex gap-1 overflow-x-auto rounded-xl border border-slate-800 bg-slate-900 p-1.5"
        style="animation-delay: 60ms"
        aria-label="Bagian pengaturan"
      >
        <button
          v-for="tab in settingTabs"
          :key="tab.value"
          type="button"
          :disabled="tab.disabled"
          :aria-pressed="activeTab === tab.value"
          @click="activeTab = tab.value"
          class="flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg border px-3.5 py-2 text-xs font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-40 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
          :class="
            activeTab === tab.value
              ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
              : 'border-transparent text-slate-400 hover:bg-slate-950/60 hover:text-slate-200'
          "
        >
          <component :is="tab.icon" class="h-3.5 w-3.5" />
          {{ tab.label }}
          <span
            v-if="tab.disabled"
            class="rounded bg-slate-800 px-1.5 py-0.5 text-[9px] font-medium text-slate-500"
            >Segera</span
          >
        </button>
      </nav>

      <!-- Skeleton loading -->
      <div
        v-if="isLoading"
        class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900"
        style="animation-delay: 100ms"
        aria-busy="true"
      >
        <div
          class="flex items-center gap-3 border-b border-slate-800/70 px-5 py-4"
        >
          <div class="h-8 w-8 animate-pulse rounded-lg bg-slate-800"></div>
          <div class="space-y-1.5">
            <div class="h-3 w-40 animate-pulse rounded bg-slate-800"></div>
            <div class="h-2 w-56 animate-pulse rounded bg-slate-800"></div>
          </div>
        </div>
        <div class="space-y-4 p-5">
          <div class="flex items-center gap-4">
            <div class="h-16 w-16 animate-pulse rounded-xl bg-slate-800"></div>
            <div class="h-8 w-32 animate-pulse rounded-lg bg-slate-800"></div>
          </div>
          <div class="h-10 w-full animate-pulse rounded-lg bg-slate-800"></div>
          <div class="h-20 w-full animate-pulse rounded-lg bg-slate-800"></div>
          <div class="grid grid-cols-2 gap-4">
            <div
              class="h-10 w-full animate-pulse rounded-lg bg-slate-800"
            ></div>
            <div
              class="h-10 w-full animate-pulse rounded-lg bg-slate-800"
            ></div>
          </div>
        </div>
      </div>

      <!-- ===== Tab: Umum ===== -->
      <section
        v-else-if="activeTab === 'general'"
        class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900"
        style="animation-delay: 100ms"
      >
        <div class="border-b border-slate-800/70 px-5 py-4 sm:px-6">
          <h2 class="text-sm font-bold tracking-tight text-slate-100">
            Informasi Sekolah
          </h2>
          <p class="mt-0.5 text-xs text-slate-500">
            Identitas sekolah yang tampil di seluruh sistem SAPA.
          </p>
        </div>

        <form @submit.prevent="handleSaveGeneral" class="space-y-5 p-5 sm:p-6">
          <!-- Logo -->
          <div>
            <label
              class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
              >Logo Sekolah</label
            >
            <div
              class="flex items-center gap-4 rounded-xl border border-slate-800 bg-slate-950/40 p-4"
            >
              <div
                class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-700 bg-slate-800"
              >
                <img
                  v-if="logoPreview || currentLogoUrl"
                  :src="logoPreview || currentLogoUrl"
                  alt="Logo sekolah"
                  class="h-full w-full object-cover"
                />
                <ImageIcon v-else class="h-6 w-6 text-slate-600" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-slate-200">
                  {{ logoFile ? "Logo baru siap diunggah" : "Logo saat ini" }}
                </p>
                <p
                  v-if="logoFile"
                  class="mt-0.5 truncate font-mono text-[10px] text-emerald-500/80"
                >
                  {{ logoFile.name }} · {{ formatFileSize(logoFile.size) }}
                </p>
                <p v-else class="mt-0.5 text-[11px] text-slate-500">
                  PNG, JPG, atau SVG · Maks. 1MB
                </p>
                <div class="mt-2 flex flex-wrap gap-2">
                  <input
                    ref="logoInputRef"
                    type="file"
                    accept="image/*"
                    class="hidden"
                    @change="handleLogoChange"
                  />
                  <button
                    type="button"
                    @click="logoInputRef?.click()"
                    class="inline-flex items-center gap-1.5 rounded-md border border-slate-700 bg-slate-800/60 px-2.5 py-1 text-[11px] font-semibold text-slate-300 transition-all duration-150 hover:border-emerald-500/40 hover:text-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
                  >
                    <Upload class="h-3.5 w-3.5" />
                    {{ logoFile ? "Ganti Berkas" : "Ganti Logo" }}
                  </button>
                  <button
                    v-if="logoFile"
                    type="button"
                    @click="cancelLogoPreview"
                    class="inline-flex items-center gap-1.5 rounded-md border border-slate-700 bg-slate-800/60 px-2.5 py-1 text-[11px] font-semibold text-slate-300 transition-all duration-150 hover:border-rose-500/40 hover:bg-rose-500/10 hover:text-rose-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/50"
                  >
                    <X class="h-3.5 w-3.5" />
                    Batalkan
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Nama sekolah -->
          <div>
            <label
              for="school-name"
              class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
              >Nama Sekolah</label
            >
            <div class="relative">
              <School
                class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
              />
              <input
                id="school-name"
                v-model="form.school_name"
                type="text"
                autocomplete="organization"
                placeholder="SMK Negeri 1 Contoh"
                :aria-invalid="!!formErrors.school_name || undefined"
                class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                :class="
                  formErrors.school_name
                    ? 'border-red-400/60'
                    : 'border-slate-800'
                "
              />
            </div>
            <p
              v-if="formErrors.school_name"
              class="mt-1.5 text-[11px] font-medium text-red-400"
            >
              {{ formErrors.school_name }}
            </p>
          </div>

          <!-- Alamat -->
          <div>
            <label
              for="school-address"
              class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
              >Alamat Sekolah</label
            >
            <div class="relative">
              <MapPin
                class="pointer-events-none absolute left-3.5 top-3.5 h-4 w-4 text-slate-500"
              />
              <textarea
                id="school-address"
                v-model="form.school_address"
                rows="3"
                placeholder="Jl. Pendidikan No. 1, Kota Contoh"
                class="w-full resize-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              ></textarea>
            </div>
          </div>

          <!-- Telepon + Email -->
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label
                for="school-phone"
                class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
                >Telepon Sekolah</label
              >
              <div class="relative">
                <Phone
                  class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
                />
                <input
                  id="school-phone"
                  v-model="form.school_phone"
                  type="tel"
                  autocomplete="tel"
                  placeholder="(021) 1234567"
                  class="w-full rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                />
              </div>
            </div>
            <div>
              <label
                for="school-email"
                class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
                >Email Sekolah</label
              >
              <div class="relative">
                <Mail
                  class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
                />
                <input
                  id="school-email"
                  v-model="form.school_email"
                  type="email"
                  autocomplete="email"
                  placeholder="info@sekolah.sch.id"
                  :aria-invalid="!!formErrors.school_email || undefined"
                  class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                  :class="
                    formErrors.school_email
                      ? 'border-red-400/60'
                      : 'border-slate-800'
                  "
                />
              </div>
              <p
                v-if="formErrors.school_email"
                class="mt-1.5 text-[11px] font-medium text-red-400"
              >
                {{ formErrors.school_email }}
              </p>
            </div>
          </div>

          <div
            class="flex flex-col-reverse gap-3 border-t border-slate-800/70 pt-5 sm:flex-row sm:items-center sm:justify-between"
          >
            <p class="flex items-center gap-1.5 text-[10px] text-slate-600">
              <ShieldCheck class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" />
              Perubahan identitas sekolah berlaku di seluruh sistem.
            </p>
            <button
              type="submit"
              :disabled="isSaving"
              class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
            >
              <Check v-if="!isSaving" class="h-4 w-4" />
              <Loader2 v-else class="h-4 w-4 animate-spin" />
              {{ isSaving ? "Menyimpan..." : "Simpan Pengaturan" }}
            </button>
          </div>
        </form>
      </section>

      <!-- ===== Tab: Tampilan ===== -->
      <section
        v-else-if="activeTab === 'appearance'"
        class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900"
        style="animation-delay: 100ms"
      >
        <div class="border-b border-slate-800/70 px-5 py-4 sm:px-6">
          <h2 class="text-sm font-bold tracking-tight text-slate-100">
            Tampilan Sistem
          </h2>
          <p class="mt-0.5 text-xs text-slate-500">
            Atur tema default dan opsi personalisasi pengguna.
          </p>
        </div>

        <form
          @submit.prevent="handleSaveAppearance"
          class="space-y-5 p-5 sm:p-6"
        >
          <!-- Tema default: kartu radio dengan mini-pratinjau -->
          <div class="space-y-2.5">
            <span
              class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
              >Tema Default Sistem</span
            >
            <div
              class="grid grid-cols-1 gap-3 sm:grid-cols-2"
              role="radiogroup"
              aria-label="Tema default"
            >
              <button
                type="button"
                :aria-pressed="appearanceForm.default_theme === 'dark'"
                @click="appearanceForm.default_theme = 'dark'"
                class="rounded-xl border p-3 text-left transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50 active:scale-[.99]"
                :class="
                  appearanceForm.default_theme === 'dark'
                    ? 'border-emerald-500/50 bg-emerald-500/6'
                    : 'border-slate-800 bg-slate-950/40 hover:border-slate-700'
                "
              >
                <!-- Mini-pratinjau gelap -->
                <span
                  class="block overflow-hidden rounded-lg border"
                  :class="
                    appearanceForm.default_theme === 'dark'
                      ? 'border-emerald-500/40'
                      : 'border-slate-800'
                  "
                >
                  <span class="block space-y-2 bg-slate-950 p-2.5">
                    <span class="flex items-center justify-between">
                      <span class="h-2.5 w-10 rounded-sm bg-slate-800"></span>
                      <span
                        class="h-2.5 w-8 rounded-full bg-emerald-500"
                      ></span>
                    </span>
                    <span
                      class="block h-1.5 w-full rounded bg-slate-800"
                    ></span>
                    <span class="block h-1.5 w-3/4 rounded bg-slate-800"></span>
                    <span
                      class="block h-1.5 w-1/2 rounded bg-emerald-500/40"
                    ></span>
                  </span>
                </span>
                <span class="mt-3 flex items-center justify-between gap-2">
                  <span class="flex items-center gap-2">
                    <Moon
                      class="h-4 w-4"
                      :class="
                        appearanceForm.default_theme === 'dark'
                          ? 'text-emerald-400'
                          : 'text-slate-500'
                      "
                    />
                    <span>
                      <span class="block text-xs font-semibold text-slate-100"
                        >Gelap</span
                      >
                      <span class="block text-[10px] text-slate-500"
                        >Identitas utama SAPA</span
                      >
                    </span>
                  </span>
                  <span
                    v-if="appearanceForm.default_theme === 'dark'"
                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-emerald-500 bg-emerald-500 text-slate-950"
                  >
                    <Check class="h-3 w-3" />
                  </span>
                </span>
              </button>

              <button
                type="button"
                :aria-pressed="appearanceForm.default_theme === 'light'"
                @click="appearanceForm.default_theme = 'light'"
                class="rounded-xl border p-3 text-left transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50 active:scale-[.99]"
                :class="
                  appearanceForm.default_theme === 'light'
                    ? 'border-emerald-500/50 bg-emerald-500/6'
                    : 'border-slate-800 bg-slate-950/40 hover:border-slate-700'
                "
              >
                <!-- Mini-pratinjau terang -->
                <span
                  class="block overflow-hidden rounded-lg border"
                  :class="
                    appearanceForm.default_theme === 'light'
                      ? 'border-emerald-500/40'
                      : 'border-slate-800'
                  "
                >
                  <span class="block space-y-2 bg-white p-2.5">
                    <span class="flex items-center justify-between">
                      <span class="h-2.5 w-10 rounded-sm bg-slate-200"></span>
                      <span
                        class="h-2.5 w-8 rounded-full bg-emerald-500"
                      ></span>
                    </span>
                    <span
                      class="block h-1.5 w-full rounded bg-slate-200"
                    ></span>
                    <span class="block h-1.5 w-3/4 rounded bg-slate-200"></span>
                    <span
                      class="block h-1.5 w-1/2 rounded bg-emerald-400/50"
                    ></span>
                  </span>
                </span>
                <span class="mt-3 flex items-center justify-between gap-2">
                  <span class="flex items-center gap-2">
                    <Sun
                      class="h-4 w-4"
                      :class="
                        appearanceForm.default_theme === 'light'
                          ? 'text-emerald-400'
                          : 'text-slate-500'
                      "
                    />
                    <span>
                      <span class="block text-xs font-semibold text-slate-100"
                        >Terang</span
                      >
                      <span class="block text-[10px] text-slate-500"
                        >Kontras tinggi untuk siang hari</span
                      >
                    </span>
                  </span>
                  <span
                    v-if="appearanceForm.default_theme === 'light'"
                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-emerald-500 bg-emerald-500 text-slate-950"
                  >
                    <Check class="h-3 w-3" />
                  </span>
                </span>
              </button>
            </div>
          </div>

          <!-- Izinkan pengguna mengubah tema: kendali toggle -->
          <label
            for="allow-user-theme"
            class="flex cursor-pointer select-none items-center gap-4 rounded-xl border p-4 transition-colors duration-200"
            :class="
              appearanceForm.allow_user_override
                ? 'border-emerald-500/30 bg-emerald-500/5'
                : 'border-slate-800 bg-slate-950/40 hover:border-slate-700'
            "
          >
            <input
              id="allow-user-theme"
              v-model="appearanceForm.allow_user_override"
              type="checkbox"
              class="peer sr-only"
            />

            <span
              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border transition-colors duration-200"
              :class="
                appearanceForm.allow_user_override
                  ? 'border-emerald-500/40 bg-emerald-500/15 text-emerald-400'
                  : 'border-slate-700 bg-slate-900 text-slate-500'
              "
            >
              <Users class="h-4 w-4" />
            </span>

            <span class="min-w-0 flex-1">
              <span class="block text-xs font-semibold text-slate-100"
                >Izinkan Pengguna Mengubah Tema Sendiri</span
              >
              <span
                class="mt-1 block text-[11px] leading-relaxed text-slate-500"
              >
                {{
                  appearanceForm.allow_user_override
                    ? "Setiap pengguna dapat memilih tema gelap atau terang pada perangkatnya masing-masing."
                    : "Semua pengguna akan selalu memakai tema default sistem."
                }}
              </span>
            </span>

            <span
              class="relative h-5 w-9 shrink-0 rounded-full transition-colors duration-200 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-400/60 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-slate-900"
              :class="
                appearanceForm.allow_user_override
                  ? 'bg-emerald-500'
                  : 'bg-slate-700'
              "
            >
              <span
                class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform duration-200"
                :class="
                  appearanceForm.allow_user_override
                    ? 'translate-x-4'
                    : 'translate-x-0'
                "
              ></span>
            </span>
          </label>

          <div
            class="flex flex-col-reverse gap-3 border-t border-slate-800/70 pt-5 sm:flex-row sm:items-center sm:justify-between"
          >
            <p class="flex items-center gap-1.5 text-[10px] text-slate-600">
              <ShieldCheck class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" />
              Tema diterapkan segera setelah disimpan.
            </p>
            <button
              type="submit"
              :disabled="isSavingAppearance"
              class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
            >
              <Check v-if="!isSavingAppearance" class="h-4 w-4" />
              <Loader2 v-else class="h-4 w-4 animate-spin" />
              {{ isSavingAppearance ? "Menyimpan..." : "Simpan Pengaturan" }}
            </button>
          </div>
        </form>
      </section>

      <!-- ===== Tab: SEO ===== -->
      <section
        v-else-if="activeTab === 'seo'"
        class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900"
        style="animation-delay: 100ms"
      >
        <div class="border-b border-slate-800/70 px-5 py-4 sm:px-6">
          <h2 class="text-sm font-bold tracking-tight text-slate-100">
            Pengaturan SEO
          </h2>
          <p class="mt-0.5 text-xs text-slate-500">
            Judul, deskripsi, dan gambar pratinjau saat tautan sistem dibagikan.
          </p>
        </div>

        <form @submit.prevent="handleSaveSeo" class="space-y-5 p-5 sm:p-6">
          <!-- Judul meta -->
          <div>
            <div class="mb-2 flex items-center justify-between">
              <label
                for="meta-title"
                class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
                >Judul Meta (Judul Tab Browser)</label
              >
              <span
                class="font-mono text-[10px] tabular-nums"
                :class="titleCountClass"
                >{{ seoForm.meta_title.length }}/70</span
              >
            </div>
            <input
              id="meta-title"
              v-model="seoForm.meta_title"
              type="text"
              placeholder="SAPA — Sistem Aspirasi dan Pengaduan Sekolah"
              :aria-invalid="!!seoErrors.meta_title || undefined"
              class="w-full rounded-lg border bg-slate-950/60 py-2.5 px-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              :class="
                seoErrors.meta_title ? 'border-red-400/60' : 'border-slate-800'
              "
            />
            <p
              v-if="seoErrors.meta_title"
              class="mt-1.5 text-[11px] font-medium text-red-400"
            >
              {{ seoErrors.meta_title }}
            </p>
          </div>

          <!-- Deskripsi meta -->
          <div>
            <div class="mb-2 flex items-center justify-between">
              <label
                for="meta-desc"
                class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
                >Deskripsi Meta</label
              >
              <span
                class="font-mono text-[10px] tabular-nums"
                :class="descCountClass"
                >{{ seoForm.meta_description?.length || 0 }}/160</span
              >
            </div>
            <textarea
              id="meta-desc"
              v-model="seoForm.meta_description"
              rows="3"
              placeholder="Platform pelaporan aspirasi, fasilitas, dan pengaduan bullying bagi warga sekolah."
              :aria-invalid="!!seoErrors.meta_description || undefined"
              class="w-full resize-none rounded-lg border bg-slate-950/60 py-2.5 px-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              :class="
                seoErrors.meta_description
                  ? 'border-red-400/60'
                  : 'border-slate-800'
              "
            ></textarea>
            <p
              v-if="seoErrors.meta_description"
              class="mt-1.5 text-[11px] font-medium text-red-400"
            >
              {{ seoErrors.meta_description }}
            </p>
            <p v-else class="mt-1.5 text-[10px] text-slate-600">
              Tampil di hasil pencarian dan pratinjau tautan.
            </p>
          </div>

          <!-- Keywords -->
          <div>
            <label
              for="meta-keywords"
              class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
              >Kata Kunci (Keywords)</label
            >
            <input
              id="meta-keywords"
              v-model="seoForm.meta_keywords"
              type="text"
              placeholder="SAPA, aspirasi sekolah, pengaduan sekolah, laporan bullying"
              class="w-full rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 px-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
            />
            <p class="mt-1.5 text-[10px] text-slate-600">
              Pisahkan dengan koma.
            </p>
          </div>

          <!-- Unggahan OG & favicon -->
          <div
            class="grid grid-cols-1 gap-5 border-t border-slate-800/70 pt-5 sm:grid-cols-2"
          >
            <div>
              <label
                class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
                >Gambar Pratinjau (Open Graph)</label
              >
              <div
                class="flex h-28 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-700 bg-slate-950/40"
              >
                <img
                  v-if="ogSrc"
                  :src="ogSrc"
                  alt="Pratinjau OG"
                  class="h-full w-full object-cover"
                />
                <ImageIcon v-else class="h-6 w-6 text-slate-600" />
              </div>
              <input
                ref="ogImageInputRef"
                type="file"
                accept="image/*"
                class="hidden"
                @change="handleOgImageChange"
              />
              <div class="mt-2 flex flex-wrap gap-2">
                <button
                  type="button"
                  @click="ogImageInputRef?.click()"
                  class="inline-flex items-center gap-1.5 rounded-md border border-slate-700 bg-slate-800/60 px-2.5 py-1 text-[11px] font-semibold text-slate-300 transition-all duration-150 hover:border-emerald-500/40 hover:text-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
                >
                  <Upload class="h-3.5 w-3.5" />
                  {{ ogImageFile ? "Ganti Berkas" : "Unggah Gambar" }}
                </button>
                <button
                  v-if="ogImageFile"
                  type="button"
                  @click="cancelOgPreview"
                  class="inline-flex items-center gap-1.5 rounded-md border border-slate-700 bg-slate-800/60 px-2.5 py-1 text-[11px] font-semibold text-slate-300 transition-all duration-150 hover:border-rose-500/40 hover:bg-rose-500/10 hover:text-rose-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/50"
                >
                  <X class="h-3.5 w-3.5" />
                  Batalkan
                </button>
              </div>
              <p
                v-if="ogImageFile"
                class="mt-1.5 truncate font-mono text-[10px] text-emerald-500/80"
              >
                {{ ogImageFile.name }} ·
                {{ formatFileSize(ogImageFile.size) }} · siap diunggah
              </p>
              <p v-else class="mt-1.5 text-[10px] text-slate-600">
                Disarankan 1200×630px · Maks. 1MB
              </p>
            </div>

            <div>
              <label
                class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
                >Favicon</label
              >
              <div
                class="flex h-28 items-center justify-center overflow-hidden rounded-lg border border-dashed border-slate-700 bg-slate-950/40"
              >
                <img
                  v-if="faviconSrc"
                  :src="faviconSrc"
                  alt="Favicon"
                  class="h-10 w-10 object-contain"
                />
                <ImageIcon v-else class="h-6 w-6 text-slate-600" />
              </div>
              <input
                ref="faviconInputRef"
                type="file"
                accept=".ico,.png"
                class="hidden"
                @change="handleFaviconChange"
              />
              <div class="mt-2 flex flex-wrap gap-2">
                <button
                  type="button"
                  @click="faviconInputRef?.click()"
                  class="inline-flex items-center gap-1.5 rounded-md border border-slate-700 bg-slate-800/60 px-2.5 py-1 text-[11px] font-semibold text-slate-300 transition-all duration-150 hover:border-emerald-500/40 hover:text-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
                >
                  <Upload class="h-3.5 w-3.5" />
                  {{ faviconFile ? "Ganti Berkas" : "Unggah Favicon" }}
                </button>
                <button
                  v-if="faviconFile"
                  type="button"
                  @click="cancelFaviconPreview"
                  class="inline-flex items-center gap-1.5 rounded-md border border-slate-700 bg-slate-800/60 px-2.5 py-1 text-[11px] font-semibold text-slate-300 transition-all duration-150 hover:border-rose-500/40 hover:bg-rose-500/10 hover:text-rose-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/50"
                >
                  <X class="h-3.5 w-3.5" />
                  Batalkan
                </button>
              </div>
              <p
                v-if="faviconFile"
                class="mt-1.5 truncate font-mono text-[10px] text-emerald-500/80"
              >
                {{ faviconFile.name }} ·
                {{ formatFileSize(faviconFile.size) }} · siap diunggah
              </p>
              <p v-else class="mt-1.5 text-[10px] text-slate-600">
                Format .ico atau .png · Maks. 512KB
              </p>
            </div>
          </div>

          <!-- ===== Pratinjau langsung ===== -->
          <div class="space-y-4 border-t border-slate-800/70 pt-5">
            <div class="flex items-center gap-2.5">
              <Search class="h-4 w-4 text-emerald-400" />
              <div>
                <p class="text-xs font-bold text-slate-100">
                  Pratinjau Langsung
                </p>
                <p class="text-[10px] text-slate-500">
                  Diperbarui otomatis mengikuti isian &amp; unggahan Anda.
                </p>
              </div>
            </div>

            <!-- Hasil pencarian (tampilan aslinya terang) -->
            <div class="max-w-xl">
              <p
                class="mb-1.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600"
              >
                Hasil Pencarian Google
              </p>
              <div class="rounded-xl bg-white p-4 sm:p-5">
                <div class="flex items-center gap-2.5">
                  <img
                    v-if="faviconSrc"
                    :src="faviconSrc"
                    alt=""
                    class="h-7 w-7 rounded-full border border-slate-200 object-contain p-0.5"
                  />
                  <div
                    v-else
                    class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-100"
                  >
                    <Globe class="h-4 w-4 text-slate-400" />
                  </div>
                  <div class="min-w-0">
                    <p class="truncate text-xs font-medium text-[#202124]">
                      {{ form.school_name?.trim() || "Nama Sekolah" }}
                    </p>
                    <p class="text-[10px] text-[#4d5156]">
                      https://{{ previewDomain }}
                    </p>
                  </div>
                </div>
                <p
                  class="mt-2 text-[15px] font-medium leading-snug text-[#1a0dab]"
                >
                  {{ previewTitle }}
                </p>
                <p class="clamp-2 mt-1 text-xs leading-relaxed text-[#4d5156]">
                  {{ previewDesc }}
                </p>
              </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <!-- Tab browser -->
              <div>
                <p
                  class="mb-1.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600"
                >
                  Tab Browser
                </p>
                <div
                  class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900"
                >
                  <div
                    class="flex items-end gap-1 border-b border-slate-800 bg-slate-950/60 px-3 pt-3"
                  >
                    <div
                      class="flex max-w-55 items-center gap-2 rounded-t-lg border border-b-0 border-slate-700 bg-slate-900 px-3 py-2"
                    >
                      <img
                        v-if="faviconSrc"
                        :src="faviconSrc"
                        alt=""
                        class="h-4 w-4 shrink-0 rounded-sm object-contain"
                      />
                      <Globe v-else class="h-4 w-4 shrink-0 text-slate-500" />
                      <span class="truncate text-[11px] text-slate-200">{{
                        previewTitle
                      }}</span>
                      <X class="h-3 w-3 shrink-0 text-slate-500" />
                    </div>
                    <div
                      class="h-8.5 w-14 rounded-t-lg border border-b-0 border-slate-800 bg-slate-950/60"
                    ></div>
                  </div>
                  <div class="flex items-center gap-2 px-3 py-2.5">
                    <Lock class="h-3 w-3 shrink-0 text-emerald-400/70" />
                    <span class="truncate font-mono text-[10px] text-slate-400"
                      >https://{{ previewDomain }}</span
                    >
                  </div>
                </div>
              </div>

              <!-- Kartu tautan (gaya WhatsApp) -->
              <div>
                <p
                  class="mb-1.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-600"
                >
                  Kartu Tautan Saat Dibagikan
                </p>
                <div class="overflow-hidden rounded-xl border border-slate-800">
                  <div
                    class="flex h-36 items-center justify-center border-b border-slate-800 bg-slate-950/60"
                  >
                    <img
                      v-if="ogSrc"
                      :src="ogSrc"
                      alt="Pratinjau OG"
                      class="h-full w-full object-cover"
                    />
                    <div
                      v-else
                      class="flex flex-col items-center gap-1.5 text-slate-600"
                    >
                      <ImageIcon class="h-6 w-6" />
                      <span class="text-[10px] font-semibold"
                        >Belum ada gambar OG</span
                      >
                    </div>
                  </div>
                  <div class="bg-slate-800/60 p-3.5">
                    <p
                      class="truncate text-[10px] font-medium uppercase tracking-wider text-slate-400"
                    >
                      {{ previewDomain }}
                    </p>
                    <p
                      class="mt-1 truncate text-xs font-semibold text-slate-100"
                    >
                      {{ previewTitle }}
                    </p>
                    <p
                      class="clamp-2 mt-0.5 text-[11px] leading-snug text-slate-400"
                    >
                      {{ previewDesc }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div
            class="flex flex-col-reverse gap-3 border-t border-slate-800/70 pt-5 sm:flex-row sm:items-center sm:justify-between"
          >
            <p class="flex items-center gap-1.5 text-[10px] text-slate-600">
              <ShieldCheck class="h-3.5 w-3.5 shrink-0 text-emerald-500/70" />
              Meta tag diterapkan pada seluruh halaman publik sistem.
            </p>
            <button
              type="submit"
              :disabled="isSavingSeo"
              class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
            >
              <Check v-if="!isSavingSeo" class="h-4 w-4" />
              <Loader2 v-else class="h-4 w-4 animate-spin" />
              {{ isSavingSeo ? "Menyimpan..." : "Simpan Pengaturan" }}
            </button>
          </div>
        </form>
      </section>
    </main>

    <!-- ============ Footer ============ -->
    <footer class="border-t border-slate-800/70">
      <div
        class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 sm:flex-row sm:px-6 lg:px-8"
      >
        <p class="text-[11px] text-slate-600">
          © {{ currentYear }} SAPA — Sistem Layanan Aspirasi &amp; Pengaduan
          Sekolah
        </p>
        <p class="flex items-center gap-1.5 text-[11px] text-slate-600">
          <ShieldCheck class="h-3.5 w-3.5 text-emerald-500/70" />
          Perubahan konfigurasi sistem tercatat pada jejak audit
        </p>
      </div>
    </footer>
  </div>
</template>

<style>
/* Inter sebagai identitas tipografi (aman dihapus jika sudah dikonfigurasi di Tailwind) */
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap");

.sapa-root {
  font-family:
    "Inter",
    ui-sans-serif,
    system-ui,
    -apple-system,
    "Segoe UI",
    Roboto,
    sans-serif;
}
</style>

<style scoped>
/* Entrance seksi: fade-up halus dengan stagger */
.fade-up {
  opacity: 0;
  animation: fade-up 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes fade-up {
  from {
    opacity: 0;
    transform: translateY(14px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Gulir horizontal tanpa scrollbar */
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
  display: none;
}

/* Batasi deskripsi pratinjau pada 2 baris */
.clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

@media (prefers-reduced-motion: reduce) {
  .fade-up {
    animation: none;
    opacity: 1;
  }
}
</style>
