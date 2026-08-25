export function createCarousel({ slides, autoPlayMs, elements }) {
  const {
    title1,
    text1,
    icon1,
    icon1Image,
    title2,
    text2,
    icon2,
    icon2Image,
    title1Mobile,
    text1Mobile,
    icon1Mobile,
    icon1MobileImage,
    prevButton,
    nextButton,
    prevDesktopButton,
    nextDesktopButton,
    dots
  } = elements;

  let activeSlideIndex = 0;
  let autoPlayId;
  let isBound = false;

  function setIconVisual(iconElement, imageElement, slideItem, imageClasses) {
    if (!iconElement) {
      return;
    }

    if (slideItem.iconImage && imageElement) {
      imageElement.src = slideItem.iconImage;
      imageElement.className = imageClasses;
      imageElement.removeAttribute("hidden");
      iconElement.className = "hidden";
      return;
    }

    if (imageElement) {
      imageElement.className = "hidden";
    }

    iconElement.className = slideItem.icon || "hidden";
  }

  function setSlide(index) {
    const current = slides[index];
    if (!current) {
      return;
    }

    activeSlideIndex = index;
    const secondaryIndex = (index + 1) % slides.length;
    const secondary = slides[secondaryIndex];

    if (title1) {
      title1.textContent = current.title;
    }
    if (text1) {
      text1.textContent = current.text;
    }
    setIconVisual(icon1, icon1Image, current, "h-12 w-12 object-contain");

    if (title2) {
      title2.textContent = secondary.title;
    }
    if (text2) {
      text2.textContent = secondary.text;
    }
    setIconVisual(icon2, icon2Image, secondary, "h-12 w-12 object-contain");

    if (title1Mobile) {
      title1Mobile.textContent = current.title;
    }
    if (text1Mobile) {
      text1Mobile.textContent = current.text;
    }
    setIconVisual(icon1Mobile, icon1MobileImage, current, "h-8 w-8 object-contain");

    dots.forEach((dot, dotIndex) => {
      const isActive = dotIndex === index;
      dot.classList.toggle("bg-[#ffd400]", isActive);
      dot.classList.toggle("opacity-100", isActive);
      dot.classList.toggle("bg-[#111827]", !isActive);
      dot.classList.toggle("opacity-70", !isActive);
      dot.setAttribute("aria-pressed", String(isActive));
    });
  }

  function isReducedMotionPreferred() {
    return typeof window.matchMedia === "function"
      ? window.matchMedia("(prefers-reduced-motion: reduce)").matches
      : false;
  }

  function stopAutoPlay() {
    window.clearInterval(autoPlayId);
    autoPlayId = undefined;
  }

  function startAutoPlay() {
    if (slides.length <= 1 || isReducedMotionPreferred() || autoPlayId) {
      return;
    }

    autoPlayId = window.setInterval(() => {
      goNext();
    }, autoPlayMs);
  }

  function restartAutoPlay() {
    stopAutoPlay();
    startAutoPlay();
  }

  function goPrev() {
    const nextIndex = (activeSlideIndex - 1 + slides.length) % slides.length;
    setSlide(nextIndex);
  }

  function goNext() {
    const nextIndex = (activeSlideIndex + 1) % slides.length;
    setSlide(nextIndex);
  }

  function bindEvents() {
    if (isBound) {
      return;
    }

    isBound = true;

    dots.forEach((dot) => {
      dot.addEventListener("click", () => {
        const slideIndex = Number(dot.dataset.slide || 0);
        setSlide(slideIndex);
        restartAutoPlay();
      });
    });

    prevButton?.addEventListener("click", () => {
      goPrev();
      restartAutoPlay();
    });

    nextButton?.addEventListener("click", () => {
      goNext();
      restartAutoPlay();
    });

    prevDesktopButton?.addEventListener("click", () => {
      goPrev();
      restartAutoPlay();
    });

    nextDesktopButton?.addEventListener("click", () => {
      goNext();
      restartAutoPlay();
    });

    const interactiveElements = [
      prevButton,
      nextButton,
      prevDesktopButton,
      nextDesktopButton,
      ...dots
    ].filter(Boolean);

    interactiveElements.forEach((element) => {
      element.addEventListener("pointerenter", stopAutoPlay);
      element.addEventListener("pointerleave", startAutoPlay);
      element.addEventListener("focusin", stopAutoPlay);
      element.addEventListener("focusout", startAutoPlay);
    });

    document.addEventListener("visibilitychange", () => {
      if (document.hidden) {
        stopAutoPlay();
        return;
      }

      startAutoPlay();
    });
  }

  function init() {
    bindEvents();
    setSlide(0);
    startAutoPlay();
  }

  return {
    init,
    setSlide,
    startAutoPlay,
    stopAutoPlay,
    restartAutoPlay,
    getActiveSlideIndex: () => activeSlideIndex
  };
}
