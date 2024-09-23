from flask import Flask, render_template, request, redirect, url_for, session, flash, jsonify
import sqlite3


app = Flask(__name__)
app.secret_key = 'C0ntr4s3ñ4. d3- v10s'

def conectar_bd():
    return sqlite3.connect('BD/GestorPresupuestos.db')

# Seccion principal
@app.route('/')
def index():
    if 'user_id' not in session:
        return redirect(url_for('login'))

    conn = conectar_bd()
    cursor = conn.cursor()

    cursor.execute('SELECT * FROM Cuentas_de_banco WHERE ID_usuario = ?', (session['user_id'],))
    cuentas = cursor.fetchall()

    categorias = cursor.execute('''
        SELECT Categoria.ID_categoria, Categoria.nombre, Cuentas_de_banco.banco 
        FROM Categoria 
        JOIN Cuentas_de_banco ON Categoria.ID_cuentabanco = Cuentas_de_banco.ID_cuentabanco
        WHERE Cuentas_de_banco.ID_usuario = ?
    ''', (session['user_id'],)).fetchall()

    conn.close()
    return render_template('index.html', cuentas=cuentas, categorias=categorias)

# Seccion de articulos
@app.route('/inicio')
def inicio():
    return render_template('inicio.html')

# Restablecimiento de contraseña
@app.route('/recuperar_password', methods=['GET', 'POST'])
def recuperar_password():
    if request.method == 'POST':
        email = request.form['email']
        
        conn = conectar_bd()
        cursor = conn.cursor()
        
        cursor.execute('SELECT * FROM Usuario WHERE email = ?', (email,))
        user = cursor.fetchone()

        if user:
            return render_template('recuperar_password.html', email=email)
        else:
            flash('No se encontró ninguna cuenta con ese correo electrónico.')
            return redirect(url_for('recuperar_password'))

    return render_template('recuperar_password.html')

@app.route('/restablecer_password', methods=['POST'])
def restablecer_password():
    email = request.form['email']
    nueva_password = request.form['password']
    confirmar_password = request.form['confirm_password']

    if nueva_password != confirmar_password:
        flash('Las contraseñas no coinciden.')
        return redirect(url_for('recuperar_password'))

    conn = conectar_bd()
    cursor = conn.cursor()
    cursor.execute('UPDATE Usuario SET password = ? WHERE email = ?', (nueva_password, email))
    conn.commit()
    conn.close()

    flash('Tu contraseña ha sido actualizada. Ya puedes iniciar sesión.')
    return redirect(url_for('login'))

# Registro de Usuario
@app.route('/registro', methods=['GET', 'POST'])
def registro():
    if request.method == 'POST':
        nombre = request.form['name']
        email = request.form['email']
        password = request.form['password']

        conn = conectar_bd()
        cursor = conn.cursor()

        try:
            cursor.execute('INSERT INTO Usuario (name, email, password) VALUES (?, ?, ?)', (nombre, email, password))
            conn.commit()
            flash('Usuario registrado exitosamente. Inicia sesión.')
            return redirect(url_for('login'))
        except sqlite3.IntegrityError:
            flash('El correo electrónico ya está en uso.')
        finally:
            conn.close()

    return render_template('registro.html')

# Inicio de Sesion
@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        email = request.form['email']
        password = request.form['password']

        conn = conectar_bd()
        cursor = conn.cursor()
        cursor.execute('SELECT * FROM Usuario WHERE email = ? AND password = ?', (email, password))
        user = cursor.fetchone()
        conn.close()

        if user:
            session['user_id'] = user[0]
            session['user'] = user[1]
            flash('Inicio de sesión exitoso')
            return redirect(url_for('index'))
        else:
            flash('Correo o contraseña incorrectos.')

    return render_template('login.html')

# Añadir nuevo banco enlazado a Usuario
@app.route('/vincular_banco', methods=['GET', 'POST'])
def vincular_banco():
    if 'user_id' not in session:
        return redirect(url_for('login'))

    if request.method == 'POST':
        banco = request.form['banco']

        conn = conectar_bd()
        cursor = conn.cursor()
        cursor.execute('INSERT INTO Cuentas_de_banco (ID_usuario, banco) VALUES (?, ?)', (session['user_id'], banco))
        conn.commit()
        conn.close()

        flash('Cuenta de banco vinculada exitosamente.')
        return redirect(url_for('index'))

    return render_template('vincular_banco.html')

# Añadir nueva categoria enlazada a Banco
@app.route('/crear_categoria/<int:cuenta_id>', methods=['GET', 'POST'])
def crear_categoria(cuenta_id):
    if 'user_id' not in session:
        return redirect(url_for('login'))

    if request.method == 'POST':
        nombre_categoria = request.form['nombre_categoria']

        conn = conectar_bd()
        cursor = conn.cursor()
        cursor.execute('INSERT INTO Categoria (ID_cuentabanco, nombre) VALUES (?, ?)', (cuenta_id, nombre_categoria))
        conn.commit()
        conn.close()

        flash('Categoría creada exitosamente.')
        return redirect(url_for('index'))

    return render_template('crear_categoria.html', cuenta_id=cuenta_id)

