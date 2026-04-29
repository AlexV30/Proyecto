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

// Cabeceras para API JSON + CORS
header('Content-Type: application/json; charset=utf-8');

// Permitir el origen exacto que hace la peticion (necesario para cookies cross-IP)
$origen = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origen) {
    header('Access-Control-Allow-Origin: ' . $origen);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configurar cookie de sesion para que funcione desde cualquier IP
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', '0');
ini_set('session.cookie_path', '/');
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
