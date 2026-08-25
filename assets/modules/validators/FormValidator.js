import { isValidDocumentNumber } from "./DocumentValidator.js";

export function canAdvanceForm({ documentNumber, documentType } = {}) {
  if (documentNumber !== undefined) {
    if (!isValidDocumentNumber(documentNumber)) return false;
  }
  if (documentType !== undefined) {
    if (!String(documentType || "").trim()) return false;
  }
  return true;
}

export function canSubmitFull({ documentType, documentNumber, acceptTerms } = {}) {
  if (!canAdvanceForm({ documentType, documentNumber })) return false;
  if (acceptTerms !== undefined && acceptTerms !== true && acceptTerms !== "on" && !String(acceptTerms).length) {
    return false;
  }
  return true;
}
