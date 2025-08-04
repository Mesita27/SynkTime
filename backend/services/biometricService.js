const database = require('../config/database');

class BiometricService {
  /**
   * Get biometric enrollment summary for an employee
   */
  async getEmployeeBiometricSummary(employeeId) {
    try {
      // Get employee info
      const employee = await database.query(
        'SELECT ID_EMPLEADO, NOMBRE, APELLIDO FROM empleados WHERE ID_EMPLEADO = ? AND ACTIVO = 1',
        [employeeId]
      );

      if (!employee.length) {
        throw new Error('Empleado no encontrado');
      }

      // Get enrolled biometric data
      const biometricData = await database.query(
        `SELECT BIOMETRIC_TYPE, FINGER_TYPE, CREATED_AT, UPDATED_AT 
         FROM biometric_data 
         WHERE ID_EMPLEADO = ? AND ACTIVO = 1 
         ORDER BY BIOMETRIC_TYPE, FINGER_TYPE`,
        [employeeId]
      );

      // Count enrollments by type
      const fingerprintCount = biometricData.filter(d => d.BIOMETRIC_TYPE === 'fingerprint').length;
      const facialCount = biometricData.filter(d => d.BIOMETRIC_TYPE === 'facial').length;

      return {
        employee: employee[0],
        enrollments: {
          fingerprint: {
            count: fingerprintCount,
            enrolled_fingers: biometricData
              .filter(d => d.BIOMETRIC_TYPE === 'fingerprint')
              .map(d => d.FINGER_TYPE)
          },
          facial: {
            count: facialCount,
            enrolled: facialCount > 0
          }
        },
        total_enrollments: biometricData.length,
        last_updated: biometricData.length > 0 
          ? Math.max(...biometricData.map(d => new Date(d.UPDATED_AT).getTime()))
          : null
      };
    } catch (error) {
      console.error('Error getting employee biometric summary:', error);
      throw error;
    }
  }

  /**
   * Enroll fingerprint for employee
   */
  async enrollFingerprint(employeeId, fingerType, fingerprintData) {
    try {
      // Validate employee exists
      const employee = await database.query(
        'SELECT ID_EMPLEADO, NOMBRE, APELLIDO FROM empleados WHERE ID_EMPLEADO = ? AND ACTIVO = 1',
        [employeeId]
      );

      if (!employee.length) {
        throw new Error('Empleado no encontrado o inactivo');
      }

      return await database.transaction(async (connection) => {
        // Create tables if they don't exist
        await this.createBiometricTables(connection);

        // Check if fingerprint already exists
        const [existing] = await connection.execute(
          'SELECT ID FROM biometric_data WHERE ID_EMPLEADO = ? AND FINGER_TYPE = ? AND ACTIVO = 1',
          [employeeId, fingerType]
        );

        let action;
        if (existing.length > 0) {
          // Update existing fingerprint
          await connection.execute(
            'UPDATE biometric_data SET BIOMETRIC_DATA = ?, UPDATED_AT = NOW() WHERE ID = ?',
            [fingerprintData, existing[0].ID]
          );
          action = 'actualizada';
        } else {
          // Insert new fingerprint
          await connection.execute(
            `INSERT INTO biometric_data (ID_EMPLEADO, BIOMETRIC_TYPE, FINGER_TYPE, BIOMETRIC_DATA, CREATED_AT)
             VALUES (?, 'fingerprint', ?, ?, NOW())`,
            [employeeId, fingerType, fingerprintData]
          );
          action = 'registrada';
        }

        // Log enrollment activity
        await connection.execute(
          `INSERT INTO biometric_logs (ID_EMPLEADO, VERIFICATION_METHOD, VERIFICATION_SUCCESS, FECHA, HORA, CREATED_AT)
           VALUES (?, 'fingerprint', 1, CURDATE(), CURTIME(), NOW())`,
          [employeeId]
        );

        return {
          message: `Huella ${action} correctamente`,
          employee: `${employee[0].NOMBRE} ${employee[0].APELLIDO}`,
          finger_type: fingerType,
          action
        };
      });
    } catch (error) {
      console.error('Error enrolling fingerprint:', error);
      throw error;
    }
  }

