import { hideFlex, showFlex, scrollToTop } from "../../utils/dom.js";

export function createScreenManager({
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
