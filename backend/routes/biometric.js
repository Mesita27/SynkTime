const express = require('express');
const { body, validationResult } = require('express-validator');
const db = require('../config/database');
const { authenticateToken, requireModuleAccess } = require('../middleware/auth');

const router = express.Router();

/**
 * GET /api/biometric/stats
 * Get biometric enrollment statistics
 */
router.get('/stats', authenticateToken, requireModuleAccess('biometric'), async (req, res) => {
  try {
    // Get total employees in company
    const totalEmployeesQuery = `
      SELECT COUNT(*) as total
      FROM EMPLEADO e
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      WHERE s.ID_EMPRESA = ? AND e.ACTIVO = 'S' AND e.ESTADO = 'A'
    `;

    const totalResult = await db.query(totalEmployeesQuery, [req.user.companyId]);
    const totalEmployees = totalResult[0].total;

    // Get employees with biometric data
    const biometricQuery = `
      SELECT 
        COUNT(DISTINCT bd.ID_EMPLEADO) as enrolled,
        SUM(CASE WHEN bd.BIOMETRIC_TYPE = 'fingerprint' THEN 1 ELSE 0 END) as fingerprint_count,
        SUM(CASE WHEN bd.BIOMETRIC_TYPE = 'facial' THEN 1 ELSE 0 END) as facial_count
      FROM biometric_data bd
      JOIN EMPLEADO e ON bd.ID_EMPLEADO = e.ID_EMPLEADO
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      WHERE s.ID_EMPRESA = ? AND bd.ACTIVO = 1 AND e.ACTIVO = 'S'
    `;

    const biometricResult = await db.query(biometricQuery, [req.user.companyId]);
    const stats = biometricResult[0];

    // Get enrollment by establishment
    const establishmentQuery = `
      SELECT 
        est.NOMBRE as establecimiento,
        COUNT(DISTINCT e.ID_EMPLEADO) as total_employees,
        COUNT(DISTINCT bd.ID_EMPLEADO) as enrolled_employees
      FROM ESTABLECIMIENTO est
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      JOIN EMPLEADO e ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      LEFT JOIN biometric_data bd ON bd.ID_EMPLEADO = e.ID_EMPLEADO AND bd.ACTIVO = 1
      WHERE s.ID_EMPRESA = ? AND e.ACTIVO = 'S' AND e.ESTADO = 'A'
      GROUP BY est.ID_ESTABLECIMIENTO, est.NOMBRE
      ORDER BY est.NOMBRE
    `;

    const establishmentStats = await db.query(establishmentQuery, [req.user.companyId]);

    res.json({
      success: true,
      data: {
        totalEmployees,
        enrolledEmployees: stats.enrolled || 0,
        fingerprintEnrollments: stats.fingerprint_count || 0,
        facialEnrollments: stats.facial_count || 0,
        enrollmentPercentage: totalEmployees > 0 ? Math.round((stats.enrolled || 0) / totalEmployees * 100) : 0,
        establishmentStats
      }
    });

  } catch (error) {
    console.error('Get biometric stats error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * GET /api/biometric/employee/:id/summary
 * Get biometric enrollment summary for specific employee
 */
router.get('/employee/:id/summary', authenticateToken, requireModuleAccess('biometric'), async (req, res) => {
  try {
    const { id } = req.params;

    // Verify employee exists and belongs to user's company
    const employeeQuery = `
      SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO
      FROM EMPLEADO e
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      WHERE e.ID_EMPLEADO = ? AND s.ID_EMPRESA = ? AND e.ACTIVO = 'S'
    `;

    const employees = await db.query(employeeQuery, [id, req.user.companyId]);

    if (employees.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Empleado no encontrado'
      });
    }

    // Get biometric data for employee
    const biometricQuery = `
      SELECT 
        ID,
        BIOMETRIC_TYPE,
        FINGER_TYPE,
        CREATED_AT,
        UPDATED_AT,
        ACTIVO
      FROM biometric_data
      WHERE ID_EMPLEADO = ? AND ACTIVO = 1
      ORDER BY BIOMETRIC_TYPE, FINGER_TYPE
    `;

    const biometricData = await db.query(biometricQuery, [id]);

    // Organize data by type
    const summary = {
      employee: employees[0],
      fingerprints: biometricData.filter(item => item.BIOMETRIC_TYPE === 'fingerprint'),
      facial: biometricData.filter(item => item.BIOMETRIC_TYPE === 'facial'),
      totalEnrollments: biometricData.length,
      hasFingerprint: biometricData.some(item => item.BIOMETRIC_TYPE === 'fingerprint'),
      hasFacial: biometricData.some(item => item.BIOMETRIC_TYPE === 'facial')
    };

    res.json({
      success: true,
      data: summary
    });

  } catch (error) {
    console.error('Get employee biometric summary error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * POST /api/biometric/enroll/fingerprint
 * Enroll fingerprint for employee
 */
router.post('/enroll/fingerprint', [
  authenticateToken,
  requireModuleAccess('biometric'),
  body('employee_id').isInt().withMessage('ID de empleado es requerido'),
  body('finger_type').notEmpty().withMessage('Tipo de dedo es requerido'),
  body('fingerprint_data').notEmpty().withMessage('Datos de huella dactilar son requeridos')
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

    const { employee_id, finger_type, fingerprint_data } = req.body;

    // Validate finger type
    const validFingers = [
      'left_thumb', 'left_index', 'left_middle', 'left_ring', 'left_pinky',
      'right_thumb', 'right_index', 'right_middle', 'right_ring', 'right_pinky'
    ];

    if (!validFingers.includes(finger_type)) {
      return res.status(400).json({
        success: false,
        message: 'Tipo de dedo inválido'
      });
    }

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

    // Check if fingerprint already exists for this finger
    const existingQuery = `
      SELECT ID FROM biometric_data 
      WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = 'fingerprint' AND FINGER_TYPE = ? AND ACTIVO = 1
    `;

    const existing = await db.query(existingQuery, [employee_id, finger_type]);

    if (existing.length > 0) {
      // Update existing fingerprint
      await db.query(
        'UPDATE biometric_data SET BIOMETRIC_DATA = ?, UPDATED_AT = CURRENT_TIMESTAMP WHERE ID = ?',
        [fingerprint_data, existing[0].ID]
      );

      // Log activity
      await db.query(
        'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
        [req.user.id, 'UPDATE_FINGERPRINT', `Huella actualizada: Empleado ${employee_id}, Dedo: ${finger_type}`]
      );

      res.json({
        success: true,
        message: 'Huella dactilar actualizada exitosamente',
        data: { id: existing[0].ID, action: 'updated' }
      });
    } else {
      // Create new fingerprint enrollment
      const insertQuery = `
        INSERT INTO biometric_data (ID_EMPLEADO, BIOMETRIC_TYPE, FINGER_TYPE, BIOMETRIC_DATA, ACTIVO)
        VALUES (?, 'fingerprint', ?, ?, 1)
      `;

      const result = await db.query(insertQuery, [employee_id, finger_type, fingerprint_data]);

      // Log activity
      await db.query(
        'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
        [req.user.id, 'ENROLL_FINGERPRINT', `Huella registrada: Empleado ${employee_id}, Dedo: ${finger_type}`]
      );

      res.status(201).json({
        success: true,
        message: 'Huella dactilar registrada exitosamente',
        data: { id: result.insertId, action: 'created' }
      });
    }

  } catch (error) {
    console.error('Fingerprint enrollment error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * POST /api/biometric/enroll/facial
 * Enroll facial data for employee
 */
router.post('/enroll/facial', [
  authenticateToken,
  requireModuleAccess('biometric'),
  body('employee_id').isInt().withMessage('ID de empleado es requerido'),
  body('facial_data').notEmpty().withMessage('Datos faciales son requeridos')
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

    const { employee_id, facial_data } = req.body;

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

    // Check if facial data already exists
    const existingQuery = `
      SELECT ID FROM biometric_data 
      WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = 'facial' AND ACTIVO = 1
    `;

    const existing = await db.query(existingQuery, [employee_id]);

    if (existing.length > 0) {
      // Update existing facial data
      await db.query(
        'UPDATE biometric_data SET BIOMETRIC_DATA = ?, UPDATED_AT = CURRENT_TIMESTAMP WHERE ID = ?',
        [facial_data, existing[0].ID]
      );

      // Log activity
      await db.query(
        'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
        [req.user.id, 'UPDATE_FACIAL', `Datos faciales actualizados: Empleado ${employee_id}`]
      );

      res.json({
        success: true,
        message: 'Datos faciales actualizados exitosamente',
        data: { id: existing[0].ID, action: 'updated' }
      });
    } else {
      // Create new facial enrollment
      const insertQuery = `
        INSERT INTO biometric_data (ID_EMPLEADO, BIOMETRIC_TYPE, BIOMETRIC_DATA, ACTIVO)
        VALUES (?, 'facial', ?, 1)
      `;

      const result = await db.query(insertQuery, [employee_id, facial_data]);

      // Log activity
      await db.query(
        'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
        [req.user.id, 'ENROLL_FACIAL', `Datos faciales registrados: Empleado ${employee_id}`]
      );

      res.status(201).json({
        success: true,
        message: 'Datos faciales registrados exitosamente',
        data: { id: result.insertId, action: 'created' }
      });
    }

  } catch (error) {
    console.error('Facial enrollment error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * POST /api/biometric/verify
 * Verify biometric data for attendance
 */
router.post('/verify', [
  authenticateToken,
  requireModuleAccess('attendance'),
  body('employee_id').isInt().withMessage('ID de empleado es requerido'),
  body('verification_type').isIn(['fingerprint', 'facial']).withMessage('Tipo de verificación inválido'),
  body('biometric_data').notEmpty().withMessage('Datos biométricos son requeridos')
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

    const { employee_id, verification_type, biometric_data, finger_type } = req.body;

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

    // Get stored biometric data
    let storedDataQuery = `
      SELECT BIOMETRIC_DATA FROM biometric_data 
      WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = ? AND ACTIVO = 1
    `;
    let queryParams = [employee_id, verification_type];

    if (verification_type === 'fingerprint' && finger_type) {
      storedDataQuery += ' AND FINGER_TYPE = ?';
      queryParams.push(finger_type);
    }

    const storedData = await db.query(storedDataQuery, queryParams);

    if (storedData.length === 0) {
      // Log failed verification
      await db.query(
        'INSERT INTO biometric_logs (ID_EMPLEADO, VERIFICATION_METHOD, VERIFICATION_SUCCESS, FECHA, HORA) VALUES (?, ?, 0, CURDATE(), CURTIME())',
        [employee_id, verification_type]
      );

      return res.status(400).json({
        success: false,
        message: 'No hay datos biométricos registrados para este empleado'
      });
    }

    // For demo purposes, we'll do a simple comparison
    // In a real implementation, you would use proper biometric matching algorithms
    const isMatch = storedData.some(record => {
      // Simple string comparison for demo
      // In production, use proper biometric matching
      return record.BIOMETRIC_DATA === biometric_data;
    });

    // Log verification attempt
    await db.query(
      'INSERT INTO biometric_logs (ID_EMPLEADO, VERIFICATION_METHOD, VERIFICATION_SUCCESS, FECHA, HORA) VALUES (?, ?, ?, CURDATE(), CURTIME())',
      [employee_id, verification_type, isMatch ? 1 : 0]
    );

    if (isMatch) {
      res.json({
        success: true,
        message: 'Verificación biométrica exitosa',
        data: {
          employee: employees[0],
          verified: true,
          verification_type
        }
      });
    } else {
      res.status(400).json({
        success: false,
        message: 'Verificación biométrica fallida',
        data: {
          verified: false,
          verification_type
        }
      });
    }

  } catch (error) {
    console.error('Biometric verification error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * DELETE /api/biometric/employee/:id
 * Delete all biometric data for employee
 */
router.delete('/employee/:id', authenticateToken, requireModuleAccess('biometric'), async (req, res) => {
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

    // Soft delete biometric data
    await db.query(
      'UPDATE biometric_data SET ACTIVO = 0 WHERE ID_EMPLEADO = ?',
      [id]
    );

    const employee = employees[0];

    // Log activity
    await db.query(
      'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
      [req.user.id, 'DELETE_BIOMETRIC', `Datos biométricos eliminados: ${employee.NOMBRE} ${employee.APELLIDO} (ID: ${id})`]
    );

    res.json({
      success: true,
      message: 'Datos biométricos eliminados exitosamente'
    });

  } catch (error) {
    console.error('Delete biometric data error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

module.exports = router;