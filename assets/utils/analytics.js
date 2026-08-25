export function trackEvent(name, payload = {}) {
  const detail = { name, ...payload };

  if (Array.isArray(window.dataLayer)) {
    window.dataLayer.push(detail);
  }

  document.dispatchEvent(new CustomEvent("app:track", { detail }));
}
