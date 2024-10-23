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

// Parámetros de filtro
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$banco = isset($_GET['banco']) ? $_GET['banco'] : '';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';

// Parámetros de paginación
$transacciones_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $transacciones_por_pagina;

// Construir la consulta con filtros y paginación
$query = "
    SELECT 
        cb.banco, 
        t.monto, 
        c.nombre AS categoria, 
        t.fecha, 
        t.descripcion 
    FROM Transacciones t
    JOIN Cuentas_de_banco cb ON t.ID_banco = cb.ID_banco
    JOIN Categorias c ON t.ID_categoria = c.ID_categoria
    WHERE t.ID_usuario = ?
";

$params = [$user_id];
$types = 'i';

if (!empty($categoria)) {
    $query .= " AND c.ID_categoria = ?";
    $params[] = $categoria;
    $types .= 'i';
}

if (!empty($banco)) {
    $query .= " AND cb.ID_banco = ?";
    $params[] = $banco;
    $types .= 'i';
}

if (!empty($fecha_inicio)) {
    $query .= " AND t.fecha >= ?";
    $params[] = $fecha_inicio;
    $types .= 's';
}

if (!empty($fecha_fin)) {
    $query .= " AND t.fecha <= ?";
    $params[] = $fecha_fin;
    $types .= 's';
}

// Obtener el número total de transacciones (para la paginación)
$total_query = "SELECT COUNT(*) FROM (" . $query . ") AS total";
$total_stmt = $db->prepare($total_query);
$total_stmt->bind_param($types, ...$params);
$total_stmt->execute();
$total_stmt->bind_result($total_transacciones);
$total_stmt->fetch();
$total_stmt->close();

// Agregar orden y límite a la consulta principal
$query .= " ORDER BY t.fecha DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $transacciones_por_pagina;
$types .= 'ii';

$stmt = $db->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Obtener categorías y bancos para los filtros
$categorias_stmt = $db->prepare("SELECT ID_categoria, nombre FROM Categorias WHERE ID_usuario = ?");
$categorias_stmt->bind_param('i', $user_id);
$categorias_stmt->execute();
$categorias_result = $categorias_stmt->get_result();

$bancos_stmt = $db->prepare("SELECT ID_banco, banco FROM Cuentas_de_banco WHERE ID_usuario = ?");
$bancos_stmt->bind_param('i', $user_id);
$bancos_stmt->execute();
$bancos_result = $bancos_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Gestor de Presupuestos</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/styless.css">
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

    <main>
        <h2>Bienvenido, <?php echo htmlspecialchars($usuario); ?></h2>
        <div class="container-gestion">

            <h3>Resumen de Transacciones</h3>

            <!-- Formulario de Filtros -->
            <form method="GET" action="dashboard.php" class="filter-form">
                <label for="categoria">Categoría:</label>
                <select name="categoria" id="categoria">
                    <option value="">Todas</option>
                    <?php while ($cat = $categorias_result->fetch_assoc()): ?>
                        <option value="<?php echo $cat['ID_categoria']; ?>" <?php if ($categoria == $cat['ID_categoria']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cat['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label for="banco">Banco:</label>
                <select name="banco" id="banco">
                    <option value="">Todos</option>
                    <?php while ($ban = $bancos_result->fetch_assoc()): ?>
                        <option value="<?php echo $ban['ID_banco']; ?>" <?php if ($banco == $ban['ID_banco']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($ban['banco']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label for="fecha_inicio">Desde:</label>
                <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">

                <label for="fecha_fin">Hasta:</label>
                <input type="date" name="fecha_fin" id="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>">

                <button type="submit">Filtrar</button>
            </form>

            <div class="btn-banco-container">
                <a href="transacciones.php">
                    <button class="btn btn-banco">Añadir Transacción</button>
                </a>
            </div> 

            <?php if ($result->num_rows > 0): ?>
                <table>
                    <tr>
                        <th>Banco</th>
                        <th>Monto</th>
                        <th>Categoría</th>
                        <th>Fecha</th>
                        <th>Descripción</th>
                    </tr>

                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['banco']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($row['monto'], 2)); ?></td>
                            <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                            <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($row['fecha']))); ?></td>
                            <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                        </tr>
                    <?php endwhile; ?>

                </table>

                <!-- Paginación -->
                <?php
                $total_paginas = ceil($total_transacciones / $transacciones_por_pagina);
                ?>

                <div class="pagination">
                    <?php
                    // Construir la URL base para los enlaces de paginación
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

            <?php else: ?>
                <p>No hay transacciones registradas.</p>
            <?php endif; ?>

        </div>
    </main>

    <footer>
        <p>&copy; Gestor de Presupuestos 2024. Todos los derechos reservados.</p>
    </footer>

    <script src="JS/menu_lateral.js"></script>
    
</body>
</html>
