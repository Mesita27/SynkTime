import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api/v1';

class ApiService {
  constructor() {
    this.client = axios.create({
      baseURL: API_BASE_URL,
      headers: {
        'Content-Type': 'application/json',
      },
    });

    // Request interceptor to add auth token
    this.client.interceptors.request.use(
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

    // Response interceptor for error handling
    this.client.interceptors.response.use(
      (response) => response.data,
      (error) => {
        if (error.response?.status === 401) {
          localStorage.removeItem('synktime_token');
          window.location.href = '/login';
        }
        return Promise.reject(error);
      }
    );
  }

  // Auth endpoints
  async login(username, password) {
    const response = await this.client.post('/auth/login', {
      username,
      password,
    });
    return response;
  }

  async logout() {
    const response = await this.client.post('/auth/logout');
    return response;
  }

  async getCurrentUser() {
    const response = await this.client.get('/auth/me');
    return response.user;
  }

  // Biometric endpoints
  async getEmployeeBiometricSummary(employeeId) {
    const response = await this.client.get(`/biometric/employee/${employeeId}/summary`);
    return response.data;
  }

  async enrollFingerprint(employeeId, fingerType, fingerprintData) {
    const response = await this.client.post('/biometric/enroll/fingerprint', {
      employee_id: employeeId,
      finger_type: fingerType,
      fingerprint_data: fingerprintData,
    });
    return response;
  }

  async enrollFacial(employeeId, facialData) {
    const response = await this.client.post('/biometric/enroll/facial', {
      employee_id: employeeId,
      facial_data: facialData,
    });
    return response;
  }

  async verifyBiometric(employeeId, biometricType, biometricData) {
    const response = await this.client.post('/biometric/verify', {
      employee_id: employeeId,
      biometric_type: biometricType,
      biometric_data: biometricData,
    });
    return response;
  }

  async getBiometricStats(empresaId) {
    const response = await this.client.get('/biometric/stats', {
      params: { empresa_id: empresaId },
    });
    return response.data;
  }

  async deleteBiometricData(employeeId, biometricType, fingerType) {
    const response = await this.client.delete(`/biometric/employee/${employeeId}`, {
      data: {
        biometric_type: biometricType,
        finger_type: fingerType,
      },
    });
    return response;
  }

  async getDeviceStatus() {
    const response = await this.client.get('/biometric/devices/status');
    return response.devices;
  }

  // Employee endpoints (basic implementations)
  async getEmployees(params = {}) {
    const response = await this.client.get('/employees', { params });
    return response.data;
  }

  // Attendance endpoints (basic implementations)
  async getAttendance(params = {}) {
    const response = await this.client.get('/attendance', { params });
    return response.data;
  }

  async registerAttendance(data) {
    const response = await this.client.post('/attendance/register', data);
    return response;
  }
}

export const apiService = new ApiService();