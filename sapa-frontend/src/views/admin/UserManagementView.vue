<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { toast } from "vue-sonner";
import userService from "@/services/userService";
import classService from "@/services/classService";
import NotificationBell from "@/components/shared/NotificationBell.vue";
import {
  Users,
  UserCheck,
  GraduationCap,
  ShieldCheck,
  LogOut,
  Plus,
  Search,
  X,
  ChevronDown,
  Pencil,
  KeyRound,
  Ban,
  CheckCircle2,
  Trash2,
  UserX,
  ChevronLeft,
  ChevronRight,
  Lock,
  User,
  Mail,
  IdCard,
  Check,
  AlertTriangle,
  BarChart3,
  FileText,
  Settings,
  Loader2,
  Lightbulb,
  Download,
  Upload,
  FileSpreadsheet,
} from "lucide-vue-next";
import { useAuthStore } from "@/stores/auth";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const logoFailed = ref(false);
const currentYear = new Date().getFullYear();
const isLoading = ref(true);

const navItems = [
  { label: "Analitik", to: "/admin/dashboard", icon: BarChart3 },
  { label: "Semua Laporan", to: "/admin/reports", icon: FileText },
  { label: "Aspirasi", to: "/admin/aspirations", icon: Lightbulb },
  { label: "Kelas", to: "/admin/classes", icon: GraduationCap },
  { label: "Manajemen User", to: "/admin/users", icon: Users },
  { label: "Pengaturan", to: "/admin/settings", icon: Settings },
];

const isActive = (item) => route?.path === item.to;

const ROLE_TO_LABEL = {
  student: "Siswa",
  staff: "Guru",
  counselor: "Guru BK",
  admin: "Admin",
};
const LABEL_TO_ROLE = {
  Siswa: "student",
  Guru: "staff",
  "Guru BK": "counselor",
  Admin: "admin",
};

/* ---------------------------------- */
/* Data pengguna — dari API            */
/* ---------------------------------- */
const users = ref([]);
const totalUsers = ref(0);
const currentPage = ref(1);
const itemsPerPage = ref(10);
const lastPageFromApi = ref(1);

const searchQuery = ref("");
const selectedRole = ref("all");
const selectedStatus = ref("all");

async function loadUsers() {
  isLoading.value = true;
  try {
    const params = { page: currentPage.value };
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim();
    if (selectedRole.value !== "all")
      params.role = LABEL_TO_ROLE[selectedRole.value];
    if (selectedStatus.value !== "all")
      params.is_active = selectedStatus.value === "Aktif" ? 1 : 0;

    const data = await userService.getUsers(params);

    users.value = data.data.map((u) => ({
      id: u.id,
      name: u.name,
      email: u.email,
      nip_nisn: u.identity_number ?? "—",
      role: ROLE_TO_LABEL[u.roles?.[0]] ?? u.roles?.[0] ?? "—",
      status: u.is_active ? "Aktif" : "Nonaktif",
      isActive: u.is_active,
      className: u.class?.name ?? null, // BARU
      academicYear: u.class?.academic_year ?? null, // BARU
      classId: u.class?.id ?? null, // BARU — dipakai saat edit
      last_login: "—",
    }));
    totalUsers.value = data.total ?? users.value.length;
    lastPageFromApi.value = data.last_page ?? 1;
  } catch {
    toast.error("Gagal memuat daftar pengguna.");
  } finally {
    isLoading.value = false;
  }
}

// Statistik
const globalStats = ref(null);

async function loadStats() {
  try {
    globalStats.value = await userService.getUserStats();
  } catch {
    toast.error("Gagal memuat statistik pengguna.");
  }
}

onMounted(() => {
  loadUsers();
  loadClassOptions();
  loadStats();
});

let searchDebounce = null;
watch(searchQuery, () => {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => {
    currentPage.value = 1;
    loadUsers();
  }, 400);
});
watch([selectedRole, selectedStatus], () => {
  currentPage.value = 1;
  loadUsers();
});
watch(currentPage, loadUsers);

/* ---------------------------------- */
/* Dropdown kelas — dari API           */
/* ---------------------------------- */
const classOptions = ref([]);

async function loadClassOptions() {
  try {
    classOptions.value = await classService.getClassOptions();
  } catch {
    toast.error("Gagal memuat daftar kelas.");
  }
}

/* ---------------------------------- */
/* Statistik Global (Mendukung Guru BK) */
/* ---------------------------------- */
const statCards = computed(() => {
  const total = globalStats.value?.total ?? totalUsers.value ?? 1;

  const studentCount =
    globalStats.value?.by_role?.student ?? statusCounts.value.roleStudent ?? 0;

  const activeCount =
    globalStats.value?.active ?? statusCounts.value["Aktif"] ?? 0;

  const staffAdminCount = globalStats.value?.by_role
    ? (globalStats.value.by_role.staff || 0) +
      (globalStats.value.by_role.counselor || 0) +
      (globalStats.value.by_role.admin || 0)
    : (statusCounts.value.roleStaff || 0) +
      (statusCounts.value.roleCounselor || 0) +
      (statusCounts.value.roleAdmin || 0);

  const studentPct = Math.min(Math.round((studentCount / total) * 100), 100);
  const activePct = Math.min(Math.round((activeCount / total) * 100), 100);
  const managementPct = Math.min(
    Math.round((staffAdminCount / total) * 100),
    100,
  );

  return [
    {
      label: "Total Pengguna",
      value: total,
      num: "text-slate-100",
      tile: "border-slate-700 bg-slate-800 text-emerald-400",
      bar: "bg-emerald-500",
      pct: 100,
      caption: "Semua akun terdaftar",
      icon: Users,
    },
    {
      label: "Siswa",
      value: studentCount,
      num: "text-emerald-400",
      tile: "border-emerald-500/30 bg-emerald-500/10 text-emerald-400",
      bar: "bg-emerald-500",
      pct: studentPct,
      caption: `${studentPct}% dari total pengguna`,
      icon: GraduationCap,
    },
    {
      label: "Pengguna Aktif",
      value: activeCount,
      num: "text-blue-400",
      tile: "border-blue-500/30 bg-blue-500/10 text-blue-400",
      bar: "bg-blue-500",
      pct: activePct,
      caption: `${activePct}% status aktif saat ini`,
      icon: UserCheck,
    },
    {
      label: "Pengelola (Staf/BK/Admin)",
      value: staffAdminCount,
      num: "text-purple-400",
      tile: "border-purple-500/30 bg-purple-500/10 text-purple-400",
      bar: "bg-purple-500",
      pct: managementPct,
      caption: `${managementPct}% pengelola hak akses (termasuk BK)`,
      icon: ShieldCheck,
    },
  ];
});

const statusPills = [
  {
    label: "Semua",
    value: "all",
    active: "border-emerald-500/30 bg-emerald-500/10 text-emerald-400",
  },
  {
    label: "Aktif",
    value: "Aktif",
    active: "border-emerald-500/30 bg-emerald-500/10 text-emerald-400",
  },
  {
    label: "Nonaktif",
    value: "Nonaktif",
    active: "border-slate-600 bg-slate-800 text-slate-200",
  },
];

const pillIdle =
  "border-slate-800 bg-slate-950/40 text-slate-400 hover:border-slate-700";

const statusCounts = computed(() => {
  return users.value.reduce(
    (acc, u) => {
      acc.all = (acc.all || 0) + 1;
      acc[u.status] = (acc[u.status] || 0) + 1;
      if (u.role === "Siswa") acc.roleStudent = (acc.roleStudent || 0) + 1;
      if (u.role === "Guru") acc.roleStaff = (acc.roleStaff || 0) + 1;
      if (u.role === "Guru BK")
        acc.roleCounselor = (acc.roleCounselor || 0) + 1;
      if (u.role === "Admin") acc.roleAdmin = (acc.roleAdmin || 0) + 1;
      return acc;
    },
    { all: 0 },
  );
});

const hasActiveFilters = computed(
  () =>
    searchQuery.value !== "" ||
    selectedRole.value !== "all" ||
    selectedStatus.value !== "all",
);

