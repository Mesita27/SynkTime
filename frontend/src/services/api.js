import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:3001/api/v1';

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

  // Employee endpoints
  async getEmployees(params = {}) {
    const response = await this.client.get('/employees', { params });
    return response.data;
  }

  async getEmployee(employeeId) {
    const response = await this.client.get(`/employees/${employeeId}`);
    return response.data;
  }

  async createEmployee(employeeData) {
    const response = await this.client.post('/employees', employeeData);
    return response;
  }

  async updateEmployee(employeeId, employeeData) {
    const response = await this.client.put(`/employees/${employeeId}`, employeeData);
    return response;
  }

  async deleteEmployee(employeeId) {
    const response = await this.client.delete(`/employees/${employeeId}`);
    return response;
  }

  async getCompanyLocations() {
    const response = await this.client.get('/employees/locations/company');
    return response.data;
  }

  async assignSchedule(employeeId, scheduleData) {
    const response = await this.client.post(`/employees/${employeeId}/schedule`, scheduleData);
    return response;
  }

  // Attendance endpoints
  async getAttendanceRecords(params = {}) {
    const response = await this.client.get('/attendance/records', { params });
    return response.data;
  }

  async getAttendanceSummary(params = {}) {
    const response = await this.client.get('/attendance/summary', { params });
    return response.data;
  }

  async getEmployeeAttendance(employeeId, params = {}) {
    const response = await this.client.get(`/attendance/employee/${employeeId}`, { params });
    return response.data;
  }

  async registerAttendance(data) {
    const response = await this.client.post('/attendance/register', data);
    return response;
  }

  async updateAttendance(attendanceId, data) {
    const response = await this.client.put(`/attendance/${attendanceId}`, data);
    return response;
  }

  async deleteAttendance(attendanceId) {
    const response = await this.client.delete(`/attendance/${attendanceId}`);
    return response;
  }
}

export const apiService = new ApiService();