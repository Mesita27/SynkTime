const express = require('express');
const { body, param, query } = require('express-validator');
const { authenticateToken, requireModuleAccess } = require('../middleware/auth');
const biometricController = require('../controllers/biometricController');
const { handleValidationErrors } = require('../utils/validation');

const router = express.Router();

// All biometric routes require authentication
router.use(authenticateToken);

/**
 * @route GET /api/v1/biometric/employee/:employeeId/summary
 * @desc Get biometric enrollment summary for an employee
 * @access Private (requires biometric module access)
 */
router.get(
  '/employee/:employeeId/summary',
  param('employeeId').isInt().withMessage('ID de empleado debe ser un número'),
  requireModuleAccess('biometric'),
  handleValidationErrors,
  biometricController.getEmployeeBiometricSummary
);

/**
 * @route POST /api/v1/biometric/enroll/fingerprint
 * @desc Enroll fingerprint for employee
 * @access Private (requires biometric module access)
 */
router.post(
  '/enroll/fingerprint',
  [
    body('employee_id').isInt().withMessage('ID de empleado requerido'),
    body('finger_type').isIn([
      'left_thumb', 'left_index', 'left_middle', 'left_ring', 'left_pinky',
      'right_thumb', 'right_index', 'right_middle', 'right_ring', 'right_pinky'
    ]).withMessage('Tipo de dedo inválido'),
    body('fingerprint_data').notEmpty().withMessage('Datos de huella requeridos')
  ],
  requireModuleAccess('biometric'),
  handleValidationErrors,
  biometricController.enrollFingerprint
);

/**
 * @route POST /api/v1/biometric/enroll/facial
 * @desc Enroll facial biometric for employee
 * @access Private (requires biometric module access)
 */
router.post(
  '/enroll/facial',
  [
    body('employee_id').isInt().withMessage('ID de empleado requerido'),
    body('facial_data').notEmpty().withMessage('Datos faciales requeridos')
  ],
  requireModuleAccess('biometric'),
  handleValidationErrors,
  biometricController.enrollFacial
);

/**
 * @route POST /api/v1/biometric/verify
 * @desc Verify biometric data for attendance
 * @access Private (requires attendance module access)
 */
router.post(
  '/verify',
  [
    body('employee_id').isInt().withMessage('ID de empleado requerido'),
    body('biometric_type').isIn(['fingerprint', 'facial']).withMessage('Tipo biométrico inválido'),
    body('biometric_data').notEmpty().withMessage('Datos biométricos requeridos')
  ],
  requireModuleAccess('attendance'),
  handleValidationErrors,
  biometricController.verifyBiometric
);

/**
 * @route GET /api/v1/biometric/stats
 * @desc Get biometric statistics
 * @access Private (requires dashboard or biometric module access)
 */
router.get(
  '/stats',
  query('empresa_id').optional().isInt().withMessage('ID de empresa debe ser un número'),
  requireModuleAccess('dashboard'),
  handleValidationErrors,
  biometricController.getBiometricStats
);

/**
 * @route DELETE /api/v1/biometric/employee/:employeeId
 * @desc Delete biometric data for employee
 * @access Private (requires biometric module access)
 */
router.delete(
  '/employee/:employeeId',
  [
    param('employeeId').isInt().withMessage('ID de empleado debe ser un número'),
    body('biometric_type').isIn(['fingerprint', 'facial']).withMessage('Tipo biométrico inválido'),
    body('finger_type').optional().isString().withMessage('Tipo de dedo debe ser texto')
  ],
  requireModuleAccess('biometric'),
  handleValidationErrors,
  biometricController.deleteBiometricData
);

/**
 * @route GET /api/v1/biometric/devices/status
 * @desc Get biometric device status
 * @access Private
 */
router.get(
  '/devices/status',
  biometricController.getDeviceStatus
);

module.exports = router;