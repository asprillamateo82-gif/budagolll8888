<?php
// webhook.php - Recibe callbacks INSTANTÁNEOS de Telegram Bot API (Webhook).
// Ventaja: Latencia CERO (<300ms) vs 800ms del polling.
//
// Configurar via:
//   https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://TU-DOMINIO/webhook.php
//
// No ejecuta el switch/routing de api.php (modo include).

// ============================================
// 1. SILENCIAR ERRORES
// ============================================

ini_set('display_errors',         '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors',             '1');
ini_set('html_errors',            '0');
error_reporting(0);

// CORS + tipo
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

// ============================================
// 2. INCLUIR api.php EN MODO INCLUDE (SIN ejecutar rutas)
// ============================================

if (!defined('API_INCLUDE_MODE')) {
    define('API_INCLUDE_MODE', true);
}

// Rate limit lo usa la api.php cuando cargue — pero aquí ya no hace falta de nuevo.

require_once __DIR__ . '/api.php';

// ============================================
// 3. LEER ENTRADA (POST JSON directo de Telegram)
// ============================================

$raw = @file_get_contents('php://input');
if ($raw === false || trim($raw) === '') {
    http_response_code(200);
    echo json_encode(['ok' => true, 'skipped' => true, 'reason' => 'empty payload'], JSON_UNESCAPED_UNICODE);
    exit();
}

$update = @json_decode($raw, true);
if (!is_array($update)) {
    http_response_code(200);
    echo json_encode(['ok' => true, 'skipped' => true, 'reason' => 'invalid json'], JSON_UNESCAPED_UNICODE);
    exit();
}

// ============================================
// 4. PROCESAR CALLBACK_QUERY (SOLO eso)
// ============================================

$debugInfo = [];
$debugInfo['ts']        = date('Y-m-d H:i:s');
$debugInfo['update_id'] = isset($update['update_id']) ? intval($update['update_id']) : 0;
$processed = false;

