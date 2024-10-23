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

// Obtener ingresos y gastos por categoría
$stmt = $db->prepare("
    SELECT c.nombre AS categoria, SUM(t.monto) AS total, c.tipo
    FROM Transacciones t
    JOIN Categorias c ON t.ID_categoria = c.ID_categoria
    WHERE t.ID_usuario = ?
    GROUP BY c.ID_categoria, c.tipo
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$categorias = [];
$datos_por_categoria = [];

while ($row = $result->fetch_assoc()) {
    $categoria = $row['categoria'];
    $total = (float)$row['total'];
    $tipo = $row['tipo'];

    if (!in_array($categoria, $categorias)) {
        $categorias[] = $categoria;
    }

    if (!isset($datos_por_categoria[$categoria])) {
        $datos_por_categoria[$categoria] = ['ingreso' => 0, 'gasto' => 0];
    }

    if ($tipo === 'ingreso') {
        $datos_por_categoria[$categoria]['ingreso'] = $total;
    } else {
        $datos_por_categoria[$categoria]['gasto'] = $total;
    }
}

// Construir los arreglos de ingresos y gastos alineados con las categorías
$ingresos = [];
$gastos = [];

foreach ($categorias as $categoria) {
    $ingresos[] = $datos_por_categoria[$categoria]['ingreso'];
    $gastos[] = $datos_por_categoria[$categoria]['gasto'];
}

// Obtener ingresos y gastos por mes
$stmt = $db->prepare("
    SELECT DATE_FORMAT(t.fecha, '%Y-%m') AS mes, 
        SUM(CASE WHEN c.tipo = 'ingreso' THEN t.monto ELSE 0 END) AS total_ingresos,
        SUM(CASE WHEN c.tipo = 'gasto' THEN t.monto ELSE 0 END) AS total_gastos
    FROM Transacciones t
    JOIN Categorias c ON t.ID_categoria = c.ID_categoria
    WHERE t.ID_usuario = ?
    GROUP BY mes
    ORDER BY mes
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$meses = [];
$ingresos_por_mes = [];
$gastos_por_mes = [];

while ($row = $result->fetch_assoc()) {
    $meses[] = $row['mes'];
    $ingresos_por_mes[] = (float)$row['total_ingresos'];
    $gastos_por_mes[] = (float)$row['total_gastos'];
}

// Obtener gastos por categoría para el gráfico de pastel
$stmt = $db->prepare("
    SELECT c.nombre AS categoria, SUM(t.monto) AS total
    FROM Transacciones t
    JOIN Categorias c ON t.ID_categoria = c.ID_categoria
    WHERE t.ID_usuario = ? AND c.tipo = 'gasto'
    GROUP BY c.ID_categoria
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$categorias_gastos = [];
$totales_gastos = [];

while ($row = $result->fetch_assoc()) {
    $categorias_gastos[] = $row['categoria'];
    $totales_gastos[] = (float)$row['total'];
}

// Obtener balance acumulado por mes
$stmt = $db->prepare("
    SELECT DATE_FORMAT(t.fecha, '%Y-%m') AS mes,
        SUM(CASE WHEN c.tipo = 'ingreso' THEN t.monto ELSE -t.monto END) AS balance_mensual
    FROM Transacciones t
    JOIN Categorias c ON t.ID_categoria = c.ID_categoria
    WHERE t.ID_usuario = ?
    GROUP BY mes
    ORDER BY mes
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$meses_balance = [];
$balances_acumulados = [];
$balance_acumulado = 0;

while ($row = $result->fetch_assoc()) {
    $mes = $row['mes'];
    $balance_mensual = (float)$row['balance_mensual'];
    $balance_acumulado += $balance_mensual;
    $meses_balance[] = $mes;
    $balances_acumulados[] = $balance_acumulado;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen Financiero - Gestor de Presupuestos</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/stylesss.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <h2>Resumen Financiero de <?php echo htmlspecialchars($usuario); ?></h2>
        <div class="container-gestion">

            <h3>Ingresos y Gastos por Categoría</h3>
            <canvas id="categoriaChart"></canvas>

            <h3>Distribución de Gastos por Categoría</h3>
            <canvas id="gastosPieChart"></canvas>

            <h3>Ingresos y Gastos Mensuales</h3>
            <canvas id="mensualChart"></canvas>

            <h3>Balance Acumulado</h3>
            <canvas id="balanceChart"></canvas>

        </div>
    </main>

    <footer>
        <p>&copy; Gestor de Presupuestos 2024. Todos los derechos reservados.</p>
    </footer>

    <!-- Scripts para generar los gráficos -->
    <script src="JS/menu_lateral.js"></script>
    <script>
        // Gráfico de Ingresos y Gastos por Categoría
        const categoriaCtx = document.getElementById('categoriaChart').getContext('2d');
        const categoriaChart = new Chart(categoriaCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($categorias); ?>,
                datasets: [
                    {
                        label: 'Ingresos',
                        data: <?php echo json_encode($ingresos); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Gastos',
                        data: <?php echo json_encode($gastos); ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255,99,132,1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                scales: {
                    x: {
                        stacked: false
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de Distribución de Gastos por Categoría (Pastel)
        const gastosPieCtx = document.getElementById('gastosPieChart').getContext('2d');
        const gastosPieChart = new Chart(gastosPieCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($categorias_gastos); ?>,
                datasets: [
                    {
                        data: <?php echo json_encode($totales_gastos); ?>,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(255, 159, 64, 0.6)',
                            'rgba(255, 205, 86, 0.6)',
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(153, 102, 255, 0.6)',
                            'rgba(201, 203, 207, 0.6)'
                        ]
                    }
                ]
            },
            options: {
                responsive: true
            }
        });

        // Gráfico de Ingresos y Gastos Mensuales
        const mensualCtx = document.getElementById('mensualChart').getContext('2d');
        const mensualChart = new Chart(mensualCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($meses); ?>,
                datasets: [
                    {
                        label: 'Ingresos',
                        data: <?php echo json_encode($ingresos_por_mes); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        fill: false,
                        tension: 0.1
                    },
                    {
                        label: 'Gastos',
                        data: <?php echo json_encode($gastos_por_mes); ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255,99,132,1)',
                        fill: false,
                        tension: 0.1
                    }
                ]
            },
            options: {
                scales: {
                    x: {
                        type: 'category',
                        title: {
                            display: true,
                            text: 'Mes'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Monto'
                        }
                    }
                }
            }
        });

        // Gráfico de Balance Acumulado
        const balanceCtx = document.getElementById('balanceChart').getContext('2d');
        const balanceChart = new Chart(balanceCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($meses_balance); ?>,
                datasets: [
                    {
                        label: 'Balance Acumulado',
                        data: <?php echo json_encode($balances_acumulados); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        fill: false,
                        tension: 0.1
                    }
                ]
            },
            options: {
                scales: {
                    x: {
                        type: 'category',
                        title: {
                            display: true,
                            text: 'Mes'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Balance Acumulado'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