  /**
   * Enroll facial biometric for employee
   */
  async enrollFacial(employeeId, facialData) {
    try {
      // Validate employee exists
      const employee = await database.query(
        'SELECT ID_EMPLEADO, NOMBRE, APELLIDO FROM empleados WHERE ID_EMPLEADO = ? AND ACTIVO = 1',
        [employeeId]
      );

      if (!employee.length) {
        throw new Error('Empleado no encontrado o inactivo');
      }

      return await database.transaction(async (connection) => {
        // Create tables if they don't exist
        await this.createBiometricTables(connection);

        // Check if facial data already exists
        const [existing] = await connection.execute(
          'SELECT ID FROM biometric_data WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = "facial" AND ACTIVO = 1',
          [employeeId]
        );

        let action;
        if (existing.length > 0) {
          // Update existing facial data
          await connection.execute(
            'UPDATE biometric_data SET BIOMETRIC_DATA = ?, UPDATED_AT = NOW() WHERE ID = ?',
            [facialData, existing[0].ID]
          );
          action = 'actualizado';
        } else {
          // Insert new facial data
          await connection.execute(
            `INSERT INTO biometric_data (ID_EMPLEADO, BIOMETRIC_TYPE, FINGER_TYPE, BIOMETRIC_DATA, CREATED_AT)
             VALUES (?, 'facial', NULL, ?, NOW())`,
            [employeeId, facialData]
          );
          action = 'registrado';
        }

        // Log enrollment activity
        await connection.execute(
          `INSERT INTO biometric_logs (ID_EMPLEADO, VERIFICATION_METHOD, VERIFICATION_SUCCESS, FECHA, HORA, CREATED_AT)
           VALUES (?, 'facial', 1, CURDATE(), CURTIME(), NOW())`,
          [employeeId]
        );

        return {
          message: `Reconocimiento facial ${action} correctamente`,
          employee: `${employee[0].NOMBRE} ${employee[0].APELLIDO}`,
          action
        };
      });
    } catch (error) {
      console.error('Error enrolling facial:', error);
      throw error;
    }
  }

  /**
   * Verify biometric data (simulated - would integrate with actual biometric libraries)
   */
  async verifyBiometric(employeeId, biometricType, biometricData) {
    try {
      // Get stored biometric data for comparison
      const query = biometricType === 'fingerprint' 
        ? 'SELECT BIOMETRIC_DATA, FINGER_TYPE FROM biometric_data WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = ? AND ACTIVO = 1'
        : 'SELECT BIOMETRIC_DATA FROM biometric_data WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = ? AND ACTIVO = 1';

      const storedData = await database.query(query, [employeeId, biometricType]);

      if (!storedData.length) {
        return {
          success: false,
          confidence: 0,
          message: 'No hay datos biométricos registrados para este empleado'
        };
      }

      // Simulated biometric comparison (in production, use actual biometric libraries)
      const confidence = this.simulateBiometricComparison(biometricData, storedData[0].BIOMETRIC_DATA);
      const threshold = biometricType === 'facial' ? 0.8 : 0.7;
      const success = confidence >= threshold;

      // Log verification attempt
      await database.query(
        `INSERT INTO biometric_logs (ID_EMPLEADO, VERIFICATION_METHOD, VERIFICATION_SUCCESS, FECHA, HORA, CREATED_AT)
         VALUES (?, ?, ?, CURDATE(), CURTIME(), NOW())`,
        [employeeId, biometricType, success ? 1 : 0]
      );

      return {
        success,
        confidence,
        message: success ? 'Verificación biométrica exitosa' : 'Verificación biométrica fallida'
      };
    } catch (error) {
      console.error('Error verifying biometric:', error);
      throw error;
    }
  }

