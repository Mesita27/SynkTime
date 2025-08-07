const express = require('express');
const { validationResult } = require('express-validator');
const db = require('../config/database');
const { authenticateToken, requireModuleAccess } = require('../middleware/auth');

const router = express.Router();

/**
 * GET /api/reports/attendance
 * Generate attendance report
 */
router.get('/attendance', authenticateToken, requireModuleAccess('reports'), async (req, res) => {
  try {
    const { 
      start_date, 
      end_date, 
      employee_id, 
      establecimiento,
      sede,
      format = 'json' 
    } = req.query;

    if (!start_date || !end_date) {
      return res.status(400).json({
        success: false,
        message: 'Fechas de inicio y fin son requeridas'
      });
    }

    let whereConditions = ['emp.ID_EMPRESA = ?', 'a.FECHA BETWEEN ? AND ?'];
    let queryParams = [req.user.companyId, start_date, end_date];

    // Add filters
    if (employee_id) {
      whereConditions.push('a.ID_EMPLEADO = ?');
      queryParams.push(employee_id);
    }

    if (establecimiento) {
      whereConditions.push('est.ID_ESTABLECIMIENTO = ?');
      queryParams.push(establecimiento);
    }

    if (sede) {
      whereConditions.push('s.ID_SEDE = ?');
      queryParams.push(sede);
    }

    const whereClause = whereConditions.join(' AND ');

    // Get attendance data
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
        s.NOMBRE as SEDE_NOMBRE,
        CASE 
          WHEN a.HORA_SALIDA IS NOT NULL THEN 
            TIME_TO_SEC(TIMEDIFF(a.HORA_SALIDA, a.HORA_ENTRADA)) / 3600
          ELSE NULL 
        END as HORAS_TRABAJADAS
      FROM asistencias a
      JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
      WHERE ${whereClause}
      ORDER BY a.FECHA DESC, e.NOMBRE, e.APELLIDO
    `;

    const attendanceData = await db.query(attendanceQuery, queryParams);

    // Calculate summary statistics
    const summaryQuery = `
      SELECT 
        COUNT(*) as total_registros,
        COUNT(DISTINCT a.ID_EMPLEADO) as empleados_distintos,
        COUNT(DISTINCT a.FECHA) as dias_distintos,
        SUM(CASE WHEN a.HORA_SALIDA IS NOT NULL THEN 1 ELSE 0 END) as registros_completos,
        AVG(CASE 
          WHEN a.HORA_SALIDA IS NOT NULL THEN 
            TIME_TO_SEC(TIMEDIFF(a.HORA_SALIDA, a.HORA_ENTRADA)) / 3600
          ELSE NULL 
        END) as promedio_horas_diarias,
        SUM(CASE 
          WHEN a.HORA_SALIDA IS NOT NULL THEN 
            TIME_TO_SEC(TIMEDIFF(a.HORA_SALIDA, a.HORA_ENTRADA)) / 3600
          ELSE 0 
        END) as total_horas_trabajadas
      FROM asistencias a
      JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
      WHERE ${whereClause}
    `;

    const summary = await db.query(summaryQuery, queryParams);

    const reportData = {
      period: {
        start_date,
        end_date
      },
      summary: summary[0],
      attendance: attendanceData
    };

    if (format === 'csv') {
      // Generate CSV format
      const csvHeaders = [
        'Fecha', 'Empleado', 'DNI', 'Establecimiento', 'Sede',
        'Hora Entrada', 'Hora Salida', 'Horas Trabajadas', 'Estado', 'Método Verificación'
      ];

      const csvRows = attendanceData.map(record => [
        record.FECHA,
        `${record.EMPLEADO_NOMBRE} ${record.EMPLEADO_APELLIDO}`,
        record.EMPLEADO_DNI,
        record.ESTABLECIMIENTO_NOMBRE,
        record.SEDE_NOMBRE,
        record.HORA_ENTRADA || '',
        record.HORA_SALIDA || '',
        record.HORAS_TRABAJADAS ? record.HORAS_TRABAJADAS.toFixed(2) : '',
        record.ESTADO,
        record.VERIFICATION_METHOD || ''
      ]);

      const csvContent = [csvHeaders, ...csvRows]
        .map(row => row.map(field => `"${field}"`).join(','))
        .join('\n');

      res.setHeader('Content-Type', 'text/csv');
      res.setHeader('Content-Disposition', `attachment; filename="reporte_asistencia_${start_date}_${end_date}.csv"`);
      res.send(csvContent);
    } else {
      // Return JSON format
      res.json({
        success: true,
        data: reportData
      });
    }

  } catch (error) {
    console.error('Generate attendance report error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * GET /api/reports/employee-summary
 * Generate employee summary report
 */
router.get('/employee-summary', authenticateToken, requireModuleAccess('reports'), async (req, res) => {
  try {
    const { start_date, end_date, establecimiento, sede } = req.query;

    if (!start_date || !end_date) {
      return res.status(400).json({
        success: false,
        message: 'Fechas de inicio y fin son requeridas'
      });
    }

    let whereConditions = ['emp.ID_EMPRESA = ?'];
    let queryParams = [req.user.companyId];

    // Add filters
    if (establecimiento) {
      whereConditions.push('est.ID_ESTABLECIMIENTO = ?');
      queryParams.push(establecimiento);
    }

    if (sede) {
      whereConditions.push('s.ID_SEDE = ?');
      queryParams.push(sede);
    }

    const whereClause = whereConditions.join(' AND ');

    // Get employee summary
    const summaryQuery = `
      SELECT 
        e.ID_EMPLEADO,
        e.NOMBRE,
        e.APELLIDO,
        e.DNI,
        est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
        s.NOMBRE as SEDE_NOMBRE,
        COUNT(a.ID_ASISTENCIA) as dias_asistidos,
        SUM(CASE WHEN a.HORA_SALIDA IS NOT NULL THEN 1 ELSE 0 END) as dias_completos,
        SUM(CASE 
          WHEN a.HORA_SALIDA IS NOT NULL THEN 
            TIME_TO_SEC(TIMEDIFF(a.HORA_SALIDA, a.HORA_ENTRADA)) / 3600
          ELSE 0 
        END) as total_horas_trabajadas,
        AVG(CASE 
          WHEN a.HORA_SALIDA IS NOT NULL THEN 
            TIME_TO_SEC(TIMEDIFF(a.HORA_SALIDA, a.HORA_ENTRADA)) / 3600
          ELSE NULL 
        END) as promedio_horas_diarias,
        COUNT(CASE WHEN a.ESTADO = 'TARDANZA' THEN 1 END) as dias_tardanza,
        COUNT(CASE WHEN a.VERIFICATION_METHOD = 'fingerprint' THEN 1 END) as verificaciones_huella,
        COUNT(CASE WHEN a.VERIFICATION_METHOD = 'facial' THEN 1 END) as verificaciones_facial,
        COUNT(CASE WHEN a.VERIFICATION_METHOD = 'traditional' THEN 1 END) as verificaciones_tradicional
      FROM EMPLEADO e
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
      LEFT JOIN asistencias a ON e.ID_EMPLEADO = a.ID_EMPLEADO 
        AND a.FECHA BETWEEN ? AND ?
      WHERE ${whereClause} AND e.ACTIVO = 'S'
      GROUP BY e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, e.DNI, est.NOMBRE, s.NOMBRE
      ORDER BY e.NOMBRE, e.APELLIDO
    `;

    const employeeSummary = await db.query(summaryQuery, [start_date, end_date, ...queryParams]);

    // Calculate total working days in period
    const totalDaysQuery = `
      SELECT COUNT(DISTINCT FECHA) as total_dias
      FROM asistencias a
      JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
      WHERE emp.ID_EMPRESA = ? AND a.FECHA BETWEEN ? AND ?
    `;

    const totalDaysResult = await db.query(totalDaysQuery, [req.user.companyId, start_date, end_date]);
    const totalWorkingDays = totalDaysResult[0].total_dias || 0;

    // Add attendance percentage to each employee
    const enrichedSummary = employeeSummary.map(employee => ({
      ...employee,
      porcentaje_asistencia: totalWorkingDays > 0 ? 
        Math.round((employee.dias_asistidos / totalWorkingDays) * 100) : 0
    }));

    res.json({
      success: true,
      data: {
        period: {
          start_date,
          end_date,
          total_working_days: totalWorkingDays
        },
        employees: enrichedSummary
      }
    });

  } catch (error) {
    console.error('Generate employee summary report error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * GET /api/reports/biometric-usage
 * Generate biometric usage report
 */
router.get('/biometric-usage', authenticateToken, requireModuleAccess('reports'), async (req, res) => {
  try {
    const { start_date, end_date } = req.query;

    if (!start_date || !end_date) {
      return res.status(400).json({
        success: false,
        message: 'Fechas de inicio y fin son requeridas'
      });
    }

    // Get biometric verification statistics
    const biometricStatsQuery = `
      SELECT 
        bl.VERIFICATION_METHOD,
        COUNT(*) as total_intentos,
        SUM(bl.VERIFICATION_SUCCESS) as intentos_exitosos,
        COUNT(DISTINCT bl.ID_EMPLEADO) as empleados_distintos,
        AVG(bl.VERIFICATION_SUCCESS) * 100 as porcentaje_exito
      FROM biometric_logs bl
      JOIN EMPLEADO e ON bl.ID_EMPLEADO = e.ID_EMPLEADO
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      WHERE s.ID_EMPRESA = ? AND bl.FECHA BETWEEN ? AND ?
      GROUP BY bl.VERIFICATION_METHOD
      ORDER BY bl.VERIFICATION_METHOD
    `;

    const biometricStats = await db.query(biometricStatsQuery, [req.user.companyId, start_date, end_date]);

    // Get daily biometric usage
    const dailyUsageQuery = `
      SELECT 
        bl.FECHA,
        bl.VERIFICATION_METHOD,
        COUNT(*) as total_intentos,
        SUM(bl.VERIFICATION_SUCCESS) as intentos_exitosos
      FROM biometric_logs bl
      JOIN EMPLEADO e ON bl.ID_EMPLEADO = e.ID_EMPLEADO
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      WHERE s.ID_EMPRESA = ? AND bl.FECHA BETWEEN ? AND ?
      GROUP BY bl.FECHA, bl.VERIFICATION_METHOD
      ORDER BY bl.FECHA DESC, bl.VERIFICATION_METHOD
    `;

    const dailyUsage = await db.query(dailyUsageQuery, [req.user.companyId, start_date, end_date]);

    // Get enrollment statistics
    const enrollmentStatsQuery = `
      SELECT 
        COUNT(DISTINCT bd.ID_EMPLEADO) as empleados_con_biometria,
        COUNT(DISTINCT e.ID_EMPLEADO) as total_empleados,
        SUM(CASE WHEN bd.BIOMETRIC_TYPE = 'fingerprint' THEN 1 ELSE 0 END) as registros_huella,
        SUM(CASE WHEN bd.BIOMETRIC_TYPE = 'facial' THEN 1 ELSE 0 END) as registros_facial
      FROM EMPLEADO e
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      LEFT JOIN biometric_data bd ON e.ID_EMPLEADO = bd.ID_EMPLEADO AND bd.ACTIVO = 1
      WHERE s.ID_EMPRESA = ? AND e.ACTIVO = 'S'
    `;

    const enrollmentStats = await db.query(enrollmentStatsQuery, [req.user.companyId]);

    res.json({
      success: true,
      data: {
        period: {
          start_date,
          end_date
        },
        verification_stats: biometricStats,
        daily_usage: dailyUsage,
        enrollment_stats: {
          ...enrollmentStats[0],
          porcentaje_enrollado: enrollmentStats[0].total_empleados > 0 ? 
            Math.round((enrollmentStats[0].empleados_con_biometria / enrollmentStats[0].total_empleados) * 100) : 0
        }
      }
    });

  } catch (error) {
    console.error('Generate biometric usage report error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * GET /api/reports/dashboard-stats
 * Get dashboard statistics
 */
router.get('/dashboard-stats', authenticateToken, async (req, res) => {
  try {
    const today = new Date().toISOString().split('T')[0];

    // Today's attendance
    const todayAttendanceQuery = `
      SELECT 
        COUNT(*) as total_asistencias_hoy,
        COUNT(DISTINCT a.ID_EMPLEADO) as empleados_presentes_hoy,
        SUM(CASE WHEN a.HORA_SALIDA IS NULL THEN 1 ELSE 0 END) as empleados_sin_salida
      FROM asistencias a
      JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      WHERE s.ID_EMPRESA = ? AND a.FECHA = ?
    `;

    const todayStats = await db.query(todayAttendanceQuery, [req.user.companyId, today]);

    // Total employees
    const totalEmployeesQuery = `
      SELECT COUNT(*) as total_empleados
      FROM EMPLEADO e
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      WHERE s.ID_EMPRESA = ? AND e.ACTIVO = 'S' AND e.ESTADO = 'A'
    `;

    const totalEmployees = await db.query(totalEmployeesQuery, [req.user.companyId]);

    // Biometric enrollment stats
    const biometricStatsQuery = `
      SELECT 
        COUNT(DISTINCT bd.ID_EMPLEADO) as empleados_con_biometria,
        SUM(CASE WHEN bd.BIOMETRIC_TYPE = 'fingerprint' THEN 1 ELSE 0 END) as huellas_registradas,
        SUM(CASE WHEN bd.BIOMETRIC_TYPE = 'facial' THEN 1 ELSE 0 END) as caras_registradas
      FROM biometric_data bd
      JOIN EMPLEADO e ON bd.ID_EMPLEADO = e.ID_EMPLEADO
      JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
      JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
      WHERE s.ID_EMPRESA = ? AND bd.ACTIVO = 1 AND e.ACTIVO = 'S'
    `;

    const biometricStats = await db.query(biometricStatsQuery, [req.user.companyId]);

    // Recent activity
    const recentActivityQuery = `
      SELECT 
        l.ACCION,
        l.DETALLE,
        l.FECHA,
        u.NOMBRE_COMPLETO as USUARIO
      FROM LOG l
      JOIN usuario u ON l.ID_USUARIO = u.ID_USUARIO
      WHERE u.ID_EMPRESA = ?
      ORDER BY l.FECHA DESC
      LIMIT 10
    `;

    const recentActivity = await db.query(recentActivityQuery, [req.user.companyId]);

    res.json({
      success: true,
      data: {
        today: {
          ...todayStats[0],
          total_empleados: totalEmployees[0].total_empleados,
          porcentaje_asistencia: totalEmployees[0].total_empleados > 0 ? 
            Math.round((todayStats[0].empleados_presentes_hoy / totalEmployees[0].total_empleados) * 100) : 0
        },
        biometric: {
          ...biometricStats[0],
          porcentaje_enrollado: totalEmployees[0].total_empleados > 0 ? 
            Math.round((biometricStats[0].empleados_con_biometria / totalEmployees[0].total_empleados) * 100) : 0
        },
        recent_activity: recentActivity
      }
    });

  } catch (error) {
    console.error('Get dashboard stats error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

module.exports = router;