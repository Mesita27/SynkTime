import React, { useState, useEffect, useRef } from 'react';
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  Box,
  Typography,
  LinearProgress,
  Grid,
  Card,
  CardContent,
  Alert,
  CircularProgress,
  Chip,
  Avatar,
} from '@mui/material';
import {
  Fingerprint,
  CameraAlt,
  CheckCircle,
  Cancel,
  Warning,
} from '@mui/icons-material';
import axios from 'axios';

interface BiometricEnrollmentProps {
  open: boolean;
  onClose: () => void;
  employee: {
    id: number;
    nombre: string;
    apellido: string;
    dni: string;
  } | null;
  onSuccess: () => void;
}

interface EnrollmentStatus {
  progress: number;
  stage: 'idle' | 'detecting' | 'capturing' | 'processing' | 'completed' | 'error';
  message: string;
  error?: string;
}

const FINGER_TYPES = [
  { id: 'left_thumb', label: 'Pulgar Izquierdo', hand: 'left' },
  { id: 'left_index', label: 'Índice Izquierdo', hand: 'left' },
  { id: 'left_middle', label: 'Medio Izquierdo', hand: 'left' },
  { id: 'left_ring', label: 'Anular Izquierdo', hand: 'left' },
  { id: 'left_pinky', label: 'Meñique Izquierdo', hand: 'left' },
  { id: 'right_thumb', label: 'Pulgar Derecho', hand: 'right' },
  { id: 'right_index', label: 'Índice Derecho', hand: 'right' },
  { id: 'right_middle', label: 'Medio Derecho', hand: 'right' },
  { id: 'right_ring', label: 'Anular Derecho', hand: 'right' },
  { id: 'right_pinky', label: 'Meñique Derecho', hand: 'right' },
];

