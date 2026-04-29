<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'campofrescodb');
define('DB_USER', 'campofreso');
define('DB_PASS', 'CampoFreso2026!');
define('DB_CHARSET', 'utf8mb4');

// Conexión PDO
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
    }
    return $pdo;
}

// Cabeceras para API JSON + CORS (necesario para fetch desde el frontend)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar sesión PHP para guardar el usuario logueado
session_start();

// Función helper para responder JSON
function responder(bool $ok, $datos = null, string $mensaje = ''): void {
    echo json_encode(['ok' => $ok, 'datos' => $datos, 'mensaje' => $mensaje]);
    exit;
}

// Leer body JSON de la petición
function leerBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}
