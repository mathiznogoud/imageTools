# Build
docker build -t image-optimizer .

# Run with default settings
docker run -v $(pwd)/images:/images image-optimizer

# Run with custom settings
docker run -v $(pwd)/images:/images \
  -e OPTIMIZATION_QUALITY=80 \
  -e BACKUP_ENABLED=true \
  image-optimizer
