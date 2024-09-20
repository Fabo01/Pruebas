from flask import Flask, render_template, request, redirect, url_for
import sqlite3

app = Flask(__name__)

# Función para conectar a la base de datos
def get_db_connection():
    conn = sqlite3.connect('BD/GestorPresupuestos.db')
    conn.row_factory = sqlite3.Row  # Permite acceder a los resultados como diccionarios
    return conn

# Página principal para listar las transacciones
@app.route('/')
def index():
    conn = get_db_connection()
    transacciones = conn.execute('SELECT * FROM Transacciones').fetchall()
    conn.close()
    return render_template('index.html', transacciones=transacciones)

# Agregar una nueva cuenta bancaria
@app.route('/agregar_cuenta', methods=('GET', 'POST'))
def agregar_cuenta():
    if request.method == 'POST':
        name = request.form['name']
        banco = request.form['banco']

        conn = get_db_connection()
        conn.execute('INSERT INTO Cuentas_de_banco (name, banco) VALUES (?, ?)', (name, banco))
        conn.commit()
        conn.close()

        return redirect(url_for('index'))
    
    return render_template('agregar_cuenta.html')

# Agregar un nuevo usuario
@app.route('/agregar_usuario', methods=('GET', 'POST'))
def agregar_usuario():
    if request.method == 'POST':
        ID_cuentabanco = request.form['ID_cuentabanco']
        name = request.form['name']
        password = request.form['password']
        fecha_registro = request.form['fecha_registro']

        conn = get_db_connection()
        conn.execute('''INSERT INTO Usuario (ID_cuentabanco, name, password, fecha_registro) 
                        VALUES (?, ?, ?, ?)''', 
                     (ID_cuentabanco, name, password, fecha_registro))
        conn.commit()
        conn.close()

        return redirect(url_for('index'))

    conn = get_db_connection()
    cuentas = conn.execute('SELECT * FROM Cuentas_de_banco').fetchall()
    conn.close()
    return render_template('agregar_usuario.html', cuentas=cuentas)

# Agregar una nueva categoría
@app.route('/agregar_categoria', methods=('GET', 'POST'))
def agregar_categoria():
    if request.method == 'POST':
        nombre = request.form['nombre']
        tipo = request.form['tipo']

        conn = get_db_connection()
        conn.execute('INSERT INTO Categoria (nombre, tipo) VALUES (?, ?)', (nombre, tipo))
        conn.commit()
        conn.close()

        return redirect(url_for('index'))
    
    return render_template('agregar_categoria.html')

# Agregar una nueva transacción
@app.route('/agregar_transaccion', methods=('GET', 'POST'))
def agregar_transaccion():
    if request.method == 'POST':
        ID_Cuentabanco = request.form['ID_Cuentabanco']
        ID_Categoria = request.form['ID_Categoria']
        desc = request.form['desc']
        fecha = request.form['fecha']
        Monto = request.form['Monto']

        conn = get_db_connection()
        conn.execute('''INSERT INTO Transacciones (ID_Cuentabanco, ID_Categoria, desc, fecha, Monto) 
                        VALUES (?, ?, ?, ?, ?)''', 
                     (ID_Cuentabanco, ID_Categoria, desc, fecha, Monto))
        conn.commit()
        conn.close()

        return redirect(url_for('index'))

    conn = get_db_connection()
    cuentas = conn.execute('SELECT * FROM Cuentas_de_banco').fetchall()
    categorias = conn.execute('SELECT * FROM Categoria').fetchall()
    conn.close()
    return render_template('agregar_transaccion.html', cuentas=cuentas, categorias=categorias)

if __name__ == '__main__':
    app.run(debug=True)