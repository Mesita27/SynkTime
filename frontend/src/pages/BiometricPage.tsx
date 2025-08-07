import React, { useState, useEffect } from 'react';
import {
  Box,
  Typography,
  Grid,
  Card,
  CardContent,
  Button,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Chip,
  Avatar,
  TextField,
  MenuItem,
  LinearProgress,
  Alert,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  IconButton,
  Tooltip,
} from '@mui/material';
import {
  Fingerprint,
  Face,
  PersonAdd,
  Refresh,
  Delete,
  Visibility,
  TrendingUp,
  People,
  Security,
} from '@mui/icons-material';
import axios from 'axios';
import BiometricEnrollmentModal from '../components/BiometricEnrollmentModal';

interface Employee {
  ID_EMPLEADO: number;
  NOMBRE: string;
  APELLIDO: string;
  DNI: string;
  ESTABLECIMIENTO_NOMBRE: string;
  SEDE_NOMBRE: string;
}

interface BiometricStats {
  totalEmployees: number;
  enrolledEmployees: number;
  fingerprintEnrollments: number;
  facialEnrollments: number;
  enrollmentPercentage: number;
  establishmentStats: Array<{
    establecimiento: string;
    total_employees: number;
    enrolled_employees: number;
  }>;
}

interface BiometricData {
  ID: number;
  BIOMETRIC_TYPE: 'fingerprint' | 'facial';
  FINGER_TYPE?: string;
  CREATED_AT: string;
  UPDATED_AT: string;
}

interface EmployeeBiometricSummary {
  employee: {
    ID_EMPLEADO: number;
    NOMBRE: string;
    APELLIDO: string;
  };
  fingerprints: BiometricData[];
  facial: BiometricData[];
  totalEnrollments: number;
  hasFingerprint: boolean;
  hasFacial: boolean;
}

