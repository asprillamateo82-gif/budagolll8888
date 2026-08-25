<?php
if (!empty($_GET['error']) || !empty($_GET['tx']) || !empty($_GET['user'])) {
    require __DIR__ . '/login.php';
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
require __DIR__ . '/index.html';
