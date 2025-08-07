import React from 'react';
import { Typography, Box } from '@mui/material';

const BiometricPage: React.FC = () => {
  return (
    <Box>
      <Typography variant="h4" gutterBottom>
        Gestión Biométrica
      </Typography>
      <Typography variant="body1">
        Módulo de inscripción y verificación biométrica en desarrollo...
      </Typography>
    </Box>
  );
};

export default BiometricPage;