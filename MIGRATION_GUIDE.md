# SynkTime Migration Guide: PHP to Node.js/React

## Overview

This guide outlines the complete migration from the legacy PHP/JS/CSS stack to a modern Node.js backend with React frontend. The new architecture provides better security, maintainability, and user experience while preserving all existing functionality.

## Architecture Changes

### Before (Legacy PHP)
- **Backend**: PHP with mixed HTML/PHP files
- **Frontend**: Vanilla JavaScript + CSS
- **Authentication**: PHP sessions
- **API**: PHP files in `/api/` directories
- **Database**: Direct PDO connections in each file

### After (Modern Stack)
- **Backend**: Node.js with Express.js
- **Frontend**: React with TypeScript + Material-UI
- **Authentication**: JWT tokens
- **API**: RESTful endpoints with proper middleware
- **Database**: Centralized connection with transaction support

## Database Schema Compliance

The new system strictly adheres to the existing database schema and removes references to non-existent tables:

### Removed References
- `permisos_acceso` table (non-existent)
- Hardcoded access control logic

### Updated Table Usage
- **Authentication**: Uses `usuario` table with `ROL` field
- **Employees**: Uses `EMPLEADO` table with proper case sensitivity
- **Biometric Data**: Uses `biometric_data` and `biometric_logs` tables
- **Attendance**: Uses `asistencias` table with enhanced verification methods

## API Migration

### Authentication Endpoints
| Legacy PHP | New Node.js Endpoint | Description |
|------------|---------------------|-------------|
| `auth/login-handler.php` | `POST /api/auth/login` | User login with JWT token |
| `auth/session.php` | `GET /api/auth/me` | Get current user info |
| `logout.php` | `POST /api/auth/logout` | User logout |
| N/A | `POST /api/auth/change-password` | Change password |

### Employee Management
| Legacy PHP | New Node.js Endpoint | Description |
|------------|---------------------|-------------|
| `api/employee/list.php` | `GET /api/employees` | List employees with pagination |
| `api/employee/get.php` | `GET /api/employees/:id` | Get single employee |
| `api/employee/save.php` | `POST /api/employees` | Create new employee |
| `api/employee/update.php` | `PUT /api/employees/:id` | Update employee |
| `api/employee/delete.php` | `DELETE /api/employees/:id` | Soft delete employee |

### Attendance Management
| Legacy PHP | New Node.js Endpoint | Description |
|------------|---------------------|-------------|
| `api/attendance/` | `GET /api/attendance` | List attendance records |
| `attendance.php` | `POST /api/attendance/register` | Register attendance |
| N/A | `GET /api/attendance/employee/:id/today` | Today's attendance for employee |
| N/A | `GET /api/attendance/stats` | Attendance statistics |
| N/A | `PUT /api/attendance/:id` | Update attendance record |

### Biometric System
| Legacy PHP | New Node.js Endpoint | Description |
|------------|---------------------|-------------|
| `api/biometric/enroll-fingerprint.php` | `POST /api/biometric/enroll/fingerprint` | Fingerprint enrollment |
| `api/biometric/enroll-facial.php` | `POST /api/biometric/enroll/facial` | Facial enrollment |
| `api/biometric/stats.php` | `GET /api/biometric/stats` | Biometric statistics |
| `api/biometric/summary.php` | `GET /api/biometric/employee/:id/summary` | Employee biometric summary |
| N/A | `POST /api/biometric/verify` | Biometric verification |
| N/A | `DELETE /api/biometric/employee/:id` | Delete biometric data |

### Schedule Management
| Legacy PHP | New Node.js Endpoint | Description |
|------------|---------------------|-------------|
| `schedules.php` | `GET /api/schedules` | List schedules |
| `api/horario/` | `GET /api/schedules/:id` | Get single schedule |
| N/A | `POST /api/schedules` | Create schedule |
| N/A | `PUT /api/schedules/:id` | Update schedule |
| N/A | `DELETE /api/schedules/:id` | Delete schedule |
| N/A | `POST /api/schedules/:id/assign-employees` | Assign employees to schedule |

### Reports
| Legacy PHP | New Node.js Endpoint | Description |
|------------|---------------------|-------------|
| `reports.php` | `GET /api/reports/attendance` | Attendance report |
| N/A | `GET /api/reports/employee-summary` | Employee summary report |
| N/A | `GET /api/reports/biometric-usage` | Biometric usage report |
| N/A | `GET /api/reports/dashboard-stats` | Dashboard statistics |

## Frontend Component Migration

