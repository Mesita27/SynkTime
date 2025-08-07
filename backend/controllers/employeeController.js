const EmployeeService = require('../services/employeeService');
const { validationResult } = require('express-validator');

class EmployeeController {
  /**
   * Get employees list
   */
  async getEmployees(req, res) {
    try {
      const { empresa_id } = req.query;
      const empresaId = empresa_id || req.userInfo?.ID_EMPRESA;

      if (!empresaId) {
        return res.status(400).json({
          success: false,
          message: 'ID de empresa requerido'
        });
      }

      const filters = {
        search: req.query.search,
        sedeId: req.query.sede_id,
        establecimientoId: req.query.establecimiento_id,
        limit: req.query.limit,
        offset: req.query.offset
      };

      const employees = await EmployeeService.getEmployees(empresaId, filters);

      res.json({
        success: true,
        data: employees,
        total: employees.length
      });
    } catch (error) {
      console.error('Error getting employees:', error);
      res.status(500).json({
        success: false,
        message: 'Error obteniendo empleados'
      });
    }
  }

  /**
   * Get single employee
   */
  async getEmployee(req, res) {
    try {
      const { id } = req.params;
      const empresaId = req.userInfo?.ID_EMPRESA;

      if (!empresaId) {
        return res.status(400).json({
          success: false,
          message: 'ID de empresa requerido'
        });
      }

      const employee = await EmployeeService.getEmployee(id, empresaId);

      if (!employee) {
        return res.status(404).json({
          success: false,
          message: 'Empleado no encontrado'
        });
      }

      res.json({
        success: true,
        data: employee
      });
    } catch (error) {
      console.error('Error getting employee:', error);
      res.status(500).json({
        success: false,
        message: 'Error obteniendo empleado'
      });
    }
  }

  /**
   * Create new employee
   */
  async createEmployee(req, res) {
    try {
      const errors = validationResult(req);
      if (!errors.isEmpty()) {
        return res.status(400).json({
          success: false,
          message: 'Datos inválidos',
          errors: errors.array()
        });
      }

      const empresaId = req.userInfo?.ID_EMPRESA;

      if (!empresaId) {
        return res.status(400).json({
          success: false,
          message: 'ID de empresa requerido'
        });
      }

      const result = await EmployeeService.createEmployee(req.body, empresaId);

      res.status(201).json({
        success: true,
        message: result.message,
        data: { ID_EMPLEADO: result.ID_EMPLEADO }
      });
    } catch (error) {
      console.error('Error creating employee:', error);
      res.status(500).json({
        success: false,
        message: error.message || 'Error creando empleado'
      });
    }
  }

  /**
   * Update employee
   */
  async updateEmployee(req, res) {
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

      const result = await EmployeeService.updateEmployee(id, req.body, empresaId);

      res.json({
        success: true,
        message: result.message
      });
    } catch (error) {
      console.error('Error updating employee:', error);
      res.status(500).json({
        success: false,
        message: error.message || 'Error actualizando empleado'
      });
    }
  }

  /**
   * Delete employee
   */
  async deleteEmployee(req, res) {
    try {
      const { id } = req.params;
      const empresaId = req.userInfo?.ID_EMPRESA;

      if (!empresaId) {
        return res.status(400).json({
          success: false,
          message: 'ID de empresa requerido'
        });
      }

      const result = await EmployeeService.deleteEmployee(id, empresaId);

      res.json({
        success: true,
        message: result.message
      });
    } catch (error) {
      console.error('Error deleting employee:', error);
      res.status(500).json({
        success: false,
        message: error.message || 'Error eliminando empleado'
      });
    }
  }

  /**
   * Get company locations (sedes and establecimientos)
   */
  async getCompanyLocations(req, res) {
    try {
      const empresaId = req.userInfo?.ID_EMPRESA;

      if (!empresaId) {
        return res.status(400).json({
          success: false,
          message: 'ID de empresa requerido'
        });
      }

      const locations = await EmployeeService.getCompanyLocations(empresaId);

      res.json({
        success: true,
        data: locations
      });
    } catch (error) {
      console.error('Error getting company locations:', error);
      res.status(500).json({
        success: false,
        message: 'Error obteniendo ubicaciones de la empresa'
      });
    }
  }

  /**
   * Assign schedule to employee
   */
  async assignSchedule(req, res) {
    try {
      const { id } = req.params;
      const { ID_HORARIO, FECHA_DESDE, FECHA_HASTA } = req.body;
      const empresaId = req.userInfo?.ID_EMPRESA;

      if (!empresaId) {
        return res.status(400).json({
          success: false,
          message: 'ID de empresa requerido'
        });
      }

      if (!ID_HORARIO || !FECHA_DESDE) {
        return res.status(400).json({
          success: false,
          message: 'ID de horario y fecha desde son requeridos'
        });
      }

      const result = await EmployeeService.assignSchedule(id, {
        ID_HORARIO,
        FECHA_DESDE,
        FECHA_HASTA
      }, empresaId);

      res.json({
        success: true,
        message: result.message
      });
    } catch (error) {
      console.error('Error assigning schedule:', error);
      res.status(500).json({
        success: false,
        message: error.message || 'Error asignando horario'
      });
    }
  }
}

module.exports = new EmployeeController();