  /**
   * Get biometric statistics for dashboard
   */
  async getBiometricStats(empresaId) {
    try {
      const stats = await database.query(
        `SELECT 
          COUNT(DISTINCT e.ID_EMPLEADO) as total_employees,
          COUNT(DISTINCT bd.ID_EMPLEADO) as enrolled_employees,
          COUNT(CASE WHEN bd.BIOMETRIC_TYPE = 'fingerprint' THEN 1 END) as fingerprint_enrollments,
          COUNT(CASE WHEN bd.BIOMETRIC_TYPE = 'facial' THEN 1 END) as facial_enrollments,
          COUNT(DISTINCT CASE WHEN bl.VERIFICATION_SUCCESS = 1 THEN bl.ID_EMPLEADO END) as successful_verifications_today
         FROM empleados e
         LEFT JOIN biometric_data bd ON e.ID_EMPLEADO = bd.ID_EMPLEADO AND bd.ACTIVO = 1
         LEFT JOIN biometric_logs bl ON e.ID_EMPLEADO = bl.ID_EMPLEADO AND bl.FECHA = CURDATE()
         WHERE e.ID_EMPRESA = ? AND e.ACTIVO = 1`,
        [empresaId]
      );

      const recentActivity = await database.query(
        `SELECT bl.*, e.NOMBRE, e.APELLIDO
         FROM biometric_logs bl
         JOIN empleados e ON bl.ID_EMPLEADO = e.ID_EMPLEADO
         WHERE e.ID_EMPRESA = ? AND bl.CREATED_AT >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
         ORDER BY bl.CREATED_AT DESC
         LIMIT 10`,
        [empresaId]
      );

      return {
        overview: stats[0],
        recent_activity: recentActivity
      };
    } catch (error) {
      console.error('Error getting biometric stats:', error);
      throw error;
    }
  }

  /**
   * Delete biometric data
   */
  async deleteBiometricData(employeeId, biometricType, fingerType = null) {
    try {
      let query = 'UPDATE biometric_data SET ACTIVO = 0 WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = ?';
      const params = [employeeId, biometricType];

      if (biometricType === 'fingerprint' && fingerType) {
        query += ' AND FINGER_TYPE = ?';
        params.push(fingerType);
      }

      await database.query(query, params);

      return {
        message: 'Datos biométricos eliminados correctamente'
      };
    } catch (error) {
      console.error('Error deleting biometric data:', error);
      throw error;
    }
  }

  /**
   * Get device status (simulated for modern web APIs)
   */
  async getDeviceStatus() {
    // This would integrate with actual device detection APIs
    return {
      camera: {
        available: true,
        status: 'connected',
        type: 'webcam'
      },
      fingerprint: {
        available: false, // Simulated - would check for WebUSB/WebHID devices
        status: 'not_connected',
        type: 'usb_scanner'
      },
      webauthn: {
        available: typeof window !== 'undefined' && 'credentials' in navigator,
        status: 'available'
      }
    };
  }

  /**
   * Create biometric tables if they don't exist
   */
  async createBiometricTables(connection) {
    const createBiometricDataTable = `
      CREATE TABLE IF NOT EXISTS biometric_data (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        ID_EMPLEADO INT NOT NULL,
        BIOMETRIC_TYPE ENUM('fingerprint', 'facial') NOT NULL,
        FINGER_TYPE VARCHAR(20),
        BIOMETRIC_DATA LONGTEXT,
        CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        ACTIVO TINYINT(1) DEFAULT 1,
        FOREIGN KEY (ID_EMPLEADO) REFERENCES empleados(ID_EMPLEADO),
        UNIQUE KEY unique_employee_finger (ID_EMPLEADO, FINGER_TYPE)
      )
    `;

    const createBiometricLogsTable = `
      CREATE TABLE IF NOT EXISTS biometric_logs (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        ID_EMPLEADO INT NOT NULL,
        VERIFICATION_METHOD ENUM('fingerprint', 'facial', 'traditional') NOT NULL,
        VERIFICATION_SUCCESS TINYINT(1) DEFAULT 0,
        FECHA DATE,
        HORA TIME,
        CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ID_EMPLEADO) REFERENCES empleados(ID_EMPLEADO)
      )
    `;

    await connection.execute(createBiometricDataTable);
    await connection.execute(createBiometricLogsTable);
  }

  /**
   * Simulate biometric comparison (for demo purposes)
   */
  simulateBiometricComparison(newData, storedData) {
    // In production, this would use actual biometric matching algorithms
    // For now, simulate based on data similarity
    const similarity = Math.random() * 0.4 + 0.6; // 60-100% similarity
    return Math.min(similarity, 1.0);
  }
}

module.exports = new BiometricService();