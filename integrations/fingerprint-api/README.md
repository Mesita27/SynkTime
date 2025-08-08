# Fingerprint API

.NET 8 microservice for fingerprint enrollment and identification using SourceAFIS.

## Requirements
- .NET 8 SDK

## Running
```bash
dotnet restore
dotnet run --urls=http://localhost:5058
```

## Endpoints
- POST /enroll - Enroll fingerprint templates
- POST /identify - Identify fingerprint (1:N)
- POST /verify - Verify fingerprint (1:1)
- GET /health - Health check

## Storage
Templates are stored in `data/templates/` directory as binary files.