const totalPages = computed(() => lastPageFromApi.value);
const filteredUsers = computed(() => users.value);

const tableRows = computed(() =>
  users.value.map((u) => ({
    ...u,
    avatarClass: getRoleAvatarClass(u.role),
    roleBadge: getRoleBadgeClass(u.role),
  })),
);

/* ---------------------------------- */
/* Modal state                         */
/* ---------------------------------- */
const showUserModal = ref(false);
const showResetModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const selectedUser = ref(null);
const isSaving = ref(false);
const isResetting = ref(false);
const isDeleting = ref(false);

const userForm = ref({
  name: "",
  email: "",
  nip_nisn: "",
  role: "Siswa",
  status: "Aktif",
  password: "",
  class_id: "",
});
const userErrors = ref({});

const roleOptions = [
  {
    value: "Siswa",
    label: "Siswa",
    desc: "Akses terbatas untuk aspirasi",
    active: "border-blue-500/50 bg-blue-500/10",
    dot: "bg-blue-400",
  },
  {
    value: "Guru",
    label: "Guru (Sarpras)",
    desc: "Penanganan laporan fasilitas",
    active: "border-emerald-500/50 bg-emerald-500/10",
    dot: "bg-emerald-400",
  },
  {
    value: "Guru BK",
    label: "Guru BK",
    desc: "Akses laporan bimbingan & perundungan",
    active: "border-amber-500/50 bg-amber-500/10",
    dot: "bg-amber-400",
  },
  {
    value: "Admin",
    label: "Admin",
    desc: "Akses penuh ke seluruh sistem",
    active: "border-purple-500/50 bg-purple-500/10",
    dot: "bg-purple-400",
  },
];

const getInitials = (name) =>
  name
    ? name
        .split(" ")
        .map((n) => n[0])
        .join("")
        .substring(0, 2)
        .toUpperCase()
    : "";

async function handleDownloadTemplate() {
  try {
    const blob = await userService.downloadImportTemplate();
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "Template_Import_Siswa.xlsx";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch {
    toast.error("Gagal mengunduh template.");
  }
}

const showExportModal = ref(false);
const showImportModal = ref(false);
const importFile = ref(null);
const importFileInputRef = ref(null);
const isImporting = ref(false);
const importResult = ref(null);
const isExporting = ref(false);
const exportFilters = ref({
  grade: "all",
  class_id: "all",
  academic_year: "all",
});
const academicYears = ref([]);
const classOptionsForExport = ref([]);
const showStaffExportModal = ref(false);
const isExportingStaff = ref(false);
const staffExportRole = ref("all");

function openImportModal() {
  importFile.value = null;
  importResult.value = null;
  showImportModal.value = true;
}

async function openExportModal() {
  exportFilters.value = { grade: "all", class_id: "all", academic_year: "all" };
  showExportModal.value = true;

  try {
    academicYears.value = await classService.getAcademicYears();
  } catch {
    toast.error("Gagal memuat daftar tahun ajaran.");
  }
}

function openStaffExportModal() {
  staffExportRole.value = "all";
  showStaffExportModal.value = true;
}

async function handleExportStaff() {
  isExportingStaff.value = true;
  try {
    const blob = await userService.exportStaff(staffExportRole.value);
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    const roleLabel =
      staffExportRole.value === "staff"
        ? "Sarpras"
        : staffExportRole.value === "counselor"
          ? "BK"
          : "Petugas";
    link.download = `Data_${roleLabel}_${new Date().toISOString().split("T")[0]}.xlsx`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showStaffExportModal.value = false;
    toast.success("Data petugas berhasil diunduh.");
  } catch {
    toast.error("Gagal mengekspor data petugas.");
  } finally {
    isExportingStaff.value = false;
  }
}

async function handleExecuteExport() {
  isExporting.value = true;
  try {
    const params = {};
    if (exportFilters.value.grade !== "all")
      params.grade = exportFilters.value.grade;
    if (exportFilters.value.class_id !== "all")
      params.class_id = exportFilters.value.class_id;
    if (exportFilters.value.academic_year !== "all")
      params.academic_year = exportFilters.value.academic_year;

    const blob = await userService.exportStudents(params);
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = `Data_Siswa_${new Date().toISOString().split("T")[0]}.xlsx`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showExportModal.value = false;
    toast.success("Data siswa berhasil diunduh.");
  } catch {
    toast.error("Gagal mengekspor data siswa.");
  } finally {
    isExporting.value = false;
  }
}

function handleImportFileSelect(e) {
  const file = e.target.files[0];
  e.target.value = "";
  if (!file) return;
  if (!file.name.endsWith(".xlsx") && !file.name.endsWith(".xls")) {
    toast.error("Hanya file Excel (.xlsx/.xls) yang didukung.");
    return;
  }
  importFile.value = file;
}

async function handleExecuteImport() {
  if (!importFile.value) {
    toast.error("Pilih file CSV terlebih dahulu.");
    return;
  }

  isImporting.value = true;
  try {
    const result = await userService.importStudents(importFile.value);
    importResult.value = result;
    toast.success(result.message);
    await loadUsers();
  } catch (error) {
    toast.error(
      error?.response?.data?.message || "Gagal mengimpor data siswa.",
    );
  } finally {
    isImporting.value = false;
  }
}

const getRoleAvatarClass = (role) => {
  switch (role) {
    case "Admin":
      return "border-purple-500/40 bg-purple-500/10 text-purple-300";
    case "Guru BK":
      return "border-amber-500/40 bg-amber-500/10 text-amber-300";
    case "Guru":
      return "border-emerald-500/40 bg-emerald-500/10 text-emerald-300";
    default:
      return "border-blue-500/40 bg-blue-500/10 text-blue-300";
  }
};

const getRoleBadgeClass = (role) => {
  switch (role) {
    case "Admin":
      return "border-purple-500/30 bg-purple-500/10 text-purple-400";
    case "Guru BK":
      return "border-amber-500/30 bg-amber-500/10 text-amber-400";
    case "Guru":
      return "border-emerald-500/30 bg-emerald-500/10 text-emerald-400";
    default:
      return "border-blue-500/30 bg-blue-500/10 text-blue-400";
  }
};

const clearFilters = () => {
  searchQuery.value = "";
  selectedRole.value = "all";
  selectedStatus.value = "all";
};

const prevPage = () => {
  if (currentPage.value > 1) currentPage.value--;
};
const nextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value++;
};

const openCreateModal = () => {
  isEditing.value = false;
  userForm.value = {
    name: "",
    email: "",
    nip_nisn: "",
    role: "Siswa",
    status: "Aktif",
    password: "",
    class_id: "",
  };
  userErrors.value = {};
  showUserModal.value = true;
};

const openEditModal = (user) => {
  isEditing.value = true;
  selectedUser.value = user;
  userForm.value = {
    name: user.name,
    email: user.email,
    nip_nisn: user.nip_nisn,
    role: user.role,
    status: user.status,
    password: "",
    class_id: user.classId ?? "",
  };
  userErrors.value = {};
  showUserModal.value = true;
};

const openResetPasswordModal = (user) => {
  selectedUser.value = user;
  showResetModal.value = true;
};

const openDeleteModal = (user) => {
  selectedUser.value = user;
  showDeleteModal.value = true;
};

async function toggleUserStatus(user) {
  try {
    await userService.toggleActive(user.id);

    const target = users.value.find((u) => u.id === user.id);
    if (target) {
      target.isActive = !target.isActive;
      target.status = target.isActive ? "Aktif" : "Nonaktif";
    }

    toast.success(
      `Akun ${user.name} berhasil ${target?.isActive ? "diaktifkan" : "dinonaktifkan"}.`,
    );
  } catch (error) {
    toast.error(
      error?.response?.data?.message || "Gagal mengubah status akun.",
    );
  }
}

