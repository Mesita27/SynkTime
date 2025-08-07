const database = require('../config/database');

class EmployeeService {
  /**
   * Get employees for a company with filters
   */
  async getEmployees(empresaId, filters = {}) {
    try {
      let query = `
        SELECT DISTINCT
          e.ID_EMPLEADO,
          e.NOMBRE,
          e.APELLIDO,
          e.DNI,
          e.CORREO,
          e.TELEFONO,
          e.FECHA_INGRESO,
          e.ESTADO,
          e.ACTIVO,
          est.NOMBRE AS ESTABLECIMIENTO_NOMBRE,
          est.ID_ESTABLECIMIENTO,
          s.NOMBRE AS SEDE_NOMBRE,
          s.ID_SEDE
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
        WHERE emp.ID_EMPRESA = ? 
        AND e.ESTADO = 'A' 
        AND e.ACTIVO = 'S'
      `;

      const params = [empresaId];

      // Add filters
      if (filters.search) {
        query += ` AND (e.NOMBRE LIKE ? OR e.APELLIDO LIKE ? OR e.DNI LIKE ?)`;
        const searchTerm = `%${filters.search}%`;
        params.push(searchTerm, searchTerm, searchTerm);
      }

      if (filters.sedeId) {
        query += ` AND s.ID_SEDE = ?`;
        params.push(filters.sedeId);
      }

      if (filters.establecimientoId) {
        query += ` AND est.ID_ESTABLECIMIENTO = ?`;
        params.push(filters.establecimientoId);
      }

      query += ` ORDER BY e.NOMBRE, e.APELLIDO`;

      // Add pagination
      if (filters.limit) {
        query += ` LIMIT ?`;
        params.push(parseInt(filters.limit));
        
        if (filters.offset) {
          query += ` OFFSET ?`;
          params.push(parseInt(filters.offset));
        }
      }

      const employees = await database.query(query, params);

      return employees;
    } catch (error) {
      console.error('Error getting employees:', error);
      throw error;
    }
  }

  /**
   * Get single employee by ID
   */
  async getEmployee(employeeId, empresaId) {
    try {
      const query = `
        SELECT 
          e.ID_EMPLEADO,
          e.NOMBRE,
          e.APELLIDO,
          e.DNI,
          e.CORREO,
          e.TELEFONO,
          e.FECHA_INGRESO,
          e.ESTADO,
          e.ACTIVO,
          est.NOMBRE AS ESTABLECIMIENTO_NOMBRE,
          est.ID_ESTABLECIMIENTO,
          s.NOMBRE AS SEDE_NOMBRE,
          s.ID_SEDE,
          emp.NOMBRE AS EMPRESA_NOMBRE
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
        WHERE e.ID_EMPLEADO = ? AND emp.ID_EMPRESA = ?
      `;

      const employees = await database.query(query, [employeeId, empresaId]);

      if (!employees.length) {
        return null;
      }

      const employee = employees[0];

      // Get employee schedules
      const schedules = await this.getEmployeeSchedules(employeeId);
      employee.HORARIOS = schedules;

      return employee;
    } catch (error) {
      console.error('Error getting employee:', error);
      throw error;
    }
  }

  /**
   * Get employee schedules
   */
  async getEmployeeSchedules(employeeId) {
    try {
      const query = `
        SELECT 
          eh.ID_HORARIO,
          h.NOMBRE,
          h.HORA_ENTRADA,
          h.HORA_SALIDA,
          h.TOLERANCIA,
          eh.FECHA_DESDE,
          eh.FECHA_HASTA
        FROM EMPLEADO_HORARIO eh
        JOIN HORARIO h ON eh.ID_HORARIO = h.ID_HORARIO
        WHERE eh.ID_EMPLEADO = ?
        ORDER BY eh.FECHA_DESDE DESC
      `;

      return await database.query(query, [employeeId]);
    } catch (error) {
      console.error('Error getting employee schedules:', error);
      throw error;
    }
  }

