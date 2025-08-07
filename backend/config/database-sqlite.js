const Database = require('better-sqlite3');
const path = require('path');

class SQLiteDatabase {
  constructor() {
    this.db = null;
    this.dbPath = path.join(__dirname, '..', 'synktime.db');
  }

  connect() {
    try {
      this.db = new Database(this.dbPath);
      console.log('SQLite database connected successfully');
      this.initializeTables();
      return this.db;
    } catch (error) {
      console.error('SQLite connection error:', error);
      throw error;
    }
  }

  initializeTables() {
    // Create tables based on our schema
    const createTables = `
      CREATE TABLE IF NOT EXISTS EMPRESA (
        ID_EMPRESA INTEGER PRIMARY KEY AUTOINCREMENT,
        NOMBRE TEXT NOT NULL,
        RUT TEXT UNIQUE,
        DIRECCION TEXT,
        TELEFONO TEXT,
        EMAIL TEXT,
        ESTADO TEXT DEFAULT 'A',
        CREATED_AT TEXT DEFAULT CURRENT_TIMESTAMP,
        UPDATED_AT TEXT DEFAULT CURRENT_TIMESTAMP
      );

      CREATE TABLE IF NOT EXISTS SEDE (
        ID_SEDE INTEGER PRIMARY KEY AUTOINCREMENT,
        ID_EMPRESA INTEGER NOT NULL,
        NOMBRE TEXT NOT NULL,
        DIRECCION TEXT,
        TELEFONO TEXT,
        ESTADO TEXT DEFAULT 'A',
        CREATED_AT TEXT DEFAULT CURRENT_TIMESTAMP,
        UPDATED_AT TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ID_EMPRESA) REFERENCES EMPRESA(ID_EMPRESA)
      );

      CREATE TABLE IF NOT EXISTS ESTABLECIMIENTO (
        ID_ESTABLECIMIENTO INTEGER PRIMARY KEY AUTOINCREMENT,
        ID_SEDE INTEGER NOT NULL,
        NOMBRE TEXT NOT NULL,
        DIRECCION TEXT,
        TELEFONO TEXT,
        ESTADO TEXT DEFAULT 'A',
        CREATED_AT TEXT DEFAULT CURRENT_TIMESTAMP,
        UPDATED_AT TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ID_SEDE) REFERENCES SEDE(ID_SEDE)
      );

      CREATE TABLE IF NOT EXISTS EMPLEADO (
        ID_EMPLEADO INTEGER PRIMARY KEY AUTOINCREMENT,
        ID_ESTABLECIMIENTO INTEGER NOT NULL,
        NOMBRE TEXT NOT NULL,
        APELLIDO TEXT NOT NULL,
        DNI TEXT UNIQUE NOT NULL,
        CORREO TEXT,
        TELEFONO TEXT,
        FECHA_INGRESO TEXT,
        ESTADO TEXT DEFAULT 'A',
        ACTIVO TEXT DEFAULT 'S',
        CREATED_AT TEXT DEFAULT CURRENT_TIMESTAMP,
        UPDATED_AT TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ID_ESTABLECIMIENTO) REFERENCES ESTABLECIMIENTO(ID_ESTABLECIMIENTO)
      );

      CREATE TABLE IF NOT EXISTS usuario (
        ID_USUARIO INTEGER PRIMARY KEY AUTOINCREMENT,
        USERNAME TEXT UNIQUE NOT NULL,
        CONTRASENA TEXT NOT NULL,
        NOMBRE_COMPLETO TEXT,
        EMAIL TEXT,
        ROL TEXT DEFAULT 'ASISTENCIA',
        ID_EMPRESA INTEGER NOT NULL,
        ESTADO TEXT DEFAULT 'A',
        ACTIVO INTEGER DEFAULT 1,
        CREATED_AT TEXT DEFAULT CURRENT_TIMESTAMP,
        UPDATED_AT TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ID_EMPRESA) REFERENCES EMPRESA(ID_EMPRESA)
      );

      CREATE TABLE IF NOT EXISTS LOG (
        ID_LOG INTEGER PRIMARY KEY AUTOINCREMENT,
        ID_USUARIO INTEGER,
        ACCION TEXT NOT NULL,
        DETALLE TEXT,
        FECHA_HORA TEXT DEFAULT CURRENT_TIMESTAMP,
        IP_ADDRESS TEXT,
        USER_AGENT TEXT,
        FOREIGN KEY (ID_USUARIO) REFERENCES usuario(ID_USUARIO)
      );

      CREATE TABLE IF NOT EXISTS asistencias (
        ID_ASISTENCIA INTEGER PRIMARY KEY AUTOINCREMENT,
        ID_EMPLEADO INTEGER NOT NULL,
        FECHA TEXT NOT NULL,
        HORA_ENTRADA TEXT,
        HORA_SALIDA TEXT,
        TIPO TEXT NOT NULL,
        VERIFICATION_METHOD TEXT DEFAULT 'traditional',
        OBSERVACIONES TEXT,
        CREATED_AT TEXT DEFAULT CURRENT_TIMESTAMP,
        UPDATED_AT TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO)
      );

      CREATE TABLE IF NOT EXISTS biometric_data (
        ID INTEGER PRIMARY KEY AUTOINCREMENT,
        ID_EMPLEADO INTEGER NOT NULL,
        BIOMETRIC_TYPE TEXT NOT NULL,
        FINGER_TYPE TEXT,
        BIOMETRIC_DATA TEXT,
        CREATED_AT TEXT DEFAULT CURRENT_TIMESTAMP,
        UPDATED_AT TEXT DEFAULT CURRENT_TIMESTAMP,
        ACTIVO INTEGER DEFAULT 1,
        FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO)
      );

      CREATE TABLE IF NOT EXISTS biometric_logs (
        ID INTEGER PRIMARY KEY AUTOINCREMENT,
        ID_EMPLEADO INTEGER NOT NULL,
        VERIFICATION_METHOD TEXT NOT NULL,
        VERIFICATION_SUCCESS INTEGER DEFAULT 0,
        VERIFICATION_SCORE REAL,
        FECHA TEXT NOT NULL,
        HORA TEXT NOT NULL,
        DEVICE_INFO TEXT,
        ERROR_MESSAGE TEXT,
        CREATED_AT TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO)
      );

      CREATE TABLE IF NOT EXISTS horarios (
        ID_HORARIO INTEGER PRIMARY KEY AUTOINCREMENT,
        NOMBRE TEXT NOT NULL,
        HORA_ENTRADA TEXT NOT NULL,
        HORA_SALIDA TEXT NOT NULL,
        DIAS_SEMANA TEXT,
        TOLERANCIA_ENTRADA INTEGER DEFAULT 15,
        TOLERANCIA_SALIDA INTEGER DEFAULT 15,
        ESTADO TEXT DEFAULT 'A',
        CREATED_AT TEXT DEFAULT CURRENT_TIMESTAMP,
        UPDATED_AT TEXT DEFAULT CURRENT_TIMESTAMP
      );

      CREATE TABLE IF NOT EXISTS empleado_horarios (
        ID INTEGER PRIMARY KEY AUTOINCREMENT,
        ID_EMPLEADO INTEGER NOT NULL,
        ID_HORARIO INTEGER NOT NULL,
        FECHA_INICIO TEXT NOT NULL,
        FECHA_FIN TEXT,
        ACTIVO INTEGER DEFAULT 1,
        CREATED_AT TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO),
        FOREIGN KEY (ID_HORARIO) REFERENCES horarios(ID_HORARIO)
      );
    `;

    // Execute table creation
    this.db.exec(createTables);

    // Insert sample data if tables are empty
    this.insertSampleData();
  }

