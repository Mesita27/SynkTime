const express = require('express');
const { body } = require('express-validator');
const { authenticateToken } = require('../middleware/auth');
const EmployeeController = require('../controllers/employeeController');

const router = express.Router();

// All employee routes require authentication
router.use(authenticateToken);

/**
 * @route GET /api/v1/employees
 * @desc Get employees list with filters
 * @access Private
 */
router.get('/', EmployeeController.getEmployees);

/**
 * @route GET /api/v1/employees/:id
 * @desc Get single employee by ID
 * @access Private
 */
router.get('/:id', EmployeeController.getEmployee);

/**
 * @route POST /api/v1/employees
 * @desc Create new employee
 * @access Private
 */
router.post(
  '/',
  [
    body('NOMBRE').trim().notEmpty().withMessage('Nombre es requerido'),
    body('APELLIDO').trim().notEmpty().withMessage('Apellido es requerido'),
    body('DNI').trim().notEmpty().withMessage('DNI es requerido'),
    body('ID_ESTABLECIMIENTO').isInt().withMessage('ID de establecimiento inválido'),
    body('CORREO').optional().isEmail().withMessage('Correo electrónico inválido'),
    body('TELEFONO').optional().isMobilePhone('es-PE').withMessage('Teléfono inválido')
  ],
  EmployeeController.createEmployee
);

/**
 * @route PUT /api/v1/employees/:id
 * @desc Update employee
 * @access Private
 */
router.put(
  '/:id',
  [
    body('NOMBRE').optional().trim().notEmpty().withMessage('Nombre no puede estar vacío'),
    body('APELLIDO').optional().trim().notEmpty().withMessage('Apellido no puede estar vacío'),
    body('DNI').optional().trim().notEmpty().withMessage('DNI no puede estar vacío'),
    body('ID_ESTABLECIMIENTO').optional().isInt().withMessage('ID de establecimiento inválido'),
    body('CORREO').optional().isEmail().withMessage('Correo electrónico inválido'),
    body('TELEFONO').optional().isMobilePhone('es-PE').withMessage('Teléfono inválido'),
    body('ESTADO').optional().isIn(['A', 'I']).withMessage('Estado inválido')
  ],
  EmployeeController.updateEmployee
);

/**
 * @route DELETE /api/v1/employees/:id
 * @desc Delete employee (soft delete)
 * @access Private
 */
router.delete('/:id', EmployeeController.deleteEmployee);

/**
 * @route GET /api/v1/employees/locations/company
 * @desc Get company locations (sedes and establecimientos)
 * @access Private
 */
router.get('/locations/company', EmployeeController.getCompanyLocations);

/**
 * @route POST /api/v1/employees/:id/schedule
 * @desc Assign schedule to employee
 * @access Private
 */
router.post(
  '/:id/schedule',
  [
    body('ID_HORARIO').isInt().withMessage('ID de horario inválido'),
    body('FECHA_DESDE').isDate().withMessage('Fecha desde inválida'),
    body('FECHA_HASTA').optional().isDate().withMessage('Fecha hasta inválida')
  ],
  EmployeeController.assignSchedule
);

module.exports = router;