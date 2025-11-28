#!/usr/bin/env python3
"""
Script to download article images from URLs

Usage: python3 download-images.py [ARTICLE_URL] [ARTICLE_SLUG]

Example:
python3 download-images.py https://bizee.com/articles/can-a-minor-own-a-business can-a-minor-own-a-business
"""

import sys
import os
import re
import json
import urllib.request
import urllib.parse
from pathlib import Path
from datetime import datetime

class ImageDownloader:
    def __init__(self, article_url, article_slug):
        self.article_url = article_url
        self.article_slug = article_slug
        # Get project base directory (two levels up from script)
        script_dir = Path(__file__).parent
        self.base_dir = script_dir.parent / 'public' / 'assets'
        self.featured_path = self.base_dir / 'articles' / 'featured'
        self.main_content_path = self.base_dir / 'articles' / 'main-content'

        # Create directories
        self.featured_path.mkdir(parents=True, exist_ok=True)
        self.main_content_path.mkdir(parents=True, exist_ok=True)

    def download_image(self, image_url, destination_path):
        """Downloads an image from a URL"""
        try:
            # Create request with headers to avoid blocking
            req = urllib.request.Request(
                image_url,
                headers={
                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                }
            )

            with urllib.request.urlopen(req) as response:
                image_data = response.read()

                # Save image
                with open(destination_path, 'wb') as f:
                    f.write(image_data)

                return True
        except Exception as e:
            print(f"✗ Error downloading {image_url}: {e}")
            return False

    def extract_images_from_html(self, html):
        """Extracts image URLs from HTML"""
        images = []

        # Find images in <img> tags
        img_pattern = r'<img[^>]+src=["\']([^"\']+)["\'][^>]*>'
        matches = re.findall(img_pattern, html, re.IGNORECASE)
        images.extend(matches)

        # Find images in data-src (lazy loading)
        data_src_pattern = r'data-src=["\']([^"\']+)["\']'
        data_matches = re.findall(data_src_pattern, html, re.IGNORECASE)
        images.extend(data_matches)

        # Find images in background-image attributes
        bg_pattern = r'background-image:\s*url\(["\']?([^"\']+)["\']?\)'
        bg_matches = re.findall(bg_pattern, html, re.IGNORECASE)
        images.extend(bg_matches)

        # Remove duplicates and filter
        unique_images = []
        seen = set()

        for img in images:
            # Filter small images (icons, etc.)
            if any(skip in img.lower() for skip in ['icon', 'logo', 'avatar', 'favicon']):
                continue

            # Normalize URL
            img = self.normalize_url(img)

            if img and img not in seen:
                seen.add(img)
                unique_images.append(img)

        return unique_images

    def normalize_url(self, url):
        """Normalizes a relative URL to absolute"""
        if not url:
            return None

        # If already absolute, return as is
        if url.startswith('http://') or url.startswith('https://'):
            return url

        # If relative, convert to absolute
        parsed = urllib.parse.urlparse(self.article_url)
        base = f"{parsed.scheme}://{parsed.netloc}"

        if url.startswith('/'):
            return base + url
        else:
            return base + '/' + url

    def get_image_extension(self, url):
        """Gets image extension from URL"""
        parsed = urllib.parse.urlparse(url)
        path = parsed.path
        ext = os.path.splitext(path)[1].lower()

        if not ext:
            # Try to detect from content-type or use png as default
            ext = '.png'

        # Normalize extensions
        if ext == '.jpg':
            ext = '.jpeg'

        return ext

    def generate_filename(self, image_url, index, is_featured=False):
        """Generates a filename for the image"""
        ext = self.get_image_extension(image_url)

        if is_featured:
            filename = f"{self.article_slug}{ext}"
            return self.featured_path / filename
        else:
            filename = f"{self.article_slug}-{index}{ext}"
            return self.main_content_path / filename

    def fetch_article_html(self):
        """Gets article HTML"""
        try:
            req = urllib.request.Request(
                self.article_url,
                headers={
                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                }
            )

            with urllib.request.urlopen(req) as response:
                return response.read().decode('utf-8')
        except Exception as e:
            print(f"✗ Error getting HTML: {e}")
            return None

    def process_article(self):
        """Processes article and downloads all images"""
        print(f"=== Downloading images for: {self.article_slug} ===\n")
        print(f"Article URL: {self.article_url}\n")

        html = self.fetch_article_html()
        if not html:
            return

        images = self.extract_images_from_html(html)

        if not images:
            print("No images found in article.")
            return

        print(f"Found {len(images)} image(s):\n")

        downloaded_images = []

        for i, image_url in enumerate(images):
            print(f"Processing ({i+1}/{len(images)}): {image_url}")

            is_featured = (i == 0)
            destination_path = self.generate_filename(image_url, i, is_featured)

            if self.download_image(image_url, destination_path):
                # Save relative path from public/assets
                relative_path = str(destination_path.relative_to(self.base_dir))

                downloaded_images.append({
                    'original_url': image_url,
                    'local_path': relative_path.replace('\\', '/'),
                    'type': 'featured' if is_featured else 'main-content',
                    'index': i
                })
                print(f"✓ Descargada: {relative_path}\n")
            else:
                print()

        # Generate report
        self.generate_report(downloaded_images)

    def generate_report(self, downloaded_images):
        """Generates a JSON report of downloaded images"""
        report = {
            'article_slug': self.article_slug,
            'article_url': self.article_url,
            'downloaded_at': datetime.now().isoformat(),
            'images': downloaded_images
        }

        script_dir = Path(__file__).parent
        report_path = script_dir / f'image-mapping-{self.article_slug}.json'

        with open(report_path, 'w', encoding='utf-8') as f:
            json.dump(report, f, indent=2, ensure_ascii=False)

        print(f"✓ Reporte guardado en: {report_path}")

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print("Usage: python3 download-images.py [ARTICLE_URL] [ARTICLE_SLUG]")
        print("\nExample:")
        print("python3 download-images.py https://bizee.com/articles/can-a-minor-own-a-business can-a-minor-own-a-business")
        sys.exit(1)

    article_url = sys.argv[1]
    article_slug = sys.argv[2]

    downloader = ImageDownloader(article_url, article_slug)
    downloader.process_article()
