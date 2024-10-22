<?php
require 'Conex.inc';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '', 
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict',
]);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
if (!$user_id) {
    session_destroy();
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo "Token CSRF inválido.";
        exit();
    }

    $contenido = filter_input(INPUT_POST, 'contenido', FILTER_SANITIZE_STRING);
    $id_articulo = filter_input(INPUT_POST, 'id_articulo', FILTER_VALIDATE_INT);

    if (!$contenido || !$id_articulo) {
        echo "Datos inválidos.";
        exit();
    }

    $stmt = $db->prepare("SELECT ID_articulo FROM Articulos WHERE ID_articulo = ?");
    if (!$stmt) {
        error_log("Error al preparar la consulta: " . $db->error);
        echo "Ocurrió un error al procesar el comentario.";
        exit();
    }

    $stmt->bind_param('i', $id_articulo);
    if (!$stmt->execute()) {
        error_log("Error al ejecutar la consulta: " . $stmt->error);
        echo "Ocurrió un error al procesar el comentario.";
        exit();
    }

    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        echo "El artículo no existe.";
        exit();
    }
    $stmt->close();

    $stmt = $db->prepare("INSERT INTO Comentarios (ID_articulo, ID_usuario, contenido, fecha_creacion) VALUES (?, ?, ?, NOW())");
    if (!$stmt) {
        error_log("Error al preparar la consulta: " . $db->error);
        echo "Ocurrió un error al procesar el comentario.";
        exit();
    }

    $stmt->bind_param('iis', $id_articulo, $user_id, $contenido);
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: ver_articulo.php?id_articulo=" . urlencode($id_articulo));
        exit();
    } else {
        error_log("Error al insertar el comentario: " . $stmt->error);
        echo "Ocurrió un error al procesar el comentario.";
        exit();
    }
} else {
    echo "Método de solicitud inválido.";
    exit();
}
?>
