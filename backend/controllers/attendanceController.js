const AttendanceService = require('../services/attendanceService');
const { validationResult } = require('express-validator');

class AttendanceController {
  /**
   * Register attendance
   */
  async registerAttendance(req, res) {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({
          success: false,
          message: 'Datos inválidos',
          errors: errors.array()
        });
      }

      const result = await AttendanceService.registerAttendance(req.body);

      res.status(201).json({
        success: true,
        message: result.message,
        data: result
      });
    } catch (error) {
      console.error('Error registering attendance:', error);
      res.status(500).json({
        success: false,
        message: error.message || 'Error registrando asistencia'
      });
    }
  }

  /**
   * Get attendance records
   */
  async getAttendanceRecords(req, res) {
    try {
      const empresaId = req.userInfo?.ID_EMPRESA;

      if (!empresaId) {
        return res.status(400).json({
          success: false,
          message: 'ID de empresa requerido'
        });
      }

      const filters = {
        fecha_desde: req.query.fecha_desde,
        fecha_hasta: req.query.fecha_hasta,
        empleado_id: req.query.empleado_id,
        establecimiento_id: req.query.establecimiento_id,
        tipo_asistencia: req.query.tipo_asistencia,
        limit: req.query.limit,
        offset: req.query.offset
      };

      const records = await AttendanceService.getAttendanceRecords(empresaId, filters);

      res.json({
        success: true,
        data: records,
        total: records.length
      });
    } catch (error) {
      console.error('Error getting attendance records:', error);
      res.status(500).json({
        success: false,
        message: 'Error obteniendo registros de asistencia'
      });
    }
  }

  /**
   * Get attendance summary
   */
  async getAttendanceSummary(req, res) {
    try {
      const empresaId = req.userInfo?.ID_EMPRESA;

      if (!empresaId) {
        return res.status(400).json({
          success: false,
          message: 'ID de empresa requerido'
        });
      }

      const { date } = req.query;
      const summary = await AttendanceService.getAttendanceSummary(empresaId, date);

      res.json({
        success: true,
        data: summary
      });
    } catch (error) {
      console.error('Error getting attendance summary:', error);
      res.status(500).json({
        success: false,
        message: 'Error obteniendo resumen de asistencia'
      });
    }
  }

  /**
   * Get employee attendance history
   */
  async getEmployeeAttendance(req, res) {
    try {
      const { id } = req.params;
      const empresaId = req.userInfo?.ID_EMPRESA;

      if (!empresaId) {
        return res.status(400).json({
          success: false,
          message: 'ID de empresa requerido'
        });
      }

      const filters = {
        fecha_desde: req.query.fecha_desde,
        fecha_hasta: req.query.fecha_hasta,
        limit: req.query.limit,
        offset: req.query.offset
      };

      const attendance = await AttendanceService.getEmployeeAttendance(id, empresaId, filters);

      res.json({
        success: true,
        data: attendance,
        total: attendance.length
      });
    } catch (error) {
      console.error('Error getting employee attendance:', error);
      res.status(500).json({
        success: false,
        message: error.message || 'Error obteniendo asistencia del empleado'
      });
    }
  }

  /**
   * Update attendance record
   */
  async updateAttendance(req, res) {
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
      const empresaId = req.userInfo?.ID_EMPRESA;

      if (!empresaId) {
        return res.status(400).json({
          success: false,
          message: 'ID de empresa requerido'
        });
      }

      const result = await AttendanceService.updateAttendance(id, req.body, empresaId);

      res.json({
        success: true,
        message: result.message
      });
    } catch (error) {
      console.error('Error updating attendance:', error);
      res.status(500).json({
        success: false,
        message: error.message || 'Error actualizando asistencia'
      });
    }
  }

  /**
   * Delete attendance record
   */
  async deleteAttendance(req, res) {
    try {
      const { id } = req.params;
      const empresaId = req.userInfo?.ID_EMPRESA;

      if (!empresaId) {
        return res.status(400).json({
          success: false,
          message: 'ID de empresa requerido'
        });
      }

      const result = await AttendanceService.deleteAttendance(id, empresaId);

      res.json({
        success: true,
        message: result.message
      });
    } catch (error) {
      console.error('Error deleting attendance:', error);
      res.status(500).json({
        success: false,
        message: error.message || 'Error eliminando asistencia'
      });
    }
  }
}

module.exports = new AttendanceController();