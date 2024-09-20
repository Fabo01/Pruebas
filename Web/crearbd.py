import sqlite3

# Conectar a la base de datos (se creará si no existe)
conn = sqlite3.connect('BD/GestorPresupuestos.db')

# Crear un cursor
cursor = conn.cursor()

# Crear tabla de cuentas bancarias
cursor.execute('''CREATE TABLE IF NOT EXISTS Cuentas_de_banco (
    ID_cuentabanco INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    banco TEXT NOT NULL
)''')

# Crear tabla de usuarios
cursor.execute('''CREATE TABLE IF NOT EXISTS Usuario (
    ID_usuario INTEGER PRIMARY KEY AUTOINCREMENT,
    ID_cuentabanco INTEGER NOT NULL,
    name TEXT NOT NULL,
    password TEXT NOT NULL,
    fecha_registro DATE NOT NULL,
    FOREIGN KEY (ID_cuentabanco) REFERENCES Cuentas_de_banco (ID_cuentabanco)
)''')

# Crear tabla de categorías
cursor.execute('''CREATE TABLE IF NOT EXISTS Categoria (
    ID_categoria INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    tipo TEXT NOT NULL
)''')

# Crear tabla de transacciones
cursor.execute('''CREATE TABLE IF NOT EXISTS Transacciones (
    ID_transaccion INTEGER PRIMARY KEY AUTOINCREMENT,
    ID_Cuentabanco INTEGER NOT NULL,
    ID_Categoria INTEGER NOT NULL,
    desc TEXT NOT NULL,
    fecha DATE NOT NULL,
    Monto REAL NOT NULL,
    FOREIGN KEY (ID_Cuentabanco) REFERENCES Cuentas_de_banco (ID_cuentabanco),
    FOREIGN KEY (ID_Categoria) REFERENCES Categoria (ID_categoria)
)''')

# Guardar cambios y cerrar la conexión
conn.commit()
conn.close()