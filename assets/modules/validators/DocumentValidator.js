export function isValidDocumentNumber(documentNumber) {
  if (!documentNumber) return false;
  const raw = String(documentNumber).replace(/\s+/g, "").trim();
  if (!/^\d{6,12}$/.test(raw)) return false;
  const digitsOnly = raw.replace(/\D/g, "");
  return digitsOnly.length >= 6 && digitsOnly.length <= 12;
}

export function isNumericInput(value) {
  return /^\d*$/.test(String(value || ""));
}
