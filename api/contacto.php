<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, null, 'Metodo no permitido');
}

$input = leerBody();
$nombre = trim($input['nombre'] ?? '');
$email = trim($input['email'] ?? '');
$asunto = $input['asunto'] ?? '';
$mensaje = trim($input['mensaje'] ?? '');

if (!$nombre || !$email || !$asunto || !$mensaje) {
    responder(false, null, 'Todos los campos son obligatorios');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responder(false, null, 'Email no valido');
}

try {
    $pdo = getDB();
    $asuntos = ['consulta' => 'Consulta general', 'pedido' => 'Informacion sobre un pedido', 'incidencia' => 'Incidencia con un producto', 'sugerencia' => 'Sugerencia', 'otro' => 'Otro'];
    $asuntoTexto = $asuntos[$asunto] ?? $asunto;

    $stmt = $pdo->prepare('INSERT INTO contactos (nombre, email, asunto, mensaje, fecha) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([$nombre, $email, $asuntoTexto, $mensaje]);

    responder(true, null, 'Mensaje enviado correctamente. Te responderemos pronto.');
} catch (PDOException $e) {
    responder(false, null, 'Error al enviar el mensaje. Intentelo mas tarde.');
}
