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

$mensaje = '';
if (isset($_GET['mensaje'])) {
    switch ($_GET['mensaje']) {
        case 'creado':
            $mensaje = 'Artículo creado exitosamente.';
            break;
        case 'actualizado':
            $mensaje = 'Artículo actualizado exitosamente.';
            break;
        case 'eliminado':
            $mensaje = 'Artículo eliminado exitosamente.';
            break;
    }
}

$articulos_por_pagina = 5;

$stmt_total = $db->prepare("SELECT COUNT(*) FROM Articulos WHERE ID_usuario = ?");
if (!$stmt_total) {
    error_log("Error al preparar la consulta: " . $db->error);
    $error_message = "Ocurrió un error al cargar tus artículos. Por favor, inténtalo de nuevo más tarde.";
} else {
    $stmt_total->bind_param('i', $ID_usuario);
    if (!$stmt_total->execute()) {
        error_log("Error al ejecutar la consulta: " . $stmt_total->error);
        $error_message = "Ocurrió un error al cargar tus artículos. Por favor, inténtalo de nuevo más tarde.";
    } else {
        $stmt_total->bind_result($total_articulos);
        $stmt_total->fetch();
        $stmt_total->close();

        $total_paginas = ceil($total_articulos / $articulos_por_pagina);

        $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        if ($pagina_actual < 1) $pagina_actual = 1;
        if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;

        $offset = ($pagina_actual - 1) * $articulos_por_pagina;

        $stmt = $db->prepare("
            SELECT ID_articulo, titulo, contenido, fecha_creacion
            FROM Articulos
            WHERE ID_usuario = ?
            ORDER BY fecha_creacion DESC
            LIMIT ?, ?
        ");
        if (!$stmt) {
            error_log("Error al preparar la consulta: " . $db->error);
            $error_message = "Ocurrió un error al cargar tus artículos. Por favor, inténtalo de nuevo más tarde.";
        } else {
            $stmt->bind_param('iii', $ID_usuario, $offset, $articulos_por_pagina);
            if (!$stmt->execute()) {
                error_log("Error al ejecutar la consulta: " . $stmt->error);
                $error_message = "Ocurrió un error al cargar tus artículos. Por favor, inténtalo de nuevo más tarde.";
            } else {
                $result = $stmt->get_result();
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Artículos</title>
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
            <li><a href="ver_perfil.php">Perfil</a></li>
            <li><a href="logout.php">Cerrar Sesión</a></li>
        </ul>
    </nav>
</header>

<aside id="sidebar" class="sidebar">
    <button id="close-btn" class="close-btn">&times;</button>
    <ul>
        <li><a href="dashboard.php">Inicio</a></li>
        <li><a href="estadistica.php">Estadísticas</a></li>
        <li><a href="logros.php">Logros</a></li>
    </ul>
</aside>

<nav class="nav-foro">
        <ul>
            <li><a href="crear_articulo.php">Crear Artículo</a></li>
            <li><a href="articulos.php">Volver a Artículos</a></li>
        </ul>
</nav>

    <main class="main-articulo">
        <h2>Mis Artículos</h2>

<!------------------------------------------------------------------------------------------------------------------------------------------------------------------>
        <?php if ($mensaje): ?>
            <p class="mensaje"><?php echo htmlspecialchars($mensaje); ?></p>
        <?php endif; ?>
<!------------------------------------------------------------------------------------------------------------------------------------------------------------------>

<!------------------------------------------------------------------------------------------------------------------------------------------------------------------>
        <?php
        if (isset($error_message)) {
            echo "<p>$error_message</p>";
        } else {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $fecha_formateada = date('d/m/Y H:i', strtotime($row['fecha_creacion']));

                    echo "<article>
                            <h3>" . htmlspecialchars($row['titulo']) . "</h3>
                            <p>" . nl2br(htmlspecialchars(substr($row['contenido'], 0, 200))) . "...</p>
                            <small>Publicado el " . htmlspecialchars($fecha_formateada) . "</small>
                            <br>
                            <a href='ver_articulo.php?id_articulo=" . urlencode($row['ID_articulo']) . "'>Leer más</a>
                            |
                            <a href='editar_articulo.php?id_articulo=" . urlencode($row['ID_articulo']) . "'>Editar</a>
                            |
                            <a href='eliminar_articulo.php?id_articulo=" . urlencode($row['ID_articulo']) . "' onclick=\"return confirm('¿Estás seguro de que deseas eliminar este artículo?');\">Eliminar</a>
                        </article>
                        <hr>";
                }
                $stmt->close();

                echo '<div class="paginacion">';
                if ($pagina_actual > 1) {
                    echo '<a href="mis_articulos.php?pagina=' . ($pagina_actual - 1) . '">&laquo; Anterior</a>';
                }

                for ($i = 1; $i <= $total_paginas; $i++) {
                    if ($i == $pagina_actual) {
                        echo '<span class="pagina-actual">' . $i . '</span>';
                    } else {
                        echo '<a href="mis_articulos.php?pagina=' . $i . '">' . $i . '</a>';
                    }
                }

                if ($pagina_actual < $total_paginas) {
                    echo '<a href="mis_articulos.php?pagina=' . ($pagina_actual + 1) . '">Siguiente &raquo;</a>';
                }
                echo '</div>';
            } else {
                echo "<p>No has creado ningún artículo aún.</p>";
            }
        }
        ?>
<!------------------------------------------------------------------------------------------------------------------------------------------------------------------>

    </main>

    <footer>
        <p>&copy; 2024 Foro de Artículos Informativos. Todos los derechos reservados.</p>
    </footer>

    <script src="JS/menu_lateral.js"></script>
</body>
</html>
