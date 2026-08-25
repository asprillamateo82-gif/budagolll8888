import { hideFlex, showFlex } from "../../utils/dom.js";

export function createLoaderController({ overlay, textElement }) {
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
