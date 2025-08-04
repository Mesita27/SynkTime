import React, { useState, useRef, useEffect } from 'react';
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  Box,
  Typography,
  Grid,
  Card,
  CardContent,
  Chip,
  CircularProgress,
  LinearProgress,
  Alert,
  List,
  ListItem,
  ListItemButton,
  ListItemText,
  ListItemAvatar,
  Avatar,
  IconButton,
  Tooltip
} from '@mui/material';
import {
  Fingerprint,
  Face,
  PhotoCamera,
  CheckCircle,
  Error,
  Person,
  Close,
  Refresh
} from '@mui/icons-material';
import Webcam from 'react-webcam';
import { useBiometric } from '../../context/BiometricContext';
import { apiService } from '../../services/api';
import { useSnackbar } from 'notistack';

const fingerOptions = [
  { id: 'left_thumb', label: 'Pulgar Izquierdo', hand: 'left' },
  { id: 'left_index', label: 'Índice Izquierdo', hand: 'left' },
  { id: 'left_middle', label: 'Medio Izquierdo', hand: 'left' },
  { id: 'left_ring', label: 'Anular Izquierdo', hand: 'left' },
  { id: 'left_pinky', label: 'Meñique Izquierdo', hand: 'left' },
  { id: 'right_thumb', label: 'Pulgar Derecho', hand: 'right' },
  { id: 'right_index', label: 'Índice Derecho', hand: 'right' },
  { id: 'right_middle', label: 'Medio Derecho', hand: 'right' },
  { id: 'right_ring', label: 'Anular Derecho', hand: 'right' },
  { id: 'right_pinky', label: 'Meñique Derecho', hand: 'right' }
];

