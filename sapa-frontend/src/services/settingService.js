import api from "./api";

export default {
  async getGeneralSettings() {
    const { data } = await api.get("/api/settings/general");
    return data;
  },

  async updateGeneralSettings(payload) {
    const formData = new FormData();
    formData.append("school_name", payload.school_name || "");
    formData.append("school_address", payload.school_address || "");
    formData.append("school_phone", payload.school_phone || "");
    formData.append("school_email", payload.school_email || "");
    if (payload.logo) formData.append("logo", payload.logo);

    const { data } = await api.post(
      "/api/admin/settings/general?_method=PATCH",
      formData,
      {
        headers: { "Content-Type": "multipart/form-data" },
      },
    );
    return data;
  },

  async getAppearanceSettings() {
    const { data } = await api.get("/api/settings/appearance");
    return data;
  },

  async updateAppearanceSettings(payload) {
    const { data } = await api.patch("/api/admin/settings/appearance", payload);
    return data;
  },

  async getSeoSettings() {
    const { data } = await api.get("/api/settings/seo");
    return data;
  },

  async updateSeoSettings(payload) {
    const formData = new FormData();
    formData.append("meta_title", payload.meta_title || "");
    formData.append("meta_description", payload.meta_description || "");
    formData.append("meta_keywords", payload.meta_keywords || "");
    if (payload.og_image) formData.append("og_image", payload.og_image);
    if (payload.favicon) formData.append("favicon", payload.favicon);

    const { data } = await api.post(
      "/api/admin/settings/seo?_method=PATCH",
      formData,
      {
        headers: { "Content-Type": "multipart/form-data" },
      },
    );
    return data;
  },
};
