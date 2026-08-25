import { APP_CONFIG } from "./config.js";
import { createAppState } from "./state.js";
import { $, $$ } from "../utils/dom.js";
import { trackEvent } from "../utils/analytics.js";
import { createSlider } from "../modules/components/Slider.js";
import { createCarousel } from "../modules/components/Carousel.js";
import { createLoaderController } from "../modules/ui/LoaderController.js";
import { createScreenManager } from "../modules/ui/ScreenManager.js";
import { createFormManager } from "../modules/ui/FormManager.js";

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
    state.loadingTimeoutId = undefined;
    state.loadingStageTimeoutId = undefined;
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
