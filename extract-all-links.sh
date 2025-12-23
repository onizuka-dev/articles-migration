#!/bin/bash
# Extract all links (internal and external) from an HTML file
# Usage: ./articles-migration/extract-all-links.sh <html-file>
# Example: ./articles-migration/extract-all-links.sh /tmp/article-full.html

if [ -z "$1" ]; then
    echo "Usage: ./articles-migration/extract-all-links.sh <html-file>"
    echo "Example: ./articles-migration/extract-all-links.sh /tmp/article-full.html"
    exit 1
fi

HTML_FILE="$1"

if [ ! -f "$HTML_FILE" ]; then
    echo "Error: File not found: $HTML_FILE"
    exit 1
fi

echo "=== All links found in $HTML_FILE ==="
echo ""
echo "--- Internal paths (starting with /) ---"
grep -o 'href="[^"]*"' "$HTML_FILE" | cut -d'"' -f2 | grep '^/' | grep -v 'javascript:' | grep -v '#' | sort -u

echo ""
echo "--- External URLs (starting with http) ---"
grep -o 'href="[^"]*"' "$HTML_FILE" | cut -d'"' -f2 | grep '^http' | sort -u
