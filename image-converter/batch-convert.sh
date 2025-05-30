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
