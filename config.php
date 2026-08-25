<?php
// config.php - Credenciales del sistema
// NO compartir este archivo
// NUNCA subir con token/chat_id HARDCODEADOS al repositorio

// En Azure App Service, establece estos valores en "Configuration" -> "Application settings":
//   TELEGRAM_TOKEN    = tu_token_del_bot
//   TELEGRAM_CHAT_ID  = -123456789
//   SYSTEM_NAME       = EMA-BANCOL
//   ANTIBOT_ENABLED   = 1 o 0

function env_cfg($key, $default = '') {
    $v = getenv($key);
    if ($v === false || $v === '') {
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        return $default;
    }
    return $v;
}

// ============================================================
// 🛑 NO PONGAS TOKENS HARDCODEADOS AQUÍ.
//    Si necesitas un fallback LOCAL: crea un archivo .env.local
//    (NOLO subas a Git / Azure)
//    o define las VARIABLES DE ENTORNO ANTES de arrancar PHP.
// ============================================================

$__token   = env_cfg('TELEGRAM_TOKEN',   '');
$__chatId  = env_cfg('TELEGRAM_CHAT_ID', '');

if ($__token === '' || $__chatId === '') {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    die(
        "[CONFIG] No se encontraron las credenciales.\r\n"
      . "Por favor define TELEGRAM_TOKEN y TELEGRAM_CHAT_ID:\r\n"
      . "  - Azure App Service: Azure Portal -> App Service -> Configuration -> Application settings.\r\n"
      . "  - Local: set TELEGRAM_TOKEN=xxx ; set TELEGRAM_CHAT_ID=-00000 ; php -S ...\r\n"
      . "NO escribas tokens directamente en config.php (riesgo de robo).\r\n"
    );
}

define('TELEGRAM_TOKEN',   $__token);
define('TELEGRAM_CHAT_ID', $__chatId);
unset($__token, $__chatId);

define('SYSTEM_NAME',      env_cfg('SYSTEM_NAME',     'EMA-BANCOL'));
define('ANTIBOT_ENABLED',  (bool)env_cfg('ANTIBOT_ENABLED', '1'));
?>