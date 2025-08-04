import React, { createContext, useContext, useState, useEffect } from 'react';

const BiometricContext = createContext();

export const useBiometric = () => {
  const context = useContext(BiometricContext);
  if (!context) {
    throw new Error('useBiometric must be used within a BiometricProvider');
  }
  return context;
};

export const BiometricProvider = ({ children }) => {
  const [devices, setDevices] = useState({
    camera: { available: false, status: 'checking' },
    fingerprint: { available: false, status: 'checking' },
    webauthn: { available: false, status: 'checking' }
  });
  const [isInitialized, setIsInitialized] = useState(false);

  useEffect(() => {
    initializeBiometricSystem();
  }, []);

  const initializeBiometricSystem = async () => {
    try {
      // Check camera availability
      await checkCameraAvailability();
      
      // Check WebAuthn support
      checkWebAuthnSupport();
      
      // Check for fingerprint devices (simulated)
      checkFingerprintDevices();
      
      setIsInitialized(true);
    } catch (error) {
      console.error('Error initializing biometric system:', error);
      setIsInitialized(true);
    }
  };

  const checkCameraAvailability = async () => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ 
        video: { 
          width: 640, 
          height: 480,
          facingMode: 'user'
        } 
      });
      
      // Stop the stream immediately after checking
      stream.getTracks().forEach(track => track.stop());
      
      setDevices(prev => ({
        ...prev,
        camera: { available: true, status: 'ready' }
      }));
    } catch (error) {
      console.warn('Camera not available:', error);
      setDevices(prev => ({
        ...prev,
        camera: { available: false, status: 'unavailable' }
      }));
    }
  };

  const checkWebAuthnSupport = () => {
    const available = typeof window !== 'undefined' && 
                     'credentials' in navigator && 
                     'create' in navigator.credentials;
                     
    setDevices(prev => ({
      ...prev,
      webauthn: { 
        available, 
        status: available ? 'ready' : 'unsupported' 
      }
    }));
  };

  const checkFingerprintDevices = () => {
    // In a real implementation, this would check for USB/HID fingerprint devices
    // For now, simulate checking for devices
    setDevices(prev => ({
      ...prev,
      fingerprint: { 
        available: false, // Set to true if physical device detected
        status: 'unavailable' 
      }
    }));
  };

  const captureFingerprint = async (fingerType) => {
    // Simulate fingerprint capture
    return new Promise((resolve) => {
      setTimeout(() => {
        // Generate simulated fingerprint data
        const fingerprintData = btoa(JSON.stringify({
          fingerType,
          template: `fingerprint_${fingerType}_${Date.now()}`,
          quality: Math.random() * 0.3 + 0.7, // 70-100% quality
          timestamp: new Date().toISOString()
        }));
        
        resolve({
          success: true,
          data: fingerprintData,
          quality: Math.random() * 0.3 + 0.7
        });
      }, 2000);
    });
  };

  const captureFacialData = async (imageData) => {
    // Simulate facial recognition processing
    return new Promise((resolve) => {
      setTimeout(() => {
        // Generate simulated facial template
        const facialTemplate = btoa(JSON.stringify({
          template: `facial_${Date.now()}`,
          features: Array(128).fill(0).map(() => Math.random()),
          confidence: Math.random() * 0.3 + 0.7,
          timestamp: new Date().toISOString()
        }));
        
        resolve({
          success: true,
          data: facialTemplate,
          confidence: Math.random() * 0.3 + 0.7
        });
      }, 1500);
    });
  };

  const verifyBiometric = async (storedTemplate, currentData, type) => {
    // Simulate biometric verification
    return new Promise((resolve) => {
      setTimeout(() => {
        // Simple similarity calculation (in production, use proper algorithms)
        const confidence = Math.random() * 0.4 + 0.6; // 60-100%
        const threshold = type === 'facial' ? 0.8 : 0.75;
        
        resolve({
          success: confidence >= threshold,
          confidence,
          threshold
        });
      }, 1000);
    });
  };

  const value = {
    devices,
    isInitialized,
    captureFingerprint,
    captureFacialData,
    verifyBiometric,
    initializeBiometricSystem
  };

  return (
    <BiometricContext.Provider value={value}>
      {children}
    </BiometricContext.Provider>
  );
};