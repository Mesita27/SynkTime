const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '..', '.env') });
const mysql = require('mysql2/promise');

class Database {
  constructor() {
    this.connection = null;
    this.config = {
      host: process.env.DB_HOST || 'localhost',
      user: process.env.DB_USER || 'root',
      password: process.env.DB_PASSWORD || '',
      database: process.env.DB_NAME || 'synktime',
      charset: 'utf8mb4'
    };
  }

  async connect() {
    try {
      this.connection = await mysql.createConnection(this.config);
      console.log('Database connected successfully');
      return this.connection;
    } catch (error) {
      console.error('Database connection error:', error);
      throw error;
    }
  }

  async query(sql, params = []) {
    try {
      if (!this.connection) {
        await this.connect();
      }
      
      const [rows, fields] = await this.connection.execute(sql, params);
      return rows;
    } catch (error) {
      console.error('Database query error:', error);
      console.error('SQL:', sql);
      console.error('Params:', params);
      throw error;
    }
  }

  async transaction(queries) {
    let connection = null;
    try {
      if (!this.connection) {
        await this.connect();
      }
      
      connection = this.connection;
      await connection.beginTransaction();
      
      const results = [];
      for (const { sql, params } of queries) {
        const [rows] = await connection.execute(sql, params || []);
        results.push(rows);
      }
      
      await connection.commit();
      return results;
    } catch (error) {
      if (connection) {
        await connection.rollback();
      }
      console.error('Transaction error:', error);
      throw error;
    }
  }

  async close() {
    if (this.connection) {
      await this.connection.end();
      this.connection = null;
    }
  }
}

// Use SQLite for development if MySQL is not available
let db;

if (process.env.USE_SQLITE === 'true') {
  console.log('Using SQLite for development');
  db = require('./database-sqlite');
} else {
  console.log('Using MySQL/MariaDB');
  db = new Database();
}

module.exports = db;