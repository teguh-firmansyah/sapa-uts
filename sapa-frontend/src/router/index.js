import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const routes = [
  // ================= PROFILE (DYNAMIC REDIRECT & ROLE BASED) =================
  {
    path: "/profile",
    name: "profile",
    redirect: () => {
      const authStore = useAuthStore();
      if (authStore.hasRole("counselor")) return { name: "counselor-profile" };
      if (authStore.hasRole("student")) return { name: "student-profile" };
      if (authStore.hasRole("admin") || authStore.hasRole("staff")) {
        return authStore.defaultRoute;
      }
      return { name: "student-profile" };
    },
    meta: { requiresAuth: true },
  },
  {
    path: "/student/profile",
    name: "student-profile",
    component: () => import("@/views/student/ProfileView.vue"),
    meta: { requiresAuth: true, roles: ["student"] },
  },
  {
    path: "/counselor/profile",
    name: "counselor-profile",
    component: () => import("@/views/counselor/ProfileView.vue"),
    meta: { requiresAuth: true, roles: ["counselor"] },
  },

  // ================= PUBLIC (GUEST ONLY) =================
  {
    path: "/login",
    name: "login",
    component: () => import("@/views/auth/LoginView.vue"),
    meta: { guestOnly: true },
  },
  {
    path: "/register",
    name: "register",
    component: () => import("@/views/auth/RegisterView.vue"),
    meta: { guestOnly: true },
  },

  // ================= SISWA =================
  {
    path: "/",
    name: "dashboard",
    component: () => import("@/views/student/DashboardView.vue"),
    meta: { requiresAuth: true, roles: ["student"] },
  },
  {
    path: "/reports",
    name: "reports-list",
    component: () => import("@/views/student/ReportListView.vue"),
    meta: { requiresAuth: true, roles: ["student"] },
  },
  {
    path: "/reports/new",
    name: "report-create",
    component: () => import("@/views/student/CreateReportView.vue"),
    meta: { requiresAuth: true, roles: ["student"] },
  },
  {
    path: "/reports/:id",
    name: "report-detail",
    component: () => import("@/views/student/ReportDetailView.vue"),
    meta: { requiresAuth: true },
    props: true,
  },
  {
    path: "/aspirations",
    name: "aspiration-feed",
    component: () => import("@/views/student/AspirationFeedView.vue"),
    meta: { requiresAuth: true },
  },

  // ================= STAFF =================
  {
    path: "/staff/facility-queue",
    name: "staff-facility-queue",
    component: () => import("@/views/staff/FacilityQueueView.vue"),
    meta: { requiresAuth: true, roles: ["staff"] },
  },
  {
    path: "/staff/facility-reports/:id",
    name: "facility-report-detail",
    component: () => import("@/views/staff/FacilityReportDetailView.vue"),
    meta: { requiresAuth: true, roles: ["staff"] },
    props: true,
  },
  {
    path: "/staff/facility-history",
    name: "facility-history",
    component: () => import("@/views/staff/FacilityHistoryView.vue"),
    meta: { requiresAuth: true, roles: ["staff"] },
  },
  {
    path: "/staff/profile",
    name: "staff-profile",
    component: () => import("@/views/staff/ProfileView.vue"),
    meta: { requiresAuth: true, roles: ["staff"] },
  },

  // ================= COUNSELOR (BK) =================
  {
    path: "/counselor/bullying-queue",
    name: "counselor-bullying-queue",
    component: () => import("@/views/counselor/BullyingQueueView.vue"),
    meta: { requiresAuth: true, roles: ["counselor"] },
  },
  {
    path: "/counselor/bullying-reports/:id",
    name: "counselor-bullying-detail",
    component: () => import("@/views/counselor/BullyingDetailView.vue"),
    meta: { requiresAuth: true, roles: ["counselor"] },
    props: true,
  },
  {
    path: "/counselor/archived-reports",
    name: "counselor-archived-reports",
    component: () => import("@/views/counselor/ArchivedReportsView.vue"),
    meta: { requiresAuth: true, roles: ["counselor"] },
  },

  // ================= ADMIN =================
  {
    path: "/admin/dashboard",
    name: "admin-dashboard",
    component: () => import("@/views/admin/DashboardView.vue"),
    meta: { requiresAuth: true, roles: ["admin"] },
  },
  {
    path: "/admin/users",
    name: "admin-users",
    component: () => import("@/views/admin/UserManagementView.vue"),
    meta: { requiresAuth: true, roles: ["admin"] },
  },
  {
    path: "/admin/reports",
    name: "admin-reports",
    component: () => import("@/views/admin/AdminReports.vue"),
    meta: { requiresAuth: true, roles: ["admin"] },
  },

  {
    path: "/admin/reports/:id",
    name: "admin-report-detail",
    component: () => import("@/views/admin/AdminReportDetailView.vue"),
    meta: { requiresAuth: true, roles: ["admin"] },
    props: true,
  },

  {
    path: "/admin/aspirations",
    name: "admin-aspirations",
    component: () => import("@/views/admin/AdminAspirationsView.vue"),
    meta: { requiresAuth: true, roles: ["admin"] },
  },
  {
    path: "/admin/classes",
    name: "admin-classes",
    component: () => import("@/views/admin/AdminClassManagementView.vue"),
    meta: { requiresAuth: true, roles: ["admin"] },
  },
  {
    path: "/admin/settings",
    name: "admin-settings",
    component: () => import("@/views/admin/AdminSettingsView.vue"),
    meta: { requiresAuth: true, roles: ["admin"] },
  },

  // ================= FALLBACK =================
  {
    path: "/unauthorized",
    name: "unauthorized",
    component: () => import("@/views/errors/UnauthorizedView.vue"),
  },
  {
    path: "/:pathMatch(.*)*",
    name: "not-found",
    component: () => import("@/views/errors/NotFoundView.vue"),
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to) => {
  const authStore = useAuthStore();

  // 1. Inisialisasi status auth user sekali saja saat app pertama kali dibuka / di-refresh
  if (!authStore.isInitialized) {
    await authStore.fetchCurrentUser();
  }

  // 2. Proteksi Halaman Khusus Tamu (Login / Register)
  if (to.meta.guestOnly && authStore.isAuthenticated) {
    return authStore.defaultRoute;
  }

  // 3. Redirect awal jika user Non-Student mengakses root URL ('/')
  if (to.path === "/" && authStore.isAuthenticated && !authStore.isStudent) {
    return authStore.defaultRoute;
  }

  // 4. Proteksi Halaman yang Membutuhkan Login
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: "login", query: { redirect: to.fullPath } };
  }

  // 5. Proteksi Role-based Access Control (RBAC)
  if (to.meta.roles && !to.meta.roles.some((role) => authStore.hasRole(role))) {
    return { name: "unauthorized" };
  }

  return true;
});

export default router;
