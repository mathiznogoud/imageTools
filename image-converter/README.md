# Image Converter CLI - Docker Setup Guide

This guide will help you build and run the PHP Image Converter CLI tool using Docker.

## Prerequisites

- Docker installed on your system
- Docker Compose (optional, but recommended)

## Project Structure

```
image-converter/
├── convert.php
├── Dockerfile
├── docker-compose.yml
├── .dockerignore
├── input/          # Directory for input images
├── output/         # Directory for converted images
└── images/         # Optional: Your existing images directory
```

## Setup Instructions

### 1. Create Project Directory and Files

```bash
mkdir image-converter
cd image-converter
```

Create the following files in your project directory:
- `convert.php` - The main PHP script
- `Dockerfile` - Docker configuration
- `docker-compose.yml` - Docker Compose configuration
- `.dockerignore` - Files to ignore during build

### 2. Create Input/Output Directories

```bash
mkdir input output
```

### 3. Build the Docker Image

#### Option A: Using Docker directly
```bash
docker build -t image-converter .
```

#### Option B: Using Docker Compose
```bash
docker-compose build
```

## Usage Examples

### Method 1: Using Docker Run

#### Convert a Single File
```bash
# Copy your image to the input directory first
cp /path/to/your/image.jpg input/

# Convert to AVIF
docker run --rm -v $(pwd)/input:/app/input -v $(pwd)/output:/app/output image-converter \
  --input=/app/input/image.jpg --output=/app/output --format=avif

# Convert to WebP with custom quality
docker run --rm -v $(pwd)/input:/app/input -v $(pwd)/output:/app/output image-converter \
  --input=/app/input/image.jpg --output=/app/output --format=webp --quality=90

# Convert to both AVIF and WebP
docker run --rm -v $(pwd)/input:/app/input -v $(pwd)/output:/app/output image-converter \
  --input=/app/input/image.jpg --output=/app/output --format=both
```

#### Convert Entire Directory
```bash
# Copy your images to the input directory
cp /path/to/your/images/* input/

# Convert all images in directory to AVIF
docker run --rm -v $(pwd)/input:/app/input -v $(pwd)/output:/app/output image-converter \
  --input=/app/input --output=/app/output --format=avif

# Convert recursively to both formats
docker run --rm -v $(pwd)/input:/app/input -v $(pwd)/output:/app/output image-converter \
  --input=/app/input --output=/app/output --format=both --recursive

# Convert recursively with subdirectories to WebP only
docker run --rm -v $(pwd)/input:/app/input -v $(pwd)/output:/app/output image-converter \
  --input=/app/input --output=/app/output --format=webp --recursive
```

### Method 2: Using Docker Compose

#### Convert Single File
```bash
# Copy your image to input directory first
cp /path/to/your/image.jpg input/

# Run conversion to AVIF
docker-compose run --rm image-converter \
  --input=/app/input/image.jpg --output=/app/output --format=avif

# Convert to both formats
docker-compose run --rm image-converter \
  --input=/app/input/image.jpg --output=/app/output --format=both
```

#### Convert Directory
```bash
# Copy your images to input directory
cp -r /path/to/your/images/* input/

# Convert all images to WebP
docker-compose run --rm image-converter \
  --input=/app/input --output=/app/output --format=webp --recursive

# Convert all images to both AVIF and WebP
docker-compose run --rm image-converter \
  --input=/app/input --output=/app/output --format=both --recursive
```

### Method 3: Mount External Directories

If you want to work with images from a specific directory on your host system:

```bash
# Convert images from /home/user/photos to /home/user/converted
docker run --rm \
  -v /home/user/photos:/app/input \
  -v /home/user/converted:/app/output \
  image-converter \
  --input=/app/input --output=/app/output --format=avif --recursive
```

## Advanced Usage

### Interactive Shell Access
```bash
# Access the container shell for debugging
docker run -it --rm -v $(pwd)/input:/app/input -v $(pwd)/output:/app/output image-converter bash

# Or with docker-compose
docker-compose run --rm image-converter bash
```

### Custom Script Location
```bash
# If you want to run the script from a different location
docker run --rm -v $(pwd):/app -w /app php:8.2-cli php convert.php --help
```

### Batch Processing Script

Create a bash script for easier batch processing:

```bash
#!/bin/bash
# batch-convert.sh

INPUT_DIR="$1"
OUTPUT_DIR="$2"
FORMAT="${3:-both}"
QUALITY="${4:-80}"

if [ -z "$INPUT_DIR" ] || [ -z "$OUTPUT_DIR" ]; then
    echo "Usage: $0 <input_dir> <output_dir> [format] [quality]"
    echo "Example: $0 ./photos ./converted webp 90"
    echo "Example: $0 ./photos ./converted both 85"
    echo "Example: $0 ./photos ./converted avif 80"
    exit 1
fi

docker run --rm \
    -v "$(realpath $INPUT_DIR):/app/input" \
    -v "$(realpath $OUTPUT_DIR):/app/output" \
    image-converter \
    --input=/app/input --output=/app/output --format=$FORMAT --quality=$QUALITY --recursive

echo "Conversion complete! Check $OUTPUT_DIR for results."
```

Make it executable:
```bash
chmod +x batch-convert.sh

# Convert to both AVIF and WebP (default)
./batch-convert.sh ./photos ./converted

# Convert to AVIF only with high quality
./batch-convert.sh ./photos ./converted avif 90

# Convert to WebP only
./batch-convert.sh ./photos ./converted webp 85
```

## Troubleshooting

### Common Issues

1. **Permission Denied Errors**
   ```bash
   # Fix ownership of output files
   sudo chown -R $USER:$USER output/
   ```

2. **ImageMagick Not Supporting AVIF**
   - The Dockerfile includes AVIF support, but if you encounter issues, try using WebP format instead

3. **Large Files Taking Too Long**
   - Reduce quality setting: `--quality=60`
   - Use WebP instead of AVIF for faster processing

4. **Out of Memory Errors**
   - Increase Docker memory limit in Docker Desktop settings
   - Process smaller batches of images

### Viewing Help
```bash
docker run --rm image-converter --help
```

### Checking ImageMagick Capabilities
```bash
# Check supported formats
docker run --rm image-converter php -r "print_r(Imagick::queryFormats());"
```

## Performance Tips

1. **Optimize for Batch Processing**: Process entire directories rather than individual files
2. **Quality Settings**: Use quality 70-80 for web images, 85-95 for archival
3. **Format Choice**: 
   - AVIF: Better compression, newer format
   - WebP: Wider browser support, faster processing
   - Both: Creates two files per image for maximum compatibility (AVIF for modern browsers, WebP as fallback)
4. **Processing Time**: Using `--format=both` takes roughly 2x longer than single format conversion
5. **Storage**: Using `--format=both` doubles the output file count but provides optimal web compatibility

## Cleanup

Remove the Docker image when no longer needed:
```bash
docker rmi image-converter
```

Remove all related containers and images:
```bash
docker-compose down --rmi all
```