  /**
   * Create new employee
   */
  async createEmployee(employeeData, empresaId) {
    try {
      return await database.transaction(async (connection) => {
        // Validate establishment belongs to company
        const [establishments] = await connection.execute(
          `SELECT est.ID_ESTABLECIMIENTO 
           FROM ESTABLECIMIENTO est
           JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
           WHERE est.ID_ESTABLECIMIENTO = ? AND s.ID_EMPRESA = ?`,
          [employeeData.ID_ESTABLECIMIENTO, empresaId]
        );

        if (!establishments.length) {
          throw new Error('Establecimiento no válido para esta empresa');
        }

        // Check if DNI already exists
        const [existingEmployee] = await connection.execute(
          'SELECT ID_EMPLEADO FROM EMPLEADO WHERE DNI = ? AND ACTIVO = "S"',
          [employeeData.DNI]
        );

        if (existingEmployee.length) {
          throw new Error('Ya existe un empleado con este DNI');
        }

        // Insert employee
        const [result] = await connection.execute(
          `INSERT INTO EMPLEADO (
            NOMBRE, APELLIDO, DNI, CORREO, TELEFONO, 
            ID_ESTABLECIMIENTO, FECHA_INGRESO, ESTADO, ACTIVO
          ) VALUES (?, ?, ?, ?, ?, ?, ?, 'A', 'S')`,
          [
            employeeData.NOMBRE,
            employeeData.APELLIDO,
            employeeData.DNI,
            employeeData.CORREO || null,
            employeeData.TELEFONO || null,
            employeeData.ID_ESTABLECIMIENTO,
            employeeData.FECHA_INGRESO || new Date().toISOString().split('T')[0]
          ]
        );

        return {
          ID_EMPLEADO: result.insertId,
          message: 'Empleado creado exitosamente'
        };
      });
    } catch (error) {
      console.error('Error creating employee:', error);
      throw error;
    }
  }

  /**
   * Update employee
   */
  async updateEmployee(employeeId, employeeData, empresaId) {
    try {
      return await database.transaction(async (connection) => {
        // Verify employee belongs to company
        const [existing] = await connection.execute(
          `SELECT e.ID_EMPLEADO 
           FROM EMPLEADO e
           JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
           JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
           WHERE e.ID_EMPLEADO = ? AND s.ID_EMPRESA = ?`,
          [employeeId, empresaId]
        );

        if (!existing.length) {
          throw new Error('Empleado no encontrado o sin permisos');
        }

        // Build update query dynamically
        const updateFields = [];
        const params = [];

        if (employeeData.NOMBRE) {
          updateFields.push('NOMBRE = ?');
          params.push(employeeData.NOMBRE);
        }
        if (employeeData.APELLIDO) {
          updateFields.push('APELLIDO = ?');
          params.push(employeeData.APELLIDO);
        }
        if (employeeData.DNI) {
          updateFields.push('DNI = ?');
          params.push(employeeData.DNI);
        }
        if (employeeData.CORREO !== undefined) {
          updateFields.push('CORREO = ?');
          params.push(employeeData.CORREO);
        }
        if (employeeData.TELEFONO !== undefined) {
          updateFields.push('TELEFONO = ?');
          params.push(employeeData.TELEFONO);
        }
        if (employeeData.ID_ESTABLECIMIENTO) {
          // Validate new establishment
          const [establishments] = await connection.execute(
            `SELECT est.ID_ESTABLECIMIENTO 
             FROM ESTABLECIMIENTO est
             JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
             WHERE est.ID_ESTABLECIMIENTO = ? AND s.ID_EMPRESA = ?`,
            [employeeData.ID_ESTABLECIMIENTO, empresaId]
          );

          if (!establishments.length) {
            throw new Error('Establecimiento no válido para esta empresa');
          }

          updateFields.push('ID_ESTABLECIMIENTO = ?');
          params.push(employeeData.ID_ESTABLECIMIENTO);
        }
        if (employeeData.ESTADO) {
          updateFields.push('ESTADO = ?');
          params.push(employeeData.ESTADO);
        }

        if (updateFields.length === 0) {
          throw new Error('No hay campos para actualizar');
        }

        params.push(employeeId);

        await connection.execute(
          `UPDATE EMPLEADO SET ${updateFields.join(', ')} WHERE ID_EMPLEADO = ?`,
          params
        );

        return {
          message: 'Empleado actualizado exitosamente'
        };
      });
    } catch (error) {
      console.error('Error updating employee:', error);
      throw error;
    }
  }

