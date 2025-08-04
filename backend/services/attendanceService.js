const database = require('../config/database');

class AttendanceService {
  /**
   * Register attendance
   */
  async registerAttendance(attendanceData) {
    try {
      return await database.transaction(async (connection) => {
        // Validate employee exists and get details
        const [employees] = await connection.execute(
          `SELECT 
            e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO,
            est.NOMBRE AS ESTABLECIMIENTO_NOMBRE,
            s.NOMBRE AS SEDE_NOMBRE
           FROM EMPLEADO e
           JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
           JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
           WHERE e.ID_EMPLEADO = ? AND e.ACTIVO = "S" AND e.ESTADO = "A"`,
          [attendanceData.ID_EMPLEADO]
        );

        if (!employees.length) {
          throw new Error('Empleado no encontrado o inactivo');
        }

        const employee = employees[0];

        // Check if attendance already exists for today
        const today = new Date().toISOString().split('T')[0];
        const [existingAttendance] = await connection.execute(
          'SELECT ID_ASISTENCIA, TIPO_ASISTENCIA FROM ASISTENCIA WHERE ID_EMPLEADO = ? AND FECHA = ?',
          [attendanceData.ID_EMPLEADO, today]
        );

        let attendanceType = 'ENTRADA';
        if (existingAttendance.length > 0) {
          if (existingAttendance[0].TIPO_ASISTENCIA === 'ENTRADA') {
            attendanceType = 'SALIDA';
          } else {
            throw new Error('Ya se registró la salida para hoy');
          }
        }

        // Get current time
        const now = new Date();
        const currentTime = now.toTimeString().split(' ')[0]; // HH:MM:SS format

        // Insert attendance record
        const [result] = await connection.execute(
          `INSERT INTO ASISTENCIA (
            ID_EMPLEADO, FECHA, HORA, TIPO_ASISTENCIA, 
            VERIFICATION_METHOD, OBSERVACIONES, CREATED_AT
          ) VALUES (?, ?, ?, ?, ?, ?, NOW())`,
          [
            attendanceData.ID_EMPLEADO,
            today,
            currentTime,
            attendanceType,
            attendanceData.VERIFICATION_METHOD || 'traditional',
            attendanceData.OBSERVACIONES || null
          ]
        );

        // Log attendance in biometric logs if it's a biometric verification
        if (attendanceData.VERIFICATION_METHOD && 
            ['fingerprint', 'facial'].includes(attendanceData.VERIFICATION_METHOD)) {
          await connection.execute(
            `INSERT INTO biometric_logs (
              ID_EMPLEADO, VERIFICATION_METHOD, VERIFICATION_SUCCESS, 
              FECHA, HORA, CREATED_AT
            ) VALUES (?, ?, 1, ?, ?, NOW())`,
            [attendanceData.ID_EMPLEADO, attendanceData.VERIFICATION_METHOD, today, currentTime]
          );
        }

        return {
          ID_ASISTENCIA: result.insertId,
          employee: `${employee.NOMBRE} ${employee.APELLIDO}`,
          fecha: today,
          hora: currentTime,
          tipo: attendanceType,
          verification_method: attendanceData.VERIFICATION_METHOD || 'traditional',
          message: `${attendanceType.toLowerCase()} registrada exitosamente`
        };
      });
    } catch (error) {
      console.error('Error registering attendance:', error);
      throw error;
    }
  }

