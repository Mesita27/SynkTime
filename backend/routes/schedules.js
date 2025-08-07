const express = require('express');
const { body, validationResult } = require('express-validator');
const db = require('../config/database');
const { authenticateToken, requireModuleAccess } = require('../middleware/auth');

const router = express.Router();

/**
 * GET /api/schedules
 * Get list of schedules
 */
router.get('/', authenticateToken, requireModuleAccess('schedules'), async (req, res) => {
  try {
    const { page = 1, limit = 10, search = '' } = req.query;
    const offset = (page - 1) * limit;

    let whereConditions = ['emp.ID_EMPRESA = ?'];
    let queryParams = [req.user.companyId];

    // Add search condition
    if (search) {
      whereConditions.push('h.NOMBRE LIKE ?');
      queryParams.push(`%${search}%`);
    }

    const whereClause = whereConditions.length > 0 ? `WHERE ${whereConditions.join(' AND ')}` : '';

    // Get total count
    const countQuery = `
      SELECT COUNT(*) as total
      FROM HORARIO h
      JOIN SEDE s ON h.ID_SEDE = s.ID_SEDE
      JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
      ${whereClause}
    `;

    const countResult = await db.query(countQuery, queryParams);
    const total = countResult[0].total;

    // Get schedules
    const schedulesQuery = `
      SELECT 
        h.ID_HORARIO,
        h.NOMBRE,
        h.HORA_ENTRADA,
        h.HORA_SALIDA,
        h.DIAS_LABORALES,
        h.ACTIVO,
        s.NOMBRE as SEDE_NOMBRE,
        s.ID_SEDE
      FROM HORARIO h
      JOIN SEDE s ON h.ID_SEDE = s.ID_SEDE
      JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
      ${whereClause}
      ORDER BY h.NOMBRE
      LIMIT ? OFFSET ?
    `;

    const schedules = await db.query(schedulesQuery, [...queryParams, parseInt(limit), parseInt(offset)]);

    res.json({
      success: true,
      data: {
        schedules,
        pagination: {
          page: parseInt(page),
          limit: parseInt(limit),
          total,
          totalPages: Math.ceil(total / limit)
        }
      }
    });

  } catch (error) {
    console.error('Get schedules error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * GET /api/schedules/:id
 * Get single schedule by ID
 */
router.get('/:id', authenticateToken, requireModuleAccess('schedules'), async (req, res) => {
  try {
    const { id } = req.params;

    const scheduleQuery = `
      SELECT 
        h.ID_HORARIO,
        h.NOMBRE,
        h.HORA_ENTRADA,
        h.HORA_SALIDA,
        h.DIAS_LABORALES,
        h.ACTIVO,
        s.NOMBRE as SEDE_NOMBRE,
        s.ID_SEDE
      FROM HORARIO h
      JOIN SEDE s ON h.ID_SEDE = s.ID_SEDE
      JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
      WHERE h.ID_HORARIO = ? AND emp.ID_EMPRESA = ?
    `;

    const schedules = await db.query(scheduleQuery, [id, req.user.companyId]);

    if (schedules.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Horario no encontrado'
      });
    }

    // Get assigned employees
    const employeesQuery = `
      SELECT 
        e.ID_EMPLEADO,
        e.NOMBRE,
        e.APELLIDO,
        e.DNI,
        est.NOMBRE as ESTABLECIMIENTO_NOMBRE
      FROM EMPLEADO e
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      WHERE e.ID_HORARIO = ? AND e.ACTIVO = 'S'
      ORDER BY e.NOMBRE, e.APELLIDO
    `;

    const employees = await db.query(employeesQuery, [id]);

    res.json({
      success: true,
      data: {
        ...schedules[0],
        assigned_employees: employees
      }
    });

  } catch (error) {
    console.error('Get schedule error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * POST /api/schedules
 * Create new schedule
 */
router.post('/', [
  authenticateToken,
  requireModuleAccess('schedules'),
  body('nombre').notEmpty().withMessage('Nombre es requerido'),
  body('hora_entrada').matches(/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/).withMessage('Formato de hora de entrada inválido'),
  body('hora_salida').matches(/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/).withMessage('Formato de hora de salida inválido'),
  body('id_sede').isInt().withMessage('Sede es requerida'),
  body('dias_laborales').isArray().withMessage('Días laborales debe ser un array')
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

    const {
      nombre,
      hora_entrada,
      hora_salida,
      id_sede,
      dias_laborales
    } = req.body;

    // Verify sede belongs to user's company
    const sedeQuery = `
      SELECT s.ID_SEDE
      FROM SEDE s
      WHERE s.ID_SEDE = ? AND s.ID_EMPRESA = ?
    `;

    const sedes = await db.query(sedeQuery, [id_sede, req.user.companyId]);

    if (sedes.length === 0) {
      return res.status(400).json({
        success: false,
        message: 'Sede inválida'
      });
    }

    // Validate dias_laborales
    const validDays = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO'];
    const invalidDays = dias_laborales.filter(day => !validDays.includes(day));
    
    if (invalidDays.length > 0) {
      return res.status(400).json({
        success: false,
        message: `Días inválidos: ${invalidDays.join(', ')}`
      });
    }

    // Check if schedule name already exists for this sede
    const existingSchedule = await db.query(
      'SELECT ID_HORARIO FROM HORARIO WHERE NOMBRE = ? AND ID_SEDE = ?',
      [nombre, id_sede]
    );

    if (existingSchedule.length > 0) {
      return res.status(400).json({
        success: false,
        message: 'Ya existe un horario con este nombre en la sede'
      });
    }

    // Insert new schedule
    const insertQuery = `
      INSERT INTO HORARIO (
        NOMBRE, HORA_ENTRADA, HORA_SALIDA, ID_SEDE, DIAS_LABORALES, ACTIVO
      ) VALUES (?, ?, ?, ?, ?, 1)
    `;

    const result = await db.query(insertQuery, [
      nombre,
      hora_entrada,
      hora_salida,
      id_sede,
      JSON.stringify(dias_laborales)
    ]);

    // Log activity
    await db.query(
      'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
      [req.user.id, 'CREATE_SCHEDULE', `Horario creado: ${nombre}`]
    );

    res.status(201).json({
      success: true,
      message: 'Horario creado exitosamente',
      data: {
        id: result.insertId
      }
    });

  } catch (error) {
    console.error('Create schedule error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * PUT /api/schedules/:id
 * Update schedule
 */
router.put('/:id', [
  authenticateToken,
  requireModuleAccess('schedules'),
  body('nombre').notEmpty().withMessage('Nombre es requerido'),
  body('hora_entrada').matches(/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/).withMessage('Formato de hora de entrada inválido'),
  body('hora_salida').matches(/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/).withMessage('Formato de hora de salida inválido'),
  body('dias_laborales').isArray().withMessage('Días laborales debe ser un array')
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
    const { nombre, hora_entrada, hora_salida, dias_laborales } = req.body;

    // Verify schedule exists and belongs to user's company
    const scheduleQuery = `
      SELECT h.ID_HORARIO
      FROM HORARIO h
      JOIN SEDE s ON h.ID_SEDE = s.ID_SEDE
      WHERE h.ID_HORARIO = ? AND s.ID_EMPRESA = ?
    `;

    const schedules = await db.query(scheduleQuery, [id, req.user.companyId]);

    if (schedules.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Horario no encontrado'
      });
    }

    // Validate dias_laborales
    const validDays = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO'];
    const invalidDays = dias_laborales.filter(day => !validDays.includes(day));
    
    if (invalidDays.length > 0) {
      return res.status(400).json({
        success: false,
        message: `Días inválidos: ${invalidDays.join(', ')}`
      });
    }

    // Update schedule
    const updateQuery = `
      UPDATE HORARIO 
      SET NOMBRE = ?, HORA_ENTRADA = ?, HORA_SALIDA = ?, DIAS_LABORALES = ?
      WHERE ID_HORARIO = ?
    `;

    await db.query(updateQuery, [
      nombre,
      hora_entrada,
      hora_salida,
      JSON.stringify(dias_laborales),
      id
    ]);

    // Log activity
    await db.query(
      'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
      [req.user.id, 'UPDATE_SCHEDULE', `Horario actualizado: ${nombre} (ID: ${id})`]
    );

    res.json({
      success: true,
      message: 'Horario actualizado exitosamente'
    });

  } catch (error) {
    console.error('Update schedule error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * DELETE /api/schedules/:id
 * Delete schedule (mark as inactive)
 */
router.delete('/:id', authenticateToken, requireModuleAccess('schedules'), async (req, res) => {
  try {
    const { id } = req.params;

    // Verify schedule exists and belongs to user's company
    const scheduleQuery = `
      SELECT h.ID_HORARIO, h.NOMBRE
      FROM HORARIO h
      JOIN SEDE s ON h.ID_SEDE = s.ID_SEDE
      WHERE h.ID_HORARIO = ? AND s.ID_EMPRESA = ?
    `;

    const schedules = await db.query(scheduleQuery, [id, req.user.companyId]);

    if (schedules.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Horario no encontrado'
      });
    }

    // Check if there are employees assigned to this schedule
    const assignedEmployees = await db.query(
      'SELECT COUNT(*) as count FROM EMPLEADO WHERE ID_HORARIO = ? AND ACTIVO = "S"',
      [id]
    );

    if (assignedEmployees[0].count > 0) {
      return res.status(400).json({
        success: false,
        message: `No se puede eliminar el horario porque tiene ${assignedEmployees[0].count} empleados asignados`
      });
    }

    // Soft delete schedule
    await db.query(
      'UPDATE HORARIO SET ACTIVO = 0 WHERE ID_HORARIO = ?',
      [id]
    );

    const schedule = schedules[0];

    // Log activity
    await db.query(
      'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
      [req.user.id, 'DELETE_SCHEDULE', `Horario eliminado: ${schedule.NOMBRE} (ID: ${id})`]
    );

    res.json({
      success: true,
      message: 'Horario eliminado exitosamente'
    });

  } catch (error) {
    console.error('Delete schedule error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * POST /api/schedules/:id/assign-employees
 * Assign employees to schedule
 */
router.post('/:id/assign-employees', [
  authenticateToken,
  requireModuleAccess('schedules'),
  body('employee_ids').isArray().withMessage('employee_ids debe ser un array')
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
    const { employee_ids } = req.body;

    // Verify schedule exists and belongs to user's company
    const scheduleQuery = `
      SELECT h.ID_HORARIO, h.NOMBRE
      FROM HORARIO h
      JOIN SEDE s ON h.ID_SEDE = s.ID_SEDE
      WHERE h.ID_HORARIO = ? AND s.ID_EMPRESA = ?
    `;

    const schedules = await db.query(scheduleQuery, [id, req.user.companyId]);

    if (schedules.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Horario no encontrado'
      });
    }

    // Verify all employees exist and belong to user's company
    if (employee_ids.length > 0) {
      const employeesQuery = `
        SELECT e.ID_EMPLEADO
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO IN (${employee_ids.map(() => '?').join(',')}) 
        AND s.ID_EMPRESA = ? AND e.ACTIVO = 'S'
      `;

      const employees = await db.query(employeesQuery, [...employee_ids, req.user.companyId]);

      if (employees.length !== employee_ids.length) {
        return res.status(400).json({
          success: false,
          message: 'Algunos empleados no fueron encontrados o no pertenecen a su empresa'
        });
      }
    }

    // Update employees with new schedule
    if (employee_ids.length > 0) {
      const updateQuery = `
        UPDATE EMPLEADO 
        SET ID_HORARIO = ? 
        WHERE ID_EMPLEADO IN (${employee_ids.map(() => '?').join(',')})
      `;

      await db.query(updateQuery, [id, ...employee_ids]);
    }

    const schedule = schedules[0];

    // Log activity
    await db.query(
      'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
      [req.user.id, 'ASSIGN_SCHEDULE', `Empleados asignados al horario: ${schedule.NOMBRE} (${employee_ids.length} empleados)`]
    );

    res.json({
      success: true,
      message: `${employee_ids.length} empleados asignados al horario exitosamente`
    });

  } catch (error) {
    console.error('Assign employees to schedule error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * DELETE /api/schedules/:id/unassign-employees
 * Remove employees from schedule
 */
router.delete('/:id/unassign-employees', [
  authenticateToken,
  requireModuleAccess('schedules'),
  body('employee_ids').isArray().withMessage('employee_ids debe ser un array')
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
    const { employee_ids } = req.body;

    // Verify schedule exists and belongs to user's company
    const scheduleQuery = `
      SELECT h.ID_HORARIO, h.NOMBRE
      FROM HORARIO h
      JOIN SEDE s ON h.ID_SEDE = s.ID_SEDE
      WHERE h.ID_HORARIO = ? AND s.ID_EMPRESA = ?
    `;

    const schedules = await db.query(scheduleQuery, [id, req.user.companyId]);

    if (schedules.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Horario no encontrado'
      });
    }

    // Remove employees from schedule
    if (employee_ids.length > 0) {
      const updateQuery = `
        UPDATE EMPLEADO 
        SET ID_HORARIO = NULL 
        WHERE ID_EMPLEADO IN (${employee_ids.map(() => '?').join(',')}) AND ID_HORARIO = ?
      `;

      await db.query(updateQuery, [...employee_ids, id]);
    }

    const schedule = schedules[0];

    // Log activity
    await db.query(
      'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
      [req.user.id, 'UNASSIGN_SCHEDULE', `Empleados removidos del horario: ${schedule.NOMBRE} (${employee_ids.length} empleados)`]
    );

    res.json({
      success: true,
      message: `${employee_ids.length} empleados removidos del horario exitosamente`
    });

  } catch (error) {
    console.error('Unassign employees from schedule error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

module.exports = router;