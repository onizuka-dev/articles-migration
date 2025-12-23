#!/bin/bash
# Extract links from HTML that match specific keywords
# Usage: ./articles-migration/extract-links-by-keywords.sh <html-file> <keyword1> [keyword2] [keyword3] ...
# Example: ./articles-migration/extract-links-by-keywords.sh /tmp/article-full.html "business naming" "Virtual Mailbox"

if [ -z "$1" ] || [ -z "$2" ]; then
    echo "Usage: ./articles-migration/extract-links-by-keywords.sh <html-file> <keyword1> [keyword2] ..."
    echo "Example: ./articles-migration/extract-links-by-keywords.sh /tmp/article-full.html \"business naming\" \"Virtual Mailbox\""
    exit 1
fi

HTML_FILE="$1"
shift

if [ ! -f "$HTML_FILE" ]; then
    echo "Error: File not found: $HTML_FILE"
    exit 1
fi

# Build grep pattern from keywords
PATTERN=$(IFS='|'; echo "$*")

echo "=== Links matching keywords: $PATTERN ==="
grep -Ei "$PATTERN" "$HTML_FILE" | grep -o 'href="[^"]*"' | cut -d'"' -f2 | sort -u | head -20
