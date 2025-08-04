const database = require('../config/database');
const BiometricService = require('../services/biometricService');

class BiometricController {
  /**
   * Get biometric enrollment summary for an employee
   */
  async getEmployeeBiometricSummary(req, res) {
    try {
      const { employeeId } = req.params;

      if (!employeeId) {
        return res.status(400).json({
          success: false,
          message: 'ID de empleado requerido'
        });
      }

      const summary = await BiometricService.getEmployeeBiometricSummary(employeeId);
      
      res.json({
        success: true,
        data: summary
      });
    } catch (error) {
      console.error('Error getting biometric summary:', error);
      res.status(500).json({
        success: false,
        message: 'Error obteniendo resumen biométrico'
      });
    }
  }

  /**
   * Enroll fingerprint for employee
   */
  async enrollFingerprint(req, res) {
    try {
      const { employee_id, finger_type, fingerprint_data } = req.body;

      if (!employee_id || !finger_type || !fingerprint_data) {
        return res.status(400).json({
          success: false,
          message: 'Datos incompletos: employee_id, finger_type y fingerprint_data son requeridos'
        });
      }

      // Validate finger type
      const validFingers = [
        'left_thumb', 'left_index', 'left_middle', 'left_ring', 'left_pinky',
        'right_thumb', 'right_index', 'right_middle', 'right_ring', 'right_pinky'
      ];

      if (!validFingers.includes(finger_type)) {
        return res.status(400).json({
          success: false,
          message: 'Tipo de dedo inválido'
        });
      }

      const result = await BiometricService.enrollFingerprint(
        employee_id, 
        finger_type, 
        fingerprint_data
      );

      res.json({
        success: true,
        message: result.message,
        data: result
      });
    } catch (error) {
      console.error('Error enrolling fingerprint:', error);
      res.status(500).json({
        success: false,
        message: 'Error registrando huella dactilar'
      });
    }
  }

  /**
   * Enroll facial biometric for employee
   */
  async enrollFacial(req, res) {
    try {
      const { employee_id, facial_data } = req.body;

      if (!employee_id || !facial_data) {
        return res.status(400).json({
          success: false,
          message: 'Datos incompletos: employee_id y facial_data son requeridos'
        });
      }

      const result = await BiometricService.enrollFacial(employee_id, facial_data);

      res.json({
        success: true,
        message: result.message,
        data: result
      });
    } catch (error) {
      console.error('Error enrolling facial:', error);
      res.status(500).json({
        success: false,
        message: 'Error registrando datos faciales'
      });
    }
  }

  /**
   * Verify biometric data for attendance
   */
  async verifyBiometric(req, res) {
    try {
      const { employee_id, biometric_type, biometric_data } = req.body;

      if (!employee_id || !biometric_type || !biometric_data) {
        return res.status(400).json({
          success: false,
          message: 'Datos incompletos para verificación biométrica'
        });
      }

      if (!['fingerprint', 'facial'].includes(biometric_type)) {
        return res.status(400).json({
          success: false,
          message: 'Tipo biométrico inválido'
        });
      }

      const verification = await BiometricService.verifyBiometric(
        employee_id,
        biometric_type,
        biometric_data
      );

      res.json({
        success: true,
        verified: verification.success,
        confidence: verification.confidence,
        message: verification.message
      });
    } catch (error) {
      console.error('Error verifying biometric:', error);
      res.status(500).json({
        success: false,
        message: 'Error verificando datos biométricos'
      });
    }
  }

  /**
   * Get biometric statistics
   */
  async getBiometricStats(req, res) {
    try {
      const { empresa_id } = req.query;
      const empresaId = empresa_id || req.userInfo.ID_EMPRESA;

      const stats = await BiometricService.getBiometricStats(empresaId);

      res.json({
        success: true,
        data: stats
      });
    } catch (error) {
      console.error('Error getting biometric stats:', error);
      res.status(500).json({
        success: false,
        message: 'Error obteniendo estadísticas biométricas'
      });
    }
  }

  /**
   * Delete biometric data for employee
   */
  async deleteBiometricData(req, res) {
    try {
      const { employeeId } = req.params;
      const { biometric_type, finger_type } = req.body;

      if (!employeeId || !biometric_type) {
        return res.status(400).json({
          success: false,
          message: 'ID de empleado y tipo biométrico requeridos'
        });
      }

      const result = await BiometricService.deleteBiometricData(
        employeeId,
        biometric_type,
        finger_type
      );

      res.json({
        success: true,
        message: result.message
      });
    } catch (error) {
      console.error('Error deleting biometric data:', error);
      res.status(500).json({
        success: false,
        message: 'Error eliminando datos biométricos'
      });
    }
  }

  /**
   * Get device status (simulated for modern web APIs)
   */
  async getDeviceStatus(req, res) {
    try {
      const deviceStatus = await BiometricService.getDeviceStatus();

      res.json({
        success: true,
        devices: deviceStatus
      });
    } catch (error) {
      console.error('Error getting device status:', error);
      res.status(500).json({
        success: false,
        message: 'Error obteniendo estado de dispositivos'
      });
    }
  }
}

module.exports = new BiometricController();