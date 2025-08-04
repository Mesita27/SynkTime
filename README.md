# SynkTime - Modern Architecture Migration

This repository contains the modernized version of the SynkTime attendance management system, migrated from PHP/JS/CSS to a modern Node.js/React.js architecture.

## 🏗️ Architecture Overview

### Backend (Node.js/Express)
- **Location**: `/backend/`
- **Technology Stack**: Node.js, Express.js, MySQL, JWT Authentication
- **Features**: 
  - RESTful API design
  - JWT-based authentication
  - Advanced biometric services
  - Database abstraction layer
  - Comprehensive error handling

### Frontend (React.js)
- **Location**: `/frontend/`
- **Technology Stack**: React.js, Material-UI, Vite, React Query
- **Features**:
  - Modern component-based architecture
  - Real-time biometric device detection
  - Advanced biometric enrollment and verification
  - Responsive design with Material-UI
  - Progressive Web App capabilities

## 🚀 Quick Start

### Prerequisites
- Node.js 16+ 
- MySQL 5.7+
- npm or yarn

### Backend Setup

1. Navigate to backend directory:
```bash
cd backend
```

2. Install dependencies:
```bash
npm install
```

3. Configure environment:
```bash
cp .env.example .env
# Edit .env with your database credentials
```

4. Start development server:
```bash
npm run dev
```

The backend will be available at `http://localhost:3001`

### Frontend Setup

1. Navigate to frontend directory:
```bash
cd frontend
```

2. Install dependencies:
```bash
npm install
```

3. Start development server:
```bash
npm run dev
```

The frontend will be available at `http://localhost:3000`

## 🔧 API Endpoints

### Authentication
- `POST /api/v1/auth/login` - User login
- `GET /api/v1/auth/me` - Get current user
- `POST /api/v1/auth/logout` - User logout

### Biometric
- `GET /api/v1/biometric/employee/:id/summary` - Get employee biometric summary
- `POST /api/v1/biometric/enroll/fingerprint` - Enroll fingerprint
- `POST /api/v1/biometric/enroll/facial` - Enroll facial biometric
- `POST /api/v1/biometric/verify` - Verify biometric data
- `GET /api/v1/biometric/stats` - Get biometric statistics
- `GET /api/v1/biometric/devices/status` - Get device status

## 🔐 Security Features

- JWT token-based authentication
- Rate limiting on API endpoints
- Input validation and sanitization
- CORS protection
- Helmet.js security headers
- Bcrypt password hashing

## 📱 Frontend Features

### Biometric Enrollment
- Interactive finger selection interface
- Real-time camera feed for facial recognition
- Progress tracking and status updates
- Device availability detection

### Attendance Registration
- Multi-step verification wizard
- Support for fingerprint, facial, and traditional methods
- Real-time feedback and error handling

### Dashboard
- Real-time statistics and charts
- Device status monitoring
- Recent activity tracking
- Responsive design

## 🛠️ Technology Stack

### Backend Dependencies
- **Express.js** - Web framework
- **MySQL2** - Database driver
- **jsonwebtoken** - JWT implementation
- **bcryptjs** - Password hashing
- **express-validator** - Input validation
- **helmet** - Security headers
- **cors** - Cross-origin resource sharing
- **morgan** - HTTP request logging

### Frontend Dependencies
- **React 18** - UI library
- **Material-UI v5** - Component library
- **React Router v6** - Client-side routing
- **React Query** - Data fetching and caching
- **Axios** - HTTP client
- **React Webcam** - Camera integration
- **Recharts** - Data visualization
- **Notistack** - Toast notifications

## 🔄 Migration from PHP System

The new architecture maintains compatibility with the existing PHP database schema while providing:

1. **Modern API Layer**: RESTful endpoints replace PHP procedural scripts
2. **Component-Based UI**: React components replace PHP mixed markup
3. **Real-time Features**: WebSocket support for live updates
4. **Enhanced Security**: JWT tokens replace session-based authentication
5. **Better UX**: Single-page application with smooth navigation

## 📂 Project Structure

```
synktime/
├── backend/                 # Node.js backend
│   ├── config/             # Database and app configuration
│   ├── controllers/        # Request handlers
│   ├── middleware/         # Custom middleware
│   ├── models/             # Data models
│   ├── routes/             # API routes
│   ├── services/           # Business logic
│   ├── utils/              # Utility functions
│   └── tests/              # Backend tests
├── frontend/               # React frontend
│   ├── public/             # Static assets
│   ├── src/
│   │   ├── components/     # Reusable components
│   │   ├── pages/          # Page components
│   │   ├── services/       # API services
│   │   ├── context/        # React context
│   │   ├── hooks/          # Custom hooks
│   │   └── utils/          # Utility functions
└── legacy/                 # Original PHP system (preserved)
```

## 🧪 Testing

### Backend Tests
```bash
cd backend
npm test
```

### Frontend Tests
```bash
cd frontend
npm test
```

## 🚀 Production Deployment

### Backend
```bash
cd backend
npm run build
npm start
```

### Frontend
```bash
cd frontend
npm run build
# Serve the dist/ directory with your web server
```

## 🔮 Advanced Features

### Biometric Integration
- WebAuthn support for hardware security keys
- Face-api.js for facial recognition
- WebUSB integration for fingerprint devices
- Progressive enhancement for device capabilities

### Real-time Updates
- WebSocket integration for live dashboard updates
- Real-time attendance notifications
- Device status monitoring

### Progressive Web App
- Offline capability
- Push notifications
- Mobile-optimized interface

## 📝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🤝 Support

For support, email your-email@example.com or create an issue in the repository.

---

Built with ❤️ for modern attendance management