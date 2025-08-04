import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Box,
  Paper,
  TextField,
  Button,
  Typography,
  Alert,
  CircularProgress,
  Container,
  Card,
  CardContent
} from '@mui/material';
import { Business, Login as LoginIcon } from '@mui/icons-material';
import { useAuth } from '../context/AuthContext';
import { Helmet } from 'react-helmet-async';

const Login = () => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!username || !password) {
      setError('Usuario y contraseña son requeridos');
      return;
    }

    setLoading(true);
    setError('');

    try {
      const result = await login(username, password);
      
      if (result.success) {
        navigate('/dashboard');
      } else {
        setError(result.message || 'Error al iniciar sesión');
      }
    } catch (error) {
      setError('Error de conexión. Intente nuevamente.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <Helmet>
        <title>Iniciar Sesión - SynkTime</title>
      </Helmet>
      
      <Box
        sx={{
          minHeight: '100vh',
          background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          p: 2
        }}
      >
        <Container maxWidth="sm">
          <Card 
            elevation={10}
            sx={{ 
              borderRadius: 3,
              overflow: 'hidden',
              backgroundColor: 'rgba(255,255,255,0.95)',
              backdropFilter: 'blur(10px)'
            }}
          >
            <CardContent sx={{ p: 4 }}>
              {/* Logo and Title */}
              <Box sx={{ textAlign: 'center', mb: 4 }}>
                <Business 
                  sx={{ 
                    fontSize: 60, 
                    color: 'primary.main', 
                    mb: 2 
                  }} 
                />
                <Typography 
                  variant="h3" 
                  component="h1" 
                  sx={{ 
                    fontWeight: 700,
                    color: 'primary.main',
                    mb: 1
                  }}
                >
                  SynkTime
                </Typography>
                <Typography 
                  variant="h6" 
                  color="text.secondary"
                  sx={{ fontWeight: 400 }}
                >
                  Sistema de Asistencia Biométrica
                </Typography>
              </Box>

              {/* Login Form */}
              <Box component="form" onSubmit={handleSubmit}>
                <TextField
                  fullWidth
                  label="Usuario"
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  margin="normal"
                  autoComplete="username"
                  autoFocus
                  disabled={loading}
                  sx={{ mb: 2 }}
                />
                
                <TextField
                  fullWidth
                  label="Contraseña"
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  margin="normal"
                  autoComplete="current-password"
                  disabled={loading}
                  sx={{ mb: 3 }}
                />

                {error && (
                  <Alert severity="error" sx={{ mb: 3 }}>
                    {error}
                  </Alert>
                )}

                <Button
                  type="submit"
                  fullWidth
                  variant="contained"
                  size="large"
                  disabled={loading || !username || !password}
                  startIcon={loading ? <CircularProgress size={20} /> : <LoginIcon />}
                  sx={{ 
                    py: 1.5,
                    fontSize: '1.1rem',
                    fontWeight: 600,
                    textTransform: 'none',
                    borderRadius: 2
                  }}
                >
                  {loading ? 'Iniciando sesión...' : 'Iniciar Sesión'}
                </Button>
              </Box>

              {/* Features */}
              <Box sx={{ mt: 4, pt: 3, borderTop: '1px solid', borderColor: 'divider' }}>
                <Typography variant="body2" color="text.secondary" align="center" gutterBottom>
                  Sistema moderno con tecnología biométrica
                </Typography>
                <Box sx={{ display: 'flex', justifyContent: 'center', gap: 3, mt: 2 }}>
                  <Box sx={{ textAlign: 'center' }}>
                    <Typography variant="caption" display="block" sx={{ fontWeight: 600 }}>
                      Huella Dactilar
                    </Typography>
                    <Typography variant="caption" color="text.secondary">
                      Verificación segura
                    </Typography>
                  </Box>
                  <Box sx={{ textAlign: 'center' }}>
                    <Typography variant="caption" display="block" sx={{ fontWeight: 600 }}>
                      Reconocimiento Facial
                    </Typography>
                    <Typography variant="caption" color="text.secondary">
                      Tecnología avanzada
                    </Typography>
                  </Box>
                  <Box sx={{ textAlign: 'center' }}>
                    <Typography variant="caption" display="block" sx={{ fontWeight: 600 }}>
                      Dashboard en Tiempo Real
                    </Typography>
                    <Typography variant="caption" color="text.secondary">
                      Estadísticas completas
                    </Typography>
                  </Box>
                </Box>
              </Box>
            </CardContent>
          </Card>
        </Container>
      </Box>
    </>
  );
};

export default Login;