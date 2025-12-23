#!/bin/bash
# Extract all internal paths from an HTML file
# Usage: ./articles-migration/extract-internal-paths.sh <html-file>
# Example: ./articles-migration/extract-internal-paths.sh /tmp/article-full.html

if [ -z "$1" ]; then
    echo "Usage: ./articles-migration/extract-internal-paths.sh <html-file>"
    echo "Example: ./articles-migration/extract-internal-paths.sh /tmp/article-full.html"
    exit 1
fi

HTML_FILE="$1"

if [ ! -f "$HTML_FILE" ]; then
    echo "Error: File not found: $HTML_FILE"
    exit 1
fi

echo "=== Internal paths found in $HTML_FILE ==="
grep -o '<a[^>]*href="[^"]*"[^>]*>' "$HTML_FILE" | grep -v 'javascript:' | grep -v '#' | grep -o 'href="[^"]*"' | cut -d'"' -f2 | grep '^/' | sort -u | head -50
