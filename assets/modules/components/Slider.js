export function formatCurrency(valueInMillions) {
  const value = Number(valueInMillions) * 1000000;
  return value.toLocaleString("es-CO", {
    style: "currency",
    currency: "COP",
    maximumFractionDigits: 0
  });
}

export function createSlider({ slider, amount, label, progress }) {
  function updateUI() {
    if (!slider || !amount || !label || !progress) {
      return;
    }

    const min = Number(slider.min);
    const max = Number(slider.max);
    const value = Number(slider.value);
    const percentage = ((value - min) / (max - min)) * 100;

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
