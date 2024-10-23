<?php
// Iniciamos la sesión si es necesario
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Información y Ayuda</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>

<header class="navbar">
<?php if (isset($_SESSION['user_id'])): ?>
    <button id="menu-btn" class="menu-btn">&#9776;</button>
    <?php endif; ?>
    <div class="logo">Gestor de Presupuestos</div>
    <nav class="nav">
        <ul>
            <!-- Verificamos si el usuario ha iniciado sesión -->
            <?php if (isset($_SESSION['user_id'])): ?>
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
            <?php else: ?>
                <li><a href="index.php">Iniciar Sesión</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<?php if (isset($_SESSION['user_id'])): ?>
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
<?php endif; ?>

<main>
    <h1>Información y Ayuda</h1>

    <!-- Barra de navegación para las secciones -->
    <div class="tab-navigation">
        <button class="tab-button active" onclick="openTab(event, 'info')">Acerca de</button>
        <button class="tab-button" onclick="openTab(event, 'guia')">Guía de Uso</button>
        <button class="tab-button" onclick="openTab(event, 'consejos')">Consejos Financieros</button>
    </div>

    <!-- Sección Acerca de -->
    <div id="info" class="tab-content" style="display: block;">
        <h2>Acerca de</h2>

        <p>La aplicación de gestión de presupuestos mensuales tiene como propósito ayudar a los usuarios a gestionar de manera eficiente sus finanzas personales.
        Permite realizar un seguimiento detallado de ingresos y gastos, asociando cada transacción con categorías específicas (como ocio, alimentación, etc.) que pueden tener presupuestos mensuales asignados.
        La aplicación ofrece información sobre cuánto se ha gastado o ahorrado en cada categoría, alertando al usuario sobre posibles excedentes o déficits,
        y brindando recomendaciones para mejorar la administración del dinero.
        Además, la aplicación incluye funciones como la creación y gestión de cuentas bancarias, la posibilidad de agregar, editar y eliminar transacciones,
        y la opción de revisar logros financieros y comentarios. Todo esto se presenta en una interfaz sencilla, con un diseño limpio pero funcional,
        que facilita la navegación y la comprensión de la situación financiera del usuario.</p>

        <h3>1.- Propósito Principal</h3>
        <p>El objetivo fundamental de la aplicación es ofrecer una herramienta intuitiva y eficiente que permita a los usuarios gestionar sus finanzas personales de manera efectiva.
        A través de la aplicación, los usuarios pueden visualizar y controlar sus ingresos, gastos y presupuestos mensuales, promoviendo una mejor planificación y toma de decisiones financieras.
        La intención es reducir el estrés asociado con la gestión del dinero y facilitar el logro de objetivos financieros a corto y largo plazo.</p>

        <h3>2.- Características Clave</h3>
        <p>Seguimiento de Ingresos y Gastos: La aplicación permite registrar cada transacción (ingresos o gastos) y clasificarlas en categorías personalizadas,
        como alimentación, transporte, entretenimiento, entre otras.
        Esto ofrece una visión clara de dónde se está gastando el dinero, lo que permite identificar áreas de oportunidad para ahorrar o redistribuir el presupuesto.</p>

        <p>Categorías con Presupuestos Asignados: A través de la funcionalidad de presupuestos por categoría,
        los usuarios pueden establecer límites mensuales de gasto. La aplicación monitorea cuánto se ha gastado en cada categoría y alerta al usuario si se está acercando o superando el límite establecido.
        Esto fomenta una mayor conciencia sobre los hábitos de consumo.</p>

        <p>Visión Global de las Finanzas: La aplicación ofrece una vista unificada del estado financiero del usuario, integrando las cuentas bancarias registradas con los presupuestos y las transacciones,
        lo que permite ver claramente el balance entre ingresos y gastos. De esta manera, el usuario puede detectar con facilidad si está alcanzando sus objetivos financieros o si necesita hacer ajustes.</p>

        <p>Alertas y Consejos Financieros: Si se detecta que una categoría de presupuesto está en déficit o si hay un sobrante significativo,
        la aplicación ofrece consejos automáticos que ayudan al usuario a tomar mejores decisiones, como redistribuir el dinero sobrante o ajustar gastos futuros.</p>

        <p>Flexibilidad en la Gestión de Bancos: La aplicación facilita la gestión de múltiples cuentas bancarias, permitiendo al usuario agregar, editar y eliminar cuentas según sea necesario.
        Esta funcionalidad mejora la transparencia al ofrecer una visión consolidada de todos los fondos del usuario.</p>

        <p>Interfaz y Usabilidad: El diseño de la interfaz está optimizado para ser lo más intuitivo posible, eliminando la sobrecarga visual y permitiendo al usuario concentrarse en las acciones importantes.
        Cada sección está claramente delimitada, y los usuarios pueden navegar fácilmente por las opciones de transacciones,
        presupuestos, cuentas y reportes. Esto es especialmente útil para usuarios que no tienen conocimientos avanzados en finanzas o tecnología.</p>

        <h3>3.- Valor para el Usuario Objetivo</h3>
        <p>El usuario objetivo de esta aplicación es una persona que desea tener un control detallado sobre sus finanzas, ya sea porque está buscando ahorrar,
        mejorar sus hábitos de gasto o simplemente obtener una visión más clara de su situación financiera actual.
        La aplicación es útil tanto para aquellos que tienen ingresos fijos, como para quienes tienen ingresos variables, debido a la flexibilidad que ofrece en la gestión de cuentas y categorías.</p>

        <p>El valor clave que la aplicación aporta a los usuarios incluye:</p>

        <p>Empoderamiento Financiero: Los usuarios toman el control de sus decisiones financieras al tener acceso en tiempo real a información precisa y relevante sobre su situación económica.
        Reducción de Estrés: Con la posibilidad de planificar presupuestos y recibir alertas cuando se exceden los límites,
        los usuarios pueden evitar sorpresas a fin de mes y estar mejor preparados para gastos imprevistos.</p>

        <p>Mejor Toma de Decisiones: Los informes y recomendaciones de la aplicación facilitan que los usuarios ajusten su comportamiento financiero antes de que se enfrenten a problemas más serios,
        como deudas o la falta de ahorros.</p>

        <p>Fomento del Ahorro: Al visualizar el dinero sobrante en determinadas categorías o en el presupuesto general, 
        la aplicación motiva a los usuarios a ahorrar más, ya sea para objetivos específicos o para emergencias.</p>

        <h3>4.- Cómo Resuelve Problemas Comunes</h3>
        <p>Falta de Visibilidad Financiera: Muchas personas carecen de una visión clara de su situación financiera general, 
        ya sea porque manejan múltiples cuentas o porque no realizan un seguimiento continuo de sus gastos.
        Esta aplicación centraliza toda esa información, brindando una panorámica completa y actualizada de sus finanzas.</p>

        <p>Dificultad para Seguir un Presupuesto: Muchas veces, establecer un presupuesto es fácil, pero seguirlo es difícil. 
        La aplicación no solo permite definir un presupuesto, sino que también hace seguimiento de los gastos en tiempo real, alertando al usuario antes de que se desvíe de su planificación.</p>

        <p>Falta de Disciplina Financiera: La aplicación refuerza hábitos financieros saludables al proporcionar recordatorios y sugerencias que ayudan a los usuarios a mantener la disciplina necesaria 
        para gestionar mejor sus finanzas.</p>

        <h3>5.- Escalabilidad y Personalización</h3>
        <p>Personalización de Categorías: La aplicación permite que los usuarios creen sus propias categorías y personalicen los presupuestos de acuerdo con sus necesidades particulares. 
        Esta flexibilidad asegura que la herramienta sea adaptable a diferentes perfiles de usuarios.</p>

        <p>Escalabilidad a Futuro: Dado que la estructura de la aplicación permite la gestión de múltiples cuentas, categorías y tipos de transacciones, 
        puede seguir siendo útil a medida que las finanzas de los usuarios se vuelvan más complejas. 
        Por ejemplo, un usuario que al principio solo maneja una cuenta bancaria puede en el futuro agregar más y seguir utilizando la aplicación sin problemas.</p>


    </div>

    <div id="guia" class="tab-content" style="display: none;">
        <h2>Guía de Uso</h2>
        <p>Para comenzar a utilizar el Gestor de Presupuestos, sigue estos pasos:</p>
        <ol>
            <li><strong>Registro:</strong> Crea una cuenta proporcionando tu información básica.</li>
            <li><strong>Agregar Cuentas Bancarias:</strong> Añade tus cuentas bancarias para comenzar a gestionar tus finanzas.</li>
            <li><strong>Crear Categorías:</strong> Organiza tus gastos e ingresos en categorías personalizadas.</li>
            <li><strong>Registrar Transacciones:</strong> Añade transacciones para llevar un seguimiento detallado de tus movimientos financieros.</li>
            <li><strong>Revisar Estadísticas:</strong> Utiliza nuestras herramientas de estadísticas para analizar tu situación financiera.</li>
        </ol>
    </div>

    <!-- Sección Consejos Financieros -->
    <div id="consejos" class="tab-content" style="display: none;">
        <h2>Consejos Financieros</h2>
        <ul>
            <li><strong>Establece un Presupuesto:</strong> Define límites claros para tus gastos y ajústate a ellos.</li>
            <li><strong>Ahorra Regularmente:</strong> Destina una parte de tus ingresos al ahorro cada mes.</li>
            <li><strong>Evita Deudas Innecesarias:</strong> Maneja tus tarjetas de crédito con responsabilidad.</li>
            <li><strong>Informa tus Decisiones:</strong> Investiga antes de realizar inversiones o grandes compras.</li>
            <li><strong>Revisa tus Finanzas:</strong> Regularmente analiza tus ingresos y gastos para identificar áreas de mejora.</li>
        </ul>
    </div>
</main>

<footer>
    <p>&copy; Gestor de Presupuestos 2024. Todos los derechos reservados.</p>
</footer>

<script src="JS/menu_lateral.js"></script>
<script src="JS/informacion.js"></script>
</body>
</html>