const BiometricEnrollmentModal: React.FC<BiometricEnrollmentProps> = ({
  open,
  onClose,
  employee,
  onSuccess,
}) => {
  const [enrollmentType, setEnrollmentType] = useState<'fingerprint' | 'facial' | null>(null);
  const [selectedFinger, setSelectedFinger] = useState<string | null>(null);
  const [enrollmentStatus, setEnrollmentStatus] = useState<EnrollmentStatus>({
    progress: 0,
    stage: 'idle',
    message: 'Selecciona el tipo de inscripción',
  });
  const [devices, setDevices] = useState({
    fingerprint: false,
    camera: false,
  });
  const [loading, setLoading] = useState(false);
  
  const intervalRef = useRef<NodeJS.Timeout | null>(null);
  const videoRef = useRef<HTMLVideoElement>(null);
  const streamRef = useRef<MediaStream | null>(null);

  useEffect(() => {
    if (open) {
      detectDevices();
      resetEnrollment();
    } else {
      cleanup();
    }
  }, [open]);

  useEffect(() => {
    return () => cleanup();
  }, []);

  const cleanup = () => {
    if (intervalRef.current) {
      clearInterval(intervalRef.current);
      intervalRef.current = null;
    }
    if (streamRef.current) {
      streamRef.current.getTracks().forEach(track => track.stop());
      streamRef.current = null;
    }
  };

  const detectDevices = async () => {
    try {
      // Detect camera
      const stream = await navigator.mediaDevices.getUserMedia({ video: true });
      setDevices(prev => ({ ...prev, camera: true }));
      stream.getTracks().forEach(track => track.stop());
    } catch (error) {
      console.warn('Camera not available:', error);
      setDevices(prev => ({ ...prev, camera: false }));
    }

    // Simulate fingerprint device detection
    // In real implementation, this would check for actual hardware
    const hasFingerprint = window.PublicKeyCredential !== undefined;
    setDevices(prev => ({ ...prev, fingerprint: hasFingerprint }));
  };

  const resetEnrollment = () => {
    setEnrollmentType(null);
    setSelectedFinger(null);
    setEnrollmentStatus({
      progress: 0,
      stage: 'idle',
      message: 'Selecciona el tipo de inscripción',
    });
    setLoading(false);
    cleanup();
  };

  const startFingerprintEnrollment = () => {
    if (!selectedFinger) {
      setEnrollmentStatus({
        progress: 0,
        stage: 'error',
        message: 'Selecciona un dedo primero',
        error: 'Dedo no seleccionado',
      });
      return;
    }

    setEnrollmentStatus({
      progress: 0,
      stage: 'detecting',
      message: 'Detectando lector de huellas...',
    });

    setTimeout(() => {
      if (!devices.fingerprint) {
        setEnrollmentStatus({
          progress: 0,
          stage: 'error',
          message: 'Lector de huellas no detectado',
          error: 'Dispositivo no disponible',
        });
        return;
      }

      simulateFingerprintCapture();
    }, 1000);
  };

  const simulateFingerprintCapture = () => {
    setEnrollmentStatus({
      progress: 0,
      stage: 'capturing',
      message: 'Coloca el dedo en el lector...',
    });

    let progress = 0;
    const increment = 10; // Smaller increments for smoother progress
    const interval = 400; // Faster updates for better UX

    intervalRef.current = setInterval(() => {
      progress += increment;
      
      setEnrollmentStatus(prev => ({
        ...prev,
        progress,
        message: progress < 100 
          ? `Capturando huella... ${progress}%`
          : 'Procesando datos...',
        stage: progress < 100 ? 'capturing' : 'processing',
      }));

      // Fix: Ensure we reach exactly 100% and complete the process
      if (progress >= 100) {
        if (intervalRef.current) {
          clearInterval(intervalRef.current);
          intervalRef.current = null;
        }
        
        // Add a short delay before showing completion
        setTimeout(() => {
          completeFingerprintEnrollment();
        }, 500);
      }
    }, interval);
  };

  const completeFingerprintEnrollment = () => {
    setEnrollmentStatus({
      progress: 100,
      stage: 'completed',
      message: 'Huella capturada exitosamente',
    });
  };

  const startFacialEnrollment = async () => {
    if (!devices.camera) {
      setEnrollmentStatus({
        progress: 0,
        stage: 'error',
        message: 'Cámara no disponible',
        error: 'Dispositivo no disponible',
      });
      return;
    }

    try {
      setEnrollmentStatus({
        progress: 0,
        stage: 'detecting',
        message: 'Iniciando cámara...',
      });

      const stream = await navigator.mediaDevices.getUserMedia({ 
        video: { width: 640, height: 480 } 
      });
      
      streamRef.current = stream;
      
      if (videoRef.current) {
        videoRef.current.srcObject = stream;
        videoRef.current.play();
      }

      setTimeout(() => {
        simulateFacialCapture();
      }, 1000);

    } catch (error) {
      console.error('Error accessing camera:', error);
      setEnrollmentStatus({
        progress: 0,
        stage: 'error',
        message: 'Error al acceder a la cámara',
        error: error instanceof Error ? error.message : 'Error desconocido',
      });
    }
  };

  const simulateFacialCapture = () => {
    setEnrollmentStatus({
      progress: 0,
      stage: 'capturing',
      message: 'Mira directamente a la cámara...',
    });

    let progress = 0;
    const increment = 12.5; // 8 steps to reach 100%
    const interval = 600;

    intervalRef.current = setInterval(() => {
      progress += increment;
      
      setEnrollmentStatus(prev => ({
        ...prev,
        progress: Math.min(progress, 100), // Ensure we don't exceed 100%
        message: progress < 100 
          ? `Analizando rostro... ${Math.round(progress)}%`
          : 'Procesando datos faciales...',
        stage: progress < 100 ? 'capturing' : 'processing',
      }));

      if (progress >= 100) {
        if (intervalRef.current) {
          clearInterval(intervalRef.current);
          intervalRef.current = null;
        }
        
        setTimeout(() => {
          completeFacialEnrollment();
        }, 800);
      }
    }, interval);
  };

  const completeFacialEnrollment = () => {
    if (streamRef.current) {
      streamRef.current.getTracks().forEach(track => track.stop());
      streamRef.current = null;
    }

    setEnrollmentStatus({
      progress: 100,
      stage: 'completed',
      message: 'Datos faciales capturados exitosamente',
    });
  };

  const saveEnrollment = async () => {
    if (!employee || enrollmentStatus.stage !== 'completed') return;

    setLoading(true);
    
    try {
      const token = localStorage.getItem('synktime_token');
      const endpoint = enrollmentType === 'fingerprint' 
        ? '/api/biometric/enroll/fingerprint'
        : '/api/biometric/enroll/facial';

      const data = enrollmentType === 'fingerprint' 
        ? {
            employee_id: employee.id,
            finger_type: selectedFinger,
            fingerprint_data: `fingerprint_${employee.id}_${selectedFinger}_${Date.now()}`
          }
        : {
            employee_id: employee.id,
            facial_data: `facial_${employee.id}_${Date.now()}`
          };

      const response = await axios.post(`http://localhost:3001${endpoint}`, data, {
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });

      if (response.data.success) {
        onSuccess();
        onClose();
      } else {
        throw new Error(response.data.message || 'Error al guardar los datos biométricos');
      }
    } catch (error: any) {
      console.error('Error saving enrollment:', error);
      setEnrollmentStatus({
        progress: 100,
        stage: 'error',
        message: 'Error al guardar los datos',
        error: error.message || 'Error desconocido',
      });
    } finally {
      setLoading(false);
    }
  };

  const getProgressColor = () => {
    switch (enrollmentStatus.stage) {
      case 'completed': return 'success';
      case 'error': return 'error';
      case 'processing': return 'info';
      default: return 'primary';
    }
  };

  return (
    <Dialog open={open} onClose={onClose} maxWidth="md" fullWidth>
      <DialogTitle>
        <Box display="flex" alignItems="center" gap={2}>
          <Fingerprint />
          <Box>
            <Typography variant="h6">Inscripción Biométrica</Typography>
            {employee && (
              <Typography variant="body2" color="textSecondary">
                {employee.nombre} {employee.apellido} - DNI: {employee.dni}
              </Typography>
            )}
          </Box>
        </Box>
      </DialogTitle>

      <DialogContent>
        {!enrollmentType ? (
          <Grid container spacing={3}>
            <Grid item xs={12} md={6}>
              <Card 
                sx={{ 
                  cursor: devices.fingerprint ? 'pointer' : 'not-allowed',
                  opacity: devices.fingerprint ? 1 : 0.5,
                }}
                onClick={() => devices.fingerprint && setEnrollmentType('fingerprint')}
              >
                <CardContent>
                  <Box display="flex" flexDirection="column" alignItems="center" gap={2}>
                    <Avatar sx={{ bgcolor: 'primary.main', width: 64, height: 64 }}>
                      <Fingerprint fontSize="large" />
                    </Avatar>
                    <Typography variant="h6">Huella Dactilar</Typography>
                    <Chip 
                      icon={devices.fingerprint ? <CheckCircle /> : <Cancel />}
                      label={devices.fingerprint ? 'Disponible' : 'No disponible'}
                      color={devices.fingerprint ? 'success' : 'error'}
                      size="small"
                    />
                  </Box>
                </CardContent>
              </Card>
            </Grid>

            <Grid item xs={12} md={6}>
              <Card 
                sx={{ 
                  cursor: devices.camera ? 'pointer' : 'not-allowed',
                  opacity: devices.camera ? 1 : 0.5,
                }}
                onClick={() => devices.camera && setEnrollmentType('facial')}
              >
                <CardContent>
                  <Box display="flex" flexDirection="column" alignItems="center" gap={2}>
                    <Avatar sx={{ bgcolor: 'secondary.main', width: 64, height: 64 }}>
                      <CameraAlt fontSize="large" />
                    </Avatar>
                    <Typography variant="h6">Reconocimiento Facial</Typography>
                    <Chip 
                      icon={devices.camera ? <CheckCircle /> : <Cancel />}
                      label={devices.camera ? 'Disponible' : 'No disponible'}
                      color={devices.camera ? 'success' : 'error'}
                      size="small"
                    />
                  </Box>
                </CardContent>
              </Card>
            </Grid>
          </Grid>
        ) : (
          <Box>
            {enrollmentType === 'fingerprint' && !selectedFinger && (
              <Box mb={3}>
                <Typography variant="h6" gutterBottom>
                  Selecciona el dedo a registrar:
                </Typography>
                <Grid container spacing={1}>
                  {FINGER_TYPES.map((finger) => (
                    <Grid item xs={6} sm={4} md={2.4} key={finger.id}>
                      <Button
                        fullWidth
                        variant={selectedFinger === finger.id ? 'contained' : 'outlined'}
                        onClick={() => setSelectedFinger(finger.id)}
                        size="small"
                      >
                        {finger.label}
                      </Button>
                    </Grid>
                  ))}
                </Grid>
              </Box>
            )}

            {enrollmentType === 'facial' && enrollmentStatus.stage === 'capturing' && (
              <Box mb={3} display="flex" justifyContent="center">
                <video
                  ref={videoRef}
                  style={{
                    width: '100%',
                    maxWidth: '400px',
                    height: 'auto',
                    borderRadius: '8px',
                  }}
                  muted
                />
              </Box>
            )}

            <Box mb={3}>
              <Typography variant="body1" gutterBottom>
                {enrollmentStatus.message}
              </Typography>
              
              <LinearProgress 
                variant="determinate" 
                value={enrollmentStatus.progress}
                color={getProgressColor()}
                sx={{ height: 10, borderRadius: 5 }}
              />
              
              <Typography variant="body2" color="textSecondary" mt={1}>
                Progreso: {Math.round(enrollmentStatus.progress)}%
              </Typography>
            </Box>

            {enrollmentStatus.error && (
              <Alert severity="error" sx={{ mb: 2 }}>
                {enrollmentStatus.error}
              </Alert>
            )}

            {enrollmentStatus.stage === 'completed' && (
              <Alert severity="success" sx={{ mb: 2 }}>
                ¡Datos biométricos capturados exitosamente! Haz clic en "Guardar" para finalizar.
              </Alert>
            )}
          </Box>
        )}
      </DialogContent>

      <DialogActions>
        <Button onClick={onClose} disabled={loading}>
          Cancelar
        </Button>
        
        {enrollmentType && enrollmentStatus.stage === 'idle' && (
          <Button
            variant="contained"
            onClick={enrollmentType === 'fingerprint' ? startFingerprintEnrollment : startFacialEnrollment}
            disabled={enrollmentType === 'fingerprint' && !selectedFinger}
          >
            Iniciar Captura
          </Button>
        )}
        
        {enrollmentStatus.stage === 'completed' && (
          <Button
            variant="contained"
            onClick={saveEnrollment}
            disabled={loading}
            startIcon={loading ? <CircularProgress size={20} /> : undefined}
          >
            {loading ? 'Guardando...' : 'Guardar'}
          </Button>
        )}
        
        {enrollmentStatus.stage === 'error' && (
          <Button
            variant="outlined"
            onClick={() => {
              resetEnrollment();
              setEnrollmentType(null);
            }}
          >
            Reintentar
          </Button>
        )}
      </DialogActions>
    </Dialog>
  );
};

export default BiometricEnrollmentModal;