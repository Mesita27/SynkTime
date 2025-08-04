import React, { useState, useEffect } from 'react';
import {
  Box,
  Typography,
  Grid,
  Card,
  CardContent,
  Paper,
  Avatar,
  Chip,
  LinearProgress
} from '@mui/material';
import {
  Dashboard as DashboardIcon,
  People,
  Fingerprint,
  Face,
  TrendingUp,
  Schedule,
  CheckCircle,
  Warning
} from '@mui/icons-material';
import { Helmet } from 'react-helmet-async';
import { 
  BarChart, 
  Bar, 
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip, 
  ResponsiveContainer,
  LineChart,
  Line,
  PieChart,
  Pie,
  Cell
} from 'recharts';
import { useAuth } from '../context/AuthContext';
import { useBiometric } from '../context/BiometricContext';

// Mock data for charts
const attendanceData = [
  { hour: '08:00', attendance: 45 },
  { hour: '09:00', attendance: 78 },
  { hour: '10:00', attendance: 65 },
  { hour: '11:00', attendance: 32 },
  { hour: '12:00', attendance: 89 },
  { hour: '13:00', attendance: 56 },
  { hour: '14:00', attendance: 67 },
  { hour: '15:00', attendance: 43 },
  { hour: '16:00', attendance: 71 },
  { hour: '17:00', attendance: 92 }
];

const biometricDistribution = [
  { name: 'Huella Dactilar', value: 65, color: '#3b82f6' },
  { name: 'Reconocimiento Facial', value: 25, color: '#10b981' },
  { name: 'Tradicional', value: 10, color: '#f59e0b' }
];

const recentActivity = [
  {
    id: 1,
    employee: 'Juan Pérez',
    action: 'Inscripción Huella',
    time: '10:30 AM',
    status: 'success'
  },
  {
    id: 2,
    employee: 'María González',
    action: 'Verificación Facial',
    time: '10:15 AM',
    status: 'success'
  },
  {
    id: 3,
    employee: 'Carlos López',
    action: 'Registro Asistencia',
    time: '09:45 AM',
    status: 'success'
  },
  {
    id: 4,
    employee: 'Ana Martínez',
    action: 'Inscripción Facial',
    time: '09:30 AM',
    status: 'warning'
  }
];

