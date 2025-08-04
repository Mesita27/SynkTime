import React, { useState, useRef } from 'react';
import {
  Box,
  Typography,
  Grid,
  Card,
  CardContent,
  Button,
  TextField,
  Autocomplete,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Alert,
  Chip,
  Avatar,
  CircularProgress,
  Stepper,
  Step,
  StepLabel,
  IconButton
} from '@mui/material';
import {
  Schedule,
  Fingerprint,
  Face,
  PhotoCamera,
  CheckCircle,
  Close,
  Person,
  AccessTime
} from '@mui/icons-material';
import { Helmet } from 'react-helmet-async';
import Webcam from 'react-webcam';
import { useBiometric } from '../context/BiometricContext';
import { useSnackbar } from 'notistack';

// Mock employee data
const mockEmployees = [
  { ID_EMPLEADO: 1, NOMBRE: 'Juan Carlos', APELLIDO: 'Pérez González', CEDULA: '12345678' },
  { ID_EMPLEADO: 2, NOMBRE: 'María Elena', APELLIDO: 'Rodríguez López', CEDULA: '87654321' },
  { ID_EMPLEADO: 3, NOMBRE: 'Carlos Alberto', APELLIDO: 'García Martínez', CEDULA: '11223344' },
];

const verificationSteps = ['Seleccionar Empleado', 'Método de Verificación', 'Verificación', 'Completado'];

