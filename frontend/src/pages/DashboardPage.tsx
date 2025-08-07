import React, { useState, useEffect } from 'react';
import {
  Paper,
  Typography,
  Box,
  Card,
  CardContent,
  CardHeader,
  Avatar,
  List,
  ListItem,
  ListItemText,
  Chip,
  CircularProgress,
} from '@mui/material';
import Grid2 from '@mui/material/Grid2';
import {
  People,
  AccessTime,
  Fingerprint,
  TrendingUp,
} from '@mui/icons-material';
import { useAuth } from '../contexts/AuthContext';
import axios from 'axios';

interface DashboardStats {
  today: {
    total_asistencias_hoy: number;
    empleados_presentes_hoy: number;
    empleados_sin_salida: number;
    total_empleados: number;
    porcentaje_asistencia: number;
  };
  biometric: {
    empleados_con_biometria: number;
    huellas_registradas: number;
    caras_registradas: number;
    porcentaje_enrollado: number;
  };
  recent_activity: Array<{
    ACCION: string;
    DETALLE: string;
    FECHA: string;
    USUARIO: string;
  }>;
}

const DashboardPage: React.FC = () => {
  const { user } = useAuth();
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const token = localStorage.getItem('synktime_token');
        const response = await axios.get('http://localhost:3001/api/reports/dashboard-stats', {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });
        setStats(response.data.data);
      } catch (error) {
        console.error('Error fetching dashboard stats:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchStats();
  }, []);

  if (loading) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" height="400px">
        <CircularProgress />
      </Box>
    );
  }

  return (
    <Box sx={{ flexGrow: 1 }}>
      <Typography variant="h4" gutterBottom>
        Dashboard
      </Typography>
      
      <Typography variant="subtitle1" color="textSecondary" gutterBottom>
        Bienvenido, {user?.nombre_completo}
      </Typography>

      {stats && (
        <Grid2 container spacing={3}>
          {/* Stats Cards */}
          <Grid2 item xs={12} sm={6} md={3}>
            <Card>
              <CardContent>
                <Box display="flex" alignItems="center">
                  <Avatar sx={{ bgcolor: 'primary.main', mr: 2 }}>
                    <People />
                  </Avatar>
                  <Box>
                    <Typography color="textSecondary" gutterBottom>
                      Empleados Presentes Hoy
                    </Typography>
                    <Typography variant="h5">
                      {stats.today.empleados_presentes_hoy} / {stats.today.total_empleados}
                    </Typography>
                    <Typography variant="body2" color="primary">
                      {stats.today.porcentaje_asistencia}% de asistencia
                    </Typography>
                  </Box>
                </Box>
              </CardContent>
            </Card>
          </Grid2>

          <Grid2 item xs={12} sm={6} md={3}>
            <Card>
              <CardContent>
                <Box display="flex" alignItems="center">
                  <Avatar sx={{ bgcolor: 'success.main', mr: 2 }}>
                    <AccessTime />
                  </Avatar>
                  <Box>
                    <Typography color="textSecondary" gutterBottom>
                      Registros de Hoy
                    </Typography>
                    <Typography variant="h5">
                      {stats.today.total_asistencias_hoy}
                    </Typography>
                    <Typography variant="body2" color="warning.main">
                      {stats.today.empleados_sin_salida} sin salida
                    </Typography>
                  </Box>
                </Box>
              </CardContent>
            </Card>
          </Grid2>

          <Grid2 item xs={12} sm={6} md={3}>
            <Card>
              <CardContent>
                <Box display="flex" alignItems="center">
                  <Avatar sx={{ bgcolor: 'secondary.main', mr: 2 }}>
                    <Fingerprint />
                  </Avatar>
                  <Box>
                    <Typography color="textSecondary" gutterBottom>
                      Empleados con Biométrico
                    </Typography>
                    <Typography variant="h5">
                      {stats.biometric.empleados_con_biometria}
                    </Typography>
                    <Typography variant="body2" color="secondary">
                      {stats.biometric.porcentaje_enrollado}% enrollados
                    </Typography>
                  </Box>
                </Box>
              </CardContent>
            </Card>
          </Grid2>

          <Grid2 item xs={12} sm={6} md={3}>
            <Card>
              <CardContent>
                <Box display="flex" alignItems="center">
                  <Avatar sx={{ bgcolor: 'info.main', mr: 2 }}>
                    <TrendingUp />
                  </Avatar>
                  <Box>
                    <Typography color="textSecondary" gutterBottom>
                      Datos Biométricos
                    </Typography>
                    <Typography variant="h5">
                      {stats.biometric.huellas_registradas + stats.biometric.caras_registradas}
                    </Typography>
                    <Typography variant="body2" color="info.main">
                      {stats.biometric.huellas_registradas} huellas, {stats.biometric.caras_registradas} caras
                    </Typography>
                  </Box>
                </Box>
              </CardContent>
            </Card>
          </Grid2>

          {/* Recent Activity */}
          <Grid2 item xs={12} md={8}>
            <Card>
              <CardHeader title="Actividad Reciente" />
              <CardContent>
                <List>
                  {stats.recent_activity.slice(0, 6).map((activity, index) => (
                    <ListItem key={index} divider>
                      <ListItemText
                        primary={activity.DETALLE}
                        secondary={
                          <Box display="flex" justifyContent="space-between" alignItems="center">
                            <Typography variant="body2" color="textSecondary">
                              Por: {activity.USUARIO}
                            </Typography>
                            <Typography variant="body2" color="textSecondary">
                              {new Date(activity.FECHA).toLocaleDateString()}
                            </Typography>
                          </Box>
                        }
                      />
                      <Chip
                        label={activity.ACCION}
                        size="small"
                        color={
                          activity.ACCION.includes('LOGIN') ? 'success' :
                          activity.ACCION.includes('CREATE') ? 'primary' :
                          activity.ACCION.includes('DELETE') ? 'error' :
                          'default'
                        }
                      />
                    </ListItem>
                  ))}
                </List>
              </CardContent>
            </Card>
          </Grid2>

          {/* Quick Actions */}
          <Grid2 item xs={12} md={4}>
            <Card>
              <CardHeader title="Acceso Rápido" />
              <CardContent>
                <Box display="flex" flexDirection="column" gap={2}>
                  <Typography variant="h6" color="primary">
                    {user?.empresa_nombre}
                  </Typography>
                  <Typography variant="body2" color="textSecondary">
                    Rol: {user?.rol}
                  </Typography>
                  <Typography variant="body2" color="textSecondary">
                    Usuario: {user?.username}
                  </Typography>
                  <Typography variant="body2" color="textSecondary">
                    Email: {user?.email}
                  </Typography>
                </Box>
              </CardContent>
            </Card>
          </Grid2>
        </Grid2>
      )}
    </Box>
  );
};

export default DashboardPage;