  /**
   * Get attendance records for a company with filters
   */
  async getAttendanceRecords(empresaId, filters = {}) {
    try {
      let query = `
        SELECT 
          a.ID_ASISTENCIA,
          a.ID_EMPLEADO,
          e.NOMBRE,
          e.APELLIDO,
          e.DNI,
          a.FECHA,
          a.HORA,
          a.TIPO_ASISTENCIA,
          a.VERIFICATION_METHOD,
          a.OBSERVACIONES,
          est.NOMBRE AS ESTABLECIMIENTO_NOMBRE,
          s.NOMBRE AS SEDE_NOMBRE
        FROM ASISTENCIA a
        JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE s.ID_EMPRESA = ?
      `;

      const params = [empresaId];

      // Add date filters
      if (filters.fecha_desde) {
        query += ` AND a.FECHA >= ?`;
        params.push(filters.fecha_desde);
      }

      if (filters.fecha_hasta) {
        query += ` AND a.FECHA <= ?`;
        params.push(filters.fecha_hasta);
      }

      // Add employee filter
      if (filters.empleado_id) {
        query += ` AND a.ID_EMPLEADO = ?`;
        params.push(filters.empleado_id);
      }

      // Add establishment filter
      if (filters.establecimiento_id) {
        query += ` AND est.ID_ESTABLECIMIENTO = ?`;
        params.push(filters.establecimiento_id);
      }

      // Add attendance type filter
      if (filters.tipo_asistencia) {
        query += ` AND a.TIPO_ASISTENCIA = ?`;
        params.push(filters.tipo_asistencia);
      }

      query += ` ORDER BY a.FECHA DESC, a.HORA DESC`;

      // Add pagination
      if (filters.limit) {
        query += ` LIMIT ?`;
        params.push(parseInt(filters.limit));
        
        if (filters.offset) {
          query += ` OFFSET ?`;
          params.push(parseInt(filters.offset));
        }
      }

      const records = await database.query(query, params);

      return records;
    } catch (error) {
      console.error('Error getting attendance records:', error);
      throw error;
    }
  }

  /**
   * Get attendance summary for dashboard
   */
  async getAttendanceSummary(empresaId, date = null) {
    try {
      const targetDate = date || new Date().toISOString().split('T')[0];

      const summary = await database.query(
        `SELECT 
          COUNT(DISTINCT CASE WHEN a.TIPO_ASISTENCIA = 'ENTRADA' THEN a.ID_EMPLEADO END) as entradas_hoy,
          COUNT(DISTINCT CASE WHEN a.TIPO_ASISTENCIA = 'SALIDA' THEN a.ID_EMPLEADO END) as salidas_hoy,
          COUNT(DISTINCT e.ID_EMPLEADO) as total_empleados_activos,
          COUNT(DISTINCT CASE WHEN a.VERIFICATION_METHOD = 'fingerprint' THEN a.ID_EMPLEADO END) as verificaciones_huella,
          COUNT(DISTINCT CASE WHEN a.VERIFICATION_METHOD = 'facial' THEN a.ID_EMPLEADO END) as verificaciones_facial
         FROM EMPLEADO e
         JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
         JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
         LEFT JOIN ASISTENCIA a ON e.ID_EMPLEADO = a.ID_EMPLEADO AND a.FECHA = ?
         WHERE s.ID_EMPRESA = ? AND e.ACTIVO = "S" AND e.ESTADO = "A"`,
        [targetDate, empresaId]
      );

      // Get hourly distribution
      const hourlyDistribution = await database.query(
        `SELECT 
          HOUR(a.HORA) as hora,
          COUNT(*) as cantidad,
          a.TIPO_ASISTENCIA
         FROM ASISTENCIA a
         JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
         JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
         JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
         WHERE s.ID_EMPRESA = ? AND a.FECHA = ?
         GROUP BY HOUR(a.HORA), a.TIPO_ASISTENCIA
         ORDER BY hora`,
        [empresaId, targetDate]
      );

      // Get recent activity
      const recentActivity = await database.query(
        `SELECT 
          a.ID_ASISTENCIA,
          e.NOMBRE,
          e.APELLIDO,
          a.HORA,
          a.TIPO_ASISTENCIA,
          a.VERIFICATION_METHOD,
          est.NOMBRE AS ESTABLECIMIENTO_NOMBRE
         FROM ASISTENCIA a
         JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
         JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
         JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
         WHERE s.ID_EMPRESA = ? AND a.FECHA = ?
         ORDER BY a.HORA DESC
         LIMIT 10`,
        [empresaId, targetDate]
      );

      return {
        summary: summary[0],
        hourly_distribution: hourlyDistribution,
        recent_activity: recentActivity,
        date: targetDate
      };
    } catch (error) {
      console.error('Error getting attendance summary:', error);
      throw error;
    }
  }

