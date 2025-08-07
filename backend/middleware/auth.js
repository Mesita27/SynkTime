const jwt = require('jsonwebtoken');
const db = require('../config/database');

const JWT_SECRET = process.env.JWT_SECRET || 'your-secret-key-change-in-production';
const JWT_EXPIRES_IN = process.env.JWT_EXPIRES_IN || '24h';

/**
 * Middleware to authenticate JWT tokens
 */
const authenticateToken = async (req, res, next) => {
  try {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1]; // Bearer TOKEN

    if (!token) {
      return res.status(401).json({
        success: false,
        message: 'Token de acceso requerido'
      });
    }

    const decoded = jwt.verify(token, JWT_SECRET);
    
    // Verify user still exists and is active
    const user = await db.query(
      'SELECT ID_USUARIO, USERNAME, ROL, ID_EMPRESA FROM usuario WHERE ID_USUARIO = ? AND ACTIVO = 1',
      [decoded.userId]
    );

    if (user.length === 0) {
      return res.status(401).json({
        success: false,
        message: 'Token inválido o usuario inactivo'
      });
    }

    req.user = {
      id: user[0].ID_USUARIO,
      username: user[0].USERNAME,
      role: user[0].ROL,
      companyId: user[0].ID_EMPRESA
    };

    next();
  } catch (error) {
    console.error('Auth middleware error:', error);
    return res.status(401).json({
      success: false,
      message: 'Token inválido'
    });
  }
};

/**
 * Middleware to check if user has required role
 */
const requireRole = (roles) => {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({
        success: false,
        message: 'No autenticado'
      });
    }

    const userRole = req.user.role;
    const allowedRoles = Array.isArray(roles) ? roles : [roles];

    if (!allowedRoles.includes(userRole)) {
      return res.status(403).json({
        success: false,
        message: 'Permisos insuficientes'
      });
    }

    next();
  };
};

/**
 * Middleware to check if user has module access
 */
const requireModuleAccess = (module) => {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({
        success: false,
        message: 'No autenticado'
      });
    }

    const userRole = req.user.role;
    
    // GERENTE, ADMIN, DUEÑO have full access
    const fullAccessRoles = ['GERENTE', 'ADMINISTRADOR', 'ADMIN', 'DUEÑO', 'DUENO'];
    if (fullAccessRoles.includes(userRole)) {
      return next();
    }
    
    // ASISTENCIA role has limited access
    if (userRole === 'ASISTENCIA') {
      const allowedModules = ['attendance', 'asistencia'];
      if (allowedModules.includes(module)) {
        return next();
      }
    }

    return res.status(403).json({
      success: false,
      message: 'Acceso denegado al módulo'
    });
  };
};

/**
 * Generate JWT token
 */
const generateToken = (userId, username, role, companyId) => {
  return jwt.sign(
    {
      userId,
      username,
      role,
      companyId
    },
    JWT_SECRET,
    { expiresIn: JWT_EXPIRES_IN }
  );
};

/**
 * Verify token
 */
const verifyToken = (token) => {
  try {
    return jwt.verify(token, JWT_SECRET);
  } catch (error) {
    throw new Error('Invalid token');
  }
};

module.exports = {
  authenticateToken,
  requireRole,
  requireModuleAccess,
  generateToken,
  verifyToken
};