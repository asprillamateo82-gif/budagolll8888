import { canAdvanceForm } from "../validators/FormValidator.js";

export function createFormManager({
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

      const clickedInsideSelector =
        documentTypeTrigger.contains(target) || documentTypeOptions.contains(target);

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