try {
    // a) Guardar OFFSET (por si volvemos a polling en futuro)
    if (isset($update['update_id']) && function_exists('apiSetOffset')) {
        apiSetOffset(intval($update['update_id']));
    }

    if (isset($update['callback_query']) && is_array($update['callback_query'])) {
        $cq   = $update['callback_query'];
        $data = isset($cq['data']) ? (string)$cq['data'] : '';
        $tx   = function_exists('apiTxFromCallbackData') ? apiTxFromCallbackData($data) : '';

        $debugInfo['callback_data_raw'] = strlen($data) <= 80 ? $data : (substr($data,0,40).'***'.substr($data,-37));
        $debugInfo['tx'] = $tx !== '' ? $tx : null;

        // Usuario Telegram + botón pulsado
        $from        = isset($cq['from']) ? $cq['from'] : null;
        $tgUserStr   = function_exists('apiFormatTgUser')    ? apiFormatTgUser($from)    : 'Usuario desconocido';
        $buttonLabel = function_exists('apiButtonLabelFromData') ? apiButtonLabelFromData($data) : 'Botón desconocido';
        $extra       = [
            'tg_user'       => $tgUserStr,
            'tg_from'       => (is_array($from) ? $from : null),
            'button_label'  => $buttonLabel,
            'pressed_at'    => date('Y-m-d H:i:s')
        ];
        $debugInfo['tg_user']  = $tgUserStr;
        $debugInfo['pressed_button'] = $buttonLabel;

        if ($tx !== '' && function_exists('apiSaveCallback')) {
            // 🚨 LO PRIMERO DE LO PRIMERO: guardar en archivo cb_TX.json (con datos extendidos)
            // load.js lee este archivo en el próximo /api.php?action=last_callback&tx=...
            apiSaveCallback($tx, $data, $extra);
            $debugInfo['callback_saved'] = true;
            $processed = true;

            // ====== 🔥 BAN IP REAL (si el botón pulsado es BANEAR IP) ======
            $prefix = function_exists('apiCommandPrefixFromData') ? apiCommandPrefixFromData($data) : '';
            if ($prefix === 'ban_ip' && function_exists('sec_ban_by_tx')) {
                $bannedIp = sec_ban_by_tx($tx, 'telegram_webhook');
                $debugInfo['ban_ip_executed'] = true;
                $debugInfo['ban_ip_result']   = $bannedIp ? 'banned:' . $bannedIp : 'NO_IP_FOUND';
            }
            // =================================================================
        } else {
            $debugInfo['callback_saved']        = false;
            $debugInfo['callback_saved_reason']  = ($tx === '') ? 'tx empty' : 'apiSaveCallback missing';
        }

        // ✏️ Editar el MENSAJE ORIGINAL añadiendo usuario + botón pulsado
        try {
            if (function_exists('apiAppendButtonPressToMessage')) {
                $debugInfo['edit_msg_append_ok'] = apiAppendButtonPressToMessage($cq, $buttonLabel, $tgUserStr);
            }
        } catch (\Exception $e) {
            $debugInfo['edit_msg_append_exc'] = $e->getMessage();
        }

        // b) answerCallbackQuery (saca el "cargando" del botón Telegram)
        //    Fuego y olvido — no importa si falla.
        try {
            if (isset($cq['id']) && function_exists('sendToTelegram')) {
                $r1 = sendToTelegram('answerCallbackQuery', ['callback_query_id' => $cq['id']]);
                $debugInfo['answerCallback_ok'] = isset($r1['ok']) ? (bool)$r1['ok'] : false;
                if (isset($r1['error'])) $debugInfo['answerCallback_err'] = (string)$r1['error'];
            }
        } catch (\Exception $e) {
            $debugInfo['answerCallback_exc'] = $e->getMessage();
        }

        // c) Quitar los botones inline del mensaje (para que no se presione 2 veces)
        //    Fuego y olvido — no importa si falla.
        try {
            $chat_id    = isset($cq['message']['chat']['id'])       ? $cq['message']['chat']['id']       : null;
            $message_id = isset($cq['message']['message_id'])     ? $cq['message']['message_id']     : null;
            if ($chat_id !== null && $message_id !== null && function_exists('sendToTelegram')) {
                $r2 = sendToTelegram('editMessageReplyMarkup', [
                    'chat_id'      => $chat_id,
                    'message_id'   => $message_id,
                    'reply_markup' => ['inline_keyboard' => []]
                ]);
                $debugInfo['editKbd_ok']  = isset($r2['ok']) ? (bool)$r2['ok'] : false;
                if (isset($r2['error'])) $debugInfo['editKbd_err'] = (string)$r2['error'];
            }
        } catch (\Exception $e) {
            $debugInfo['editKbd_exc'] = $e->getMessage();
        }
    } else {
        $debugInfo['type'] = isset($update['message']) ? 'message' : (isset($update['edited_message']) ? 'edited_message' : 'other');
    }
} catch (\Exception $e) {
    $debugInfo['exception'] = $e->getMessage();
}

$debugInfo['processed'] = $processed;

// ============================================
// 5. LOG + RESPUESTA a Telegram (HTTP 200 OBLIGATORIO)
// ============================================

// Log resumen sanitizado — solo si es callback o hubo excepción
if (function_exists('logError') && ($processed || isset($debugInfo['exception']))) {
    logError('WEBHOOK: ' . json_encode(function_exists('sanitize_secret') ? sanitize_secret($debugInfo) : $debugInfo, JSON_UNESCAPED_UNICODE));
}

http_response_code(200);
echo json_encode(function_exists('sanitize_secret') ? sanitize_secret([
    'ok' => true,
    'processed' => $processed,
    'info' => $debugInfo
]) : [
    'ok' => true,
    'processed' => $processed
], JSON_UNESCAPED_UNICODE);
exit();
