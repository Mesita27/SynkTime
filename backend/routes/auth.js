const express = require('express');
const bcrypt = require('bcryptjs');
const { body, validationResult } = require('express-validator');
const db = require('../config/database');
const { generateToken, authenticateToken } = require('../middleware/auth');

const router = express.Router();

/**
 * POST /api/auth/login
 * User login endpoint
 */
router.post('/login', [
  body('username').notEmpty().withMessage('Username es requerido'),
  body('password').notEmpty().withMessage('Password es requerido')
], async (req, res) => {
  try {
    // Validate input
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({
        success: false,
        message: 'Datos inválidos',
        errors: errors.array()
      });
    }

    const { username, password } = req.body;

    // Get user with company info
    const userQuery = `
      SELECT 
        u.ID_USUARIO,
        u.USERNAME,
        u.CONTRASENA as PASSWORD,
        u.NOMBRE_COMPLETO,
        u.EMAIL,
        u.ROL,
        u.ID_EMPRESA,
        u.ACTIVO,
        e.NOMBRE as EMPRESA_NOMBRE
      FROM usuario u
      LEFT JOIN EMPRESA e ON u.ID_EMPRESA = e.ID_EMPRESA
      WHERE u.USERNAME = ? AND u.ACTIVO = 1
    `;

    const users = await db.query(userQuery, [username]);

    if (users.length === 0) {
      return res.status(401).json({
        success: false,
        message: 'Credenciales inválidas'
      });
    }

    const user = users[0];

    // Verify password
    const isValidPassword = await bcrypt.compare(password, user.PASSWORD);
    if (!isValidPassword) {
      return res.status(401).json({
        success: false,
        message: 'Credenciales inválidas'
      });
    }

    // Generate JWT token
    const token = generateToken(
      user.ID_USUARIO,
      user.USERNAME,
      user.ROL,
      user.ID_EMPRESA
    );

    // Log activity
    await db.query(
      'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
      [user.ID_USUARIO, 'LOGIN', 'Login exitoso']
    );

    res.json({
      success: true,
      message: 'Login exitoso',
      data: {
        token,
        user: {
          id: user.ID_USUARIO,
          username: user.USERNAME,
          nombre_completo: user.NOMBRE_COMPLETO,
          email: user.EMAIL,
          rol: user.ROL,
          id_empresa: user.ID_EMPRESA,
          empresa_nombre: user.EMPRESA_NOMBRE
        }
      }
    });

  } catch (error) {
    console.error('Login error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * POST /api/auth/logout
 * User logout endpoint
 */
router.post('/logout', authenticateToken, async (req, res) => {
  try {
    // Log activity
    await db.query(
      'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
      [req.user.id, 'LOGOUT', 'Logout exitoso']
    );

    res.json({
      success: true,
      message: 'Logout exitoso'
    });
  } catch (error) {
    console.error('Logout error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * GET /api/auth/me
 * Get current user info
 */
router.get('/me', authenticateToken, async (req, res) => {
  try {
    const userQuery = `
      SELECT 
        u.ID_USUARIO,
        u.USERNAME,
        u.NOMBRE_COMPLETO,
        u.EMAIL,
        u.ROL,
        u.ID_EMPRESA,
        e.NOMBRE as EMPRESA_NOMBRE
      FROM usuario u
      LEFT JOIN EMPRESA e ON u.ID_EMPRESA = e.ID_EMPRESA
      WHERE u.ID_USUARIO = ?
    `;

    const users = await db.query(userQuery, [req.user.id]);

    if (users.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Usuario no encontrado'
      });
    }

    const user = users[0];

    res.json({
      success: true,
      data: {
        id: user.ID_USUARIO,
        username: user.USERNAME,
        nombre_completo: user.NOMBRE_COMPLETO,
        email: user.EMAIL,
        rol: user.ROL,
        id_empresa: user.ID_EMPRESA,
        empresa_nombre: user.EMPRESA_NOMBRE
      }
    });

  } catch (error) {
    console.error('Get user info error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

/**
 * POST /api/auth/change-password
 * Change user password
 */
router.post('/change-password', [
  authenticateToken,
  body('currentPassword').notEmpty().withMessage('Password actual es requerido'),
  body('newPassword').isLength({ min: 6 }).withMessage('El nuevo password debe tener al menos 6 caracteres')
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

    const { currentPassword, newPassword } = req.body;

    // Get current user password
    const users = await db.query(
      'SELECT CONTRASENA as PASSWORD FROM usuario WHERE ID_USUARIO = ?',
      [req.user.id]
    );

    if (users.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Usuario no encontrado'
      });
    }

    // Verify current password
    const isValidPassword = await bcrypt.compare(currentPassword, users[0].PASSWORD);
    if (!isValidPassword) {
      return res.status(400).json({
        success: false,
        message: 'Password actual incorrecto'
      });
    }

    // Hash new password
    const hashedPassword = await bcrypt.hash(newPassword, 12);

    // Update password
    await db.query(
      'UPDATE usuario SET CONTRASENA = ? WHERE ID_USUARIO = ?',
      [hashedPassword, req.user.id]
    );

    // Log activity
    await db.query(
      'INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) VALUES (?, ?, ?)',
      [req.user.id, 'CHANGE_PASSWORD', 'Password cambiado exitosamente']
    );

    res.json({
      success: true,
      message: 'Password actualizado exitosamente'
    });

  } catch (error) {
    console.error('Change password error:', error);
    res.status(500).json({
      success: false,
      message: 'Error interno del servidor'
    });
  }
});

module.exports = router;