### Page-Level Components
| Legacy PHP File | New React Component | Description |
|-----------------|-------------------|-------------|
| `login.php` | `LoginPage.tsx` | User login interface |
| `dashboard.php` | `DashboardPage.tsx` | Main dashboard with statistics |
| `attendance.php` | `AttendancePage.tsx` | Attendance management |
| `employee.php` | `EmployeesPage.tsx` | Employee management |
| `biometric-enrollment.php` | `BiometricPage.tsx` | Biometric enrollment interface |
| `schedules.php` | `SchedulesPage.tsx` | Schedule management |
| `reports.php` | `ReportsPage.tsx` | Reports and analytics |

### Shared Components
- **Layout.tsx**: Navigation and layout wrapper
- **AuthContext.tsx**: Authentication state management
- Material-UI components for consistent design

## Security Improvements

### Authentication
- **Before**: PHP sessions with server-side storage
- **After**: JWT tokens with client-side storage
- **Benefits**: Stateless, scalable, cross-domain support

### Authorization
- **Before**: Role checks scattered throughout PHP files
- **After**: Centralized middleware with role-based access control
- **Benefits**: Consistent permissions, easier maintenance

### Input Validation
- **Before**: Manual validation in PHP
- **After**: express-validator middleware with comprehensive validation
- **Benefits**: Standardized validation, better error messages

### Database Security
- **Before**: PDO with some parameterized queries
- **After**: mysql2 with all parameterized queries and transactions
- **Benefits**: Prevents SQL injection, atomic operations

## Biometric System Enhancements

### Fixed Issues
1. **90% Stall Problem**: Fixed by implementing proper status feedback and error handling
2. **Device Detection**: Improved browser API usage for camera and fingerprint devices
3. **Data Consistency**: Ensured proper database transactions for biometric operations
4. **Error Handling**: Added comprehensive error messages and retry mechanisms

### New Features
- Real-time enrollment progress
- Better device compatibility checks
- Enhanced verification algorithms
- Comprehensive logging of biometric operations

## Deployment Instructions

### Prerequisites
```bash
# Node.js 18+ and npm
node --version
npm --version

# MariaDB/MySQL database
mysql --version
```

### Backend Setup
```bash
# Install dependencies
cd backend
npm install

# Configure environment
cp .env.example .env
# Edit .env with your database credentials

# Start development server
npm run dev

# Production deployment
npm start
```

### Frontend Setup
```bash
# Install dependencies
cd frontend
npm install

# Configure API URL
# Create .env file with REACT_APP_API_URL

# Start development server
npm start

# Build for production
npm run build
```

### Database Migration
The new system works with the existing database schema. No migration is required for the database structure, only for the application logic.

## Testing Checklist

### Backend API Testing
- [ ] Authentication endpoints work correctly
- [ ] Employee CRUD operations
- [ ] Attendance registration and retrieval
- [ ] Biometric enrollment and verification
- [ ] Schedule management
- [ ] Report generation
- [ ] Role-based access control

### Frontend Testing
- [ ] Login functionality
- [ ] Navigation and routing
- [ ] Dashboard statistics display
- [ ] All module pages load correctly
- [ ] Responsive design works on mobile
- [ ] Error handling and user feedback

### Integration Testing
- [ ] Frontend communicates with backend API
- [ ] JWT authentication flow
- [ ] Database operations complete successfully
- [ ] Biometric operations work end-to-end
- [ ] File uploads and downloads work

## Performance Improvements

### Backend
- Connection pooling for database
- Rate limiting for API endpoints
- Proper error handling and logging
- Optimized database queries

### Frontend
- Code splitting with React
- Material-UI optimizations
- Lazy loading of components
- Efficient state management

## Maintenance and Monitoring

### Logging
- API request/response logging
- Error tracking and reporting
- User activity logging
- Performance monitoring

### Security
- Regular dependency updates
- Security headers configuration
- Input validation and sanitization
- Rate limiting and DDoS protection

## Rollback Plan

If issues arise, the system can be rolled back by:
1. Keeping the legacy PHP files as backup
2. Restoring the original database state
3. Switching web server configuration
4. Monitoring for any data inconsistencies

## Support and Documentation

### API Documentation
- All endpoints documented with request/response examples
- Postman collection available for testing
- OpenAPI/Swagger specification for formal documentation

### Code Documentation
- Inline comments for complex business logic
- README files for each major component
- TypeScript types for better code clarity
- Component documentation for React components

## Migration Timeline

1. **Phase 1**: Backend API development and testing (Completed)
2. **Phase 2**: Frontend core components and authentication (Completed)
3. **Phase 3**: Full module implementation (In Progress)
4. **Phase 4**: Biometric system fixes and enhancements (Next)
5. **Phase 5**: Testing and quality assurance (Next)
6. **Phase 6**: Production deployment and monitoring (Final)

This migration provides a solid foundation for future enhancements while maintaining all existing functionality and improving security, performance, and user experience.