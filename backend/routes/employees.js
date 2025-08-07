const express = require('express');
const { body, validationResult } = require('express-validator');
const db = require('../config/database');
const { authenticateToken, requireModuleAccess } = require('../middleware/auth');

const router = express.Router();

/**
 * GET /api/employees
 * Get list of employees
 */
router.get('/', authenticateToken, requireModuleAccess('employees'), async (req, res) => {
  try {
    const { page = 1, limit = 10, search = '', establecimiento = '', sede = '' } = req.query;
    const offset = (page - 1) * limit;

    let whereConditions = ['emp.ID_EMPRESA = ?'];
    let queryParams = [req.user.companyId];

    // Add search condition
    if (search) {
      whereConditions.push('(e.NOMBRE LIKE ? OR e.APELLIDO LIKE ? OR e.DNI LIKE ?)');
      queryParams.push(`%${search}%`, `%${search}%`, `%${search}%`);
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
      FROM EMPLEADO e
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
      ${whereClause}
      AND e.ESTADO = 'A' AND e.ACTIVO = 'S'
    `;

    const countResult = await db.query(countQuery, queryParams);
    const total = countResult[0].total;

    // Get employees
    const employeesQuery = `
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
        s.ID_SEDE
      FROM EMPLEADO e
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
      ${whereClause}
      AND e.ESTADO = 'A' AND e.ACTIVO = 'S'
      ORDER BY e.NOMBRE, e.APELLIDO
      LIMIT ? OFFSET ?
    `;

    const employees = await db.query(employeesQuery, [...queryParams, parseInt(limit), parseInt(offset)]);

    res.json({
      success: true,
      data: {
        employees,
        pagination: {
          page: parseInt(page),
          limit: parseInt(limit),
          total,
          totalPages: Math.ceil(total / limit)
        }
      }
    });

  } catch (error) {
    console.error('Get employees error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * GET /api/employees/:id
 * Get single employee by ID
 */
router.get('/:id', authenticateToken, requireModuleAccess('employees'), async (req, res) => {
  try {
    const { id } = req.params;

    const employeeQuery = `
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
        s.ID_SEDE
      FROM EMPLEADO e
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
      WHERE e.ID_EMPLEADO = ? AND emp.ID_EMPRESA = ?
    `;

    const employees = await db.query(employeeQuery, [id, req.user.companyId]);

    if (employees.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Empleado no encontrado'
      });
    }

    res.json({
      success: true,
      data: employees[0]
    });

  } catch (error) {
    console.error('Get employee error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * POST /api/employees
 * Create new employee
 */
router.post('/', [
  authenticateToken,
  requireModuleAccess('employees'),
  body('nombre').notEmpty().withMessage('Nombre es requerido'),
  body('apellido').notEmpty().withMessage('Apellido es requerido'),
  body('dni').notEmpty().withMessage('DNI es requerido'),
  body('correo').isEmail().withMessage('Email válido es requerido'),
  body('id_establecimiento').isInt().withMessage('Establecimiento es requerido')
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
      apellido,
      dni,
      correo,
      telefono,
      fecha_ingreso,
      id_establecimiento
    } = req.body;

    // Verify establishment belongs to user's company
    const estQuery = `
      SELECT est.ID_ESTABLECIMIENTO
      FROM ESTABLECIMIENTO est
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      WHERE est.ID_ESTABLECIMIENTO = ? AND s.ID_EMPRESA = ?
    `;

    const establishments = await db.query(estQuery, [id_establecimiento, req.user.companyId]);

    if (establishments.length === 0) {
      return res.status(400).json({
        success: false,
        message: 'Establecimiento inválido'
      });
    }

    // Check if DNI already exists
    const existingEmployee = await db.query(
      'SELECT ID_EMPLEADO FROM EMPLEADO WHERE DNI = ?',
      [dni]
    );

    if (existingEmployee.length > 0) {
      return res.status(400).json({
        success: false,
        message: 'Ya existe un empleado con este DNI'
      });
    }

    // Insert new employee
    const insertQuery = `
      INSERT INTO EMPLEADO (
        NOMBRE, APELLIDO, DNI, CORREO, TELEFONO, 
        FECHA_INGRESO, ID_ESTABLECIMIENTO, ESTADO, ACTIVO
      ) VALUES (?, ?, ?, ?, ?, ?, ?, 'A', 'S')
    `;

    const result = await db.query(insertQuery, [
      nombre,
      apellido,
      dni,
      correo,
      telefono || null,
      fecha_ingreso || new Date().toISOString().split('T')[0],
      id_establecimiento
    ]);

    // Log activity
    await db.query(
      'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
      [req.user.id, 'CREATE_EMPLOYEE', `Empleado creado: ${nombre} ${apellido} (DNI: ${dni})`]
    );

    res.status(201).json({
      success: true,
      message: 'Empleado creado exitosamente',
      data: {
        id: result.insertId
      }
    });

  } catch (error) {
    console.error('Create employee error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * PUT /api/employees/:id
 * Update employee
 */
router.put('/:id', [
  authenticateToken,
  requireModuleAccess('employees'),
  body('nombre').notEmpty().withMessage('Nombre es requerido'),
  body('apellido').notEmpty().withMessage('Apellido es requerido'),
  body('correo').isEmail().withMessage('Email válido es requerido')
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
    const { nombre, apellido, correo, telefono, id_establecimiento } = req.body;

    // Verify employee exists and belongs to user's company
    const employeeQuery = `
      SELECT e.ID_EMPLEADO
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

    // Verify establishment if provided
    if (id_establecimiento) {
      const estQuery = `
        SELECT est.ID_ESTABLECIMIENTO
        FROM ESTABLECIMIENTO est
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE est.ID_ESTABLECIMIENTO = ? AND s.ID_EMPRESA = ?
      `;

      const establishments = await db.query(estQuery, [id_establecimiento, req.user.companyId]);

      if (establishments.length === 0) {
        return res.status(400).json({
          success: false,
          message: 'Establecimiento inválido'
        });
      }
    }

    // Update employee
    let updateQuery = 'UPDATE EMPLEADO SET NOMBRE = ?, APELLIDO = ?, CORREO = ?, TELEFONO = ?';
    let updateParams = [nombre, apellido, correo, telefono || null];

    if (id_establecimiento) {
      updateQuery += ', ID_ESTABLECIMIENTO = ?';
      updateParams.push(id_establecimiento);
    }

    updateQuery += ' WHERE ID_EMPLEADO = ?';
    updateParams.push(id);

    await db.query(updateQuery, updateParams);

    // Log activity
    await db.query(
      'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
      [req.user.id, 'UPDATE_EMPLOYEE', `Empleado actualizado: ID ${id}`]
    );

    res.json({
      success: true,
      message: 'Empleado actualizado exitosamente'
    });

  } catch (error) {
    console.error('Update employee error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * DELETE /api/employees/:id
 * Soft delete employee (mark as inactive)
 */
router.delete('/:id', authenticateToken, requireModuleAccess('employees'), async (req, res) => {
  try {
    const { id } = req.params;

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

    // Soft delete employee
    await db.query(
      'UPDATE EMPLEADO SET ACTIVO = "N", ESTADO = "I" WHERE ID_EMPLEADO = ?',
      [id]
    );

    const employee = employees[0];

    // Log activity
    await db.query(
      'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
      [req.user.id, 'DELETE_EMPLOYEE', `Empleado eliminado: ${employee.NOMBRE} ${employee.APELLIDO} (ID: ${id})`]
    );

    res.json({
      success: true,
      message: 'Empleado eliminado exitosamente'
    });

  } catch (error) {
    console.error('Delete employee error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

module.exports = router;