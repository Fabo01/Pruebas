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
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar el token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        $error = "Token CSRF inválido.";
    } else {
        // Recopilar y validar los datos del formulario
        $id_banco = isset($_POST['id_banco']) ? intval($_POST['id_banco']) : 0;
        $monto = isset($_POST['monto']) ? floatval($_POST['monto']) : 0;
        $id_categoria = isset($_POST['id_categoria']) ? intval($_POST['id_categoria']) : 0;
        $fecha = isset($_POST['fecha']) ? $_POST['fecha'] : '';
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';

        // Validar campos obligatorios
        if ($id_banco <= 0 || $monto == 0 || $id_categoria <= 0 || empty($fecha)) {
            $error = "Por favor, completa todos los campos obligatorios.";
        } else {
            // Verificar que el banco pertenece al usuario
            $stmt_banco = $db->prepare("SELECT COUNT(*) FROM Cuentas_de_banco WHERE ID_banco = ? AND ID_usuario = ?");
            $stmt_banco->bind_param('ii', $id_banco, $user_id);
            $stmt_banco->execute();
            $stmt_banco->bind_result($banco_count);
            $stmt_banco->fetch();
            $stmt_banco->close();

            if ($banco_count == 0) {
                $error = "El banco seleccionado no es válido.";
            } else {
                // Verificar que la categoría pertenece al usuario
                $stmt_categoria = $db->prepare("SELECT COUNT(*) FROM Categorias WHERE ID_categoria = ? AND ID_usuario = ?");
                $stmt_categoria->bind_param('ii', $id_categoria, $user_id);
                $stmt_categoria->execute();
                $stmt_categoria->bind_result($categoria_count);
                $stmt_categoria->fetch();
                $stmt_categoria->close();

                if ($categoria_count == 0) {
                    $error = "La categoría seleccionada no es válida.";
                } else {
                    // Insertar la transacción en la base de datos
                    $stmt = $db->prepare("INSERT INTO Transacciones (ID_usuario, ID_banco, monto, ID_categoria, fecha, descripcion) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param('iidiss', $user_id, $id_banco, $monto, $id_categoria, $fecha, $descripcion);

                    if ($stmt->execute()) {
                        $mensaje = "Transacción registrada exitosamente.";
                        // Redirigir o limpiar los campos del formulario si es necesario
                        header('Location: dashboard.php?mensaje=transaccion_creada');
                        exit();
                    } else {
                        $error = "Error al registrar la transacción.";
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// Obtener las cuentas bancarias del usuario
$stmt_bancos = $db->prepare("SELECT ID_banco, banco, nombre FROM Cuentas_de_banco WHERE ID_usuario = ?");
$stmt_bancos->bind_param('i', $user_id);
$stmt_bancos->execute();
$result_bancos = $stmt_bancos->get_result();
$bancos = $result_bancos->fetch_all(MYSQLI_ASSOC);
$stmt_bancos->close();

// Obtener las categorías del usuario
$stmt_categorias = $db->prepare("SELECT ID_categoria, nombre FROM Categorias WHERE ID_usuario = ?");
$stmt_categorias->bind_param('i', $user_id);
$stmt_categorias->execute();
$result_categorias = $stmt_categorias->get_result();
$categorias = $result_categorias->fetch_all(MYSQLI_ASSOC);
$stmt_categorias->close();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Transacción</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/stylesss.css">
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
        <h2>Crear Transacción</h2>

        <!-- Mostrar mensajes de éxito o error -->
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="transacciones.php" method="POST" class="form-transaccion">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group">
                <label for="id_banco">Banco:</label>
                <select name="id_banco" id="id_banco" required>
                    <option value="">Seleccione un banco</option>
                    <?php foreach ($bancos as $banco): ?>
                        <option value="<?php echo $banco['ID_banco']; ?>">
                            <?php echo htmlspecialchars($banco['banco'] . ' - ' . $banco['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="monto">Monto:</label>
                <input type="number" step="0.01" name="monto" id="monto" placeholder="Monto de la transacción" required>
            </div>

            <div class="form-group">
                <label for="id_categoria">Categoría:</label>
                <select name="id_categoria" id="id_categoria" required>
                    <option value="">Seleccione una categoría</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo $categoria['ID_categoria']; ?>">
                            <?php echo htmlspecialchars($categoria['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="fecha">Fecha:</label>
                <input type="date" name="fecha" id="fecha" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción:</label>
                <textarea name="descripcion" id="descripcion" rows="4" placeholder="Descripción de la transacción (opcional)"></textarea>
            </div>

            <div class="button-group">
                <button type="submit">Guardar Transacción</button>
            </div>
        </form>
    </main>

    <footer>
        <p>&copy; Gestor de Presupuestos 2024. Todos los derechos reservados.</p>
    </footer>

    <script src="menu_lateral.js"></script>
</body>
</html>
