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

$stmt = $db->prepare("SELECT usuario, nombre, apellido, email, nacionalidad, foto FROM Usuarios WHERE ID_usuario = ?");
if (!$stmt) {
    error_log("Error al preparar la consulta: " . $db->error);
    $error_message = "Ocurrió un error al cargar tu perfil. Por favor, inténtalo de nuevo más tarde.";
} else {
    $stmt->bind_param('i', $user_id);
    if (!$stmt->execute()) {
        error_log("Error al ejecutar la consulta: " . $stmt->error);
        $error_message = "Ocurrió un error al cargar tu perfil. Por favor, inténtalo de nuevo más tarde.";
    } else {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $username = htmlspecialchars($row['usuario']);
            $email = htmlspecialchars($row['email']);
            $nombre = htmlspecialchars($row['nombre']);
            $apellido = htmlspecialchars($row['apellido']);
            $nacionalidad = htmlspecialchars($row['nacionalidad']);
            $foto_perfil = htmlspecialchars($row['foto']);
        } else {
            $error_message = "Usuario no encontrado.";
        }
    }
    $stmt->close();
}

if (isset($error_message)) {
    echo "<p>$error_message</p>";
    exit();
}
?>
<!------------------------------------------------------------------------------------------------------------------------------------------------------------------>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil de Usuario</title>
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

<div class="container">
    <div class="profile-content">
        <div class="profile-header">
            <img src="<?php echo isset($foto_perfil) && !empty($foto_perfil) ? 'uploads/' . $foto_perfil : 'img/user.jpg'; ?>" alt="Perfil" class="profile-picture">
            <div class="profile-info">
                <strong>Nombre de Usuario:</strong> <?php echo $username; ?><br>
                <strong>Email:</strong> <?php echo $email; ?><br>
                <strong>Nombre:</strong> <?php echo $nombre . ' ' . $apellido; ?><br>
                <strong>Nacionalidad:</strong> <?php echo $nacionalidad; ?><br>
                <form action="cambiar_foto.php" method="POST" enctype="multipart/form-data">
                    <input type="file" name="foto_perfil" accept="image/*" required>
                    <button type="submit" class="change-photo">Cambiar foto de perfil</button>
                </form>
            </div>
        </div>
        
        <div id="dynamicContent">
            <p>Selecciona una opción del panel para comenzar.</p>
        </div>
    </div>

    <div class="sidebar">
        <button onclick="loadContent('seguridad')">Seguridad</button>
        <button onclick="loadContent('personalizacion')">Personalización</button>
        <button onclick="loadContent('logros')">Logros</button>
        <button onclick="loadContent('configuracionCuenta')">Configuración de cuenta</button>
        <button onclick="loadContent('configuracionAhorros')">Configuración de ahorros</button>
        <button onclick="loadContent('grafico')">Gráfico</button>
    </div>
</div>

<script src="JS/perfil.js"></script>
<script src="JS/menu_lateral.js"></script>
</body>
</html>