const Dashboard = () => {
  const { user } = useAuth();
  const { devices, isInitialized } = useBiometric();
  const [stats, setStats] = useState({
    totalEmployees: 156,
    enrolledEmployees: 98,
    todayAttendance: 142,
    biometricVerifications: 89
  });

  const completionRate = Math.round((stats.enrolledEmployees / stats.totalEmployees) * 100);

  return (
    <>
      <Helmet>
        <title>Dashboard - SynkTime</title>
      </Helmet>

      <Box>
        {/* Header */}
        <Box sx={{ mb: 4 }}>
          <Typography variant="h4" component="h1" gutterBottom sx={{ fontWeight: 600 }}>
            Dashboard
          </Typography>
          <Typography variant="body1" color="text.secondary">
            Bienvenido de vuelta, {user?.username}. Aquí está el resumen de hoy.
          </Typography>
        </Box>

        {/* Stats Cards */}
        <Grid container spacing={3} sx={{ mb: 4 }}>
          <Grid item xs={12} sm={6} md={3}>
            <Card sx={{ height: '100%' }}>
              <CardContent>
                <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                  <Avatar sx={{ bgcolor: 'primary.main', mr: 2 }}>
                    <People />
                  </Avatar>
                  <Box>
                    <Typography variant="h4" sx={{ fontWeight: 600 }}>
                      {stats.totalEmployees}
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                      Total Empleados
                    </Typography>
                  </Box>
                </Box>
                <Box sx={{ display: 'flex', alignItems: 'center' }}>
                  <TrendingUp sx={{ color: 'success.main', fontSize: 16, mr: 0.5 }} />
                  <Typography variant="caption" color="success.main">
                    +12% vs mes anterior
                  </Typography>
                </Box>
              </CardContent>
            </Card>
          </Grid>

          <Grid item xs={12} sm={6} md={3}>
            <Card sx={{ height: '100%' }}>
              <CardContent>
                <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                  <Avatar sx={{ bgcolor: 'success.main', mr: 2 }}>
                    <Fingerprint />
                  </Avatar>
                  <Box>
                    <Typography variant="h4" sx={{ fontWeight: 600 }}>
                      {stats.enrolledEmployees}
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                      Inscritos Biométrica
                    </Typography>
                  </Box>
                </Box>
                <Box sx={{ mb: 1 }}>
                  <LinearProgress 
                    variant="determinate" 
                    value={completionRate} 
                    sx={{ height: 6, borderRadius: 3 }}
                  />
                </Box>
                <Typography variant="caption" color="text.secondary">
                  {completionRate}% de cobertura
                </Typography>
              </CardContent>
            </Card>
          </Grid>

          <Grid item xs={12} sm={6} md={3}>
            <Card sx={{ height: '100%' }}>
              <CardContent>
                <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                  <Avatar sx={{ bgcolor: 'info.main', mr: 2 }}>
                    <Schedule />
                  </Avatar>
                  <Box>
                    <Typography variant="h4" sx={{ fontWeight: 600 }}>
                      {stats.todayAttendance}
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                      Asistencias Hoy
                    </Typography>
                  </Box>
                </Box>
                <Box sx={{ display: 'flex', alignItems: 'center' }}>
                  <CheckCircle sx={{ color: 'success.main', fontSize: 16, mr: 0.5 }} />
                  <Typography variant="caption" color="success.main">
                    91% de asistencia
                  </Typography>
                </Box>
              </CardContent>
            </Card>
          </Grid>

          <Grid item xs={12} sm={6} md={3}>
            <Card sx={{ height: '100%' }}>
              <CardContent>
                <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                  <Avatar sx={{ bgcolor: 'warning.main', mr: 2 }}>
                    <Face />
                  </Avatar>
                  <Box>
                    <Typography variant="h4" sx={{ fontWeight: 600 }}>
                      {stats.biometricVerifications}
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                      Verificaciones Biométricas
                    </Typography>
                  </Box>
                </Box>
                <Box sx={{ display: 'flex', alignItems: 'center' }}>
                  <TrendingUp sx={{ color: 'success.main', fontSize: 16, mr: 0.5 }} />
                  <Typography variant="caption" color="success.main">
                    +8% vs ayer
                  </Typography>
                </Box>
              </CardContent>
            </Card>
          </Grid>
        </Grid>

        {/* Device Status */}
        <Card sx={{ mb: 4 }}>
          <CardContent>
            <Typography variant="h6" gutterBottom>
              Estado de Dispositivos Biométricos
            </Typography>
            <Grid container spacing={2}>
              <Grid item xs={12} md={4}>
                <Box sx={{ display: 'flex', alignItems: 'center', p: 2, bgcolor: 'background.default', borderRadius: 2 }}>
                  <Avatar sx={{ bgcolor: devices.camera.available ? 'success.main' : 'error.main', mr: 2 }}>
                    <Face />
                  </Avatar>
                  <Box>
                    <Typography variant="subtitle1">Cámara</Typography>
                    <Chip 
                      label={devices.camera.status}
                      color={devices.camera.available ? 'success' : 'error'}
                      size="small"
                    />
                  </Box>
                </Box>
              </Grid>
              <Grid item xs={12} md={4}>
                <Box sx={{ display: 'flex', alignItems: 'center', p: 2, bgcolor: 'background.default', borderRadius: 2 }}>
                  <Avatar sx={{ bgcolor: devices.fingerprint.available ? 'success.main' : 'error.main', mr: 2 }}>
                    <Fingerprint />
                  </Avatar>
                  <Box>
                    <Typography variant="subtitle1">Lector de Huellas</Typography>
                    <Chip 
                      label={devices.fingerprint.status}
                      color={devices.fingerprint.available ? 'success' : 'error'}
                      size="small"
                    />
                  </Box>
                </Box>
              </Grid>
              <Grid item xs={12} md={4}>
                <Box sx={{ display: 'flex', alignItems: 'center', p: 2, bgcolor: 'background.default', borderRadius: 2 }}>
                  <Avatar sx={{ bgcolor: devices.webauthn.available ? 'success.main' : 'warning.main', mr: 2 }}>
                    <CheckCircle />
                  </Avatar>
                  <Box>
                    <Typography variant="subtitle1">WebAuthn</Typography>
                    <Chip 
                      label={devices.webauthn.status}
                      color={devices.webauthn.available ? 'success' : 'warning'}
                      size="small"
                    />
                  </Box>
                </Box>
              </Grid>
            </Grid>
          </CardContent>
        </Card>

        {/* Charts */}
        <Grid container spacing={3} sx={{ mb: 4 }}>
          <Grid item xs={12} lg={8}>
            <Card sx={{ height: 400 }}>
              <CardContent>
                <Typography variant="h6" gutterBottom>
                  Asistencias por Hora
                </Typography>
                <ResponsiveContainer width="100%" height={320}>
                  <BarChart data={attendanceData}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="hour" />
                    <YAxis />
                    <Tooltip />
                    <Bar dataKey="attendance" fill="#3b82f6" radius={[4, 4, 0, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>
          </Grid>

          <Grid item xs={12} lg={4}>
            <Card sx={{ height: 400 }}>
              <CardContent>
                <Typography variant="h6" gutterBottom>
                  Métodos de Verificación
                </Typography>
                <ResponsiveContainer width="100%" height={320}>
                  <PieChart>
                    <Pie
                      data={biometricDistribution}
                      cx="50%"
                      cy="50%"
                      outerRadius={80}
                      dataKey="value"
                      label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                    >
                      {biometricDistribution.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                      ))}
                    </Pie>
                    <Tooltip />
                  </PieChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>
          </Grid>
        </Grid>

        {/* Recent Activity */}
        <Card>
          <CardContent>
            <Typography variant="h6" gutterBottom>
              Actividad Reciente
            </Typography>
            <Box>
              {recentActivity.map((activity) => (
                <Box 
                  key={activity.id}
                  sx={{ 
                    display: 'flex', 
                    alignItems: 'center', 
                    py: 2, 
                    borderBottom: '1px solid',
                    borderColor: 'divider',
                    '&:last-child': { borderBottom: 'none' }
                  }}
                >
                  <Avatar sx={{ mr: 2, bgcolor: 'primary.main' }}>
                    {activity.employee.charAt(0)}
                  </Avatar>
                  <Box sx={{ flexGrow: 1 }}>
                    <Typography variant="subtitle2">{activity.employee}</Typography>
                    <Typography variant="body2" color="text.secondary">
                      {activity.action}
                    </Typography>
                  </Box>
                  <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                    <Typography variant="caption" color="text.secondary">
                      {activity.time}
                    </Typography>
                    <Chip
                      size="small"
                      label={activity.status === 'success' ? 'Exitoso' : 'Pendiente'}
                      color={activity.status === 'success' ? 'success' : 'warning'}
                    />
                  </Box>
                </Box>
              ))}
            </Box>
          </CardContent>
        </Card>
      </Box>
    </>
  );
};

export default Dashboard;