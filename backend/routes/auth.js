const express = require('express');
const { body } = require('express-validator');
const { generateToken, comparePassword } = require('../middleware/auth');
const database = require('../config/database');
const { handleValidationErrors } = require('../utils/validation');

const router = express.Router();

/**
 * @route POST /api/v1/auth/login
 * @desc Authenticate user and return JWT token
 * @access Public
 */
router.post(
  '/login',
  [
    body('username').trim().notEmpty().withMessage('Usuario requerido'),
    body('password').notEmpty().withMessage('Contraseña requerida')
  ],
  handleValidationErrors,
  async (req, res) => {
    try {
      const { username, password } = req.body;

      // Get user from database
      const users = await database.query(
        'SELECT * FROM usuarios WHERE USUARIO = ? AND ACTIVO = 1',
        [username]
      );

      if (!users.length) {
        return res.status(401).json({
          success: false,
          message: 'Usuario o contraseña incorrectos'
        });
      }

      const user = users[0];

      // For PHP compatibility, check if password is hashed or plain text
      let isValidPassword = false;
      if (user.PASSWORD.startsWith('$2')) {
        // Bcrypt hash
        isValidPassword = await comparePassword(password, user.PASSWORD);
      } else {
        // Plain text password (legacy)
        isValidPassword = password === user.PASSWORD;
      }

      if (!isValidPassword) {
        return res.status(401).json({
          success: false,
          message: 'Usuario o contraseña incorrectos'
        });
      }

      // Generate JWT token
      const token = generateToken(user);

      // Get additional user info
      const empresa = await database.query(
        'SELECT NOMBRE_EMPRESA FROM empresas WHERE ID_EMPRESA = ?',
        [user.ID_EMPRESA]
      );

      res.json({
        success: true,
        message: 'Inicio de sesión exitoso',
        token,
        user: {
          id: user.ID,
          username: user.USUARIO,
          rol: user.ROL,
          empresa_id: user.ID_EMPRESA,
          empresa_nombre: empresa.length ? empresa[0].NOMBRE_EMPRESA : 'N/A'
        }
      });
    } catch (error) {
      console.error('Login error:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  }
);

/**
 * @route POST /api/v1/auth/logout
 * @desc Logout user (for session cleanup if needed)
 * @access Private
 */
router.post('/logout', (req, res) => {
  // In JWT-based auth, logout is handled client-side by removing the token
  res.json({
    success: true,
    message: 'Sesión cerrada exitosamente'
  });
});

/**
 * @route GET /api/v1/auth/me
 * @desc Get current user information
 * @access Private
 */
router.get('/me', async (req, res) => {
  try {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];

    if (!token) {
      return res.status(401).json({
        success: false,
        message: 'Token requerido'
      });
    }

    const { verifyToken } = require('../middleware/auth');
    const decoded = verifyToken(token);

    // Get fresh user data
    const users = await database.query(
      'SELECT u.*, e.NOMBRE_EMPRESA FROM usuarios u LEFT JOIN empresas e ON u.ID_EMPRESA = e.ID_EMPRESA WHERE u.ID = ? AND u.ACTIVO = 1',
      [decoded.id]
    );

    if (!users.length) {
      return res.status(401).json({
        success: false,
        message: 'Usuario no válido'
      });
    }

    const user = users[0];

    res.json({
      success: true,
      user: {
        id: user.ID,
        username: user.USUARIO,
        rol: user.ROL,
        empresa_id: user.ID_EMPRESA,
        empresa_nombre: user.NOMBRE_EMPRESA || 'N/A'
      }
    });
  } catch (error) {
    console.error('Get user info error:', error);
    res.status(401).json({
      success: false,
      message: 'Token inválido'
    });
  }
});

module.exports = router;