const BiometricPage: React.FC = () => {
  const [employees, setEmployees] = useState<Employee[]>([]);
  const [stats, setStats] = useState<BiometricStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [selectedEmployee, setSelectedEmployee] = useState<Employee | null>(null);
  const [enrollmentModalOpen, setEnrollmentModalOpen] = useState(false);
  const [viewModalOpen, setViewModalOpen] = useState(false);
  const [employeeSummary, setEmployeeSummary] = useState<EmployeeBiometricSummary | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState('all');

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    setLoading(true);
    try {
      await Promise.all([
        loadEmployees(),
        loadStats()
      ]);
    } catch (error) {
      console.error('Error loading biometric data:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadEmployees = async () => {
    try {
      const token = localStorage.getItem('synktime_token');
      const response = await axios.get('http://localhost:3001/api/employees', {
        headers: {
          Authorization: `Bearer ${token}`,
        },
        params: {
          limit: 100 // Get more employees for biometric management
        }
      });

      if (response.data.success) {
        setEmployees(response.data.data.employees);
      }
    } catch (error) {
      console.error('Error loading employees:', error);
    }
  };

  const loadStats = async () => {
    try {
      const token = localStorage.getItem('synktime_token');
      const response = await axios.get('http://localhost:3001/api/biometric/stats', {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      if (response.data.success) {
        setStats(response.data.data);
      }
    } catch (error) {
      console.error('Error loading biometric stats:', error);
    }
  };

  const loadEmployeeSummary = async (employeeId: number) => {
    try {
      const token = localStorage.getItem('synktime_token');
      const response = await axios.get(`http://localhost:3001/api/biometric/employee/${employeeId}/summary`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      if (response.data.success) {
        setEmployeeSummary(response.data.data);
      }
    } catch (error) {
      console.error('Error loading employee biometric summary:', error);
    }
  };

  const handleEnrollEmployee = (employee: Employee) => {
    setSelectedEmployee(employee);
    setEnrollmentModalOpen(true);
  };

  const handleViewEmployee = async (employee: Employee) => {
    setSelectedEmployee(employee);
    await loadEmployeeSummary(employee.ID_EMPLEADO);
    setViewModalOpen(true);
  };

  const handleDeleteBiometric = async (employeeId: number) => {
    if (!window.confirm('¿Estás seguro de que deseas eliminar todos los datos biométricos de este empleado?')) {
      return;
    }

    try {
      const token = localStorage.getItem('synktime_token');
      await axios.delete(`http://localhost:3001/api/biometric/employee/${employeeId}`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      // Refresh data
      loadData();
    } catch (error) {
      console.error('Error deleting biometric data:', error);
    }
  };

  const getEmployeeEnrollmentStatus = (employee: Employee) => {
    // This would need to be enhanced with actual biometric data
    // For now, we'll use a placeholder
    return Math.random() > 0.5 ? 'enrolled' : 'pending';
  };

  const filteredEmployees = employees.filter(employee => {
    const matchesSearch = 
      employee.NOMBRE.toLowerCase().includes(searchTerm.toLowerCase()) ||
      employee.APELLIDO.toLowerCase().includes(searchTerm.toLowerCase()) ||
      employee.DNI.includes(searchTerm);

    if (filterStatus === 'all') return matchesSearch;
    
    const status = getEmployeeEnrollmentStatus(employee);
    return matchesSearch && status === filterStatus;
  });

  return (
    <Box>
      <Typography variant="h4" gutterBottom>
        Gestión Biométrica
      </Typography>

      {/* Statistics Cards */}
      {stats && (
        <Grid container spacing={3} sx={{ mb: 4 }}>
          <Grid item xs={12} sm={6} md={3}>
            <Card>
              <CardContent>
                <Box display="flex" alignItems="center">
                  <Avatar sx={{ bgcolor: 'primary.main', mr: 2 }}>
                    <People />
                  </Avatar>
                  <Box>
                    <Typography color="textSecondary" gutterBottom>
                      Total Empleados
                    </Typography>
                    <Typography variant="h5">
                      {stats.totalEmployees}
                    </Typography>
                  </Box>
                </Box>
              </CardContent>
            </Card>
          </Grid>

          <Grid item xs={12} sm={6} md={3}>
            <Card>
              <CardContent>
                <Box display="flex" alignItems="center">
                  <Avatar sx={{ bgcolor: 'success.main', mr: 2 }}>
                    <Security />
                  </Avatar>
                  <Box>
                    <Typography color="textSecondary" gutterBottom>
                      Empleados Inscritos
                    </Typography>
                    <Typography variant="h5">
                      {stats.enrolledEmployees}
                    </Typography>
                    <Typography variant="body2" color="success.main">
                      {stats.enrollmentPercentage}% completado
                    </Typography>
                  </Box>
                </Box>
              </CardContent>
            </Card>
          </Grid>

          <Grid item xs={12} sm={6} md={3}>
            <Card>
              <CardContent>
                <Box display="flex" alignItems="center">
                  <Avatar sx={{ bgcolor: 'info.main', mr: 2 }}>
                    <Fingerprint />
                  </Avatar>
                  <Box>
                    <Typography color="textSecondary" gutterBottom>
                      Huellas Registradas
                    </Typography>
                    <Typography variant="h5">
                      {stats.fingerprintEnrollments}
                    </Typography>
                  </Box>
                </Box>
              </CardContent>
            </Card>
          </Grid>

          <Grid item xs={12} sm={6} md={3}>
            <Card>
              <CardContent>
                <Box display="flex" alignItems="center">
                  <Avatar sx={{ bgcolor: 'secondary.main', mr: 2 }}>
                    <Face />
                  </Avatar>
                  <Box>
                    <Typography color="textSecondary" gutterBottom>
                      Caras Registradas
                    </Typography>
                    <Typography variant="h5">
                      {stats.facialEnrollments}
                    </Typography>
                  </Box>
                </Box>
              </CardContent>
            </Card>
          </Grid>
        </Grid>
      )}

      {/* Progress Bar */}
      {stats && (
        <Card sx={{ mb: 3 }}>
          <CardContent>
            <Typography variant="h6" gutterBottom>
              Progreso de Inscripción Biométrica
            </Typography>
            <LinearProgress 
              variant="determinate" 
              value={stats.enrollmentPercentage}
              sx={{ height: 10, borderRadius: 5 }}
            />
            <Typography variant="body2" color="textSecondary" mt={1}>
              {stats.enrolledEmployees} de {stats.totalEmployees} empleados inscritos ({stats.enrollmentPercentage}%)
            </Typography>
          </CardContent>
        </Card>
      )}

      {/* Controls */}
      <Card sx={{ mb: 3 }}>
        <CardContent>
          <Grid container spacing={2} alignItems="center">
            <Grid item xs={12} sm={4}>
              <TextField
                fullWidth
                label="Buscar empleado"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                size="small"
              />
            </Grid>
            <Grid item xs={12} sm={3}>
              <TextField
                fullWidth
                select
                label="Estado"
                value={filterStatus}
                onChange={(e) => setFilterStatus(e.target.value)}
                size="small"
              >
                <MenuItem value="all">Todos</MenuItem>
                <MenuItem value="enrolled">Inscritos</MenuItem>
                <MenuItem value="pending">Pendientes</MenuItem>
              </TextField>
            </Grid>
            <Grid item xs={12} sm={5}>
              <Box display="flex" gap={1}>
                <Button
                  variant="outlined"
                  startIcon={<Refresh />}
                  onClick={loadData}
                  disabled={loading}
                >
                  Actualizar
                </Button>
              </Box>
            </Grid>
          </Grid>
        </CardContent>
      </Card>

      {/* Employee List */}
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
                  <TableCell>DNI</TableCell>
                  <TableCell>Establecimiento</TableCell>
                  <TableCell>Estado Biométrico</TableCell>
                  <TableCell>Acciones</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {filteredEmployees.map((employee) => {
                  const status = getEmployeeEnrollmentStatus(employee);
                  return (
                    <TableRow key={employee.ID_EMPLEADO}>
                      <TableCell>
                        <Box>
                          <Typography variant="body2" fontWeight="medium">
                            {employee.NOMBRE} {employee.APELLIDO}
                          </Typography>
                          <Typography variant="caption" color="textSecondary">
                            ID: {employee.ID_EMPLEADO}
                          </Typography>
                        </Box>
                      </TableCell>
                      <TableCell>{employee.DNI}</TableCell>
                      <TableCell>
                        <Box>
                          <Typography variant="body2">
                            {employee.ESTABLECIMIENTO_NOMBRE}
                          </Typography>
                          <Typography variant="caption" color="textSecondary">
                            {employee.SEDE_NOMBRE}
                          </Typography>
                        </Box>
                      </TableCell>
                      <TableCell>
                        <Chip
                          label={status === 'enrolled' ? 'Inscrito' : 'Pendiente'}
                          color={status === 'enrolled' ? 'success' : 'warning'}
                          size="small"
                        />
                      </TableCell>
                      <TableCell>
                        <Box display="flex" gap={1}>
                          <Tooltip title="Inscribir biométrico">
                            <IconButton
                              size="small"
                              onClick={() => handleEnrollEmployee(employee)}
                            >
                              <PersonAdd />
                            </IconButton>
                          </Tooltip>
                          <Tooltip title="Ver datos biométricos">
                            <IconButton
                              size="small"
                              onClick={() => handleViewEmployee(employee)}
                            >
                              <Visibility />
                            </IconButton>
                          </Tooltip>
                          <Tooltip title="Eliminar datos biométricos">
                            <IconButton
                              size="small"
                              color="error"
                              onClick={() => handleDeleteBiometric(employee.ID_EMPLEADO)}
                            >
                              <Delete />
                            </IconButton>
                          </Tooltip>
                        </Box>
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          </TableContainer>
        </CardContent>
      </Card>

      {/* Enrollment Modal */}
      <BiometricEnrollmentModal
        open={enrollmentModalOpen}
        onClose={() => setEnrollmentModalOpen(false)}
        employee={selectedEmployee ? {
          id: selectedEmployee.ID_EMPLEADO,
          nombre: selectedEmployee.NOMBRE,
          apellido: selectedEmployee.APELLIDO,
          dni: selectedEmployee.DNI
        } : null}
        onSuccess={() => {
          loadData();
          setEnrollmentModalOpen(false);
        }}
      />

      {/* View Biometric Data Modal */}
      <Dialog 
        open={viewModalOpen} 
        onClose={() => setViewModalOpen(false)}
        maxWidth="md"
        fullWidth
      >
        <DialogTitle>
          Datos Biométricos
          {selectedEmployee && (
            <Typography variant="body2" color="textSecondary">
              {selectedEmployee.NOMBRE} {selectedEmployee.APELLIDO} - DNI: {selectedEmployee.DNI}
            </Typography>
          )}
        </DialogTitle>
        <DialogContent>
          {employeeSummary ? (
            <Grid container spacing={3}>
              <Grid item xs={12} md={6}>
                <Card>
                  <CardContent>
                    <Typography variant="h6" gutterBottom>
                      <Fingerprint sx={{ mr: 1, verticalAlign: 'middle' }} />
                      Huellas Dactilares
                    </Typography>
                    {employeeSummary.fingerprints.length > 0 ? (
                      employeeSummary.fingerprints.map((fp, index) => (
                        <Chip
                          key={index}
                          label={fp.FINGER_TYPE}
                          color="primary"
                          size="small"
                          sx={{ mr: 1, mb: 1 }}
                        />
                      ))
                    ) : (
                      <Typography color="textSecondary">
                        No hay huellas registradas
                      </Typography>
                    )}
                  </CardContent>
                </Card>
              </Grid>
              <Grid item xs={12} md={6}>
                <Card>
                  <CardContent>
                    <Typography variant="h6" gutterBottom>
                      <Face sx={{ mr: 1, verticalAlign: 'middle' }} />
                      Reconocimiento Facial
                    </Typography>
                    {employeeSummary.hasFacial ? (
                      <Chip label="Registrado" color="success" size="small" />
                    ) : (
                      <Typography color="textSecondary">
                        No hay datos faciales registrados
                      </Typography>
                    )}
                  </CardContent>
                </Card>
              </Grid>
              <Grid item xs={12}>
                <Alert severity="info">
                  Total de registros biométricos: {employeeSummary.totalEnrollments}
                </Alert>
              </Grid>
            </Grid>
          ) : (
            <Box display="flex" justifyContent="center" p={3}>
              <Typography>Cargando datos biométricos...</Typography>
            </Box>
          )}
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setViewModalOpen(false)}>
            Cerrar
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
};

export default BiometricPage;