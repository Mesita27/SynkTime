import React from 'react';
import { Typography, Box } from '@mui/material';

const EmployeesPage: React.FC = () => {
  return (
    <Box>
      <Typography variant="h4" gutterBottom>
        Gestión de Empleados
      </Typography>
      <Typography variant="body1">
        Módulo de gestión de empleados en desarrollo...
      </Typography>
    </Box>
  );
};

export default EmployeesPage;