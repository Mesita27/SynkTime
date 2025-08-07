import axios, { AxiosInstance } from 'axios';

const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:3001/api';

export interface User {
  id: number;
  username: string;
  nombre_completo: string;
  email: string;
  rol: string;
  id_empresa: number;
  empresa_nombre: string;
}

export interface LoginResponse {
  success: boolean;
  message: string;
  data: {
    token: string;
    user: User;
  };
}

class AuthService {
  private api: AxiosInstance;

  constructor() {
    this.api = axios.create({
      baseURL: API_BASE_URL,
      headers: {
        'Content-Type': 'application/json',
      },
    });

    // Add token to requests if available
    this.api.interceptors.request.use(
      (config) => {
        const token = localStorage.getItem('synktime_token');
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
      },
      (error) => {
        return Promise.reject(error);
      }
    );

    // Handle response errors
    this.api.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error.response?.status === 401) {
          localStorage.removeItem('synktime_token');
          window.location.href = '/login';
        }
        return Promise.reject(error);
      }
    );
  }

  async login(username: string, password: string): Promise<{ token: string; user: User }> {
    const response = await this.api.post<LoginResponse>('/auth/login', {
      username,
      password,
    });

    if (response.data.success) {
      return response.data.data;
    } else {
      throw new Error(response.data.message || 'Login failed');
    }
  }

  async logout(): Promise<void> {
    await this.api.post('/auth/logout');
  }

  async getCurrentUser(): Promise<User> {
    const response = await this.api.get('/auth/me');
    
    if (response.data.success) {
      return response.data.data;
    } else {
      throw new Error(response.data.message || 'Failed to get user data');
    }
  }

  async changePassword(currentPassword: string, newPassword: string): Promise<void> {
    const response = await this.api.post('/auth/change-password', {
      currentPassword,
      newPassword,
    });

    if (!response.data.success) {
      throw new Error(response.data.message || 'Failed to change password');
    }
  }
}

export const authService = new AuthService();