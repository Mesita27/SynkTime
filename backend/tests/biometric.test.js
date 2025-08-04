const BiometricService = require('../services/biometricService');

// Mock database
jest.mock('../config/database', () => ({
  query: jest.fn(),
  transaction: jest.fn()
}));

describe('BiometricService', () => {
  describe('simulateBiometricComparison', () => {
    test('should return confidence between 0.6 and 1.0', () => {
      const confidence = BiometricService.simulateBiometricComparison('test1', 'test2');
      expect(confidence).toBeGreaterThanOrEqual(0.6);
      expect(confidence).toBeLessThanOrEqual(1.0);
    });
  });

  describe('getDeviceStatus', () => {
    test('should return device status object', async () => {
      const deviceStatus = await BiometricService.getDeviceStatus();
      
      expect(deviceStatus).toHaveProperty('camera');
      expect(deviceStatus).toHaveProperty('fingerprint');
      expect(deviceStatus).toHaveProperty('webauthn');
      
      expect(deviceStatus.camera).toHaveProperty('available');
      expect(deviceStatus.camera).toHaveProperty('status');
      expect(deviceStatus.camera).toHaveProperty('type');
    });
  });
});