  /**
   * Soft delete employee
   */
  async deleteEmployee(employeeId, empresaId) {
    try {
      return await database.transaction(async (connection) => {
        // Verify employee belongs to company
        const [existing] = await connection.execute(
          `SELECT e.ID_EMPLEADO 
           FROM EMPLEADO e
           JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
           JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
           WHERE e.ID_EMPLEADO = ? AND s.ID_EMPRESA = ?`,
          [employeeId, empresaId]
        );

        if (!existing.length) {
          throw new Error('Empleado no encontrado o sin permisos');
        }

        // Soft delete
        await connection.execute(
          'UPDATE EMPLEADO SET ACTIVO = "N", ESTADO = "I" WHERE ID_EMPLEADO = ?',
          [employeeId]
        );

        return {
          message: 'Empleado eliminado exitosamente'
        };
      });
    } catch (error) {
      console.error('Error deleting employee:', error);
      throw error;
    }
  }

  /**
   * Get company locations (sedes and establecimientos)
   */
  async getCompanyLocations(empresaId) {
    try {
      const sedes = await database.query(
        'SELECT ID_SEDE, NOMBRE, DIRECCION FROM SEDE WHERE ID_EMPRESA = ? AND ESTADO = "A"',
        [empresaId]
      );

      for (const sede of sedes) {
        sede.ESTABLECIMIENTOS = await database.query(
          'SELECT ID_ESTABLECIMIENTO, NOMBRE, DIRECCION FROM ESTABLECIMIENTO WHERE ID_SEDE = ? AND ESTADO = "A"',
          [sede.ID_SEDE]
        );
      }

      return sedes;
    } catch (error) {
      console.error('Error getting company locations:', error);
      throw error;
    }
  }
  /**
   * Assign schedule to employee
   */
  async assignSchedule(employeeId, scheduleData, empresaId) {
    try {
      return await database.transaction(async (connection) => {
        // Verify employee belongs to company
        const [existing] = await connection.execute(
          `SELECT e.ID_EMPLEADO 
           FROM EMPLEADO e
           JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
           JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
           WHERE e.ID_EMPLEADO = ? AND s.ID_EMPRESA = ?`,
          [employeeId, empresaId]
        );

        if (!existing.length) {
          throw new Error('Empleado no encontrado o sin permisos');
        }

        // Verify schedule exists
        const [schedules] = await connection.execute(
          'SELECT ID_HORARIO FROM HORARIO WHERE ID_HORARIO = ?',
          [scheduleData.ID_HORARIO]
        );

        if (!schedules.length) {
          throw new Error('Horario no encontrado');
        }

        // Check for overlapping schedules
        let overlapQuery = `
          SELECT ID_HORARIO FROM EMPLEADO_HORARIO 
          WHERE ID_EMPLEADO = ? AND FECHA_DESDE <= ?
        `;
        const overlapParams = [employeeId, scheduleData.FECHA_HASTA || '9999-12-31'];

        if (scheduleData.FECHA_HASTA) {
          overlapQuery += ' AND (FECHA_HASTA IS NULL OR FECHA_HASTA >= ?)';
          overlapParams.push(scheduleData.FECHA_DESDE);
        } else {
          overlapQuery += ' AND FECHA_HASTA IS NULL';
        }

        const [overlapping] = await connection.execute(overlapQuery, overlapParams);

        if (overlapping.length > 0) {
          throw new Error('Ya existe un horario asignado en el rango de fechas especificado');
        }

        // Insert schedule assignment
        await connection.execute(
          `INSERT INTO EMPLEADO_HORARIO (ID_EMPLEADO, ID_HORARIO, FECHA_DESDE, FECHA_HASTA)
           VALUES (?, ?, ?, ?)`,
          [
            employeeId,
            scheduleData.ID_HORARIO,
            scheduleData.FECHA_DESDE,
            scheduleData.FECHA_HASTA || null
          ]
        );

        return {
          message: 'Horario asignado exitosamente'
        };
      });
    } catch (error) {
      console.error('Error assigning schedule:', error);
      throw error;
    }
  }
}

module.exports = new EmployeeService();