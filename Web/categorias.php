<?php
require 'Conex.inc';
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Obtener información del usuario
$user_id = $_SESSION['user_id'];
$usuario = $_SESSION['usuario'];

$error = '';
$mensaje = '';

// Generar token CSRF
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['token'];

// Procesar formulario de creación o edición de categoría
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token'])) {
        $error = "Token CSRF inválido.";
    } else {
        $nombre_categoria = trim($_POST['nombre_categoria']);
        $tipo = trim($_POST['tipo']);
        $id_categoria = isset($_POST['id_categoria']) ? intval($_POST['id_categoria']) : 0;

        if (empty($nombre_categoria) || empty($tipo)) {
            $error = "Por favor, completa todos los campos.";
        } else {
            // Validación adicional: verificar duplicados
            $stmt_check = $db->prepare("SELECT COUNT(*) FROM Categorias WHERE nombre = ? AND tipo = ? AND ID_usuario = ? AND ID_categoria != ?");
            $stmt_check->bind_param('ssii', $nombre_categoria, $tipo, $user_id, $id_categoria);
            $stmt_check->execute();
            $stmt_check->bind_result($count);
            $stmt_check->fetch();
            $stmt_check->close();

            if ($count > 0) {
                $error = "Ya tienes una categoría con ese nombre y tipo.";
            } else {
                if ($id_categoria > 0) {
                    // Actualizar categoría existente
                    $stmt = $db->prepare("UPDATE Categorias SET nombre = ?, tipo = ? WHERE ID_categoria = ? AND ID_usuario = ?");
                    $stmt->bind_param('ssii', $nombre_categoria, $tipo, $id_categoria, $user_id);
                    if ($stmt->execute()) {
                        $mensaje = "Categoría actualizada exitosamente.";
                    } else {
                        $error = "Error al actualizar la categoría.";
                    }
                    $stmt->close();
                } else {
                    // Insertar nueva categoría
                    $stmt = $db->prepare("INSERT INTO Categorias (nombre, tipo, ID_usuario) VALUES (?, ?, ?)");
                    $stmt->bind_param('ssi', $nombre_categoria, $tipo, $user_id);
                    if ($stmt->execute()) {
                        $mensaje = "Categoría creada exitosamente.";
                    } else {
                        $error = "Error al crear la categoría.";
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// Procesar eliminación de categoría
if (isset($_GET['eliminar'])) {
    $id_categoria = intval($_GET['eliminar']);
    // Eliminar la categoría
    $stmt_delete = $db->prepare("DELETE FROM Categorias WHERE ID_categoria = ? AND ID_usuario = ?");
    $stmt_delete->bind_param('ii', $id_categoria, $user_id);
    if ($stmt_delete->execute()) {
        $mensaje = "Categoría eliminada exitosamente.";
    } else {
        $error = "Error al eliminar la categoría.";
    }
    $stmt_delete->close();
}

// Parámetros de búsqueda y paginación
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$categorias_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $categorias_por_pagina;

// Obtener el número total de categorías
$query_total = "SELECT COUNT(*) FROM Categorias WHERE ID_usuario = ?";
$params_total = [$user_id];
$types_total = 'i';

if (!empty($buscar)) {
    $query_total .= " AND nombre LIKE CONCAT('%', ?, '%')";
    $params_total[] = $buscar;
    $types_total .= 's';
}

$stmt_total = $db->prepare($query_total);
$stmt_total->bind_param($types_total, ...$params_total);
$stmt_total->execute();
$stmt_total->bind_result($total_categorias);
$stmt_total->fetch();
$stmt_total->close();

// Calcular total de páginas
$total_paginas = ceil($total_categorias / $categorias_por_pagina);

// Obtener las categorías del usuario con filtros y paginación
$query = "SELECT ID_categoria, nombre, tipo FROM Categorias WHERE ID_usuario = ?";
$params = [$user_id];
$types = 'i';

if (!empty($buscar)) {
    $query .= " AND nombre LIKE CONCAT('%', ?, '%')";
    $params[] = $buscar;
    $types .= 's';
}

$query .= " ORDER BY nombre ASC LIMIT ?, ?";
$params[] = $offset;
$params[] = $categorias_por_pagina;
$types .= 'ii';

$stmt = $db->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Categorías</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/intro_datos.css">
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
                    <a href="ayuda.php">
                        <button class="btn btn-boletines">Ayuda</button>
                    </a>
                </li>

                <li>
                    <div class="user-dropdown">
                        <img src="img/user.jpg" alt="Perfil" class="user-avatar">
                        <span>Usuario: <?php echo htmlspecialchars($usuario); ?></span>
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
            <li><a href="bancos.php">Bancos</a></li>
            <li><a href="categorias.php">Categorías</a></li>
            <li><a href="articulos.php">Ver Artículos</a></li>
            <li><a href="estadisticas.php">Estadísticas</a></li>
            <li><a href="logros.php">Logros</a></li>
        </ul>
    </aside>

    <main>

        <h3><?php echo isset($_GET['editar']) ? 'Editar Categoría' : 'Añadir una nueva categoría'; ?></h3>

        <!-- Mostrar mensajes de éxito o error -->
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php
        // Si se está editando una categoría, obtener sus datos
        $editar_nombre = '';
        $editar_tipo = '';
        $id_categoria_editar = 0;

        if (isset($_GET['editar'])) {
            $id_categoria_editar = intval($_GET['editar']);
            $stmt_editar = $db->prepare("SELECT nombre, tipo FROM Categorias WHERE ID_categoria = ? AND ID_usuario = ?");
            $stmt_editar->bind_param('ii', $id_categoria_editar, $user_id);
            $stmt_editar->execute();
            $stmt_editar->bind_result($editar_nombre, $editar_tipo);
            $stmt_editar->fetch();
            $stmt_editar->close();
        }
        ?>

        <form action="categorias.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" name="id_categoria" value="<?php echo $id_categoria_editar; ?>">

            <label for="nombre_categoria">Nombre de la Categoría: </label>
            <input type="text" name="nombre_categoria" placeholder="Asigne un nombre a la categoría" value="<?php echo htmlspecialchars($editar_nombre); ?>" required>

            <label for="tipo">Tipo: </label>
            <select name="tipo" required>
                <option value="">Seleccione</option>
                <option value="ingreso" <?php if ($editar_tipo == 'ingreso') echo 'selected'; ?>>Ingreso</option>
                <option value="gasto" <?php if ($editar_tipo == 'gasto') echo 'selected'; ?>>Gasto</option>
            </select>

            <button type="submit"><?php echo isset($_GET['editar']) ? 'Actualizar Categoría' : 'Añadir Categoría'; ?></button>
        </form>

        <!-- Barra de búsqueda -->
        <form method="GET" action="categorias.php" class="search-form">
            <input type="text" name="buscar" placeholder="Buscar categoría" value="<?php echo htmlspecialchars($buscar); ?>">
            <button type="submit">Buscar</button>
        </form>

        <div class="container-gestion">
            <h3>Mis Categorías</h3>
            <table>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Acciones</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($row['tipo'])); ?></td>
                        <td>
                            <a href="categorias.php?editar=<?php echo $row['ID_categoria']; ?>">Editar</a> |
                            <a href="categorias.php?eliminar=<?php echo $row['ID_categoria']; ?>" onclick="return confirm('¿Estás seguro de eliminar esta categoría?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>

            <!-- Paginación -->
            <div class="pagination">
                <?php
                $query_params = $_GET;
                unset($query_params['pagina']);
                $base_url = '?' . http_build_query($query_params);

                if ($pagina_actual > 1):
                ?>
                    <a href="<?php echo $base_url . '&pagina=' . ($pagina_actual - 1); ?>">&laquo; Anterior</a>
                <?php endif; ?>

                <span>Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>

                <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="<?php echo $base_url . '&pagina=' . ($pagina_actual + 1); ?>">Siguiente &raquo;</a>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <footer>
        <p>&copy; Gestor de Presupuestos 2024. Todos los derechos reservados.</p>
    </footer>

    <script src="JS/menu_lateral.js"></script>
</body>
</html>
