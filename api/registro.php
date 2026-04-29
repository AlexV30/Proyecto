<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, null, 'Método no permitido');
}

$body = leerBody();
$nombre = trim($body['nombre'] ?? '');
$email  = trim($body['email']  ?? '');
$pass   = $body['password'] ?? '';

// Validaciones
if (strlen($nombre) < 2) {
    responder(false, null, 'El nombre debe tener al menos 2 caracteres.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responder(false, null, 'El formato del email no es válido.');
}
if (strlen($pass) < 6) {
    responder(false, null, 'La contraseña debe tener al menos 6 caracteres.');
}

$db = getDB();

// Comprobar que el email no esté ya registrado
$stmt = $db->prepare('SELECT id FROM usuarios WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    responder(false, null, 'Este email ya está registrado.');
}

// Guardar usuario con contraseña hasheada (seguro)
$hash = password_hash($pass, PASSWORD_DEFAULT);
$stmt = $db->prepare('INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)');
$stmt->execute([$nombre, $email, $hash]);

responder(true, ['nombre' => $nombre, 'email' => $email], 'Cuenta creada correctamente.');
