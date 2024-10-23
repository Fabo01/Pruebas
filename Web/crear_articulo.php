<?php
require 'Conex.inc';

// Configurar las cookies de sesión de manera segura
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict',
]);

session_start();

// Verificar si el usuario ha iniciado sesión
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

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        $message = "Token CSRF inválido.";
    } else {
        // Sanitizar y validar los datos de entrada
        $titulo = trim($_POST['titulo']);
        $contenido = trim($_POST['contenido']);

        if (empty($titulo) || empty($contenido)) {
            $message = "El título y el contenido no pueden estar vacíos.";
        } else {
            // Preparar la consulta SQL
            $stmt = $db->prepare("INSERT INTO Articulos (titulo, contenido, ID_usuario, fecha_creacion) VALUES (?, ?, ?, NOW())");
            if (!$stmt) {
                error_log("Error al preparar la consulta: " . $db->error);
                $message = "Ocurrió un error al crear el artículo. Por favor, inténtalo de nuevo más tarde.";
            } else {
                $stmt->bind_param('ssi', $titulo, $contenido, $ID_usuario);
                if ($stmt->execute()) {
                    header('Location: mis_articulos.php?mensaje=creado');
                    exit();
                } else {
                    error_log("Error al ejecutar la consulta: " . $stmt->error);
                    $message = "Ocurrió un error al crear el artículo. Por favor, inténtalo de nuevo más tarde.";
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Artículo</title>
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
            <li><a href="estadisticas.php">Estadísticas</a></li>
            <li><a href="logros.php">Logros</a></li>
        </ul>
    </aside>

    <main>
        <h1>Crear un nuevo artículo</h1>

        <?php if ($message): ?>
            <p class="mensaje"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form method="post" action="crear_articulo.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div class="form-group">
                <label for="titulo">Título:</label>
                <input type="text" id="titulo" name="titulo" required>
            </div>
            <div class="form-group">
                <label for="contenido">Contenido:</label>
                <textarea id="contenido" name="contenido" rows="10" required></textarea>
            </div>
            <button type="submit">Crear Artículo</button>
        </form>
    </main>

    <footer>
        <p>&copy; 2024 Foro de Artículos Informativos. Todos los derechos reservados.</p>
    </footer>

    <script src="JS/menu_lateral.js"></script>
</body>
</html>
