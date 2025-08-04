const jwt = require('jsonwebtoken');
const bcrypt = require('bcryptjs');
const database = require('../config/database');

const JWT_SECRET = process.env.JWT_SECRET || 'synktime-secret';
const JWT_EXPIRES_IN = process.env.JWT_EXPIRES_IN || '7d';

/**
 * Generate JWT token for user
 */
const generateToken = (user) => {
  return jwt.sign(
    {
      id: user.ID,
      username: user.USUARIO,
      empresa_id: user.ID_EMPRESA,
      rol: user.ROL
    },
    JWT_SECRET,
    { expiresIn: JWT_EXPIRES_IN }
  );
};

/**
 * Verify JWT token
 */
const verifyToken = (token) => {
  return jwt.verify(token, JWT_SECRET);
};

/**
 * Hash password
 */
const hashPassword = async (password) => {
  return await bcrypt.hash(password, 10);
};

/**
 * Compare password with hash
 */
const comparePassword = async (password, hash) => {
  return await bcrypt.compare(password, hash);
};

/**
 * Authentication middleware
 */
const authenticateToken = async (req, res, next) => {
  try {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];

    if (!token) {
      return res.status(401).json({
        success: false,
        message: 'Token de acceso requerido'
      });
    }

    const decoded = verifyToken(token);
    
    // Verify user still exists and is active
    const user = await database.query(
      'SELECT * FROM usuarios WHERE ID = ? AND ACTIVO = 1',
      [decoded.id]
    );

    if (!user.length) {
      return res.status(401).json({
        success: false,
        message: 'Usuario no válido'
      });
    }

    req.user = decoded;
    req.userInfo = user[0];
    next();
  } catch (error) {
    if (error.name === 'TokenExpiredError') {
      return res.status(401).json({
        success: false,
        message: 'Token expirado'
      });
    }
    
    if (error.name === 'JsonWebTokenError') {
      return res.status(401).json({
        success: false,
        message: 'Token inválido'
      });
    }

    return res.status(500).json({
      success: false,
      message: 'Error de autenticación'
    });
  }
};

/**
 * Authorization middleware for specific modules
 */
const requireModuleAccess = (moduleName) => {
  return async (req, res, next) => {
    try {
      // Check if user has access to the module
      const hasAccess = await database.query(
        `SELECT pa.* FROM permisos_acceso pa 
         JOIN usuarios u ON u.ROL = pa.ROL 
         WHERE u.ID = ? AND pa.MODULO = ? AND pa.ACTIVO = 1`,
        [req.user.id, moduleName]
      );

      if (!hasAccess.length) {
        return res.status(403).json({
          success: false,
          message: 'No tiene permisos para acceder a este módulo'
        });
      }

      req.userPermissions = hasAccess[0];
      next();
    } catch (error) {
      return res.status(500).json({
        success: false,
        message: 'Error verificando permisos'
      });
    }
  };
};

module.exports = {
  generateToken,
  verifyToken,
  hashPassword,
  comparePassword,
  authenticateToken,
  requireModuleAccess
};