const AttendanceRegistration = () => {
  const [selectedEmployee, setSelectedEmployee] = useState(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [activeStep, setActiveStep] = useState(0);
  const [verificationMethod, setVerificationMethod] = useState(null);
  const [isVerifying, setIsVerifying] = useState(false);
  const [verificationComplete, setVerificationComplete] = useState(false);
  const [verificationResult, setVerificationResult] = useState(null);

  const webcamRef = useRef(null);
  const { devices, verifyBiometric, captureFacialData, captureFingerprint } = useBiometric();
  const { enqueueSnackbar } = useSnackbar();

  const handleOpenModal = () => {
    setModalOpen(true);
    setActiveStep(0);
    setSelectedEmployee(null);
    setVerificationMethod(null);
    setVerificationComplete(false);
    setVerificationResult(null);
  };

  const handleCloseModal = () => {
    setModalOpen(false);
    setActiveStep(0);
    setSelectedEmployee(null);
    setVerificationMethod(null);
    setVerificationComplete(false);
    setVerificationResult(null);
  };

  const handleEmployeeSelect = (employee) => {
    setSelectedEmployee(employee);
    setActiveStep(1);
  };

  const handleMethodSelect = (method) => {
    setVerificationMethod(method);
    setActiveStep(2);
  };

  const handleStartVerification = async () => {
    setIsVerifying(true);

    try {
      let verificationData;
      
      if (verificationMethod === 'facial') {
        // Capture facial image
        const imageSrc = webcamRef.current.getScreenshot();
        const facialResult = await captureFacialData(imageSrc);
        verificationData = facialResult.data;
      } else if (verificationMethod === 'fingerprint') {
        // Simulate fingerprint capture
        const fingerprintResult = await captureFingerprint('right_index');
        verificationData = fingerprintResult.data;
      } else {
        // Traditional photo capture
        const imageSrc = webcamRef.current.getScreenshot();
        verificationData = imageSrc;
      }

      // Simulate verification process
      await new Promise(resolve => setTimeout(resolve, 2000));

      // Mock verification result
      const confidence = Math.random() * 0.4 + 0.6; // 60-100%
      const success = confidence >= 0.75;

      setVerificationResult({
        success,
        confidence: Math.round(confidence * 100),
        method: verificationMethod
      });

      if (success) {
        enqueueSnackbar('Verificación exitosa. Asistencia registrada.', { variant: 'success' });
        setActiveStep(3);
        setVerificationComplete(true);
      } else {
        enqueueSnackbar('Verificación fallida. Intente nuevamente.', { variant: 'error' });
      }
    } catch (error) {
      enqueueSnackbar('Error durante la verificación.', { variant: 'error' });
    } finally {
      setIsVerifying(false);
    }
  };

  const renderEmployeeSelection = () => (
    <Box>
      <Typography variant="h6" gutterBottom>
        Seleccionar Empleado
      </Typography>
      <Autocomplete
        options={mockEmployees}
        getOptionLabel={(option) => `${option.NOMBRE} ${option.APELLIDO} - ${option.CEDULA}`}
        renderOption={(props, option) => (
          <Box component="li" {...props}>
            <Avatar sx={{ mr: 2, bgcolor: 'primary.main' }}>
              {option.NOMBRE.charAt(0)}
            </Avatar>
            <Box>
              <Typography variant="subtitle2">
                {option.NOMBRE} {option.APELLIDO}
              </Typography>
              <Typography variant="caption" color="text.secondary">
                Cédula: {option.CEDULA}
              </Typography>
            </Box>
          </Box>
        )}
        renderInput={(params) => (
          <TextField
            {...params}
            label="Buscar empleado"
            placeholder="Nombre, apellido o cédula"
            fullWidth
          />
        )}
        onChange={(event, value) => {
          if (value) {
            handleEmployeeSelect(value);
          }
        }}
        sx={{ mb: 3 }}
      />

      <Grid container spacing={2}>
        {mockEmployees.map((employee) => (
          <Grid item xs={12} md={4} key={employee.ID_EMPLEADO}>
            <Card 
              sx={{ 
                cursor: 'pointer',
                '&:hover': { transform: 'translateY(-2px)', boxShadow: 4 }
              }}
              onClick={() => handleEmployeeSelect(employee)}
            >
              <CardContent sx={{ textAlign: 'center' }}>
                <Avatar sx={{ mx: 'auto', mb: 2, bgcolor: 'primary.main', width: 56, height: 56 }}>
                  {employee.NOMBRE.charAt(0)}
                </Avatar>
                <Typography variant="subtitle1" gutterBottom>
                  {employee.NOMBRE} {employee.APELLIDO}
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  {employee.CEDULA}
                </Typography>
              </CardContent>
            </Card>
          </Grid>
        ))}
      </Grid>
    </Box>
  );

  const renderMethodSelection = () => (
    <Box>
      <Typography variant="h6" gutterBottom>
        Método de Verificación
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Empleado: {selectedEmployee?.NOMBRE} {selectedEmployee?.APELLIDO}
      </Typography>

      <Grid container spacing={2}>
        <Grid item xs={12} md={4}>
          <Card 
            sx={{ 
              cursor: devices.fingerprint.available ? 'pointer' : 'not-allowed',
              opacity: devices.fingerprint.available ? 1 : 0.5,
              '&:hover': {
                transform: devices.fingerprint.available ? 'translateY(-2px)' : 'none',
                boxShadow: devices.fingerprint.available ? 4 : 1
              }
            }}
            onClick={() => devices.fingerprint.available && handleMethodSelect('fingerprint')}
          >
            <CardContent sx={{ textAlign: 'center', py: 3 }}>
              <Fingerprint sx={{ fontSize: 48, color: 'primary.main', mb: 2 }} />
              <Typography variant="h6" gutterBottom>
                Huella Dactilar
              </Typography>
              <Chip 
                label={devices.fingerprint.available ? 'Disponible' : 'No disponible'}
                color={devices.fingerprint.available ? 'success' : 'error'}
                size="small"
              />
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} md={4}>
          <Card 
            sx={{ 
              cursor: devices.camera.available ? 'pointer' : 'not-allowed',
              opacity: devices.camera.available ? 1 : 0.5,
              '&:hover': {
                transform: devices.camera.available ? 'translateY(-2px)' : 'none',
                boxShadow: devices.camera.available ? 4 : 1
              }
            }}
            onClick={() => devices.camera.available && handleMethodSelect('facial')}
          >
            <CardContent sx={{ textAlign: 'center', py: 3 }}>
              <Face sx={{ fontSize: 48, color: 'primary.main', mb: 2 }} />
              <Typography variant="h6" gutterBottom>
                Reconocimiento Facial
              </Typography>
              <Chip 
                label={devices.camera.available ? 'Disponible' : 'No disponible'}
                color={devices.camera.available ? 'success' : 'error'}
                size="small"
              />
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} md={4}>
          <Card 
            sx={{ 
              cursor: devices.camera.available ? 'pointer' : 'not-allowed',
              opacity: devices.camera.available ? 1 : 0.5,
              '&:hover': {
                transform: devices.camera.available ? 'translateY(-2px)' : 'none',
                boxShadow: devices.camera.available ? 4 : 1
              }
            }}
            onClick={() => devices.camera.available && handleMethodSelect('traditional')}
          >
            <CardContent sx={{ textAlign: 'center', py: 3 }}>
              <PhotoCamera sx={{ fontSize: 48, color: 'primary.main', mb: 2 }} />
              <Typography variant="h6" gutterBottom>
                Foto Tradicional
              </Typography>
              <Chip 
                label={devices.camera.available ? 'Disponible' : 'No disponible'}
                color={devices.camera.available ? 'success' : 'error'}
                size="small"
              />
            </CardContent>
          </Card>
        </Grid>
      </Grid>
    </Box>
  );

  const renderVerification = () => (
    <Box sx={{ textAlign: 'center' }}>
      <Typography variant="h6" gutterBottom>
        Verificación en Proceso
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Método: {verificationMethod === 'facial' ? 'Reconocimiento Facial' : 
                 verificationMethod === 'fingerprint' ? 'Huella Dactilar' : 'Foto Tradicional'}
      </Typography>

      {(verificationMethod === 'facial' || verificationMethod === 'traditional') && devices.camera.available && (
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

      {verificationMethod === 'fingerprint' && (
        <Box sx={{ mb: 3 }}>
          <Fingerprint sx={{ fontSize: 120, color: 'primary.main', mb: 2 }} />
          <Typography variant="body1">
            Coloque su dedo en el lector de huellas
          </Typography>
        </Box>
      )}

      {!isVerifying && !verificationComplete && (
        <Button
          variant="contained"
          size="large"
          onClick={handleStartVerification}
          startIcon={verificationMethod === 'fingerprint' ? <Fingerprint /> : <PhotoCamera />}
        >
          Iniciar Verificación
        </Button>
      )}

      {isVerifying && (
        <Box>
          <CircularProgress size={60} sx={{ mb: 2 }} />
          <Typography variant="body1">
            Verificando...
          </Typography>
        </Box>
      )}

      {verificationResult && !verificationResult.success && (
        <Alert severity="error" sx={{ mt: 2 }}>
          Verificación fallida. Confianza: {verificationResult.confidence}%
        </Alert>
      )}
    </Box>
  );

  const renderComplete = () => (
    <Box sx={{ textAlign: 'center' }}>
      <CheckCircle sx={{ fontSize: 80, color: 'success.main', mb: 2 }} />
      <Typography variant="h5" gutterBottom>
        ¡Asistencia Registrada!
      </Typography>
      <Typography variant="body1" color="text.secondary" sx={{ mb: 3 }}>
        La asistencia de {selectedEmployee?.NOMBRE} {selectedEmployee?.APELLIDO} ha sido registrada exitosamente.
      </Typography>
      
      {verificationResult && (
        <Box sx={{ mb: 3 }}>
          <Chip
            icon={<CheckCircle />}
            label={`Verificación exitosa - ${verificationResult.confidence}% confianza`}
            color="success"
          />
        </Box>
      )}

      <Typography variant="body2" color="text.secondary">
        Hora de registro: {new Date().toLocaleTimeString()}
      </Typography>
    </Box>
  );

  return (
    <>
      <Helmet>
        <title>Registro de Asistencia - SynkTime</title>
      </Helmet>

      <Box>
        {/* Header */}
        <Box sx={{ mb: 4 }}>
          <Typography variant="h4" component="h1" gutterBottom sx={{ fontWeight: 600 }}>
            Registro de Asistencia
          </Typography>
          <Typography variant="body1" color="text.secondary">
            Registre la asistencia de empleados utilizando verificación biométrica
          </Typography>
        </Box>

        {/* Quick Actions */}
        <Grid container spacing={3} sx={{ mb: 4 }}>
          <Grid item xs={12} md={6}>
            <Card>
              <CardContent sx={{ textAlign: 'center', py: 4 }}>
                <Schedule sx={{ fontSize: 60, color: 'primary.main', mb: 2 }} />
                <Typography variant="h5" gutterBottom>
                  Registrar Asistencia
                </Typography>
                <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
                  Verificación biométrica para registro de asistencia
                </Typography>
                <Button
                  variant="contained"
                  size="large"
                  startIcon={<Person />}
                  onClick={handleOpenModal}
                >
                  Iniciar Registro
                </Button>
              </CardContent>
            </Card>
          </Grid>

          <Grid item xs={12} md={6}>
            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom>
                  Resumen del Día
                </Typography>
                <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2 }}>
                  <Typography variant="body2">Empleados registrados:</Typography>
                  <Typography variant="body2" sx={{ fontWeight: 600 }}>142</Typography>
                </Box>
                <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2 }}>
                  <Typography variant="body2">Verificaciones biométricas:</Typography>
                  <Typography variant="body2" sx={{ fontWeight: 600 }}>89</Typography>
                </Box>
                <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2 }}>
                  <Typography variant="body2">Último registro:</Typography>
                  <Typography variant="body2" sx={{ fontWeight: 600 }}>
                    {new Date().toLocaleTimeString()}
                  </Typography>
                </Box>
              </CardContent>
            </Card>
          </Grid>
        </Grid>

        {/* Registration Modal */}
        <Dialog 
          open={modalOpen} 
          onClose={handleCloseModal}
          maxWidth="md" 
          fullWidth
          PaperProps={{
            sx: { minHeight: 500 }
          }}
        >
          <DialogTitle>
            <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <Typography variant="h5">
                Registro de Asistencia
              </Typography>
              <IconButton onClick={handleCloseModal}>
                <Close />
              </IconButton>
            </Box>
          </DialogTitle>

          <DialogContent>
            <Box sx={{ mb: 3 }}>
              <Stepper activeStep={activeStep} alternativeLabel>
                {verificationSteps.map((label) => (
                  <Step key={label}>
                    <StepLabel>{label}</StepLabel>
                  </Step>
                ))}
              </Stepper>
            </Box>

            {activeStep === 0 && renderEmployeeSelection()}
            {activeStep === 1 && renderMethodSelection()}
            {activeStep === 2 && renderVerification()}
            {activeStep === 3 && renderComplete()}
          </DialogContent>

          <DialogActions>
            {activeStep > 0 && activeStep < 3 && !isVerifying && (
              <Button onClick={() => setActiveStep(activeStep - 1)}>
                Atrás
              </Button>
            )}
            
            {activeStep === 3 && (
              <Button 
                variant="contained" 
                onClick={handleCloseModal}
                startIcon={<CheckCircle />}
              >
                Finalizar
              </Button>
            )}
            
            {activeStep < 3 && !isVerifying && (
              <Button onClick={handleCloseModal}>
                Cancelar
              </Button>
            )}
          </DialogActions>
        </Dialog>
      </Box>
    </>
  );
};

export default AttendanceRegistration;