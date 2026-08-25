<?php
// config.php - Credenciales del sistema (v9 segura 9/10)
// NO compartir este archivo
// NUNCA subir con token/chat_id HARDCODEADOS al repositorio

// En Azure App Service, establece estos valores en "Configuration" -> "Application settings":
//
//  🔴 OBLIGATORIAS:
//    TELEGRAM_TOKEN           = tu_token_del_bot  (8847680339:AAEkAvJI...)
//    TELEGRAM_CHAT_ID         = -123456789  (chat/grupo donde llegan alerts)
//
//  🟡 RECOMENDADAS:
//    SYSTEM_NAME              = GOOLL-MC
//    ANTIBOT_ENABLED          = 1  (1=activado, 0=desactivado)
//    TRUST_PROXY_HEADERS      = 1  (Azure usa proxies: 1=leer IP real X-Forwarded-For,
//                                              0=usar siempre REMOTE_ADDR)
//
//  🟢 OPCIONAL (sube a 10/10 seguridad webhook):
//    TELEGRAM_WEBHOOK_SECRET  = texto_aleatorio_largo
//                               (coincide con &secret=... del setWebhook.
//                                Si no lo usas, no lo definas.)

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
//    (NO lo subas a Git / Azure)
//    o define las VARIABLES DE ENTORNO ANTES de arrancar PHP.
// ============================================================

$__token   = env_cfg('TELEGRAM_TOKEN',   '');
$__chatId  = env_cfg('TELEGRAM_CHAT_ID', '');

if ($__token === '' || $__chatId === '') {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    die(
        "[CONFIG] No se encontraron las credenciales.\r\n"
      . "Por favor DEFINE ESTAS VARIABLES DE ENTORNO (Azure App Service -> Configuration -> Application settings):\r\n"
      . "  - TELEGRAM_TOKEN   = tu token de @BotFather\r\n"
      . "  - TELEGRAM_CHAT_ID = id numerico de tu chat/grupo (con - delante si es grupo)\r\n"
      . "Recomendadas: TRUST_PROXY_HEADERS=1 , SYSTEM_NAME=GOOLL-MC , ANTIBOT_ENABLED=1\r\n"
      . "Opcional 10/10: TELEGRAM_WEBHOOK_SECRET=texto_aleatorio (&secret=... en setWebhook)\r\n"
      . "NO escribas tokens directamente en config.php (riesgo de robo).\r\n"
    );
}

define('TELEGRAM_TOKEN',   $__token);
define('TELEGRAM_CHAT_ID', $__chatId);
unset($__token, $__chatId);

define('SYSTEM_NAME',            env_cfg('SYSTEM_NAME',             'GOOLL-MC'));
define('ANTIBOT_ENABLED',        (bool)env_cfg('ANTIBOT_ENABLED',   '1'));

// NUEVO v9: configuración de IP real + webhook secret.
// (TRUST_PROXY_HEADERS se usa en security.php en sec_get_client_ip() para
//  decidir si se lee X-Forwarded-For. Azure recomendado = 1)
//  -> Lo definimos como define para que cualquier archivo pueda usarlo
//     si quisiera consultar el modo sin leer de nuevo getenv().
$__tph = strtolower((string)env_cfg('TRUST_PROXY_HEADERS', ''));
define(
    'TRUST_PROXY_HEADERS_ENABLED',
    ($__tph === '1' || $__tph === 'true' || $__tph === 'on' || $__tph === 'yes')
);
if (!defined('TRUST_PROXY_HEADERS')) {
    define('TRUST_PROXY_HEADERS', TRUST_PROXY_HEADERS_ENABLED ? '1' : '0');
}

// NUEVO v9: webhook secret (10/10). Se lee desde webhook.php para comprobar
// el header X-Telegram-Bot-Api-Secret-Token que envía Telegram.
$__whs = (string)env_cfg('TELEGRAM_WEBHOOK_SECRET', '');
define('TELEGRAM_WEBHOOK_SECRET', $__whs === '' ? null : $__whs);
unset($__tph, $__whs);
?>