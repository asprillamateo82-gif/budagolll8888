j<?php
// Router para el servidor integrado de PHP.
// Bloquea archivos sensibles y deja pasar assets/HTML/PHP públicos.

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$normalizedPath = strtolower(trim((string) $requestPath, '/'));

$blockedFiles = [
    'config.php',
    'security.php',
    '.htaccess',
    'php.ini'
];

if (in_array($normalizedPath, $blockedFiles, true)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo '403 Forbidden';
    return true;
}

$fullPath = __DIR__ . $requestPath;

if ($requestPath !== '/' && is_file($fullPath)) {
    return false;
}

require __DIR__ . '/index.html';
return true;
