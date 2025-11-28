#!/usr/bin/env python3
"""
Improved script to download ONLY images from article content
(excludes navigation, footer, social icons, etc.)

Usage: python3 download-images-improved.py [ARTICLE_URL] [ARTICLE_SLUG]
"""

import sys
import os
import re
import json
import urllib.request
import urllib.parse
from pathlib import Path
from datetime import datetime
from html.parser import HTMLParser

class ArticleContentParser(HTMLParser):
    """Parser to extract only images from article content"""
    def __init__(self):
        super().__init__()
        self.images = []
        self.in_article_content = False
        self.in_main_content = False
        self.skip_patterns = [
            'nav', 'header', 'footer', 'sidebar',
            'social', 'icon', 'logo', 'trustpilot',
            'twitter', 'linkedin', 'facebook',
            'mobile', 'menu', 'button-icon'
        ]

    def handle_starttag(self, tag, attrs):
        if tag == 'main' or (tag == 'article' and not self.in_article_content):
            self.in_main_content = True
        elif tag == 'img':
            # Extract src and data-src
            src = None
            for attr_name, attr_value in attrs:
                if attr_name in ('src', 'data-src'):
                    src = attr_value
                    break

            if src and self.is_content_image(src):
                self.images.append(src)

    def handle_endtag(self, tag):
        if tag == 'main' or tag == 'article':
            self.in_main_content = False

    def is_content_image(self, url):
        """Checks if image is from content and not navigation/footer"""
        url_lower = url.lower()

        # Exclude navigation/footer images
        for pattern in self.skip_patterns:
            if pattern in url_lower:
                return False

        # Exclude embedded data URIs
        if url.startswith('data:'):
            return False

        # Exclude Next.js optimization images from layout
        if '/_next/image' in url and any(p in url_lower for p in ['mobile', 'icon', 'logo']):
            return False

        # Exclude small images that are thumbnails from related articles
        if '/_next/image' in url:
            # Check size in parameters (w=384 or w=256 are small thumbnails)
            if 'w=384' in url or 'w=256' in url:
                return False

        # Include only real content images
        content_patterns = [
            'teenager', 'headphones', 'map', 'infographic',
            'states', 'minor', 'business', 'entrepreneur',
            'article', 'featured', 'content'
        ]

        # If it has content patterns, include it
        if any(pattern in url_lower for pattern in content_patterns):
            return True

        # If it's in main content area and not a small icon
        if self.in_main_content and not any(p in url_lower for p in ['icon', 'logo', 'svg']):
            # Check approximate size (exclude small icons)
            if '.svg' not in url_lower:
                return True

        return False

class ImageDownloader:
    def __init__(self, article_url, article_slug):
        self.article_url = article_url
        self.article_slug = article_slug
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
            # Clean URL from Next.js parameters
            if '/_next/image' in image_url:
                # Extract real URL from parameter
                parsed = urllib.parse.urlparse(image_url)
                params = urllib.parse.parse_qs(parsed.query)
                if 'url' in params:
                    image_url = urllib.parse.unquote(params['url'][0])
                    if not image_url.startswith('http'):
                        image_url = 'https://bizee.com' + image_url

            req = urllib.request.Request(
                image_url,
                headers={
                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                }
            )

            with urllib.request.urlopen(req) as response:
                image_data = response.read()

                with open(destination_path, 'wb') as f:
                    f.write(image_data)

                return True
        except Exception as e:
            print(f"✗ Error downloading {image_url}: {e}")
            return False

    def extract_content_images(self, html):
        """Extracts only images from article content"""
        parser = ArticleContentParser()
        parser.feed(html)
        return parser.images

    def normalize_url(self, url):
        """Normalizes a relative URL to absolute"""
        if not url:
            return None

        if url.startswith('http://') or url.startswith('https://'):
            return url

        parsed = urllib.parse.urlparse(self.article_url)
        base = f"{parsed.scheme}://{parsed.netloc}"

        if url.startswith('/'):
            return base + url
        else:
            return base + '/' + url

    def get_image_extension(self, url):
        """Gets image extension"""
        # Clean URL from parameters
        clean_url = url.split('?')[0]
        parsed = urllib.parse.urlparse(clean_url)
        path = parsed.path
        ext = os.path.splitext(path)[1].lower()

        if not ext:
            ext = '.webp'  # Default webp

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
            # Try to extract descriptive name from URL
            parsed = urllib.parse.urlparse(image_url)
            path_parts = parsed.path.split('/')
            base_name = path_parts[-1].split('.')[0] if path_parts else f"{self.article_slug}-{index}"

            # Clean name
            base_name = re.sub(r'[^a-zA-Z0-9-]', '-', base_name)
            filename = f"{base_name}{ext}"
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
        """Processes article and downloads only content images"""
        print(f"=== Downloading CONTENT images for: {self.article_slug} ===\n")
        print(f"Article URL: {self.article_url}\n")
        print("⚠️  Excluding navigation, footer and site element images\n")

        html = self.fetch_article_html()
        if not html:
            return

        images = self.extract_content_images(html)

        if not images:
            print("No content images found in article.")
            return

        print(f"Found {len(images)} content image(s):\n")

        downloaded_images = []

        for i, image_url in enumerate(images):
            print(f"Processing ({i+1}/{len(images)}): {image_url}")

            normalized_url = self.normalize_url(image_url)
            is_featured = (i == 0)
            destination_path = self.generate_filename(normalized_url, i, is_featured)

            if self.download_image(normalized_url, destination_path):
                relative_path = str(destination_path.relative_to(self.base_dir))

                downloaded_images.append({
                    'original_url': image_url,
                    'normalized_url': normalized_url,
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
            'note': 'Only images from article content (excludes navigation/footer)',
            'images': downloaded_images
        }

        script_dir = Path(__file__).parent
        report_path = script_dir / f'image-mapping-{self.article_slug}.json'

        with open(report_path, 'w', encoding='utf-8') as f:
            json.dump(report, f, indent=2, ensure_ascii=False)

        print(f"✓ Reporte guardado en: {report_path}")

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print("Usage: python3 download-images-improved.py [ARTICLE_URL] [ARTICLE_SLUG]")
        print("\nExample:")
        print("python3 download-images-improved.py https://bizee.com/articles/can-a-minor-own-a-business can-a-minor-own-a-business")
        sys.exit(1)

    article_url = sys.argv[1]
    article_slug = sys.argv[2]

    downloader = ImageDownloader(article_url, article_slug)
    downloader.process_article()
