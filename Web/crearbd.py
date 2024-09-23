import sqlite3

def crearbd():
    conn = sqlite3.connect('BD/GestorPresupuestos.db')
    cursor = conn.cursor()

    # Crear tabla de Cuentas de banco
    cursor.execute('''CREATE TABLE IF NOT EXISTS Cuentas_de_banco 
        (ID_cuentabanco INTEGER PRIMARY KEY AUTOINCREMENT,
        ID_usuario INTEGER,
        banco TEXT)''')

    # Crear tabla de Usuarios
    cursor.execute('''CREATE TABLE IF NOT EXISTS Usuario 
        (ID_usuario INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        email TEXT UNIQUE,
        password TEXT,
        token_recuperacion TEXT)''')

    # Crear tabla de Categorías
    cursor.execute('''CREATE TABLE IF NOT EXISTS Categoria 
        (ID_categoria INTEGER PRIMARY KEY AUTOINCREMENT,
        ID_cuentabanco INTEGER,
        nombre TEXT)''')

    # Crear tabla de Presupuestos
    cursor.execute('''CREATE TABLE IF NOT EXISTS Presupuestos 
        (ID_Presupuesto INTEGER PRIMARY KEY AUTOINCREMENT,
        ID_categoria INTEGER,
        gasto_mensual INTEGER,
        saldo_restante INTEGER,
        FOREIGN KEY (ID_categoria) REFERENCES Categoria(ID_categoria))''')

    # Crear tabla de Transacciones
    cursor.execute('''CREATE TABLE IF NOT EXISTS Transacciones 
        (ID_trans INTEGER PRIMARY KEY AUTOINCREMENT,
        ID_Cuentabanco INTEGER,
        ID_Categoria INTEGER,
        desc TEXT,
        fecha DATE,
        Monto INTEGER,
        FOREIGN KEY (ID_Cuentabanco) REFERENCES Cuentas_de_banco(ID_cuentabanco),
        FOREIGN KEY (ID_Categoria) REFERENCES Categoria(ID_categoria))''')
    
    # Crear tabla Articulos
    cursor.execute('''CREATE TABLE IF NOT EXISTS Articulos (
        ID_articulo INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo TEXT NOT NULL,
        contenido TEXT NOT NULL,
        autor TEXT NOT NULL,
        fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP)''')

    conn.commit()
    conn.close()

crearbd()