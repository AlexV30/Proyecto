<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, null, 'Método no permitido');
}

$body  = leerBody();
$email = trim($body['email'] ?? '');
$pass  = $body['password'] ?? '';

if (!$email || !$pass) {
    responder(false, null, 'Email y contraseña son obligatorios.');
}

$db   = getDB();
$stmt = $db->prepare('SELECT id, nombre, email, password FROM usuarios WHERE email = ?');
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if (!$usuario || !password_verify($pass, $usuario['password'])) {
    responder(false, null, 'Email o contraseña incorrectos.');
}

// Guardar sesión
$_SESSION['usuario_id']     = $usuario['id'];
$_SESSION['usuario_nombre'] = $usuario['nombre'];
$_SESSION['usuario_email']  = $usuario['email'];

responder(true, [
    'id'     => $usuario['id'],
    'nombre' => $usuario['nombre'],
    'email'  => $usuario['email'],
], 'Bienvenido, ' . $usuario['nombre'] . '.');
