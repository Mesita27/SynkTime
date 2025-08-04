import React, { useState, useEffect } from 'react';
import {
  Box,
  Typography,
  Grid,
  Card,
  CardContent,
  Button,
  TextField,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Chip,
  Avatar,
  Alert,
  CircularProgress,
  InputAdornment,
  Fab
} from '@mui/material';
import {
  Search,
  Add,
  Fingerprint,
  Face,
  CheckCircle,
  Person,
  Business,
  LocationOn
} from '@mui/icons-material';
import { Helmet } from 'react-helmet-async';
import { useQuery } from 'react-query';
import { apiService } from '../services/api';
import { useBiometric } from '../context/BiometricContext';
import BiometricEnrollmentModal from '../components/biometric/BiometricEnrollmentModal';

// Mock employee data - in real app this would come from API
const mockEmployees = [
  {
    ID_EMPLEADO: 1,
    NOMBRE: 'Juan Carlos',
    APELLIDO: 'Pérez González',
    CEDULA: '12345678',
    SEDE: 'Sede Principal',
    ESTABLECIMIENTO: 'Oficina Central',
    CARGO: 'Gerente',
    ACTIVO: 1
  },
  {
    ID_EMPLEADO: 2,
    NOMBRE: 'María Elena',
    APELLIDO: 'Rodríguez López',
    CEDULA: '87654321',
    SEDE: 'Sede Principal',
    ESTABLECIMIENTO: 'Oficina Central',
    CARGO: 'Analista',
    ACTIVO: 1
  },
  {
    ID_EMPLEADO: 3,
    NOMBRE: 'Carlos Alberto',
    APELLIDO: 'García Martínez',
    CEDULA: '11223344',
    SEDE: 'Sede Norte',
    ESTABLECIMIENTO: 'Sucursal A',
    CARGO: 'Supervisor',
    ACTIVO: 1
  }
];

