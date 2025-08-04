const request = require('supertest');
const app = require('../server');

describe('Authentication Endpoints', () => {
  describe('POST /api/v1/auth/login', () => {
    test('should return 400 for missing credentials', async () => {
      const response = await request(app)
        .post('/api/v1/auth/login')
        .send({});
      
      expect(response.status).toBe(400);
      expect(response.body.success).toBe(false);
    });

    test('should return 400 for missing username', async () => {
      const response = await request(app)
        .post('/api/v1/auth/login')
        .send({ password: 'test123' });
      
      expect(response.status).toBe(400);
      expect(response.body.success).toBe(false);
    });

    test('should return 400 for missing password', async () => {
      const response = await request(app)
        .post('/api/v1/auth/login')
        .send({ username: 'testuser' });
      
      expect(response.status).toBe(400);
      expect(response.body.success).toBe(false);
    });
  });

  describe('GET /api/v1/auth/me', () => {
    test('should return 401 for missing token', async () => {
      const response = await request(app)
        .get('/api/v1/auth/me');
      
      expect(response.status).toBe(401);
      expect(response.body.success).toBe(false);
    });
  });
});

describe('Health Check', () => {
  test('should return health status', async () => {
    const response = await request(app)
      .get('/health');
    
    expect(response.status).toBe(200);
    expect(response.body.status).toBe('OK');
  });
});