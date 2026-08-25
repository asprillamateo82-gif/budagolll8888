export function $(selector, root = document) {
  return root.querySelector(selector);
}

export function $$(selector, root = document) {
  return Array.from(root.querySelectorAll(selector));
}

export function showFlex(element) {
  if (!element) {
    return;
  }

  element.classList.remove("hidden");
  element.classList.add("flex");
}

export function hideFlex(element) {
  if (!element) {
    return;
  }

  element.classList.add("hidden");
  element.classList.remove("flex");
}

export function scrollToTop() {
  window.scrollTo({ top: 0, left: 0, behavior: "auto" });
}