async function handleSaveUser() {
  userErrors.value = {};
  if (!userForm.value.name.trim()) userErrors.value.name = "Nama wajib diisi";
  if (!userForm.value.email.trim())
    userErrors.value.email = "Email wajib diisi";
  if (!userForm.value.nip_nisn.trim())
    userErrors.value.nip_nisn = "NIP/NISN wajib diisi";
  if (!isEditing.value && !userForm.value.password.trim())
    userErrors.value.password = "Password wajib diisi untuk akun baru";

  if (Object.keys(userErrors.value).length > 0) return;

  isSaving.value = true;
  try {
    if (isEditing.value) {
      await userService.updateUser(selectedUser.value.id, {
        name: userForm.value.name,
        email: userForm.value.email,
        identity_number: userForm.value.nip_nisn,
        class_id: userForm.value.class_id || null,
      });

      if (userForm.value.role !== selectedUser.value.role) {
        await userService.assignRole(
          selectedUser.value.id,
          LABEL_TO_ROLE[userForm.value.role],
        );
      }

      const wantActive = userForm.value.status === "Aktif";
      if (wantActive !== selectedUser.value.isActive) {
        await userService.toggleActive(selectedUser.value.id);
      }

      toast.success("Data pengguna berhasil diperbarui.");
    } else {
      await userService.createUser({
        name: userForm.value.name,
        email: userForm.value.email,
        password: userForm.value.password,
        identity_number: userForm.value.nip_nisn,
        class_id: userForm.value.class_id || null,
        role: LABEL_TO_ROLE[userForm.value.role],
      });
      toast.success("Pengguna baru berhasil ditambahkan.");
    }

    showUserModal.value = false;
    await loadUsers();
  } catch (error) {
    const validationErrors = error?.response?.data?.errors;
    if (validationErrors) {
      const fieldMap = { identity_number: "nip_nisn" };
      Object.entries(validationErrors).forEach(([key, msgs]) => {
        userErrors.value[fieldMap[key] ?? key] = msgs[0];
      });
    }
    toast.error(
      error?.response?.data?.message || "Gagal menyimpan data pengguna.",
    );
  } finally {
    isSaving.value = false;
  }
}

const generatedPassword = "SAPA2026!";

async function handleConfirmReset() {
  isResetting.value = true;
  try {
    await userService.resetPassword(selectedUser.value.id, generatedPassword);
    toast.success(`Password ${selectedUser.value.name} berhasil direset.`);
    showResetModal.value = false;
  } catch (error) {
    toast.error(error?.response?.data?.message || "Gagal mereset password.");
  } finally {
    isResetting.value = false;
  }
}

async function handleDeleteUser() {
  isDeleting.value = true;
  try {
    await userService.deleteUser(selectedUser.value.id);
    toast.success(`Akun ${selectedUser.value.name} berhasil dihapus.`);
    showDeleteModal.value = false;
    await loadUsers();
  } catch (error) {
    toast.error(error?.response?.data?.message || "Gagal menghapus pengguna.");
  } finally {
    isDeleting.value = false;
  }
}

const handleLogout = async () => {
  await authStore.logout();
  toast.success("Berhasil keluar dari sistem.");
  router.push({ name: "login" });
};

