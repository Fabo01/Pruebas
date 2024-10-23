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

$ID_usuario = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
if (!$ID_usuario) {
    session_destroy();
    header('Location: index.php');
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$message = '';

$id_articulo = filter_input(INPUT_GET, 'id_articulo', FILTER_VALIDATE_INT);
if (!$id_articulo) {
    echo "Artículo no válido.";
    exit();
}

$stmt = $db->prepare("SELECT titulo, contenido FROM Articulos WHERE ID_articulo = ? AND ID_usuario = ?");
if (!$stmt) {
    error_log("Error al preparar la consulta: " . $db->error);
    echo "Ocurrió un error al cargar el artículo.";
    exit();
}

$stmt->bind_param('ii', $id_articulo, $ID_usuario);
if (!$stmt->execute()) {
    error_log("Error al ejecutar la consulta: " . $stmt->error);
    echo "Ocurrió un error al cargar el artículo.";
    exit();
}

$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "No tienes permiso para editar este artículo.";
    exit();
}

$articulo = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        $message = "Token CSRF inválido.";
    } else {
        $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
        $contenido = filter_input(INPUT_POST, 'contenido', FILTER_SANITIZE_STRING);

        if (empty($titulo) || empty($contenido)) {
            $message = "El título y el contenido no pueden estar vacíos.";
        } else {
            $stmt = $db->prepare("UPDATE Articulos SET titulo = ?, contenido = ? WHERE ID_articulo = ? AND ID_usuario = ?");
            if (!$stmt) {
                error_log("Error al preparar la consulta: " . $db->error);
                $message = "Ocurrió un error al actualizar el artículo. Por favor, inténtalo de nuevo más tarde.";
            } else {
                $stmt->bind_param('ssii', $titulo, $contenido, $id_articulo, $ID_usuario);
                if ($stmt->execute()) {
                    $message = "Artículo actualizado exitosamente.";
                    header('Location: mis_articulos.php?mensaje=actualizado');
                    exit();
                } else {
                    error_log("Error al ejecutar la consulta: " . $stmt->error);
                    $message = "Ocurrió un error al actualizar el artículo. Por favor, inténtalo de nuevo más tarde.";
                }
                $stmt->close();
            }
        }
    }
}
?>
<!------------------------------------------------------------------------------------------------------------------------------------------------------------------>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Artículo</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/articulos.css">
</head>
<body>
    <header class="navbar">
        <button id="menu-btn" class="menu-btn">&#9776;</button>
        <div class="logo">
            Gestor de Presupuestos
        </div>
        <nav class="nav">
            <ul>
                <li>
                    <a href="informacion.php">
                        <button class="btn btn-boletines">Ayuda</button>
                    </a>
                </li>
                <li>
                    <div class="user-dropdown">
                        <img src="img/user.jpg" alt="Perfil" class="user-avatar">
                        <span>Usuario: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </div>
                </li>
                <li><a href="perfil.php">Perfil</a></li>
                <li><a href="logout.php">Cerrar Sesión</a></li>
            </ul>
        </nav>
    </header>

<aside id="sidebar" class="sidebar">
    <button id="close-btn" class="close-btn">&times;</button>
    <ul>
        <li><a href="dashboard.php">Inicio</a></li>
        <li><a href="articulos.php">Ver Artículos</a></li>
        <li><a href="estadistica.php">Estadísticas</a></li>
        <li><a href="logros.php">Logros</a></li>
    </ul>
</aside>

    <main>
        <h1>Editar Artículo</h1>

<!------------------------------------------------------------------------------------------------------------------------------------------------------------------>
        <?php if ($message): ?>
            <p class="mensaje"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
<!------------------------------------------------------------------------------------------------------------------------------------------------------------------>

        <form method="post" action="editar_articulo.php?id_articulo=<?php echo urlencode($id_articulo); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group">
                <label for="titulo">Título:</label>
                <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($articulo['titulo']); ?>" required>
            </div>
            <div class="form-group">
                <label for="contenido">Contenido:</label>
                <textarea id="contenido" name="contenido" rows="10" required><?php echo htmlspecialchars($articulo['contenido']); ?></textarea>
            </div>
            <button type="submit">Actualizar Artículo</button>
        </form>
    </main>


    <footer>
        <p>&copy; 2024 Foro de Artículos Informativos. Todos los derechos reservados.</p>
    </footer>


    <script src="JS/menu_lateral.js"></script>
</body>
</html>
