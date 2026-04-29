<?php
require_once 'config.php';

$db     = getDB();
$metodo = $_SERVER['REQUEST_METHOD'];

// Obtener ID de la URL si viene (?id=X)
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($metodo) {

    // ---- GET: listar todos o uno solo ----
    case 'GET':
        if ($id) {
            $stmt = $db->prepare('SELECT * FROM productos WHERE id = ?');
            $stmt->execute([$id]);
            $producto = $stmt->fetch();
            if (!$producto) {
                responder(false, null, 'Producto no encontrado.');
            }
            responder(true, $producto);
        } else {
            // Filtro opcional por categoría
            $cat = $_GET['categoria'] ?? '';
            if ($cat && in_array($cat, ['frutas', 'verduras', 'basicos'])) {
                $stmt = $db->prepare('SELECT * FROM productos WHERE categoria = ? ORDER BY nombre');
                $stmt->execute([$cat]);
            } else {
                $stmt = $db->query('SELECT * FROM productos ORDER BY categoria, nombre');
            }
            responder(true, $stmt->fetchAll());
        }
        break;

    // ---- POST: crear nuevo producto (requiere sesión) ----
    case 'POST':
        if (!isset($_SESSION['usuario_id'])) {
            responder(false, null, 'Debes iniciar sesión para añadir productos.');
        }
        $body = leerBody();
        $nombre      = trim($body['nombre']      ?? '');
        $descripcion = trim($body['descripcion'] ?? '');
        $precio      = (float)($body['precio']   ?? 0);
        $categoria   = $body['categoria']        ?? '';
        $imagen      = trim($body['imagen']      ?? '../Fase2/img/manzanas.PNG');

        if (!$nombre) {
            responder(false, null, 'El nombre es obligatorio.');
        }
        if ($precio <= 0) {
            responder(false, null, 'El precio debe ser mayor que 0.');
        }
        if (!in_array($categoria, ['frutas', 'verduras', 'basicos'])) {
            responder(false, null, 'Categoría no válida.');
        }

        $stmt = $db->prepare(
            'INSERT INTO productos (nombre, descripcion, precio, categoria, imagen) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nombre, $descripcion, $precio, $categoria, $imagen]);
        $nuevoId = $db->lastInsertId();

        responder(true, ['id' => $nuevoId, 'nombre' => $nombre], 'Producto creado correctamente.');
        break;

    // ---- PUT: editar producto (requiere sesión) ----
    case 'PUT':
        if (!isset($_SESSION['usuario_id'])) {
            responder(false, null, 'Debes iniciar sesión.');
        }
        if (!$id) {
            responder(false, null, 'Falta el ID del producto.');
        }
        $body = leerBody();
        $nombre      = trim($body['nombre']      ?? '');
        $descripcion = trim($body['descripcion'] ?? '');
        $precio      = (float)($body['precio']   ?? 0);
        $categoria   = $body['categoria']        ?? '';
        $imagen      = trim($body['imagen']      ?? '');

        if (!$nombre || $precio <= 0 || !in_array($categoria, ['frutas', 'verduras', 'basicos'])) {
            responder(false, null, 'Datos del producto no válidos.');
        }

        $stmt = $db->prepare(
            'UPDATE productos SET nombre=?, descripcion=?, precio=?, categoria=?, imagen=? WHERE id=?'
        );
        $stmt->execute([$nombre, $descripcion, $precio, $categoria, $imagen, $id]);

        if ($stmt->rowCount() === 0) {
            responder(false, null, 'Producto no encontrado o sin cambios.');
        }
        responder(true, null, 'Producto actualizado correctamente.');
        break;

    // ---- DELETE: eliminar producto (requiere sesión) ----
    case 'DELETE':
        if (!isset($_SESSION['usuario_id'])) {
            responder(false, null, 'Debes iniciar sesión.');
        }
        if (!$id) {
            responder(false, null, 'Falta el ID del producto.');
        }
        $stmt = $db->prepare('SELECT nombre FROM productos WHERE id = ?');
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if (!$p) {
            responder(false, null, 'Producto no encontrado.');
        }
        $db->prepare('DELETE FROM productos WHERE id = ?')->execute([$id]);
        responder(true, null, 'Producto "' . $p['nombre'] . '" eliminado correctamente.');
        break;

    default:
        responder(false, null, 'Método no permitido.');
}
