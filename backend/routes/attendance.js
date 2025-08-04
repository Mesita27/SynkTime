const express = require('express');
const { body } = require('express-validator');
const { authenticateToken } = require('../middleware/auth');
const AttendanceController = require('../controllers/attendanceController');

const router = express.Router();

// All attendance routes require authentication
router.use(authenticateToken);

/**
 * @route POST /api/v1/attendance/register
 * @desc Register attendance
 * @access Private
 */
router.post(
  '/register',
  [
    body('ID_EMPLEADO').isInt().withMessage('ID de empleado inválido'),
    body('VERIFICATION_METHOD')
      .optional()
      .isIn(['traditional', 'fingerprint', 'facial'])
      .withMessage('Método de verificación inválido'),
    body('OBSERVACIONES').optional().trim()
  ],
  AttendanceController.registerAttendance
);

/**
 * @route GET /api/v1/attendance/records
 * @desc Get attendance records with filters
 * @access Private
 */
router.get('/records', AttendanceController.getAttendanceRecords);

/**
 * @route GET /api/v1/attendance/summary
 * @desc Get attendance summary for dashboard
 * @access Private
 */
router.get('/summary', AttendanceController.getAttendanceSummary);

/**
 * @route GET /api/v1/attendance/employee/:id
 * @desc Get attendance history for specific employee
 * @access Private
 */
router.get('/employee/:id', AttendanceController.getEmployeeAttendance);

/**
 * @route PUT /api/v1/attendance/:id
 * @desc Update attendance record
 * @access Private
 */
router.put(
  '/:id',
  [
    body('HORA').optional().matches(/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/).withMessage('Formato de hora inválido (HH:MM:SS)'),
    body('OBSERVACIONES').optional().trim()
  ],
  AttendanceController.updateAttendance
);

/**
 * @route DELETE /api/v1/attendance/:id
 * @desc Delete attendance record
 * @access Private
 */
router.delete('/:id', AttendanceController.deleteAttendance);

module.exports = router;