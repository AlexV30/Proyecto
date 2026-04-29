<?php
require_once 'config.php';
if (isset($_SESSION['usuario_id'])) {
    responder(true, [
        'id'     => $_SESSION['usuario_id'],
        'nombre' => $_SESSION['usuario_nombre'],
        'email'  => $_SESSION['usuario_email'],
    ]);
} else {
    responder(false, null, 'No hay sesión activa.');
}
