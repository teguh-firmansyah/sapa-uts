import settingService from "@/services/settingService";

function setMetaTag(selector, attr, value) {
  if (!value) return;
  let el = document.querySelector(selector);
  if (!el) {
    el = document.createElement("meta");
    const [attrName, attrValue] = selector.match(/\[(.+?)="(.+?)"\]/).slice(1);
    el.setAttribute(attrName, attrValue);
    document.head.appendChild(el);
  }
  el.setAttribute(attr, value);
}

function setFavicon(url) {
  if (!url) return;
  let link = document.querySelector('link[rel="icon"]');
  if (!link) {
    link = document.createElement("link");
    link.rel = "icon";
    document.head.appendChild(link);
  }
  link.href = url;
}

export async function applySeoSettings() {
  try {
    const data = await settingService.getSeoSettings();

    if (data.meta_title) document.title = data.meta_title;

    setMetaTag('meta[name="description"]', "content", data.meta_description);
    setMetaTag('meta[name="keywords"]', "content", data.meta_keywords);
    setMetaTag('meta[property="og:title"]', "content", data.meta_title);
    setMetaTag(
      'meta[property="og:description"]',
      "content",
      data.meta_description,
    );
    if (data.og_image_url)
      setMetaTag('meta[property="og:image"]', "content", data.og_image_url);
    if (data.favicon_url) setFavicon(data.favicon_url);
  } catch {}
}