  /**
   * Get employee attendance history
   */
  async getEmployeeAttendance(employeeId, empresaId, filters = {}) {
    try {
      // Verify employee belongs to company
      const [employees] = await database.query(
        `SELECT e.ID_EMPLEADO 
         FROM EMPLEADO e
         JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
         JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
         WHERE e.ID_EMPLEADO = ? AND s.ID_EMPRESA = ?`,
        [employeeId, empresaId]
      );

      if (!employees.length) {
        throw new Error('Empleado no encontrado o sin permisos');
      }

      let query = `
        SELECT 
          a.ID_ASISTENCIA,
          a.FECHA,
          a.HORA,
          a.TIPO_ASISTENCIA,
          a.VERIFICATION_METHOD,
          a.OBSERVACIONES
        FROM ASISTENCIA a
        WHERE a.ID_EMPLEADO = ?
      `;

      const params = [employeeId];

      // Add date filters
      if (filters.fecha_desde) {
        query += ` AND a.FECHA >= ?`;
        params.push(filters.fecha_desde);
      }

      if (filters.fecha_hasta) {
        query += ` AND a.FECHA <= ?`;
        params.push(filters.fecha_hasta);
      }

      query += ` ORDER BY a.FECHA DESC, a.HORA DESC`;

      // Add pagination
      if (filters.limit) {
        query += ` LIMIT ?`;
        params.push(parseInt(filters.limit));
        
        if (filters.offset) {
          query += ` OFFSET ?`;
          params.push(parseInt(filters.offset));
        }
      }

      const attendance = await database.query(query, params);

      return attendance;
    } catch (error) {
      console.error('Error getting employee attendance:', error);
      throw error;
    }
  }

  /**
   * Update attendance record
   */
  async updateAttendance(attendanceId, updateData, empresaId) {
    try {
      return await database.transaction(async (connection) => {
        // Verify attendance belongs to company
        const [existing] = await connection.execute(
          `SELECT a.ID_ASISTENCIA 
           FROM ASISTENCIA a
           JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
           JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
           JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
           WHERE a.ID_ASISTENCIA = ? AND s.ID_EMPRESA = ?`,
          [attendanceId, empresaId]
        );

        if (!existing.length) {
          throw new Error('Registro de asistencia no encontrado o sin permisos');
        }

        // Build update query
        const updateFields = [];
        const params = [];

        if (updateData.HORA) {
          updateFields.push('HORA = ?');
          params.push(updateData.HORA);
        }

        if (updateData.OBSERVACIONES !== undefined) {
          updateFields.push('OBSERVACIONES = ?');
          params.push(updateData.OBSERVACIONES);
        }

        if (updateFields.length === 0) {
          throw new Error('No hay campos para actualizar');
        }

        params.push(attendanceId);

        await connection.execute(
          `UPDATE ASISTENCIA SET ${updateFields.join(', ')} WHERE ID_ASISTENCIA = ?`,
          params
        );

        return {
          message: 'Registro de asistencia actualizado exitosamente'
        };
      });
    } catch (error) {
      console.error('Error updating attendance:', error);
      throw error;
    }
  }

  /**
   * Delete attendance record
   */
  async deleteAttendance(attendanceId, empresaId) {
    try {
      return await database.transaction(async (connection) => {
        // Verify attendance belongs to company
        const [existing] = await connection.execute(
          `SELECT a.ID_ASISTENCIA 
           FROM ASISTENCIA a
           JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
           JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
           JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
           WHERE a.ID_ASISTENCIA = ? AND s.ID_EMPRESA = ?`,
          [attendanceId, empresaId]
        );

        if (!existing.length) {
          throw new Error('Registro de asistencia no encontrado o sin permisos');
        }

        await connection.execute(
          'DELETE FROM ASISTENCIA WHERE ID_ASISTENCIA = ?',
          [attendanceId]
        );

        return {
          message: 'Registro de asistencia eliminado exitosamente'
        };
      });
    } catch (error) {
      console.error('Error deleting attendance:', error);
      throw error;
    }
  }
}

module.exports = new AttendanceService();