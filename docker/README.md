# SynkTime Biometric Services Docker Setup

This directory contains Docker configuration for running the biometric recognition services required by SynkTime.

## Services Included

- **InsightFace-REST**: Facial recognition service (Port 18081)
- **SourceAFIS HTTP**: Fingerprint recognition service (Port 18082)
- **Redis**: Caching service (Port 6379) - Optional
- **Nginx**: Load balancer/proxy (Port 8080) - Optional

## Quick Start

1. **Copy environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Start the services:**
   ```bash
   docker-compose -f docker-compose.biometrics.yml up -d
   ```

3. **Verify services are running:**
   ```bash
   curl http://localhost:18081/docs  # InsightFace docs
   curl http://localhost:18082/      # SourceAFIS status
   ```

## Configuration

### Environment Variables

Edit the `.env` file to customize:

- `FACE_API_BASE`: InsightFace service URL
- `FINGER_API_BASE`: SourceAFIS service URL  
- `FACE_MATCH_THRESHOLD`: Facial recognition threshold (0.0-1.0)
- `FINGER_MATCH_THRESHOLD`: Fingerprint match threshold
- `INSIGHTFACE_CPU_ONLY`: Set to 0 for GPU acceleration
- `SOURCEAFIS_MEMORY`: Java heap size for SourceAFIS

### GPU Support

To enable GPU acceleration for facial recognition:

1. Install [NVIDIA Container Toolkit](https://github.com/NVIDIA/nvidia-docker)
2. Set `INSIGHTFACE_CPU_ONLY=0` in `.env`
3. Uncomment GPU configuration in docker-compose file

## Service URLs

Once running, services are available at:

- **InsightFace-REST**: http://localhost:18081
  - API docs: http://localhost:18081/docs
  - Extract embeddings: `POST /extract`
  - Detect faces: `POST /detect`

- **SourceAFIS HTTP**: http://localhost:18082
  - Generate template: `POST /template`
  - Verify fingerprint: `POST /verify`

- **Nginx Proxy** (if enabled): http://localhost:8080
  - Face API: http://localhost:8080/face/
  - Fingerprint API: http://localhost:8080/fingerprint/

## Performance Tuning

### CPU-Only Mode (Default)
- Face recognition: ~2-3 seconds per verification
- Fingerprint: ~1-2 seconds per verification
- Recommended for development and small deployments

### GPU Mode (Requires NVIDIA GPU)
- Face recognition: ~0.5-1 second per verification
- Fingerprint: ~1-2 seconds per verification (no GPU acceleration)
- Recommended for production with high load

### Memory Usage
- InsightFace: ~1-2GB RAM (CPU mode), ~2-4GB VRAM (GPU mode)
- SourceAFIS: ~256-512MB RAM
- Redis: ~256MB RAM
- Total: ~2-3GB RAM minimum

## Monitoring

### Health Checks

Services include health checks:

```bash
# Check all services
docker-compose -f docker-compose.biometrics.yml ps

# Check specific service
docker-compose -f docker-compose.biometrics.yml exec insightface-rest curl localhost:18080/docs
```

### Logs

View service logs:

```bash
# All services
docker-compose -f docker-compose.biometrics.yml logs

# Specific service
docker-compose -f docker-compose.biometrics.yml logs insightface-rest
```

## Troubleshooting

### Common Issues

1. **Port conflicts:**
   ```bash
   # Check what's using the ports
   sudo netstat -tulpn | grep :18081
   sudo netstat -tulpn | grep :18082
   ```

2. **Out of memory:**
   - Reduce `SOURCEAFIS_MEMORY` in `.env`
   - Reduce `INSIGHTFACE_MAX_SIZE` for smaller images
   - Add swap space to system

3. **Slow performance:**
   - Enable GPU if available
   - Increase allocated memory
   - Use Redis caching
   - Scale with multiple instances

4. **Permission errors:**
   ```bash
   # Fix file permissions
   sudo chown -R $USER:$USER .
   chmod +x *.sh
   ```

### Performance Testing

Test API performance:

```bash
# Face recognition performance
curl -X POST "http://localhost:18081/extract" \
  -H "Content-Type: application/json" \
  -d '{"images": {"data": ["base64_image_here"]}}'

# Fingerprint performance  
curl -X POST "http://localhost:18082/template" \
  -H "Content-Type: application/json" \
  -d '{"image": "base64_fingerprint_here"}'
```

## Security Notes

1. **Network Security:**
   - Services run on internal Docker network
   - Only exposed ports are accessible externally
   - Consider firewall rules for production

2. **Data Security:**
   - Biometric templates are processed in-memory
   - No permanent storage of biometric data in containers
   - Images are not cached by default

3. **Production Recommendations:**
   - Use HTTPS reverse proxy
   - Implement API authentication
   - Regular security updates
   - Monitor access logs

## Scaling

For high-load environments:

1. **Horizontal Scaling:**
   ```bash
   # Scale InsightFace instances
   docker-compose -f docker-compose.biometrics.yml up -d --scale insightface-rest=3
   ```

2. **Load Balancing:**
   - Enable nginx service
   - Configure upstream servers
   - Add health checks

3. **Caching:**
   - Enable Redis service
   - Cache recognition results
   - Implement rate limiting

## Backup and Maintenance

### Data Backup
```bash
# Backup Docker volumes
docker run --rm -v synktime-biometric_insightface_models:/data -v $(pwd):/backup alpine tar czf /backup/models.tar.gz /data

# Backup configuration
tar czf config-backup.tar.gz .env *.yml *.conf
```

### Updates
```bash
# Pull latest images
docker-compose -f docker-compose.biometrics.yml pull

# Restart services
docker-compose -f docker-compose.biometrics.yml up -d
```

### Cleanup
```bash
# Stop and remove containers
docker-compose -f docker-compose.biometrics.yml down

# Remove volumes (careful - this deletes model data)
docker-compose -f docker-compose.biometrics.yml down -v

# Clean up unused images
docker system prune -a
```

## Support

For issues with:
- **InsightFace**: Check [InsightFace documentation](https://github.com/deepinsight/insightface)
- **SourceAFIS**: Check [SourceAFIS documentation](https://sourceafis.machinezoo.com/)
- **Docker**: Check [Docker documentation](https://docs.docker.com/)
- **SynkTime Integration**: Refer to main application documentation