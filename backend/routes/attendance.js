const express = require('express');
const { body, validationResult } = require('express-validator');
const db = require('../config/database');
const { authenticateToken, requireModuleAccess } = require('../middleware/auth');

const router = express.Router();

/**
 * GET /api/attendance
 * Get attendance records
 */
router.get('/', authenticateToken, requireModuleAccess('attendance'), async (req, res) => {
  try {
    const { 
      page = 1, 
      limit = 10, 
      employee_id, 
      start_date, 
      end_date, 
      establecimiento,
      sede 
    } = req.query;
    
    const offset = (page - 1) * limit;

    let whereConditions = ['emp.ID_EMPRESA = ?'];
    let queryParams = [req.user.companyId];

    // Add employee filter
    if (employee_id) {
      whereConditions.push('a.ID_EMPLEADO = ?');
      queryParams.push(employee_id);
    }

    // Add date range filter
    if (start_date) {
      whereConditions.push('a.FECHA >= ?');
      queryParams.push(start_date);
    }

    if (end_date) {
      whereConditions.push('a.FECHA <= ?');
      queryParams.push(end_date);
    }

    // Add establecimiento filter
    if (establecimiento) {
      whereConditions.push('est.ID_ESTABLECIMIENTO = ?');
      queryParams.push(establecimiento);
    }

    // Add sede filter
    if (sede) {
      whereConditions.push('s.ID_SEDE = ?');
      queryParams.push(sede);
    }

    const whereClause = whereConditions.length > 0 ? `WHERE ${whereConditions.join(' AND ')}` : '';

    // Get total count
    const countQuery = `
      SELECT COUNT(*) as total
      FROM asistencias a
      JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
      ${whereClause}
    `;

    const countResult = await db.query(countQuery, queryParams);
    const total = countResult[0].total;

    // Get attendance records
    const attendanceQuery = `
      SELECT 
        a.ID_ASISTENCIA,
        a.ID_EMPLEADO,
        a.FECHA,
        a.HORA_ENTRADA,
        a.HORA_SALIDA,
        a.ESTADO,
        a.VERIFICATION_METHOD,
        e.NOMBRE as EMPLEADO_NOMBRE,
        e.APELLIDO as EMPLEADO_APELLIDO,
        e.DNI as EMPLEADO_DNI,
        est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
        s.NOMBRE as SEDE_NOMBRE
      FROM asistencias a
      JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
      ${whereClause}
      ORDER BY a.FECHA DESC, a.HORA_ENTRADA DESC
      LIMIT ? OFFSET ?
    `;

    const attendance = await db.query(attendanceQuery, [...queryParams, parseInt(limit), parseInt(offset)]);

    res.json({
      success: true,
      data: {
        attendance,
        pagination: {
          page: parseInt(page),
          limit: parseInt(limit),
          total,
          totalPages: Math.ceil(total / limit)
        }
      }
    });

  } catch (error) {
    console.error('Get attendance error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * POST /api/attendance/register
 * Register attendance (check-in or check-out)
 */
router.post('/register', [
  authenticateToken,
  requireModuleAccess('attendance'),
  body('employee_id').isInt().withMessage('ID de empleado es requerido'),
  body('verification_method').isIn(['fingerprint', 'facial', 'traditional']).withMessage('Método de verificación inválido')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({
        success: false,
        message: 'Datos inválidos',
        errors: errors.array()
      });
    }

    const { employee_id, verification_method, biometric_data, finger_type } = req.body;

    // Verify employee exists and belongs to user's company
    const employeeQuery = `
      SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO
      FROM EMPLEADO e
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      WHERE e.ID_EMPLEADO = ? AND s.ID_EMPRESA = ? AND e.ACTIVO = 'S'
    `;

    const employees = await db.query(employeeQuery, [employee_id, req.user.companyId]);

    if (employees.length === 0) {
      return res.status(400).json({
        success: false,
        message: 'Empleado no encontrado o inactivo'
      });
    }

    const employee = employees[0];
    const today = new Date().toISOString().split('T')[0];
    const currentTime = new Date().toTimeString().split(' ')[0];

    // If biometric verification, verify the data first
    if ((verification_method === 'fingerprint' || verification_method === 'facial') && biometric_data) {
      let storedDataQuery = `
        SELECT BIOMETRIC_DATA FROM biometric_data 
        WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = ? AND ACTIVO = 1
      `;
      let queryParams = [employee_id, verification_method];

      if (verification_method === 'fingerprint' && finger_type) {
        storedDataQuery += ' AND FINGER_TYPE = ?';
        queryParams.push(finger_type);
      }

      const storedData = await db.query(storedDataQuery, queryParams);

      if (storedData.length === 0) {
        await db.query(
          'INSERT INTO biometric_logs (ID_EMPLEADO, VERIFICATION_METHOD, VERIFICATION_SUCCESS, FECHA, HORA) VALUES (?, ?, 0, CURDATE(), CURTIME())',
          [employee_id, verification_method]
        );

        return res.status(400).json({
          success: false,
          message: 'No hay datos biométricos registrados para este empleado'
        });
      }

      // Simple biometric verification (in production, use proper algorithms)
      const isMatch = storedData.some(record => record.BIOMETRIC_DATA === biometric_data);

      if (!isMatch) {
        await db.query(
          'INSERT INTO biometric_logs (ID_EMPLEADO, VERIFICATION_METHOD, VERIFICATION_SUCCESS, FECHA, HORA) VALUES (?, ?, 0, CURDATE(), CURTIME())',
          [employee_id, verification_method]
        );

        return res.status(400).json({
          success: false,
          message: 'Verificación biométrica fallida'
        });
      }

      // Log successful verification
      await db.query(
        'INSERT INTO biometric_logs (ID_EMPLEADO, VERIFICATION_METHOD, VERIFICATION_SUCCESS, FECHA, HORA) VALUES (?, ?, 1, CURDATE(), CURTIME())',
        [employee_id, verification_method]
      );
    }

    // Check if there's already an attendance record for today
    const existingQuery = `
      SELECT ID_ASISTENCIA, HORA_ENTRADA, HORA_SALIDA 
      FROM asistencias 
      WHERE ID_EMPLEADO = ? AND FECHA = ?
    `;

    const existing = await db.query(existingQuery, [employee_id, today]);

    if (existing.length > 0) {
      const record = existing[0];
      
      if (!record.HORA_SALIDA) {
        // Check-out: Update existing record with exit time
        await db.query(
          'UPDATE asistencias SET HORA_SALIDA = ?, VERIFICATION_METHOD = ? WHERE ID_ASISTENCIA = ?',
          [currentTime, verification_method, record.ID_ASISTENCIA]
        );

        // Log activity
        await db.query(
          'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
          [req.user.id, 'CHECKOUT', `Salida registrada: ${employee.NOMBRE} ${employee.APELLIDO} - ${currentTime}`]
        );

        res.json({
          success: true,
          message: 'Salida registrada exitosamente',
          data: {
            type: 'checkout',
            employee: employee,
            fecha: today,
            hora_salida: currentTime,
            verification_method
          }
        });
      } else {
        // Employee already checked out today
        res.status(400).json({
          success: false,
          message: 'El empleado ya registró entrada y salida para hoy'
        });
      }
    } else {
      // Check-in: Create new attendance record
      const insertQuery = `
        INSERT INTO asistencias (ID_EMPLEADO, FECHA, HORA_ENTRADA, ESTADO, VERIFICATION_METHOD)
        VALUES (?, ?, ?, 'PRESENTE', ?)
      `;

      const result = await db.query(insertQuery, [employee_id, today, currentTime, verification_method]);

      // Log activity
      await db.query(
        'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
        [req.user.id, 'CHECKIN', `Entrada registrada: ${employee.NOMBRE} ${employee.APELLIDO} - ${currentTime}`]
      );

      res.status(201).json({
        success: true,
        message: 'Entrada registrada exitosamente',
        data: {
          type: 'checkin',
          id: result.insertId,
          employee: employee,
          fecha: today,
          hora_entrada: currentTime,
          verification_method
        }
      });
    }

  } catch (error) {
    console.error('Register attendance error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * GET /api/attendance/employee/:id/today
 * Get today's attendance for specific employee
 */
router.get('/employee/:id/today', authenticateToken, requireModuleAccess('attendance'), async (req, res) => {
  try {
    const { id } = req.params;
    const today = new Date().toISOString().split('T')[0];

    // Verify employee exists and belongs to user's company
    const employeeQuery = `
      SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO
      FROM EMPLEADO e
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      WHERE e.ID_EMPLEADO = ? AND s.ID_EMPRESA = ?
    `;

    const employees = await db.query(employeeQuery, [id, req.user.companyId]);

    if (employees.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Empleado no encontrado'
      });
    }

    // Get today's attendance
    const attendanceQuery = `
      SELECT 
        ID_ASISTENCIA,
        FECHA,
        HORA_ENTRADA,
        HORA_SALIDA,
        ESTADO,
        VERIFICATION_METHOD
      FROM asistencias
      WHERE ID_EMPLEADO = ? AND FECHA = ?
    `;

    const attendance = await db.query(attendanceQuery, [id, today]);

    res.json({
      success: true,
      data: {
        employee: employees[0],
        attendance: attendance.length > 0 ? attendance[0] : null,
        hasCheckedIn: attendance.length > 0,
        hasCheckedOut: attendance.length > 0 && attendance[0].HORA_SALIDA !== null
      }
    });

  } catch (error) {
    console.error('Get employee today attendance error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * GET /api/attendance/stats
 * Get attendance statistics
 */
router.get('/stats', authenticateToken, requireModuleAccess('attendance'), async (req, res) => {
  try {
    const { start_date, end_date } = req.query;
    const today = new Date().toISOString().split('T')[0];

    let dateCondition = '';
    let queryParams = [req.user.companyId];

    if (start_date && end_date) {
      dateCondition = 'AND a.FECHA BETWEEN ? AND ?';
      queryParams.push(start_date, end_date);
    } else {
      dateCondition = 'AND a.FECHA = ?';
      queryParams.push(today);
    }

    // Get attendance statistics
    const statsQuery = `
      SELECT 
        COUNT(*) as total_registros,
        COUNT(DISTINCT a.ID_EMPLEADO) as empleados_presentes,
        SUM(CASE WHEN a.HORA_SALIDA IS NOT NULL THEN 1 ELSE 0 END) as registros_completos,
        SUM(CASE WHEN a.VERIFICATION_METHOD = 'fingerprint' THEN 1 ELSE 0 END) as verificacion_huella,
        SUM(CASE WHEN a.VERIFICATION_METHOD = 'facial' THEN 1 ELSE 0 END) as verificacion_facial,
        SUM(CASE WHEN a.VERIFICATION_METHOD = 'traditional' THEN 1 ELSE 0 END) as verificacion_tradicional
      FROM asistencias a
      JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
      WHERE emp.ID_EMPRESA = ? ${dateCondition}
    `;

    const stats = await db.query(statsQuery, queryParams);

    // Get total active employees
    const totalEmployeesQuery = `
      SELECT COUNT(*) as total
      FROM EMPLEADO e
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      WHERE s.ID_EMPRESA = ? AND e.ACTIVO = 'S' AND e.ESTADO = 'A'
    `;

    const totalResult = await db.query(totalEmployeesQuery, [req.user.companyId]);
    const totalEmployees = totalResult[0].total;

    res.json({
      success: true,
      data: {
        ...stats[0],
        total_empleados: totalEmployees,
        porcentaje_asistencia: totalEmployees > 0 ? Math.round((stats[0].empleados_presentes / totalEmployees) * 100) : 0
      }
    });

  } catch (error) {
    console.error('Get attendance stats error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * PUT /api/attendance/:id
 * Update attendance record
 */
router.put('/:id', [
  authenticateToken,
  requireModuleAccess('attendance'),
  body('hora_entrada').optional().matches(/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/).withMessage('Formato de hora inválido'),
  body('hora_salida').optional().matches(/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/).withMessage('Formato de hora inválido'),
  body('estado').optional().isIn(['PRESENTE', 'TARDANZA', 'FALTA']).withMessage('Estado inválido')
], async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({
        success: false,
        message: 'Datos inválidos',
        errors: errors.array()
      });
    }

    const { id } = req.params;
    const { hora_entrada, hora_salida, estado } = req.body;

    // Verify attendance record exists and belongs to user's company
    const attendanceQuery = `
      SELECT a.ID_ASISTENCIA, e.NOMBRE, e.APELLIDO
      FROM asistencias a
      JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      WHERE a.ID_ASISTENCIA = ? AND s.ID_EMPRESA = ?
    `;

    const attendance = await db.query(attendanceQuery, [id, req.user.companyId]);

    if (attendance.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Registro de asistencia no encontrado'
      });
    }

    // Build update query
    let updateFields = [];
    let updateParams = [];

    if (hora_entrada !== undefined) {
      updateFields.push('HORA_ENTRADA = ?');
      updateParams.push(hora_entrada);
    }

    if (hora_salida !== undefined) {
      updateFields.push('HORA_SALIDA = ?');
      updateParams.push(hora_salida);
    }

    if (estado !== undefined) {
      updateFields.push('ESTADO = ?');
      updateParams.push(estado);
    }

    if (updateFields.length === 0) {
      return res.status(400).json({
        success: false,
        message: 'No hay campos para actualizar'
      });
    }

    const updateQuery = `UPDATE asistencias SET ${updateFields.join(', ')} WHERE ID_ASISTENCIA = ?`;
    updateParams.push(id);

    await db.query(updateQuery, updateParams);

    const employee = attendance[0];

    // Log activity
    await db.query(
      'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
      [req.user.id, 'UPDATE_ATTENDANCE', `Asistencia actualizada: ${employee.NOMBRE} ${employee.APELLIDO} (ID: ${id})`]
    );

    res.json({
      success: true,
      message: 'Registro de asistencia actualizado exitosamente'
    });

  } catch (error) {
    console.error('Update attendance error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

module.exports = router;