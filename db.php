<?php
/**
 * Conexión PDO a MySQL (se incluye desde index.php).
 */
require_once __DIR__ . '/config.php';

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        $detalle = APP_DEBUG ? htmlspecialchars($e->getMessage()) : 'Revisa config.php';
        echo '<!doctype html><meta charset="utf-8"><title>Error de conexión</title>'
           . '<div style="font-family:system-ui;max-width:640px;margin:60px auto;padding:24px;border:1px solid #fca5a5;background:#fef2f2;border-radius:12px">'
           . '<h2 style="margin-top:0;color:#b91c1c">No se pudo conectar a la base de datos</h2>'
           . '<p><b>Detalle:</b> ' . $detalle . '</p>'
           . '<ul><li>Verifica DB_NAME y DB_USER con el prefijo de cPanel.</li>'
           . '<li>Verifica la contraseña del usuario.</li>'
           . '<li>Confirma que el usuario fue agregado a la BD con TODOS LOS PRIVILEGIOS.</li>'
           . '<li>Confirma que importaste dump.sql en phpMyAdmin.</li></ul></div>';
        exit;
    }
    return $pdo;
}
