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

$id_articulo = filter_input(INPUT_GET, 'id_articulo', FILTER_VALIDATE_INT);
if (!$id_articulo) {
    echo "Artículo no válido.";
    exit();
}

$stmt = $db->prepare("
    SELECT A.titulo, A.contenido, A.fecha_creacion, U.usuario
    FROM Articulos A
    INNER JOIN Usuarios U ON A.ID_usuario = U.ID_usuario
    WHERE A.ID_articulo = ?
");
if (!$stmt) {
    error_log("Error al preparar la consulta: " . $db->error);
    echo "Ocurrió un error al cargar el artículo. Por favor, inténtalo de nuevo más tarde.";
    exit();
}

$stmt->bind_param('i', $id_articulo);
if (!$stmt->execute()) {
    error_log("Error al ejecutar la consulta: " . $stmt->error);
    echo "Ocurrió un error al cargar el artículo. Por favor, inténtalo de nuevo más tarde.";
    exit();
}

$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "Artículo no encontrado.";
    exit();
}

$articulo = $result->fetch_assoc();
$stmt->close();

$stmt = $db->prepare("
    SELECT C.contenido, C.fecha_creacion, U.usuario
    FROM Comentarios C
    INNER JOIN Usuarios U ON C.ID_usuario = U.ID_usuario
    WHERE C.ID_articulo = ?
    ORDER BY C.fecha_creacion ASC
");
if (!$stmt) {
    error_log("Error al preparar la consulta: " . $db->error);
    $error_message = "Ocurrió un error al cargar los comentarios.";
} else {
    $stmt->bind_param('i', $id_articulo);
    if (!$stmt->execute()) {
        error_log("Error al ejecutar la consulta: " . $stmt->error);
        $error_message = "Ocurrió un error al cargar los comentarios.";
    } else {
        $comentarios = $stmt->get_result();
    }
    $stmt->close();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!------------------------------------------------------------------------------------------------------------------------------------------------------------------>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($articulo['titulo']); ?></title>
    <link rel="stylesheet" href="CSS/style.css">
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
            <li><a href="bancos.php">Tus Cuentas</a></li>
            <li><a href="categorias.php">Tus Categorías</a></li>
            <li><a href="articulos.php">Ver Artículos</a></li>
            <li><a href="estadisticas.php">Estadísticas</a></li>
            <li><a href="logros.php">Logros</a></li>
        </ul>
    </aside>

<main class="main-articulo">
    <article>
        <h2><?php echo htmlspecialchars($articulo['titulo']); ?></h2>
        <p><?php echo nl2br(htmlspecialchars($articulo['contenido'])); ?></p>
        <small>Escrito por: <?php echo htmlspecialchars($articulo['usuario']); ?> el <?php echo date('d/m/Y H:i', strtotime($articulo['fecha_creacion'])); ?></small>
    </article>
    <hr>
    <section id="comentarios">
        <h3>Comentarios</h3>

<!------------------------------------------------------------------------------------------------------------------------------------------------------------------>
        <?php
        if (isset($error_message)) {
            echo "<p>$error_message</p>";
        } else {
            if ($comentarios->num_rows > 0) {
                while ($comentario = $comentarios->fetch_assoc()) {
                    echo "<div class='comentario'>
                            <p>" . nl2br(htmlspecialchars($comentario['contenido'])) . "</p>
                            <small>Por " . htmlspecialchars($comentario['usuario']) . " el " . date('d/m/Y H:i', strtotime($comentario['fecha_creacion'])) . "</small>
                        </div>";
                }
            } else {
                echo "<p>No hay comentarios aún. ¡Sé el primero en comentar!</p>";
            }
        }
        ?>
<!------------------------------------------------------------------------------------------------------------------------------------------------------------------>

    </section>
    <hr>
    <section id="agregar-comentario">
        <h3>Agregar Comentario</h3>
        <form action="procesar_comentario.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="id_articulo" value="<?php echo htmlspecialchars($id_articulo); ?>">
            <div class="form-group">
                <label for="contenido">Comentario:</label>
                <textarea id="contenido" name="contenido" rows="4" required></textarea>
            </div>
            <button type="submit">Enviar</button>
        </form>
        <a href="articulos.php">Volver</a>
    </section>
</main>

<footer>
    <p>&copy; 2024 Foro de Artículos Informativos. Todos los derechos reservados.</p>
</footer>

<script src="JS/menu_lateral.js"></script>
</body>
</html>
