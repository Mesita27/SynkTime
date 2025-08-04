const express = require('express');
const { authenticateToken } = require('../middleware/auth');

const router = express.Router();

// All employee routes require authentication
router.use(authenticateToken);

/**
 * @route GET /api/v1/employees
 * @desc Get employees list
 * @access Private
 */
router.get('/', async (req, res) => {
  res.json({
    success: true,
    message: 'Employees endpoint - to be implemented',
    data: []
  });
});

module.exports = router;