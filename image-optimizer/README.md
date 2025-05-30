# Docker Image Optimizer

A comprehensive Docker-based image optimization tool that uses the Spatie Image Optimizer package to compress and optimize images while maintaining quality. This tool supports JPEG, PNG, GIF, WebP, AVIF, and SVG formats with best-in-class optimization settings.

## Features

- 🖼️ **Multi-format support**: JPEG, PNG, GIF, WebP, AVIF, SVG
- 🚀 **Best-in-class optimization**: Optimal settings for each image format
- 📁 **Recursive directory processing**: Processes all images in subdirectories
- 💾 **Automatic backups**: Optional backup creation before optimization
- 📊 **Detailed statistics**: Shows file sizes, savings, and compression ratios
- ⚙️ **Configurable quality**: Adjustable optimization quality levels
- 🐳 **Docker-ready**: Easy deployment with Docker and Docker Compose
- 🔧 **Environment variables**: Easy configuration without code changes

## Quick Start

1. **Clone or create the project structure:**
```bash
mkdir image-optimizer && cd image-optimizer
```

2. **Create the required files** (see Project Structure below)

3. **Add your images** to the `images/` directory

4. **Run with Docker Compose:**
```bash
docker-compose up --build
```

## Project Structure

```
image-optimizer/
├── Dockerfile
├── docker-compose.yml
├── README.md
├── src/
│   ├── optimize.php
│   └── composer.json
└── images/
    ├── your-image1.jpg
    ├── subfolder/
    │   └── your-image2.png
    └── backup/         (created automatically)
        ├── your-image1.jpg
        └── subfolder/
            └── your-image2.png
```

## Installation & Usage

### Method 1: Docker Compose (Recommended)

1. **Build and run:**
```bash
docker-compose up --build
```

2. **Run with custom settings:**
```bash
# Edit docker-compose.yml to change environment variables
OPTIMIZATION_QUALITY=80 BACKUP_ENABLED=false docker-compose up
```

### Method 2: Docker CLI

1. **Build the image:**
```bash
docker build -t image-optimizer .
```

2. **Run with default settings:**
```bash
docker run -v $(pwd)/images:/images image-optimizer
```

3. **Run with custom settings:**
```bash
docker run -v $(pwd)/images:/images \
  -e OPTIMIZATION_QUALITY=80 \
  -e BACKUP_ENABLED=true \
  image-optimizer
```

4. **Run on a different directory:**
```bash
docker run -v /path/to/your/images:/images image-optimizer
```

## Configuration

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `OPTIMIZATION_QUALITY` | `85` | Quality level for JPEG/WebP optimization (1-100) |
| `BACKUP_ENABLED` | `true` | Create backups before optimization |

### Optimization Settings by Format

| Format | Tools Used | Settings |
|--------|------------|----------|
| **JPEG** | jpegoptim | Progressive encoding, 85% quality, strip metadata |
| **PNG** | pngquant + optipng | Two-pass optimization, 65-85% quality range |
| **GIF** | gifsicle | Highest optimization level, animation-safe |
| **WebP** | cwebp | Method 6, 85% quality, multi-threading |
| **AVIF** | avifenc | Speed 0, high-quality CPU encoding |
| **SVG** | svgo | Multi-pass, remove dimensions, clean markup |

## Examples

### Basic Usage
```bash
# Place images in ./images/ directory
cp /path/to/photos/* ./images/

# Run optimization
docker-compose up --build
```

### Custom Quality Setting
```bash
# Lower quality for smaller files (good for web)
docker run -v $(pwd)/images:/images \
  -e OPTIMIZATION_QUALITY=70 \
  image-optimizer

# Higher quality for print (larger files)
docker run -v $(pwd)/images:/images \
  -e OPTIMIZATION_QUALITY=95 \
  image-optimizer
```

### Batch Processing Different Directories
```bash
# Optimize multiple directories
for dir in /home/user/photos/*; do
  docker run -v "$dir":/images image-optimizer
done
```

### Without Backups (Faster)
```bash
docker run -v $(pwd)/images:/images \
  -e BACKUP_ENABLED=false \
  image-optimizer
```

## Sample Output

```
Starting optimization with quality: 85%
Backup enabled

Processing: vacation/IMG_001.jpg
Original size: 2.45 MB
New size: 891.23 KB
Saved: 1.56 MB (63.64%)
--------------------------------------------------
Processing: documents/scan.png
Original size: 1.23 MB
New size: 456.78 KB
Saved: 792.45 KB (64.43%)
--------------------------------------------------

Optimization Summary:
Total files processed: 25/25
Errors encountered: 0
Total space saved: 18.92 MB
Average savings per file: 756.8 KB
Backups created in: /images/backup/
```

## Supported File Formats

- **JPEG/JPG**: Lossy compression with progressive encoding
- **PNG**: Lossless compression with palette optimization
- **GIF**: Lossless compression preserving animations
- **WebP**: Modern format with excellent compression
- **AVIF**: Next-generation format with superior compression
- **SVG**: Vector format with markup optimization

## Performance Tips

1. **Quality Settings:**
   - Web images: 70-80
   - General use: 80-90
   - Print quality: 90-95

2. **Large Batches:**
   - Disable backups for faster processing
   - Process directories separately for better progress tracking

3. **Storage:**
   - Monitor disk space when using backups
   - Clean backup directory periodically

## Troubleshooting

### Common Issues

**1. Permission Denied**
```bash
# Fix file permissions
chmod -R 755 images/
```

**2. No Images Found**
```bash
# Check if images directory exists and contains supported formats
ls -la images/
```

**3. Out of Disk Space**
```bash
# Disable backups or clean backup directory
docker run -v $(pwd)/images:/images -e BACKUP_ENABLED=false image-optimizer
```

**4. Docker Build Fails**
```bash
# Clean Docker cache and rebuild
docker system prune
docker-compose build --no-cache
```

### Debug Mode

To see detailed optimization output, modify the PHP script to remove `--quiet` flags from the optimizers.

## Advanced Usage

### Custom Docker Build

```dockerfile
# Add custom tools or modify settings
FROM php:8.2-cli
# ... your modifications
```

### Integration with CI/CD

```yaml
# GitHub Actions example
name: Optimize Images
on: [push]
jobs:
  optimize:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Optimize Images
        run: |
          docker build -t optimizer .
          docker run -v $PWD/assets:/images optimizer
```

## Security Considerations

- The container runs as root by default
- Only mount directories you want to modify
- Review the backup directory contents before deployment
- Consider using read-only mounts for source directories

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Changelog

### v1.0.0
- Initial release with multi-format support
- Docker and Docker Compose configuration
- Automatic backup functionality
- Environment variable configuration
- Comprehensive documentation

## Support

For issues and questions:
1. Check the troubleshooting section
2. Review Docker logs: `docker-compose logs`
3. Open an issue with reproduction steps

---

**Note**: Always test on a small batch of images first to ensure the optimization settings meet your quality requirements.