const BiometricEnrollment = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedEmployee, setSelectedEmployee] = useState(null);
  const [enrollmentModalOpen, setEnrollmentModalOpen] = useState(false);
  const [biometricStats, setBiometricStats] = useState(null);
  const { devices, isInitialized } = useBiometric();

  // Filter employees based on search term
  const filteredEmployees = mockEmployees.filter(employee =>
    `${employee.NOMBRE} ${employee.APELLIDO}`.toLowerCase().includes(searchTerm.toLowerCase()) ||
    employee.CEDULA.includes(searchTerm) ||
    employee.CARGO.toLowerCase().includes(searchTerm.toLowerCase())
  );

  // Fetch biometric statistics
  const { data: stats, isLoading: statsLoading } = useQuery(
    'biometricStats',
    () => apiService.getBiometricStats(),
    {
      onSuccess: (data) => setBiometricStats(data),
      onError: (error) => console.error('Error fetching biometric stats:', error)
    }
  );

  const handleOpenEnrollment = (employee) => {
    setSelectedEmployee(employee);
    setEnrollmentModalOpen(true);
  };

  const handleCloseEnrollment = () => {
    setEnrollmentModalOpen(false);
    setSelectedEmployee(null);
  };

  const handleEnrollmentComplete = () => {
    // Refresh data after enrollment
    // In real app, you'd refetch the employee's biometric status
    console.log('Enrollment completed for', selectedEmployee);
  };

  const getEnrollmentStatus = (employeeId) => {
    // Mock enrollment status - in real app this would come from API
    const statuses = {
      1: { fingerprint: 3, facial: true },
      2: { fingerprint: 1, facial: false },
      3: { fingerprint: 0, facial: true }
    };
    return statuses[employeeId] || { fingerprint: 0, facial: false };
  };

  return (
    <>
      <Helmet>
        <title>Inscripción Biométrica - SynkTime</title>
      </Helmet>

      <Box>
        {/* Header */}
        <Box sx={{ mb: 4 }}>
          <Typography variant="h4" component="h1" gutterBottom sx={{ fontWeight: 600 }}>
            Inscripción Biométrica
          </Typography>
          <Typography variant="body1" color="text.secondary">
            Gestione las inscripciones biométricas de empleados para verificación de asistencia
          </Typography>
        </Box>

        {/* Device Status Alert */}
        {isInitialized && (!devices.camera.available && !devices.fingerprint.available) && (
          <Alert severity="warning" sx={{ mb: 3 }}>
            No se detectaron dispositivos biométricos. Verifique que la cámara esté conectada y los permisos estén habilitados.
          </Alert>
        )}

        {/* Statistics Cards */}
        <Grid container spacing={3} sx={{ mb: 4 }}>
          <Grid item xs={12} md={3}>
            <Card>
              <CardContent>
                <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                  <Person sx={{ color: 'primary.main', mr: 1 }} />
                  <Typography variant="h6">Total Empleados</Typography>
                </Box>
                <Typography variant="h3" sx={{ fontWeight: 600 }}>
                  {statsLoading ? <CircularProgress size={24} /> : mockEmployees.length}
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  Empleados activos
                </Typography>
              </CardContent>
            </Card>
          </Grid>

          <Grid item xs={12} md={3}>
            <Card>
              <CardContent>
                <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                  <Fingerprint sx={{ color: 'success.main', mr: 1 }} />
                  <Typography variant="h6">Huellas Inscritas</Typography>
                </Box>
                <Typography variant="h3" sx={{ fontWeight: 600, color: 'success.main' }}>
                  {statsLoading ? <CircularProgress size={24} /> : '4'}
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  Total de huellas registradas
                </Typography>
              </CardContent>
            </Card>
          </Grid>

          <Grid item xs={12} md={3}>
            <Card>
              <CardContent>
                <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                  <Face sx={{ color: 'info.main', mr: 1 }} />
                  <Typography variant="h6">Facial Registrado</Typography>
                </Box>
                <Typography variant="h3" sx={{ fontWeight: 600, color: 'info.main' }}>
                  {statsLoading ? <CircularProgress size={24} /> : '2'}
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  Empleados con facial
                </Typography>
              </CardContent>
            </Card>
          </Grid>

          <Grid item xs={12} md={3}>
            <Card>
              <CardContent>
                <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                  <CheckCircle sx={{ color: 'warning.main', mr: 1 }} />
                  <Typography variant="h6">Cobertura</Typography>
                </Box>
                <Typography variant="h3" sx={{ fontWeight: 600, color: 'warning.main' }}>
                  {statsLoading ? <CircularProgress size={24} /> : '67%'}
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  Empleados con biometría
                </Typography>
              </CardContent>
            </Card>
          </Grid>
        </Grid>

        {/* Search and Actions */}
        <Card sx={{ mb: 3 }}>
          <CardContent>
            <Grid container spacing={2} alignItems="center">
              <Grid item xs={12} md={6}>
                <TextField
                  fullWidth
                  placeholder="Buscar empleados por nombre, cédula o cargo..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  InputProps={{
                    startAdornment: (
                      <InputAdornment position="start">
                        <Search />
                      </InputAdornment>
                    ),
                  }}
                />
              </Grid>
              <Grid item xs={12} md={6}>
                <Box sx={{ display: 'flex', gap: 2, justifyContent: 'flex-end' }}>
                  <Chip
                    icon={<Business />}
                    label="Sede Principal"
                    variant="outlined"
                    color="primary"
                  />
                  <Chip
                    icon={<LocationOn />}
                    label="Todas las ubicaciones"
                    variant="outlined"
                  />
                </Box>
              </Grid>
            </Grid>
          </CardContent>
        </Card>

        {/* Employees Table */}
        <Card>
          <CardContent>
            <Typography variant="h6" gutterBottom>
              Lista de Empleados
            </Typography>
            
            <TableContainer>
              <Table>
                <TableHead>
                  <TableRow>
                    <TableCell>Empleado</TableCell>
                    <TableCell>Cédula</TableCell>
                    <TableCell>Cargo</TableCell>
                    <TableCell>Ubicación</TableCell>
                    <TableCell align="center">Estado Biométrico</TableCell>
                    <TableCell align="center">Acciones</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {filteredEmployees.map((employee) => {
                    const enrollmentStatus = getEnrollmentStatus(employee.ID_EMPLEADO);
                    return (
                      <TableRow key={employee.ID_EMPLEADO}>
                        <TableCell>
                          <Box sx={{ display: 'flex', alignItems: 'center' }}>
                            <Avatar sx={{ mr: 2, bgcolor: 'primary.main' }}>
                              {employee.NOMBRE.charAt(0)}
                            </Avatar>
                            <Box>
                              <Typography variant="subtitle2">
                                {employee.NOMBRE} {employee.APELLIDO}
                              </Typography>
                              <Typography variant="caption" color="text.secondary">
                                ID: {employee.ID_EMPLEADO}
                              </Typography>
                            </Box>
                          </Box>
                        </TableCell>
                        <TableCell>{employee.CEDULA}</TableCell>
                        <TableCell>{employee.CARGO}</TableCell>
                        <TableCell>
                          <Box>
                            <Typography variant="body2">{employee.SEDE}</Typography>
                            <Typography variant="caption" color="text.secondary">
                              {employee.ESTABLECIMIENTO}
                            </Typography>
                          </Box>
                        </TableCell>
                        <TableCell align="center">
                          <Box sx={{ display: 'flex', justifyContent: 'center', gap: 1 }}>
                            <Chip
                              icon={<Fingerprint />}
                              label={`${enrollmentStatus.fingerprint} huellas`}
                              size="small"
                              color={enrollmentStatus.fingerprint > 0 ? 'success' : 'default'}
                            />
                            <Chip
                              icon={<Face />}
                              label={enrollmentStatus.facial ? 'Facial OK' : 'Sin facial'}
                              size="small"
                              color={enrollmentStatus.facial ? 'success' : 'default'}
                            />
                          </Box>
                        </TableCell>
                        <TableCell align="center">
                          <Button
                            variant="contained"
                            size="small"
                            startIcon={<Add />}
                            onClick={() => handleOpenEnrollment(employee)}
                            disabled={!isInitialized || (!devices.camera.available && !devices.fingerprint.available)}
                          >
                            Inscribir
                          </Button>
                        </TableCell>
                      </TableRow>
                    );
                  })}
                </TableBody>
              </Table>
            </TableContainer>

            {filteredEmployees.length === 0 && (
              <Box sx={{ textAlign: 'center', py: 4 }}>
                <Typography variant="body1" color="text.secondary">
                  No se encontraron empleados que coincidan con la búsqueda
                </Typography>
              </Box>
            )}
          </CardContent>
        </Card>

        {/* Floating Action Button */}
        <Fab
          color="primary"
          aria-label="add"
          sx={{
            position: 'fixed',
            bottom: 16,
            right: 16,
          }}
          onClick={() => {
            // Quick action - could open a modal to select employee
            if (filteredEmployees.length > 0) {
              handleOpenEnrollment(filteredEmployees[0]);
            }
          }}
        >
          <Add />
        </Fab>

        {/* Biometric Enrollment Modal */}
        <BiometricEnrollmentModal
          open={enrollmentModalOpen}
          onClose={handleCloseEnrollment}
          selectedEmployee={selectedEmployee}
          onEnrollmentComplete={handleEnrollmentComplete}
        />
      </Box>
    </>
  );
};

export default BiometricEnrollment;