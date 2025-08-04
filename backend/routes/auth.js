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

      // Get user from database using correct table name from PHP schema
      const users = await database.query(
        `SELECT 
          u.ID_USUARIO,
          u.USERNAME,
          u.CONTRASENA,
          u.NOMBRE_COMPLETO,
          u.EMAIL,
          u.ROL,
          u.ID_EMPRESA,
          u.ESTADO,
          e.NOMBRE AS EMPRESA_NOMBRE,
          e.ESTADO AS EMPRESA_ESTADO
        FROM USUARIO u
        INNER JOIN EMPRESA e ON u.ID_EMPRESA = e.ID_EMPRESA
        WHERE u.USERNAME = ? AND u.ESTADO = 'A'`,
        [username]
      );

      if (!users.length) {
        return res.status(401).json({
          success: false,
          message: 'Usuario o contraseña incorrectos'
        });
      }

      const user = users[0];

      // Verify company is active
      if (user.EMPRESA_ESTADO !== 'A') {
        return res.status(401).json({
          success: false,
          message: 'Empresa inactiva. Contacte al administrador'
        });
      }

      // For PHP compatibility, check if password is hashed or plain text
      let isValidPassword = false;
      if (user.CONTRASENA && user.CONTRASENA.startsWith('$2')) {
        // Bcrypt hash
        isValidPassword = await comparePassword(password, user.CONTRASENA);
      } else {
        // Plain text password (legacy)
        isValidPassword = password === user.CONTRASENA;
      }

      if (!isValidPassword) {
        return res.status(401).json({
          success: false,
          message: 'Usuario o contraseña incorrectos'
        });
      }

      // Generate JWT token
      const token = generateToken(user);

      res.json({
        success: true,
        message: 'Inicio de sesión exitoso',
        token,
        user: {
          id: user.ID_USUARIO,
          username: user.USERNAME,
          nombre_completo: user.NOMBRE_COMPLETO,
          email: user.EMAIL,
          rol: user.ROL,
          empresa_id: user.ID_EMPRESA,
          empresa_nombre: user.EMPRESA_NOMBRE
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

    // Get fresh user data using correct table names
    const users = await database.query(
      `SELECT 
        u.ID_USUARIO,
        u.USERNAME,
        u.NOMBRE_COMPLETO,
        u.EMAIL,
        u.ROL,
        u.ID_EMPRESA,
        u.ESTADO,
        e.NOMBRE AS EMPRESA_NOMBRE
      FROM USUARIO u 
      LEFT JOIN EMPRESA e ON u.ID_EMPRESA = e.ID_EMPRESA 
      WHERE u.ID_USUARIO = ? AND u.ESTADO = 'A'`,
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
        id: user.ID_USUARIO,
        username: user.USERNAME,
        nombre_completo: user.NOMBRE_COMPLETO,
        email: user.EMAIL,
        rol: user.ROL,
        empresa_id: user.ID_EMPRESA,
        empresa_nombre: user.EMPRESA_NOMBRE || 'N/A'
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