const BiometricEnrollmentModal = ({ 
  open, 
  onClose, 
  selectedEmployee,
  onEnrollmentComplete 
}) => {
  const [step, setStep] = useState('type'); // 'type', 'finger', 'capture', 'processing'
  const [enrollmentType, setEnrollmentType] = useState(null);
  const [selectedFinger, setSelectedFinger] = useState(null);
  const [progress, setProgress] = useState(0);
  const [isCapturing, setIsCapturing] = useState(false);
  const [captureComplete, setCaptureComplete] = useState(false);
  const [error, setError] = useState(null);
  const [employeeBiometrics, setEmployeeBiometrics] = useState(null);

  const webcamRef = useRef(null);
  const { devices, captureFingerprint, captureFacialData } = useBiometric();
  const { enqueueSnackbar } = useSnackbar();

  useEffect(() => {
    if (open && selectedEmployee) {
      loadEmployeeBiometrics();
      resetModal();
    }
  }, [open, selectedEmployee]);

  const resetModal = () => {
    setStep('type');
    setEnrollmentType(null);
    setSelectedFinger(null);
    setProgress(0);
    setIsCapturing(false);
    setCaptureComplete(false);
    setError(null);
  };

  const loadEmployeeBiometrics = async () => {
    try {
      if (selectedEmployee) {
        const biometrics = await apiService.getEmployeeBiometricSummary(selectedEmployee.ID_EMPLEADO);
        setEmployeeBiometrics(biometrics);
      }
    } catch (error) {
      console.error('Error loading employee biometrics:', error);
    }
  };

  const handleTypeSelection = (type) => {
    setEnrollmentType(type);
    if (type === 'fingerprint') {
      setStep('finger');
    } else if (type === 'facial') {
      setStep('capture');
    }
  };

  const handleFingerSelection = (finger) => {
    setSelectedFinger(finger);
    setStep('capture');
  };

  const startCapture = async () => {
    setIsCapturing(true);
    setError(null);
    setProgress(0);

    try {
      if (enrollmentType === 'fingerprint') {
        await captureFingerprintData();
      } else if (enrollmentType === 'facial') {
        await captureFacialImage();
      }
    } catch (error) {
      setError(error.message);
      setIsCapturing(false);
    }
  };

  const captureFingerprintData = async () => {
    setStep('processing');
    
    // Simulate fingerprint capture with progress
    for (let i = 0; i <= 100; i += 10) {
      setProgress(i);
      await new Promise(resolve => setTimeout(resolve, 200));
    }

    try {
      const result = await captureFingerprint(selectedFinger.id);
      
      if (result.success) {
        // Send to backend
        const response = await apiService.enrollFingerprint(
          selectedEmployee.ID_EMPLEADO,
          selectedFinger.id,
          result.data
        );

        setCaptureComplete(true);
        enqueueSnackbar(response.message, { variant: 'success' });
        
        if (onEnrollmentComplete) {
          onEnrollmentComplete();
        }
      } else {
        throw new Error('Error capturing fingerprint');
      }
    } catch (error) {
      setError(error.message);
    } finally {
      setIsCapturing(false);
    }
  };

  const captureFacialImage = async () => {
    if (!webcamRef.current) {
      throw new Error('Cámara no disponible');
    }

    setStep('processing');

    // Capture image from webcam
    const imageSrc = webcamRef.current.getScreenshot();
    
    // Simulate processing
    for (let i = 0; i <= 100; i += 15) {
      setProgress(i);
      await new Promise(resolve => setTimeout(resolve, 150));
    }

    try {
      const result = await captureFacialData(imageSrc);
      
      if (result.success) {
        // Send to backend
        const response = await apiService.enrollFacial(
          selectedEmployee.ID_EMPLEADO,
          result.data
        );

        setCaptureComplete(true);
        enqueueSnackbar(response.message, { variant: 'success' });
        
        if (onEnrollmentComplete) {
          onEnrollmentComplete();
        }
      } else {
        throw new Error('Error processing facial data');
      }
    } catch (error) {
      setError(error.message);
    } finally {
      setIsCapturing(false);
    }
  };

  const renderTypeSelection = () => (
    <Box>
      <Typography variant="h6" gutterBottom>
        Seleccionar tipo de inscripción biométrica
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Empleado: {selectedEmployee?.NOMBRE} {selectedEmployee?.APELLIDO}
      </Typography>

      <Grid container spacing={2}>
        <Grid item xs={12} md={6}>
          <Card 
            sx={{ 
              cursor: devices.fingerprint.available ? 'pointer' : 'not-allowed',
              opacity: devices.fingerprint.available ? 1 : 0.5,
              '&:hover': {
                transform: devices.fingerprint.available ? 'translateY(-2px)' : 'none',
                boxShadow: devices.fingerprint.available ? 4 : 1
              }
            }}
            onClick={() => devices.fingerprint.available && handleTypeSelection('fingerprint')}
          >
            <CardContent sx={{ textAlign: 'center', py: 3 }}>
              <Fingerprint sx={{ fontSize: 48, color: 'primary.main', mb: 2 }} />
              <Typography variant="h6" gutterBottom>
                Huella Dactilar
              </Typography>
              <Typography variant="body2" color="text.secondary">
                Registrar huellas dactilares para verificación biométrica
              </Typography>
              <Box sx={{ mt: 2 }}>
                <Chip 
                  label={devices.fingerprint.available ? 'Disponible' : 'No disponible'}
                  color={devices.fingerprint.available ? 'success' : 'error'}
                  size="small"
                />
              </Box>
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} md={6}>
          <Card 
            sx={{ 
              cursor: devices.camera.available ? 'pointer' : 'not-allowed',
              opacity: devices.camera.available ? 1 : 0.5,
              '&:hover': {
                transform: devices.camera.available ? 'translateY(-2px)' : 'none',
                boxShadow: devices.camera.available ? 4 : 1
              }
            }}
            onClick={() => devices.camera.available && handleTypeSelection('facial')}
          >
            <CardContent sx={{ textAlign: 'center', py: 3 }}>
              <Face sx={{ fontSize: 48, color: 'primary.main', mb: 2 }} />
              <Typography variant="h6" gutterBottom>
                Reconocimiento Facial
              </Typography>
              <Typography variant="body2" color="text.secondary">
                Registrar patrones faciales para verificación biométrica
              </Typography>
              <Box sx={{ mt: 2 }}>
                <Chip 
                  label={devices.camera.available ? 'Disponible' : 'No disponible'}
                  color={devices.camera.available ? 'success' : 'error'}
                  size="small"
                />
              </Box>
            </CardContent>
          </Card>
        </Grid>
      </Grid>

      {employeeBiometrics && (
        <Box sx={{ mt: 3 }}>
          <Typography variant="subtitle1" gutterBottom>
            Estado actual de inscripciones
          </Typography>
          <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap' }}>
            <Chip 
              icon={<Fingerprint />}
              label={`Huellas: ${employeeBiometrics.enrollments.fingerprint.count}`}
              color={employeeBiometrics.enrollments.fingerprint.count > 0 ? 'success' : 'default'}
              size="small"
            />
            <Chip 
              icon={<Face />}
              label={`Facial: ${employeeBiometrics.enrollments.facial.enrolled ? 'Registrado' : 'No registrado'}`}
              color={employeeBiometrics.enrollments.facial.enrolled ? 'success' : 'default'}
              size="small"
            />
          </Box>
        </Box>
      )}
    </Box>
  );

  const renderFingerSelection = () => (
    <Box>
      <Typography variant="h6" gutterBottom>
        Seleccionar dedo para inscripción
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Seleccione el dedo que desea registrar
      </Typography>

      <Grid container spacing={2}>
        <Grid item xs={12} md={6}>
          <Typography variant="subtitle2" gutterBottom>Mano Izquierda</Typography>
          <List dense>
            {fingerOptions.filter(f => f.hand === 'left').map((finger) => {
              const isEnrolled = employeeBiometrics?.enrollments.fingerprint.enrolled_fingers.includes(finger.id);
              return (
                <ListItem key={finger.id} disablePadding>
                  <ListItemButton 
                    onClick={() => handleFingerSelection(finger)}
                    disabled={isCapturing}
                  >
                    <ListItemAvatar>
                      <Avatar sx={{ bgcolor: isEnrolled ? 'success.main' : 'grey.300' }}>
                        {isEnrolled ? <CheckCircle /> : <Fingerprint />}
                      </Avatar>
                    </ListItemAvatar>
                    <ListItemText 
                      primary={finger.label}
                      secondary={isEnrolled ? 'Ya registrado' : 'No registrado'}
                    />
                  </ListItemButton>
                </ListItem>
              );
            })}
          </List>
        </Grid>

        <Grid item xs={12} md={6}>
          <Typography variant="subtitle2" gutterBottom>Mano Derecha</Typography>
          <List dense>
            {fingerOptions.filter(f => f.hand === 'right').map((finger) => {
              const isEnrolled = employeeBiometrics?.enrollments.fingerprint.enrolled_fingers.includes(finger.id);
              return (
                <ListItem key={finger.id} disablePadding>
                  <ListItemButton 
                    onClick={() => handleFingerSelection(finger)}
                    disabled={isCapturing}
                  >
                    <ListItemAvatar>
                      <Avatar sx={{ bgcolor: isEnrolled ? 'success.main' : 'grey.300' }}>
                        {isEnrolled ? <CheckCircle /> : <Fingerprint />}
                      </Avatar>
                    </ListItemAvatar>
                    <ListItemText 
                      primary={finger.label}
                      secondary={isEnrolled ? 'Ya registrado' : 'No registrado'}
                    />
                  </ListItemButton>
                </ListItem>
              );
            })}
          </List>
        </Grid>
      </Grid>
    </Box>
  );

  const renderCapture = () => (
    <Box>
      <Typography variant="h6" gutterBottom>
        {enrollmentType === 'fingerprint' ? 'Captura de Huella Dactilar' : 'Captura Facial'}
      </Typography>
      
      {enrollmentType === 'fingerprint' && selectedFinger && (
        <Alert severity="info" sx={{ mb: 3 }}>
          Preparándose para capturar: <strong>{selectedFinger.label}</strong>
        </Alert>
      )}

      {enrollmentType === 'facial' && devices.camera.available && (
        <Box sx={{ display: 'flex', justifyContent: 'center', mb: 3 }}>
          <Box sx={{ 
            width: 320, 
            height: 240, 
            border: '2px dashed #ccc', 
            borderRadius: 2,
            overflow: 'hidden'
          }}>
            <Webcam
              ref={webcamRef}
              audio={false}
              width={320}
              height={240}
              screenshotFormat="image/jpeg"
              videoConstraints={{
                width: 320,
                height: 240,
                facingMode: "user"
              }}
            />
          </Box>
        </Box>
      )}

      {!isCapturing && !captureComplete && (
        <Box sx={{ textAlign: 'center' }}>
          <Button
            variant="contained"
            size="large"
            startIcon={enrollmentType === 'fingerprint' ? <Fingerprint /> : <PhotoCamera />}
            onClick={startCapture}
            disabled={!devices[enrollmentType === 'fingerprint' ? 'fingerprint' : 'camera'].available}
          >
            {enrollmentType === 'fingerprint' ? 'Capturar Huella' : 'Capturar Imagen'}
          </Button>
        </Box>
      )}

      {error && (
        <Alert severity="error" sx={{ mt: 2 }}>
          {error}
        </Alert>
      )}
    </Box>
  );

  const renderProcessing = () => (
    <Box sx={{ textAlign: 'center' }}>
      <Typography variant="h6" gutterBottom>
        Procesando datos biométricos...
      </Typography>
      
      <Box sx={{ my: 4 }}>
        <CircularProgress size={60} />
      </Box>
      
      <Box sx={{ mb: 3 }}>
        <LinearProgress variant="determinate" value={progress} sx={{ height: 8, borderRadius: 4 }} />
        <Typography variant="body2" color="text.secondary" sx={{ mt: 1 }}>
          {progress}% completado
        </Typography>
      </Box>

      {captureComplete && (
        <Alert severity="success">
          <Typography variant="subtitle1">
            ¡Inscripción biométrica completada exitosamente!
          </Typography>
        </Alert>
      )}
    </Box>
  );

  const renderStep = () => {
    switch (step) {
      case 'type':
        return renderTypeSelection();
      case 'finger':
        return renderFingerSelection();
      case 'capture':
        return renderCapture();
      case 'processing':
        return renderProcessing();
      default:
        return renderTypeSelection();
    }
  };

  return (
    <Dialog 
      open={open} 
      onClose={!isCapturing ? onClose : undefined}
      maxWidth="md" 
      fullWidth
      PaperProps={{
        sx: { minHeight: 500 }
      }}
    >
      <DialogTitle>
        <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <Typography variant="h5">
            Inscripción Biométrica
          </Typography>
          {!isCapturing && (
            <IconButton onClick={onClose}>
              <Close />
            </IconButton>
          )}
        </Box>
      </DialogTitle>

      <DialogContent>
        {renderStep()}
      </DialogContent>

      <DialogActions>
        {step !== 'type' && step !== 'processing' && !isCapturing && (
          <Button onClick={() => setStep(step === 'finger' ? 'type' : 'finger')}>
            Atrás
          </Button>
        )}
        
        {captureComplete && (
          <Button 
            variant="contained" 
            onClick={onClose}
            startIcon={<CheckCircle />}
          >
            Finalizar
          </Button>
        )}
        
        {!captureComplete && !isCapturing && step !== 'processing' && (
          <Button onClick={onClose}>
            Cancelar
          </Button>
        )}
      </DialogActions>
    </Dialog>
  );
};

export default BiometricEnrollmentModal;