import React from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import {
  Drawer,
  List,
  ListItem,
  ListItemButton,
  ListItemIcon,
  ListItemText,
  Box,
  Typography,
  Divider,
  Chip
} from '@mui/material';
import {
  Dashboard,
  Fingerprint,
  AccessTime,
  People,
  Assessment,
  Schedule,
  Settings,
  Business
} from '@mui/icons-material';
import { useBiometric } from '../../context/BiometricContext';

const drawerWidth = 280;

const menuItems = [
  {
    text: 'Dashboard',
    icon: <Dashboard />,
    path: '/dashboard'
  },
  {
    text: 'Inscripción Biométrica',
    icon: <Fingerprint />,
    path: '/biometric-enrollment'
  },
  {
    text: 'Registro de Asistencia',
    icon: <AccessTime />,
    path: '/attendance'
  },
  {
    text: 'Empleados',
    icon: <People />,
    path: '/employees'
  },
  {
    text: 'Reportes',
    icon: <Assessment />,
    path: '/reports'
  },
  {
    text: 'Horarios',
    icon: <Schedule />,
    path: '/schedules'
  },
  {
    text: 'Configuración',
    icon: <Settings />,
    path: '/settings'
  }
];

const Sidebar = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { devices, isInitialized } = useBiometric();

  const isSelected = (path) => location.pathname === path;

  const getDeviceStatusColor = () => {
    if (!isInitialized) return 'default';
    
    const cameraOk = devices.camera.available;
    const fingerprintOk = devices.fingerprint.available;
    
    if (cameraOk && fingerprintOk) return 'success';
    if (cameraOk || fingerprintOk) return 'warning';
    return 'error';
  };

  const getDeviceStatusText = () => {
    if (!isInitialized) return 'Verificando...';
    
    const cameraOk = devices.camera.available;
    const fingerprintOk = devices.fingerprint.available;
    
    if (cameraOk && fingerprintOk) return 'Todos OK';
    if (cameraOk) return 'Solo Cámara';
    if (fingerprintOk) return 'Solo Huella';
    return 'Sin Dispositivos';
  };

  return (
    <Drawer
      variant="permanent"
      sx={{
        width: drawerWidth,
        flexShrink: 0,
        '& .MuiDrawer-paper': {
          width: drawerWidth,
          boxSizing: 'border-box',
          backgroundColor: '#1e293b',
          color: 'white'
        },
      }}
    >
      <Box sx={{ p: 3, textAlign: 'center' }}>
        <Business sx={{ fontSize: 40, color: '#3b82f6', mb: 1 }} />
        <Typography variant="h5" sx={{ fontWeight: 600, color: 'white' }}>
          SynkTime
        </Typography>
        <Typography variant="body2" sx={{ color: 'rgba(255,255,255,0.7)' }}>
          Sistema de Asistencia
        </Typography>
        
        <Box sx={{ mt: 2 }}>
          <Chip
            label={`Dispositivos: ${getDeviceStatusText()}`}
            size="small"
            color={getDeviceStatusColor()}
            sx={{ 
              backgroundColor: getDeviceStatusColor() === 'success' ? '#10b981' : 
                             getDeviceStatusColor() === 'warning' ? '#f59e0b' : '#ef4444',
              color: 'white',
              fontSize: '0.75rem'
            }}
          />
        </Box>
      </Box>

      <Divider sx={{ borderColor: 'rgba(255,255,255,0.12)' }} />

      <List sx={{ px: 1, py: 2 }}>
        {menuItems.map((item) => (
          <ListItem key={item.text} disablePadding sx={{ mb: 0.5 }}>
            <ListItemButton
              onClick={() => navigate(item.path)}
              selected={isSelected(item.path)}
              sx={{
                borderRadius: 2,
                mx: 1,
                '&.Mui-selected': {
                  backgroundColor: '#3b82f6',
                  '&:hover': {
                    backgroundColor: '#2563eb',
                  },
                },
                '&:hover': {
                  backgroundColor: 'rgba(255,255,255,0.08)',
                },
              }}
            >
              <ListItemIcon sx={{ 
                color: isSelected(item.path) ? 'white' : 'rgba(255,255,255,0.7)',
                minWidth: 40
              }}>
                {item.icon}
              </ListItemIcon>
              <ListItemText 
                primary={item.text}
                primaryTypographyProps={{
                  fontSize: '0.9rem',
                  fontWeight: isSelected(item.path) ? 600 : 400,
                  color: isSelected(item.path) ? 'white' : 'rgba(255,255,255,0.9)'
                }}
              />
            </ListItemButton>
          </ListItem>
        ))}
      </List>

      <Box sx={{ flexGrow: 1 }} />

      <Box sx={{ p: 2, borderTop: '1px solid rgba(255,255,255,0.12)' }}>
        <Typography variant="caption" sx={{ color: 'rgba(255,255,255,0.6)' }}>
          Estado de Dispositivos Biométricos
        </Typography>
        <Box sx={{ mt: 1, display: 'flex', flexDirection: 'column', gap: 0.5 }}>
          <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <Typography variant="caption" sx={{ color: 'rgba(255,255,255,0.8)' }}>
              Cámara
            </Typography>
            <Chip
              label={devices.camera.status}
              size="small"
              color={devices.camera.available ? 'success' : 'error'}
              sx={{ height: 16, fontSize: '0.6rem' }}
            />
          </Box>
          <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <Typography variant="caption" sx={{ color: 'rgba(255,255,255,0.8)' }}>
              Huella
            </Typography>
            <Chip
              label={devices.fingerprint.status}
              size="small"
              color={devices.fingerprint.available ? 'success' : 'error'}
              sx={{ height: 16, fontSize: '0.6rem' }}
            />
          </Box>
        </Box>
      </Box>
    </Drawer>
  );
};

export default Sidebar;