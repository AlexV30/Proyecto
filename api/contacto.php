<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(false, null, 'Método no permitido');

$body   = leerBody();
$nombre  = trim($body['nombre']  ?? '');
$email   = trim($body['email']   ?? '');
$asunto  = trim($body['asunto']  ?? '');
$mensaje = trim($body['mensaje'] ?? '');

if (!$nombre || !$email || !$asunto || !$mensaje)
    responder(false, null, 'Todos los campos son obligatorios');

if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    responder(false, null, 'El email no es válido');

if (mb_strlen($mensaje) > 2000)
    responder(false, null, 'El mensaje es demasiado largo');

getDB()->prepare('INSERT INTO mensajes (nombre, email, asunto, mensaje) VALUES (?, ?, ?, ?)')
       ->execute([$nombre, $email, $asunto, $mensaje]);

responder(true, null, '¡Mensaje enviado! Te responderemos lo antes posible.');