# Añadir Presupuesto enlazado a Categoria
@app.route('/crear_presupuesto/<int:categoria_id>', methods=['GET', 'POST'])
def crear_presupuesto(categoria_id):
    if 'user_id' not in session:
        return redirect(url_for('login'))

    conn = conectar_bd()
    cursor = conn.cursor()

    if request.method == 'POST':
        monto = request.form['monto']
        presupuesto_existente = cursor.execute('SELECT * FROM Presupuestos WHERE ID_categoria = ?', (categoria_id,)).fetchone()

        if presupuesto_existente:
            cursor.execute('UPDATE Presupuestos SET gasto_mensual = ?, saldo_restante = ? WHERE ID_categoria = ?', 
                           (monto, monto, categoria_id))
            flash('Presupuesto actualizado exitosamente.')
        else:
            cursor.execute('INSERT INTO Presupuestos (ID_categoria, gasto_mensual, saldo_restante) VALUES (?, ?, ?)', 
                           (categoria_id, monto, monto))
            flash('Presupuesto creado exitosamente.')

        conn.commit()
        conn.close()
        return redirect(url_for('index'))

    return render_template('crear_presupuesto.html', categoria_id=categoria_id)

# Añadir Transaccion enlazado a Presupuesto
@app.route('/agregar_transaccion/<int:categoria_id>', methods=['GET', 'POST'])
def agregar_transaccion(categoria_id):
    if 'user_id' not in session:
        return redirect(url_for('login'))

    conn = conectar_bd()
    if request.method == 'POST':
        descripcion = request.form['descripcion']
        monto = int(request.form['monto'])
        fecha = request.form['fecha']
        
        presupuesto = conn.execute('SELECT * FROM Presupuestos WHERE ID_categoria = ?', (categoria_id,)).fetchone()
        saldo_restante = presupuesto[3] - monto  # saldo_restante es el cuarto elemento
        
        conn.execute('UPDATE Presupuestos SET saldo_restante = ? WHERE ID_categoria = ?', (saldo_restante, categoria_id))
        conn.execute('INSERT INTO Transacciones (ID_Cuentabanco, ID_Categoria, desc, fecha, Monto) VALUES (?, ?, ?, ?, ?)', 
                     (1, categoria_id, descripcion, fecha, monto))
        conn.commit()
        
        flash('Transacción agregada correctamente.')
        return redirect(url_for('ver_presupuesto', categoria_id=categoria_id))
    
    return render_template('agregar_transaccion.html', categoria_id=categoria_id)

# Visualizacion
@app.route('/ver_categorias/<int:banco_id>')
def ver_categorias(banco_id):
    if 'user_id' not in session:
        return redirect(url_for('login'))

    conn = conectar_bd()
    cursor = conn.cursor()

    banco = cursor.execute('SELECT * FROM Cuentas_de_banco WHERE ID_cuentabanco = ?', (banco_id,)).fetchone()
    categorias = cursor.execute('SELECT * FROM Categoria WHERE ID_cuentabanco = ?', (banco_id,)).fetchall()
    
    presupuestos = []
    for categoria in categorias:
        presupuesto = cursor.execute('SELECT * FROM Presupuestos WHERE ID_categoria = ?', (categoria[0],)).fetchall()
        presupuestos.append((categoria, presupuesto))

    conn.close()
    
    return render_template('ver_categorias.html', categorias=categorias, presupuestos=presupuestos, banco=banco)

@app.route('/ver_presupuesto/<int:categoria_id>')
def ver_presupuesto(categoria_id):
    if 'user_id' not in session:
        return redirect(url_for('login'))

    conn = conectar_bd()
    cursor = conn.cursor()

    banco = cursor.execute('SELECT * FROM Cuentas_de_banco WHERE ID_cuentabanco = ?', (categoria_id,)).fetchone()
    categoria = cursor.execute('SELECT * FROM Categoria WHERE ID_categoria = ?', (categoria_id,)).fetchone()
    presupuesto = cursor.execute('SELECT * FROM Presupuestos WHERE ID_categoria = ?', (categoria_id,)).fetchone()
    transacciones = cursor.execute('SELECT * FROM Transacciones WHERE ID_Categoria = ?', (categoria_id,)).fetchall()
    
    conn.close()
    
    return render_template('ver_presupuesto.html', presupuesto=presupuesto, transacciones=transacciones, categoria=categoria, banco=banco)

# Prueba
@app.route('/api/datos')
def obtener_datos():
    conn = conectar_bd()
    cursor = conn.cursor()
    presupuestos = cursor.execute('SELECT * FROM Presupuestos').fetchall()
    transacciones = cursor.execute('SELECT * FROM Transacciones').fetchall()
    conn.close()

    return jsonify({
        'presupuestos': [dict(p) for p in presupuestos],
        'transacciones': [dict(t) for t in transacciones]
    })

# Añadir articulo al Inicio
@app.route('/crear_articulo', methods=['GET', 'POST'])
def crear_articulo():
    if 'user_id' not in session:
        return redirect(url_for('login'))

    if request.method == 'POST':
        titulo = request.form['titulo']
        contenido = request.form['contenido']
        autor = session['user']

        conn = conectar_bd()
        cursor = conn.cursor()
        cursor.execute('INSERT INTO Articulos (titulo, contenido, autor) VALUES (?, ?, ?)', (titulo, contenido, autor))
        conn.commit()
        conn.close()

        flash('Artículo creado exitosamente.')
        return redirect(url_for('index'))

    return render_template('crear_articulo.html')

@app.route('/articulos')
def ver_articulos():
    conn = conectar_bd()
    cursor = conn.cursor()

    cursor.execute('SELECT * FROM Articulos ORDER BY fecha_creacion DESC')
    articulos = cursor.fetchall()

    conn.close()
    return render_template('ver_articulos.html', articulos=articulos)

# Cerrar Sesion
@app.route('/logout')
def logout():
    session.pop('user_id', None)
    flash('Has cerrado sesión exitosamente.')
    return redirect(url_for('login'))

if __name__ == '__main__':
    app.run(debug=True)