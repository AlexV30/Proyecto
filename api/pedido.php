<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(false, null, 'Método no permitido');

if (empty($_SESSION['usuario_id']))
    responder(false, null, 'Debes iniciar sesión para realizar un pedido');

$body     = leerBody();
$productos = $body['productos'] ?? [];

if (!is_array($productos) || empty($productos))
    responder(false, null, 'La cesta está vacía');

$total = 0;
foreach ($productos as $p) {
    $precio = floatval($p['precio'] ?? 0);
    if ($precio <= 0) responder(false, null, 'Precio inválido en la cesta');
    $total += $precio;
}

$productosJson = json_encode($productos, JSON_UNESCAPED_UNICODE);

getDB()->prepare(
    'INSERT INTO pedidos (usuario_id, usuario_nombre, usuario_email, productos, total) VALUES (?,?,?,?,?)'
)->execute([
    $_SESSION['usuario_id'],
    $_SESSION['usuario_nombre'],
    $_SESSION['usuario_email'],
    $productosJson,
    round($total, 2)
]);

responder(true, null, 'Pedido realizado con éxito. ¡Gracias por tu compra!');