const anyModalOpenExtra = computed(() => showImportModal.value);
watch(showImportModal, (open) => {
  document.body.style.overflow = open ? "hidden" : "";
});
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
          <!-- Merek -->
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
                Sistem Layanan Aspirasi &amp; Pengaduan sekolah
              </p>
            </div>
          </div>

          <!-- Navigasi -->
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
      <!-- ===== Kepala halaman ===== -->
      <section class="fade-up">
        <div
          class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between"
        >
          <div class="min-w-0">
            <div class="flex items-center gap-2.5">
              <span
                class="h-2 w-2 rounded-full bg-emerald-500"
                aria-hidden="true"
              ></span>
              <p
                class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-300/90"
              >
                Panel Admin · Manajemen Pengguna
              </p>
            </div>
            <h1
              class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl"
            >
              Manajemen Pengguna
            </h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-400">
              Kelola data pengguna, hak akses, peranan akun, serta pemantauan
              status aktivitas pada platform SAPA.
            </p>
          </div>

          <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
            <button
              type="button"
              @click="openStaffExportModal"
              class="inline-flex h-10 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-slate-700 bg-slate-800/50 px-4 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-amber-500/40 hover:text-amber-400 active:scale-[.97]"
            >
              <Download class="h-4 w-4" />
              <span>Ekspor BK/Staff</span>
            </button>

            <button
              type="button"
              @click="openExportModal"
              :disabled="isExporting"
              class="inline-flex h-10 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-slate-700 bg-slate-800/50 px-4 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] disabled:opacity-50"
            >
              <Loader2 v-if="isExporting" class="h-4 w-4 animate-spin" />
              <Download v-else class="h-4 w-4" />
              <span>Ekspor Siswa</span>
            </button>

            <button
              type="button"
              @click="openImportModal"
              class="inline-flex h-10 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-slate-700 bg-slate-800/50 px-4 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-blue-500/40 hover:text-blue-400 active:scale-[.97]"
            >
              <Upload class="h-4 w-4" />
              <span>Impor Siswa</span>
            </button>

            <button
              type="button"
              @click="openCreateModal"
              class="group inline-flex h-10 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-emerald-500 px-4 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
            >
              <Plus
                class="h-4 w-4 transition-transform duration-300 group-hover:rotate-90"
              />
              <span>User</span>
            </button>
          </div>
        </div>
      </section>

      <!-- ===== Statistik pengguna ===== -->
      <section class="fade-up space-y-4" style="animation-delay: 90ms">
        <div class="flex items-center gap-3">
          <span
            class="h-4 w-0.75 rounded-full bg-emerald-500"
            aria-hidden="true"
          ></span>
          <div>
            <h2 class="text-base font-bold tracking-tight text-slate-100">
              Ringkasan Pengguna
            </h2>
            <p class="mt-0.5 text-xs text-slate-500">
              Komposisi peran dan status seluruh akun terdaftar.
            </p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
          <article
            v-for="s in statCards"
            :key="s.label"
            class="group rounded-xl border border-slate-800 bg-slate-900/40 p-4 backdrop-blur-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-700/80 sm:p-5"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p
                  class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500"
                >
                  {{ s.label }}
                </p>
                <p
                  class="mt-2 text-3xl font-extrabold tracking-tight tabular-nums"
                  :class="s.num"
                >
                  {{ s.value }}
                </p>
              </div>
              <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border transition-transform duration-200 group-hover:scale-110"
                :class="s.tile"
              >
                <component :is="s.icon" class="h-5 w-5" />
              </div>
            </div>

            <div class="mt-4">
              <!-- Bar Garis Mempresentasikannya -->
              <div
                class="h-1.5 w-full overflow-hidden rounded-full bg-slate-800"
              >
                <div
                  class="h-full rounded-full transition-all duration-500 ease-out"
                  :class="s.bar"
                  :style="{ width: s.pct + '%' }"
                ></div>
              </div>

              <p class="mt-2 truncate text-[10px] text-slate-500">
                {{ s.caption }}
              </p>
            </div>
          </article>
        </div>
      </section>

      <!-- ===== Tabel pengguna ===== -->
      <section
        class="fade-up overflow-hidden rounded-xl border border-slate-800 bg-slate-900"
        style="animation-delay: 180ms"
      >
        <!-- Kepala seksi -->
        <div
          class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b border-slate-800/80 px-5 py-4 sm:px-6 sm:py-5"
        >
          <div class="flex items-center gap-3">
            <span
              class="h-4 w-0.75 rounded-full bg-emerald-500"
              aria-hidden="true"
            ></span>
            <div>
              <h2 class="text-base font-bold tracking-tight text-slate-100">
                Daftar Pengguna Terdaftar
              </h2>
              <p class="mt-0.5 text-xs text-slate-500">
                Kelola akun, peran, dan status aktivitas pengguna sistem.
              </p>
            </div>
          </div>
          <span
            class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-400"
            >{{ totalUsers }} Akun</span
          >
        </div>

        <!-- Toolbar filter -->
        <div class="space-y-4 border-b border-slate-800/80 p-4 sm:p-5">
          <div class="flex flex-col gap-3 sm:flex-row">
            <!-- Pencarian -->
            <div class="relative flex-1">
              <Search
                class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
              />
              <input
                v-model="searchQuery"
                type="text"
                aria-label="Cari pengguna"
                placeholder="Cari berdasarkan Nama, Email, atau NIP/NISN..."
                class="w-full rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              />
              <button
                v-if="searchQuery"
                type="button"
                @click="searchQuery = ''"
                aria-label="Bersihkan pencarian"
                class="absolute right-2.5 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-500 transition-colors duration-150 hover:bg-slate-800 hover:text-slate-200"
              >
                <X class="h-3.5 w-3.5" />
              </button>
            </div>

            <!-- Filter peran -->
            <div class="relative w-full sm:w-56">
              <Users
                class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
              />
              <select
                v-model="selectedRole"
                aria-label="Filter peran"
                class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
              >
                <option value="all">Semua Peran</option>
                <option value="Siswa">Siswa</option>
                <option value="Guru">Guru (Sarpras)</option>
                <option value="Guru BK">Guru BK</option>
                <option value="Admin">Admin</option>
              </select>
              <ChevronDown
                class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
              />
            </div>
          </div>

          <!-- Pil status + ringkasan hasil -->
          <div class="flex flex-wrap items-center gap-2">
            <button
              v-for="opt in statusPills"
              :key="opt.value"
              type="button"
              :aria-pressed="selectedStatus === opt.value"
              @click="selectedStatus = opt.value"
              class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-all duration-200 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
              :class="selectedStatus === opt.value ? opt.active : pillIdle"
            >
              <span
                class="h-1.5 w-1.5 rounded-full bg-current opacity-80"
                aria-hidden="true"
              ></span>
              {{ opt.label }}
              <span
                class="rounded bg-slate-800/90 px-1.5 py-px text-[10px] font-semibold tabular-nums text-slate-500"
                >{{ statusCounts[opt.value] || 0 }}</span
              >
            </button>

            <div class="ml-auto flex items-center gap-3 pl-2">
              <p class="whitespace-nowrap text-[11px] text-slate-500">
                Menampilkan
                <span class="font-semibold tabular-nums text-slate-300">{{
                  filteredUsers.length
                }}</span>
                dari {{ totalUsers }} pengguna
              </p>
              <button
                v-if="hasActiveFilters"
                type="button"
                @click="clearFilters"
                class="inline-flex items-center gap-1 whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2 py-1 text-[11px] font-semibold text-slate-400 transition-all duration-150 hover:border-emerald-500/40 hover:text-emerald-400 active:scale-[.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
              >
                Atur ulang
                <X class="h-3 w-3" />
              </button>
            </div>
          </div>
        </div>

        <!-- Label kolom (desktop lebar) -->
        <div
          class="hidden border-b border-slate-800/70 bg-slate-950/50 px-5 py-2.5 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1.3fr)_130px_130px_95px_100px_140px_160px] xl:gap-x-4"
        >
          <p
            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
          >
            Pengguna
          </p>
          <p
            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
          >
            NIP / NISN
          </p>
          <p
            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
          >
            Kelas
          </p>
          <p
            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
          >
            Peran
          </p>
          <p
            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
          >
            Status
          </p>
          <p
            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
          >
            Terakhir Aktif
          </p>
          <p
            class="text-right text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-600"
          >
            Aksi
          </p>
        </div>

        <div v-if="isLoading" class="divide-y divide-slate-800/70">
          <div v-for="i in 5" :key="i" class="px-5 py-4 sm:px-6">
            <div class="h-4 w-40 rounded bg-slate-800 animate-pulse mb-2"></div>
            <div class="h-3 w-56 rounded bg-slate-800 animate-pulse"></div>
          </div>
        </div>

        <div
          v-else-if="tableRows.length > 0"
          class="divide-y divide-slate-800/70"
        >
          <!-- Baris pengguna -->
          <div v-if="tableRows.length > 0" class="divide-y divide-slate-800/70">
            <article
              v-for="(user, i) in tableRows"
              :key="user.id"
              class="card-enter group relative flex flex-col gap-3 px-5 py-4 transition-colors duration-150 hover:bg-slate-800/40 sm:px-6 xl:grid xl:grid-cols-[minmax(0,1.3fr)_130px_130px_95px_100px_140px_160px] xl:items-center xl:gap-x-4"
              :style="{ animationDelay: i * 60 + 'ms' }"
            >
              <span
                class="absolute bottom-3 left-0 top-3 w-0.75 rounded-r-full bg-emerald-500/70 opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                aria-hidden="true"
              ></span>

              <div class="flex min-w-0 items-center gap-3">
                <div
                  class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border text-[11px] font-bold transition-colors duration-150"
                  :class="user.avatarClass"
                >
                  {{ getInitials(user.name) }}
                </div>
                <div class="min-w-0">
                  <p
                    class="truncate text-sm font-semibold text-slate-100 transition-colors duration-150 group-hover:text-white"
                  >
                    {{ user.name }}
                  </p>
                  <p class="mt-0.5 truncate text-[11px] text-slate-500">
                    {{ user.email }}
                  </p>
                </div>
              </div>

              <div
                class="flex flex-wrap items-center gap-x-5 gap-y-2.5 xl:contents"
              >
                <div class="min-w-0">
                  <p class="truncate font-mono text-xs text-slate-400">
                    {{ user.nip_nisn || "—" }}
                  </p>
                </div>

                <div class="min-w-0">
                  <template v-if="user.className">
                    <p class="truncate text-xs font-semibold text-slate-200">
                      {{ user.className }}
                    </p>
                    <p class="mt-0.5 truncate text-[10px] text-slate-500">
                      {{ user.academicYear }}
                    </p>
                  </template>
                  <p v-else class="text-xs text-slate-600">—</p>
                </div>

                <div>
                  <span
                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                    :class="user.roleBadge"
                  >
                    <span
                      class="h-1.5 w-1.5 rounded-full bg-current"
                      aria-hidden="true"
                    ></span>
                    {{ user.role }}
                  </span>
                </div>

                <div>
                  <span
                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-[11px] font-medium"
                    :class="
                      user.isActive
                        ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400'
                        : 'border-slate-500/20 bg-slate-500/10 text-slate-300'
                    "
                  >
                    <span
                      v-if="user.isActive"
                      class="relative flex h-1.5 w-1.5"
                    >
                      <span
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"
                      ></span>
                      <span
                        class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"
                      ></span>
                    </span>
                    <span
                      v-else
                      class="h-1.5 w-1.5 rounded-full bg-current"
                      aria-hidden="true"
                    ></span>
                    {{ user.status }}
                  </span>
                </div>

                <div class="min-w-0">
                  <p class="truncate font-mono text-[11px] text-slate-400">
                    {{ user.last_login }}
                  </p>
                </div>
              </div>

              <!-- Aksi -->
              <div class="flex flex-wrap items-center justify-end gap-1.5">
                <button
                  type="button"
                  @click="openEditModal(user)"
                  title="Edit pengguna"
                  aria-label="Edit pengguna"
                  class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/60 text-slate-400 transition-all duration-150 hover:border-slate-600 hover:text-white active:scale-[.95] focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500"
                >
                  <Pencil class="h-3.5 w-3.5" />
                </button>

                <button
                  type="button"
                  @click="openResetPasswordModal(user)"
                  title="Reset kata sandi"
                  aria-label="Reset kata sandi"
                  class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/60 text-slate-400 transition-all duration-150 hover:border-amber-500/30 hover:bg-amber-500/10 hover:text-amber-400 active:scale-[.95] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60"
                >
                  <KeyRound class="h-3.5 w-3.5" />
                </button>

                <button
                  type="button"
                  @click="toggleUserStatus(user)"
                  :title="user.isActive ? 'Nonaktifkan akun' : 'Aktifkan akun'"
                  :aria-label="
                    user.isActive ? 'Nonaktifkan akun' : 'Aktifkan akun'
                  "
                  class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/60 text-slate-400 transition-all duration-150 active:scale-[.95] focus:outline-none focus-visible:ring-2"
                  :class="
                    user.isActive
                      ? 'hover:border-rose-500/30 hover:bg-rose-500/10 hover:text-rose-400 focus-visible:ring-rose-400/60'
                      : 'hover:border-emerald-500/30 hover:bg-emerald-500/10 hover:text-emerald-400 focus-visible:ring-emerald-400/60'
                  "
                >
                  <Ban v-if="user.isActive" class="h-3.5 w-3.5" />
                  <CheckCircle2 v-else class="h-3.5 w-3.5" />
                </button>

                <button
                  type="button"
                  @click="openDeleteModal(user)"
                  title="Hapus pengguna"
                  aria-label="Hapus pengguna"
                  class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-800 bg-slate-900/60 text-slate-400 transition-all duration-150 hover:border-rose-500/30 hover:bg-rose-500/10 hover:text-rose-400 active:scale-[.95] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/60"
                >
                  <Trash2 class="h-3.5 w-3.5" />
                </button>
              </div>
            </article>
          </div>
        </div>

        <!-- Keadaan kosong -->
        <div v-else class="flex flex-col items-center px-6 py-16 text-center">
          <div
            class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 text-slate-400"
          >
            <Search v-if="hasActiveFilters" class="h-6 w-6" />
            <UserX v-else class="h-6 w-6" />
          </div>
          <p class="mt-4 text-sm font-semibold text-slate-200">
            {{
              hasActiveFilters
                ? "Pengguna tidak ditemukan"
                : "Belum ada pengguna terdaftar"
            }}
          </p>
          <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500">
            {{
              hasActiveFilters
                ? "Coba gunakan kata kunci lain atau atur ulang filter peran dan status."
                : "Tambahkan pengguna pertama untuk mulai mengelola akses sistem."
            }}
          </p>

          <button
            v-if="hasActiveFilters"
            type="button"
            @click="clearFilters"
            class="mt-6 inline-flex items-center gap-1.5 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-2 text-xs font-semibold text-emerald-400 transition-all duration-200 hover:bg-emerald-500 hover:text-slate-950 active:scale-[.97]"
          >
            Atur Ulang Filter
          </button>
          <button
            v-else
            type="button"
            @click="openCreateModal"
            class="mt-6 inline-flex items-center gap-1.5 rounded-lg bg-emerald-500 px-4 py-2 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97]"
          >
            <Plus class="h-4 w-4" />
            Tambah Pengguna Pertama
          </button>
        </div>

        <!-- Kaki tabel: info + paginasi -->
        <div
          class="flex flex-col items-center justify-between gap-4 border-t border-slate-800/70 bg-slate-950/40 px-5 py-4 sm:flex-row sm:px-6"
        >
          <p class="text-[11px] text-slate-500">
            Menampilkan
            <span class="font-semibold tabular-nums text-slate-300">{{
              filteredUsers.length > 0
                ? (currentPage - 1) * itemsPerPage + 1
                : 0
            }}</span>
            sampai
            <span class="font-semibold tabular-nums text-slate-300">{{
              Math.min(currentPage * itemsPerPage, filteredUsers.length)
            }}</span>
            dari
            <span class="font-semibold tabular-nums text-slate-300">{{
              filteredUsers.length
            }}</span>
            entri
          </p>

          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="prevPage"
              :disabled="currentPage === 1"
              class="group inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-1.5 text-xs font-semibold text-slate-300 transition-all duration-150 hover:border-slate-700 hover:text-white active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
            >
              <ChevronLeft
                class="h-3.5 w-3.5 transition-transform duration-150 group-hover:-translate-x-0.5"
              />
              Sebelumnya
            </button>

            <span
              class="whitespace-nowrap rounded-md border border-slate-700 bg-slate-800/60 px-2.5 py-1 text-[11px] font-semibold tabular-nums text-slate-400"
            >
              Halaman {{ currentPage }} / {{ totalPages }}
            </span>

            <button
              type="button"
              @click="nextPage"
              :disabled="currentPage >= totalPages"
              class="group inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-1.5 text-xs font-semibold text-slate-300 transition-all duration-150 hover:border-slate-700 hover:text-white active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50"
            >
              Selanjutnya
              <ChevronRight
                class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5"
              />
            </button>
          </div>
        </div>
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
          <Lock class="h-3.5 w-3.5 text-emerald-500/70" />
          Perubahan hak akses pengguna tercatat pada jejak audit sistem
        </p>
      </div>
    </footer>

    <!-- ============ Modal form pengguna ============ -->
    <div
      v-if="showUserModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="user-form-title"
    >
      <div
        class="backdrop-in absolute inset-0 bg-slate-950/80 backdrop-blur-sm"
        aria-hidden="true"
        @click="showUserModal = false"
      ></div>

      <div
        class="modal-panel relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl shadow-black/50"
      >
        <span
          class="absolute inset-x-0 top-0 z-20 h-0.5 bg-linear-to-r from-emerald-500/70 via-emerald-500/20 to-transparent"
          aria-hidden="true"
        ></span>

        <!-- Kepala modal -->
        <div
          class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-800/70 px-5 py-4"
        >
          <div>
            <h3
              id="user-form-title"
              class="text-sm font-bold tracking-tight text-slate-100"
            >
              {{ isEditing ? "Edit Data Pengguna" : "Tambah Pengguna Baru" }}
            </h3>
            <p class="mt-0.5 text-[11px] text-slate-500">
              {{
                isEditing
                  ? "Perbarui identitas, peran, dan status akun"
                  : "Lengkapi data akun baru pada sistem"
              }}
            </p>
          </div>
          <button
            type="button"
            @click="showUserModal = false"
            aria-label="Tutup"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors duration-200 hover:bg-slate-800 hover:text-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <!-- Badan modal -->
        <form
          @submit.prevent="handleSaveUser"
          class="modal-scroll min-h-0 flex-1 space-y-5 overflow-y-auto p-5"
        >
          <!-- Nama -->
          <div>
            <label
              for="uf-name"
              class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
              >Nama Lengkap</label
            >
            <div class="relative">
              <User
                class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
              />
              <input
                id="uf-name"
                v-model="userForm.name"
                type="text"
                autocomplete="name"
                placeholder="Masukkan nama lengkap"
                :aria-invalid="!!userErrors.name || undefined"
                class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                :class="
                  userErrors.name ? 'border-red-400/60' : 'border-slate-800'
                "
              />
            </div>
            <p
              v-if="userErrors.name"
              class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400"
            >
              <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
              {{ userErrors.name }}
            </p>
          </div>

          <!-- Email -->
          <div>
            <label
              for="uf-email"
              class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
              >Alamat Email</label
            >
            <div class="relative">
              <Mail
                class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
              />
              <input
                id="uf-email"
                v-model="userForm.email"
                type="email"
                autocomplete="email"
                placeholder="contoh@sekolah.sch.id"
                :aria-invalid="!!userErrors.email || undefined"
                class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                :class="
                  userErrors.email ? 'border-red-400/60' : 'border-slate-800'
                "
              />
            </div>
            <p
              v-if="userErrors.email"
              class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400"
            >
              <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
              {{ userErrors.email }}
            </p>
          </div>

          <!-- NIP / NISN -->
          <div>
            <label
              for="uf-nip"
              class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
              >NIP / NISN</label
            >
            <div class="relative">
              <IdCard
                class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
              />
              <input
                id="uf-nip"
                v-model="userForm.nip_nisn"
                type="text"
                autocomplete="off"
                placeholder="Masukkan NIP atau NISN"
                :aria-invalid="!!userErrors.nip_nisn || undefined"
                class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-3 font-mono text-sm text-slate-100 placeholder-slate-600 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                :class="
                  userErrors.nip_nisn ? 'border-red-400/60' : 'border-slate-800'
                "
              />
            </div>
            <p
              v-if="userErrors.nip_nisn"
              class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400"
            >
              <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
              {{ userErrors.nip_nisn }}
            </p>

            <!-- Kelas — hanya tampil kalau role = Siswa -->
            <div v-if="userForm.role === 'Siswa'">
              <label
                for="uf-class"
                class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 mt-4"
                >Kelas</label
              >
              <div class="relative">
                <GraduationCap
                  class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
                />
                <select
                  id="uf-class"
                  v-model="userForm.class_id"
                  class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 pl-10 pr-9 text-sm text-slate-100 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                >
                  <option value="">Belum ditempatkan</option>
                  <option v-for="c in classOptions" :key="c.id" :value="c.id">
                    {{ c.name }} ({{ c.academic_year }})
                  </option>
                </select>
                <ChevronDown
                  class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
                />
              </div>
            </div>

            <div v-if="!isEditing">
              <label
                for="uf-password"
                class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 mt-5"
                >Password Awal</label
              >
              <div class="relative">
                <Lock
                  class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
                />
                <input
                  id="uf-password"
                  v-model="userForm.password"
                  type="text"
                  placeholder="Minimal 8 karakter"
                  :aria-invalid="!!userErrors.password || undefined"
                  class="w-full rounded-lg border bg-slate-950/60 py-2.5 pl-10 pr-3.5 text-sm text-slate-100 placeholder-slate-500 transition-colors duration-200 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
                  :class="
                    userErrors.password
                      ? 'border-red-400/60'
                      : 'border-slate-800'
                  "
                />
              </div>
              <p
                v-if="userErrors.password"
                class="mt-1.5 flex items-center gap-1.5 text-[11px] font-medium text-red-400"
              >
                <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                {{ userErrors.password }}
              </p>
            </div>
          </div>

          <!-- Peran: kartu radio semantik -->
          <div class="space-y-2.5 border-t border-slate-800/70 pt-4">
            <span
              class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
              >Peran (Role)</span
            >
            <div
              class="grid grid-cols-1 gap-2 sm:grid-cols-2"
              role="radiogroup"
              aria-label="Peran pengguna"
            >
              <button
                v-for="opt in roleOptions"
                :key="opt.value"
                type="button"
                :aria-pressed="userForm.role === opt.value"
                @click="userForm.role = opt.value"
                class="flex items-center gap-2.5 rounded-lg border px-3 py-2.5 text-left transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50 active:scale-[.99]"
                :class="
                  userForm.role === opt.value
                    ? opt.active
                    : 'border-slate-800 bg-slate-950/40 hover:border-slate-700'
                "
              >
                <span
                  class="h-2 w-2 shrink-0 rounded-full transition-colors duration-200"
                  :class="
                    userForm.role === opt.value ? opt.dot : 'bg-slate-600'
                  "
                  aria-hidden="true"
                ></span>
                <span class="min-w-0 flex-1">
                  <span class="block text-xs font-semibold text-slate-100">{{
                    opt.label
                  }}</span>
                  <span class="block text-[10px] leading-snug text-slate-500">{{
                    opt.desc
                  }}</span>
                </span>
                <Check
                  v-if="userForm.role === opt.value"
                  class="h-3.5 w-3.5 shrink-0 text-emerald-400"
                />
              </button>
            </div>
          </div>

          <!-- Status: kendali tersegmentasi -->
          <div class="space-y-2.5 border-t border-slate-800/70 pt-4">
            <span
              class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
              >Status Akun</span
            >
            <div
              class="grid grid-cols-2 gap-2"
              role="radiogroup"
              aria-label="Status akun"
            >
              <button
                type="button"
                :aria-pressed="userForm.status === 'Aktif'"
                @click="userForm.status = 'Aktif'"
                class="flex items-center justify-center gap-2 rounded-lg border px-3 py-2.5 text-xs font-semibold transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50 active:scale-[.99]"
                :class="
                  userForm.status === 'Aktif'
                    ? 'border-emerald-500/50 bg-emerald-500/6 text-emerald-400'
                    : 'border-slate-800 bg-slate-950/40 text-slate-400 hover:border-slate-700'
                "
              >
                <span
                  class="h-1.5 w-1.5 rounded-full"
                  :class="
                    userForm.status === 'Aktif'
                      ? 'bg-emerald-400'
                      : 'bg-slate-600'
                  "
                  aria-hidden="true"
                ></span>
                Aktif
              </button>
              <button
                type="button"
                :aria-pressed="userForm.status === 'Nonaktif'"
                @click="userForm.status = 'Nonaktif'"
                class="flex items-center justify-center gap-2 rounded-lg border px-3 py-2.5 text-xs font-semibold transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50 active:scale-[.99]"
                :class="
                  userForm.status === 'Nonaktif'
                    ? 'border-slate-500/60 bg-slate-500/10 text-slate-200'
                    : 'border-slate-800 bg-slate-950/40 text-slate-400 hover:border-slate-700'
                "
              >
                <span
                  class="h-1.5 w-1.5 rounded-full"
                  :class="
                    userForm.status === 'Nonaktif'
                      ? 'bg-slate-300'
                      : 'bg-slate-600'
                  "
                  aria-hidden="true"
                ></span>
                Nonaktif
              </button>
            </div>
          </div>

          <!-- Catatan hak akses -->
          <p
            class="flex items-start gap-1.5 text-[10px] leading-relaxed text-slate-500"
          >
            <Lock class="mt-px h-3 w-3 shrink-0 text-emerald-500/70" />
            Perubahan peran langsung memengaruhi hak akses kanal — Guru BK dapat
            mengakses data perundungan rahasia.
          </p>
        </form>

        <!-- Kaki modal -->
        <div class="shrink-0 border-t border-slate-800/70 bg-slate-950/30 p-5">
          <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button
              type="button"
              @click="showUserModal = false"
              class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500"
            >
              Batal
            </button>
            <button
              type="submit"
              @click="handleSaveUser"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 transition-all duration-200 hover:bg-emerald-400 active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
            >
              <Check class="h-4 w-4" />
              {{ isEditing ? "Simpan Perubahan" : "Tambah User" }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ Modal reset kata sandi ============ -->
    <div
      v-if="showResetModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="reset-title"
    >
      <div
        class="backdrop-in absolute inset-0 bg-slate-950/80 backdrop-blur-sm"
        aria-hidden="true"
        @click="showResetModal = false"
      ></div>

      <div
        class="modal-panel relative flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-amber-500/30 bg-slate-900 shadow-2xl shadow-black/50"
      >
        <span
          class="absolute inset-x-0 top-0 z-20 h-0.5 bg-linear-to-r from-amber-500/70 via-amber-500/20 to-transparent"
          aria-hidden="true"
        ></span>

        <!-- Kepala modal -->
        <div
          class="flex shrink-0 items-start justify-between gap-4 border-b border-amber-500/20 px-5 py-4"
        >
          <div>
            <h3
              id="reset-title"
              class="text-sm font-bold tracking-tight text-slate-100"
            >
              Reset Kata Sandi
            </h3>
            <p class="mt-0.5 text-[11px] text-slate-500">
              Atur ulang kata sandi akun ke nilai default sistem
            </p>
          </div>
          <button
            type="button"
            @click="showResetModal = false"
            aria-label="Tutup"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors duration-200 hover:bg-slate-800 hover:text-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <!-- Badan modal -->
        <div class="modal-scroll min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
          <div
            class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3"
          >
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border text-[11px] font-bold"
              :class="
                selectedUser
                  ? getRoleAvatarClass(selectedUser.role)
                  : 'border-slate-700 bg-slate-800 text-slate-300'
              "
            >
              {{ selectedUser ? getInitials(selectedUser.name) : "?" }}
            </div>
            <div class="min-w-0">
              <p class="truncate text-xs font-semibold text-slate-100">
                {{ selectedUser?.name }}
              </p>
              <p class="mt-0.5 truncate text-[11px] text-slate-500">
                {{ selectedUser?.email }}
              </p>
            </div>
          </div>

          <p class="text-xs leading-relaxed text-slate-400">
            Konfirmasi pengaturan ulang kata sandi untuk
            <span class="font-semibold text-slate-200">{{
              selectedUser?.name
            }}</span
            >. Kata sandi akan disetel ulang menjadi:
          </p>

          <div
            class="rounded-lg border border-amber-500/25 bg-amber-500/6 p-3 text-center font-mono text-sm font-bold tracking-wider text-amber-300"
          >
            SAPA2026!
          </div>

          <p
            class="flex items-start gap-1.5 text-[10px] leading-relaxed text-slate-500"
          >
            <Lock class="mt-px h-3 w-3 shrink-0 text-amber-400/70" />
            Pengguna wajib mengganti kata sandi default ini setelah berhasil
            masuk.
          </p>
        </div>

        <!-- Kaki modal -->
        <div class="shrink-0 border-t border-amber-500/20 bg-slate-950/30 p-5">
          <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button
              type="button"
              @click="showResetModal = false"
              class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500"
            >
              Batal
            </button>
            <button
              type="button"
              @click="handleConfirmReset"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-amber-500/20 transition-all duration-200 hover:bg-amber-400 active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400"
            >
              <KeyRound class="h-4 w-4" />
              Konfirmasi Reset
            </button>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="showStaffExportModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
    >
      <div
        class="backdrop-in absolute inset-0 bg-slate-950/80 backdrop-blur-sm"
        aria-hidden="true"
        @click="showStaffExportModal = false"
      ></div>

      <div
        class="modal-panel relative flex w-full max-w-sm flex-col overflow-hidden rounded-2xl border border-amber-500/30 bg-slate-900 shadow-2xl shadow-black/50"
      >
        <span
          class="absolute inset-x-0 top-0 z-20 h-0.5 bg-linear-to-r from-amber-500/70 via-amber-500/20 to-transparent"
          aria-hidden="true"
        ></span>

        <div
          class="flex items-start justify-between gap-4 border-b border-slate-800/70 px-5 py-4"
        >
          <div>
            <h3 class="text-sm font-bold tracking-tight text-slate-100">
              Ekspor Data Petugas
            </h3>
            <p class="mt-0.5 text-[11px] text-slate-500">
              Pilih kategori petugas yang diekspor
            </p>
          </div>
          <button
            type="button"
            @click="showStaffExportModal = false"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-800 hover:text-slate-200"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="space-y-2 p-5">
          <label
            v-for="opt in [
              { value: 'staff', label: 'Guru Sarpras' },
              { value: 'counselor', label: 'Guru BK' },
              { value: 'all', label: 'Semua Petugas (Sarpras + BK)' },
            ]"
            :key="opt.value"
            class="flex cursor-pointer items-center gap-2.5 rounded-lg border px-3.5 py-2.5 transition-colors duration-150"
            :class="
              staffExportRole === opt.value
                ? 'border-amber-500/50 bg-amber-500/6'
                : 'border-slate-800 bg-slate-950/40 hover:border-slate-700'
            "
          >
            <input
              type="radio"
              v-model="staffExportRole"
              :value="opt.value"
              class="accent-amber-500"
            />
            <span class="text-xs font-medium text-slate-200">{{
              opt.label
            }}</span>
          </label>
        </div>

        <div class="border-t border-slate-800/70 bg-slate-950/30 p-5">
          <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button
              type="button"
              @click="showStaffExportModal = false"
              class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto"
            >
              Batal
            </button>
            <button
              type="button"
              @click="handleExportStaff"
              :disabled="isExportingStaff"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-amber-500/20 hover:bg-amber-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto"
            >
              <Loader2 v-if="isExportingStaff" class="h-4 w-4 animate-spin" />
              <Download v-else class="h-4 w-4" />
              {{ isExportingStaff ? "Mengunduh..." : "Unduh Excel" }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="showExportModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
    >
      <div
        class="backdrop-in absolute inset-0 bg-slate-950/80 backdrop-blur-sm"
        aria-hidden="true"
        @click="showExportModal = false"
      ></div>

      <div
        class="modal-panel relative flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-emerald-500/30 bg-slate-900 shadow-2xl shadow-black/50"
      >
        <span
          class="absolute inset-x-0 top-0 z-20 h-0.5 bg-linear-to-r from-emerald-500/70 via-emerald-500/20 to-transparent"
          aria-hidden="true"
        ></span>

        <div
          class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-800/70 px-5 py-4"
        >
          <div>
            <h3 class="text-sm font-bold tracking-tight text-slate-100">
              Ekspor Data Siswa
            </h3>
            <p class="mt-0.5 text-[11px] text-slate-500">
              Filter data sebelum mengunduh (opsional)
            </p>
          </div>
          <button
            type="button"
            @click="showExportModal = false"
            aria-label="Tutup"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-800 hover:text-slate-200"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="modal-scroll min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
          <div>
            <label
              class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
              >Tingkat</label
            >
            <select
              v-model="exportFilters.grade"
              class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 px-3.5 text-sm text-slate-100 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
            >
              <option value="all">Semua Tingkat</option>
              <option value="X">Kelas X</option>
              <option value="XI">Kelas XI</option>
              <option value="XII">Kelas XII</option>
            </select>
          </div>

          <div>
            <label
              class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
              >Kelas Spesifik</label
            >
            <select
              v-model="exportFilters.class_id"
              class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 px-3.5 text-sm text-slate-100 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
            >
              <option value="all">Semua Kelas</option>
              <option v-for="c in classOptions" :key="c.id" :value="c.id">
                {{ c.name }}
              </option>
            </select>
          </div>

          <div>
            <label
              class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400"
              >Tahun Ajaran / Angkatan</label
            >
            <select
              v-model="exportFilters.academic_year"
              class="w-full cursor-pointer appearance-none rounded-lg border border-slate-800 bg-slate-950/60 py-2.5 px-3.5 text-sm text-slate-100 focus:border-emerald-500/60 focus:outline-none focus:ring-2 focus:ring-emerald-500/15"
            >
              <option value="all">Semua Tahun Ajaran</option>
              <option v-for="y in academicYears" :key="y" :value="y">
                {{ y }}
              </option>
            </select>
          </div>

          <p
            class="flex items-start gap-1.5 text-[10px] leading-relaxed text-slate-500"
          >
            <Check class="mt-px h-3 w-3 shrink-0 text-emerald-500/70" />
            Kosongkan semua filter untuk mengekspor seluruh data siswa.
          </p>
        </div>

        <div class="shrink-0 border-t border-slate-800/70 bg-slate-950/30 p-5">
          <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button
              type="button"
              @click="showExportModal = false"
              class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto"
            >
              Batal
            </button>
            <button
              type="button"
              @click="handleExecuteExport"
              :disabled="isExporting"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 shadow-lg shadow-emerald-500/20 hover:bg-emerald-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto"
            >
              <Loader2 v-if="isExporting" class="h-4 w-4 animate-spin" />
              <Download v-else class="h-4 w-4" />
              {{ isExporting ? "Mengunduh..." : "Unduh Excel" }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ Modal impor siswa ============ -->
    <div
      v-if="showImportModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="import-title"
    >
      <div
        class="backdrop-in absolute inset-0 bg-slate-950/80 backdrop-blur-sm"
        aria-hidden="true"
        @click="showImportModal = false"
      ></div>

      <div
        class="modal-panel relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-blue-500/30 bg-slate-900 shadow-2xl shadow-black/50"
      >
        <span
          class="absolute inset-x-0 top-0 z-20 h-0.5 bg-linear-to-r from-blue-500/70 via-blue-500/20 to-transparent"
          aria-hidden="true"
        ></span>

        <div
          class="flex shrink-0 items-start justify-between gap-4 border-b border-blue-500/20 px-5 py-4"
        >
          <div>
            <h3
              id="import-title"
              class="text-sm font-bold tracking-tight text-slate-100"
            >
              Impor Data Siswa
            </h3>
            <p class="mt-0.5 text-[11px] text-slate-500">
              Unggah file Excel berisi data siswa secara massal
            </p>
          </div>
          <button
            type="button"
            @click="showImportModal = false"
            aria-label="Tutup"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors duration-200 hover:bg-slate-800 hover:text-slate-200"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="modal-scroll min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
          <!-- Langkah 1: unduh template -->
          <div class="rounded-lg border border-slate-800 bg-slate-950/40 p-4">
            <p class="text-xs font-semibold text-slate-200">Unduh Template</p>
            <p class="mt-1 text-[11px] leading-relaxed text-slate-500">
              Gunakan template Excel resmi supaya format data sesuai dan
              terhindar dari error.
            </p>
            <button
              type="button"
              @click="handleDownloadTemplate"
              class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800/60 px-3 py-2 text-[11px] font-semibold text-slate-300 transition-all duration-150 hover:border-emerald-500/40 hover:text-emerald-400"
            >
              <FileSpreadsheet class="h-3.5 w-3.5" />
              Unduh Template Excel
            </button>
          </div>

          <!-- Langkah 2: upload -->
          <div class="rounded-lg border border-slate-800 bg-slate-950/40 p-4">
            <p class="text-xs font-semibold text-slate-200">Unggah File</p>
            <p class="mt-1 text-[11px] leading-relaxed text-slate-500">
              Isi template dengan data siswa, lalu unggah kembali di sini.
            </p>

            <input
              ref="importFileInputRef"
              type="file"
              accept=".xlsx,.xls"
              class="hidden"
              @change="handleImportFileSelect"
            />

            <button
              type="button"
              @click="importFileInputRef?.click()"
              class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border-2 border-dashed border-slate-700 bg-slate-950/30 px-4 py-6 text-xs font-medium text-slate-400 transition-colors duration-150 hover:border-blue-500/40 hover:text-blue-400"
            >
              <Upload class="h-4 w-4" />
              {{
                importFile
                  ? importFile.name
                  : "Klik untuk pilih file Excel (.xlsx)"
              }}
            </button>
          </div>

          <!-- Hasil import -->
          <div v-if="importResult" class="space-y-2">
            <div
              class="flex items-center gap-2 rounded-lg border border-emerald-500/25 bg-emerald-500/6 px-3.5 py-3"
            >
              <CheckCircle2 class="h-4 w-4 shrink-0 text-emerald-400" />
              <p class="text-[11px] text-emerald-300">
                {{ importResult.success_count }} siswa berhasil diimpor.
              </p>
            </div>
            <div
              v-if="importResult.errors?.length"
              class="rounded-lg border border-amber-500/25 bg-amber-500/6 p-3.5"
            >
              <p
                class="flex items-center gap-1.5 text-[11px] font-semibold text-amber-300"
              >
                <AlertTriangle class="h-3.5 w-3.5" />
                {{ importResult.errors.length }} baris dilewati:
              </p>
              <ul
                class="mt-2 max-h-32 space-y-1 overflow-y-auto text-[10px] text-slate-400"
              >
                <li v-for="(err, i) in importResult.errors" :key="i">
                  {{ err }}
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div class="shrink-0 border-t border-blue-500/20 bg-slate-950/30 p-5">
          <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button
              type="button"
              @click="showImportModal = false"
              class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto"
            >
              Tutup
            </button>
            <button
              type="button"
              @click="handleExecuteImport"
              :disabled="isImporting || !importFile"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-500 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-blue-500/20 transition-all duration-200 hover:bg-blue-400 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto"
            >
              <Upload v-if="!isImporting" class="h-4 w-4" />
              <Loader2 v-else class="h-4 w-4 animate-spin" />
              {{ isImporting ? "Mengimpor..." : "Impor Sekarang" }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ Modal hapus pengguna ============ -->
    <div
      v-if="showDeleteModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
      role="dialog"
      aria-modal="true"
      aria-labelledby="delete-title"
    >
      <div
        class="backdrop-in absolute inset-0 bg-slate-950/80 backdrop-blur-sm"
        aria-hidden="true"
        @click="showDeleteModal = false"
      ></div>

      <div
        class="modal-panel relative flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-rose-500/30 bg-slate-900 shadow-2xl shadow-black/50"
      >
        <span
          class="absolute inset-x-0 top-0 z-20 h-0.5 bg-linear-to-r from-rose-500/70 via-rose-500/20 to-transparent"
          aria-hidden="true"
        ></span>

        <!-- Kepala modal -->
        <div
          class="flex shrink-0 items-start justify-between gap-4 border-b border-rose-500/20 px-5 py-4"
        >
          <div>
            <h3
              id="delete-title"
              class="text-sm font-bold tracking-tight text-slate-100"
            >
              Hapus Pengguna
            </h3>
            <p class="mt-0.5 text-[11px] text-slate-500">
              Tindakan permanen dan tidak dapat dibatalkan
            </p>
          </div>
          <button
            type="button"
            @click="showDeleteModal = false"
            aria-label="Tutup"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors duration-200 hover:bg-slate-800 hover:text-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400/60"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <!-- Badan modal -->
        <div class="modal-scroll min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
          <div
            class="flex items-center gap-3 rounded-lg border border-slate-800 bg-slate-950/40 px-3.5 py-3"
          >
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border text-[11px] font-bold"
              :class="
                selectedUser
                  ? getRoleAvatarClass(selectedUser.role)
                  : 'border-slate-700 bg-slate-800 text-slate-300'
              "
            >
              {{ selectedUser ? getInitials(selectedUser.name) : "?" }}
            </div>
            <div class="min-w-0">
              <p class="truncate text-xs font-semibold text-slate-100">
                {{ selectedUser?.name }}
              </p>
              <p class="mt-0.5 truncate text-[11px] text-slate-500">
                {{ selectedUser?.email }}
              </p>
            </div>
            <span
              v-if="selectedUser"
              class="ml-auto inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
              :class="getRoleBadgeClass(selectedUser.role)"
            >
              {{ selectedUser.role }}
            </span>
          </div>

          <p class="text-xs leading-relaxed text-slate-400">
            Apakah Anda yakin ingin menghapus akun
            <span class="font-semibold text-slate-200">{{
              selectedUser?.name
            }}</span
            >? Seluruh hak akses pengguna ini pada sistem akan dicabut secara
            permanen.
          </p>

          <div
            v-if="selectedUser?.role === 'Admin'"
            class="flex items-start gap-2.5 rounded-lg border border-rose-500/25 bg-rose-500/6 px-3.5 py-3"
          >
            <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0 text-rose-400/90" />
            <p class="text-[11px] leading-relaxed text-slate-400">
              <span class="font-semibold text-rose-300"
                >Akun Administrator.</span
              >
              Pastikan masih tersedia minimal satu administrator lain sebelum
              melanjutkan penghapusan.
            </p>
          </div>
        </div>

        <!-- Kaki modal -->
        <div class="shrink-0 border-t border-rose-500/20 bg-slate-950/30 p-5">
          <div class="flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button
              type="button"
              @click="showDeleteModal = false"
              class="inline-flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-800/50 px-4 py-2.5 text-xs font-semibold text-slate-300 transition-all duration-200 hover:border-slate-600 hover:text-white active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500"
            >
              Batal
            </button>
            <button
              type="button"
              @click="handleDeleteUser"
              class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-rose-500 px-4 py-2.5 text-xs font-bold text-white shadow-lg shadow-rose-500/20 transition-all duration-200 hover:bg-rose-400 hover:text-slate-950 active:scale-[.97] sm:w-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400"
            >
              <Trash2 class="h-4 w-4" />
              Hapus Akun
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ==========================================================================
   Animasi & Performa
   ========================================================================== */

/* Keyframes Muncul Modal */
@keyframes modalIn {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(10px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

/* Keyframes Fade Background Backdrop */
@keyframes backdropIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

/* Keyframes Entri Elemen Halaman */
@keyframes fadeUp {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Keyframes Baris Tabel */
@keyframes cardEnter {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Utilitas Kelas Animasi */
.backdrop-in {
  animation: backdropIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.modal-panel {
  animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  will-change: transform, opacity;
}

.fade-up {
  animation: fadeUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  will-change: transform, opacity;
}

.card-enter {
  animation: cardEnter 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  will-change: transform, opacity;
}

/* Transisi Bar Statistik */
.stat-bar {
  transition: width 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

/* ==========================================================================
   Kustomisasi Scrollbar
   ========================================================================== */

/* Sembunyikan Scrollbar Navigasi Horizontal (Mobile) */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

/* Scrollbar Halus untuk Modal */
.modal-scroll::-webkit-scrollbar {
  width: 5px;
}
.modal-scroll::-webkit-scrollbar-track {
  background: rgba(15, 23, 42, 0.6);
}
.modal-scroll::-webkit-scrollbar-thumb {
  background: rgba(51, 65, 85, 0.8);
  border-radius: 9999px;
}
.modal-scroll::-webkit-scrollbar-thumb:hover {
  background: rgba(16, 185, 129, 0.5);
}
</style>