  insertSampleData() {
    const checkEmpresa = this.db.prepare('SELECT COUNT(*) as count FROM EMPRESA').get();
    
    if (checkEmpresa.count === 0) {
      // Insert sample data
      const insertEmpresa = this.db.prepare('INSERT INTO EMPRESA (NOMBRE, RUT, EMAIL, ESTADO) VALUES (?, ?, ?, ?)');
      insertEmpresa.run('Empresa Demo', '12345678-9', 'admin@empresademo.com', 'A');

      const insertSede = this.db.prepare('INSERT INTO SEDE (ID_EMPRESA, NOMBRE, ESTADO) VALUES (?, ?, ?)');
      insertSede.run(1, 'Sede Principal', 'A');

      const insertEstablecimiento = this.db.prepare('INSERT INTO ESTABLECIMIENTO (ID_SEDE, NOMBRE, ESTADO) VALUES (?, ?, ?)');
      insertEstablecimiento.run(1, 'Oficina Central', 'A');

      // Create default admin user (password: admin123)
      const bcrypt = require('bcryptjs');
      const hashedPassword = bcrypt.hashSync('admin123', 12);
      const insertUser = this.db.prepare('INSERT INTO usuario (USERNAME, CONTRASENA, NOMBRE_COMPLETO, EMAIL, ROL, ID_EMPRESA, ESTADO) VALUES (?, ?, ?, ?, ?, ?, ?)');
      insertUser.run('admin', hashedPassword, 'Administrador', 'admin@synktime.com', 'ADMIN', 1, 'A');

      // Insert sample employee
      const insertEmpleado = this.db.prepare('INSERT INTO EMPLEADO (ID_ESTABLECIMIENTO, NOMBRE, APELLIDO, DNI, CORREO, FECHA_INGRESO, ESTADO, ACTIVO) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
      insertEmpleado.run(1, 'Juan', 'Pérez', '12345678', 'juan.perez@empresa.com', new Date().toISOString().split('T')[0], 'A', 'S');

      // Create sample schedule
      const insertHorario = this.db.prepare('INSERT INTO horarios (NOMBRE, HORA_ENTRADA, HORA_SALIDA, DIAS_SEMANA) VALUES (?, ?, ?, ?)');
      insertHorario.run('Horario Estándar', '08:00:00', '17:00:00', '[1,2,3,4,5]');

      console.log('Sample data inserted successfully');
    }
  }

  async query(sql, params = []) {
    try {
      if (!this.db) {
        this.connect();
      }
      
      if (sql.trim().toUpperCase().startsWith('SELECT')) {
        const stmt = this.db.prepare(sql);
        return stmt.all(params);
      } else if (sql.trim().toUpperCase().startsWith('INSERT')) {
        const stmt = this.db.prepare(sql);
        const result = stmt.run(params);
        return { insertId: result.lastInsertRowid, affectedRows: result.changes };
      } else {
        const stmt = this.db.prepare(sql);
        const result = stmt.run(params);
        return { affectedRows: result.changes };
      }
    } catch (error) {
      console.error('Database query error:', error);
      console.error('SQL:', sql);
      console.error('Params:', params);
      throw error;
    }
  }

  async transaction(queries) {
    try {
      if (!this.db) {
        this.connect();
      }
      
      const transaction = this.db.transaction(() => {
        const results = [];
        for (const { sql, params } of queries) {
          const stmt = this.db.prepare(sql);
          const result = stmt.run(params || []);
          results.push(result);
        }
        return results;
      });
      
      return transaction();
    } catch (error) {
      console.error('Transaction error:', error);
      throw error;
    }
  }

  async close() {
    if (this.db) {
      this.db.close();
      this.db = null;
    }
  }
}

// Create singleton instance
const db = new SQLiteDatabase();

module.exports = db;