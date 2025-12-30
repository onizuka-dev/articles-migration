/**
 * Visual Verification Script using Playwright
 *
 * Verifies that migrated articles have:
 * 1. Hero/featured image visible
 * 2. Content images visible
 *
 * Usage: node articles-migration/verify-visual.js [article-path] [base-url]
 * Example: node articles-migration/verify-visual.js "/articles/legal/certificate-of-formation" "https://bizee.test"
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

async function verifyVisualElements(articlePath, baseUrl = 'https://bizee.test') {
  const fullUrl = `${baseUrl}${articlePath}`;

  console.log('\n📸 VERIFICACIÓN VISUAL CON PLAYWRIGHT');
  console.log('=====================================');
  console.log(`URL: ${fullUrl}`);
  console.log('');

  const results = {
    passed: true,
    errors: [],
    warnings: [],
    hero: {
      found: false,
      visible: false,
      src: null,
      dimensions: null,
    },
    contentImages: {
      expected: 0,
      found: 0,
      visible: 0,
      images: [],
    },
    screenshots: {
      hero: null,
      fullPage: null,
    },
  };

  let browser;

  try {
    browser = await chromium.launch({
      headless: true,
      args: ['--ignore-certificate-errors'],
    });

    const context = await browser.newContext({
      ignoreHTTPSErrors: true,
      viewport: { width: 1280, height: 720 },
    });

    const page = await context.newPage();

    // Track failed image requests
    const failedImages = [];
    page.on('response', response => {
      if (response.request().resourceType() === 'image' && !response.ok()) {
        failedImages.push({
          url: response.url(),
          status: response.status(),
        });
      }
    });

    console.log('🔄 Cargando página...');

    try {
      await page.goto(fullUrl, {
        waitUntil: 'networkidle',
        timeout: 30000,
      });
    } catch (navError) {
      results.passed = false;
      results.errors.push(`No se pudo cargar la página: ${navError.message}`);
      return results;
    }

    // Wait for images to load
    await page.waitForTimeout(2000);

    console.log('✅ Página cargada');
    console.log('');

    // =====================
    // 1. VERIFY HERO IMAGE
    // =====================
    console.log('🖼️  Verificando Hero Image...');

    // Common selectors for hero image in article pages
    const heroSelectors = [
      'article header img',
      '.article-hero img',
      '.featured-image img',
      '[class*="hero"] img',
      'article > div:first-child img',
      '.article-header img',
      'header img[src*="featured"]',
      'img[src*="featured"]',
      'img[src*="hero"]',
      'article img:first-of-type',
    ];

    for (const selector of heroSelectors) {
      try {
        const heroImg = await page.$(selector);
        if (heroImg) {
          const box = await heroImg.boundingBox();
          const src = await heroImg.getAttribute('src');

          if (box && box.width > 200 && box.height > 100) {
            results.hero.found = true;
            results.hero.src = src;
            results.hero.dimensions = { width: box.width, height: box.height };

            // Check if actually visible (not hidden, not zero opacity)
            const isVisible = await heroImg.isVisible();
            results.hero.visible = isVisible;

            console.log(`   ✅ Hero encontrado: ${src}`);
            console.log(`   📐 Dimensiones: ${Math.round(box.width)}x${Math.round(box.height)}px`);
            break;
          }
        }
      } catch (e) {
        // Selector didn't match, continue
      }
    }

    if (!results.hero.found) {
      results.passed = false;
      results.errors.push('Hero image NOT FOUND in the page');
      console.log('   ❌ Hero image NO encontrado');
    } else if (!results.hero.visible) {
      results.passed = false;
      results.errors.push('Hero image found but NOT VISIBLE');
      console.log('   ⚠️  Hero encontrado pero NO visible');
    }

    console.log('');

    // ========================
    // 2. VERIFY CONTENT IMAGES
    // ========================
    console.log('🖼️  Verificando imágenes del contenido...');

    // Get all images within article content
    const contentImageSelectors = [
      'article img:not(header img)',
      '.article-content img',
      '.main-content img',
      '[class*="content"] img',
      'article section img',
      '.rich-text img',
      'img[src*="main-content"]',
    ];

    const allImages = [];

    for (const selector of contentImageSelectors) {
      try {
        const images = await page.$$(selector);
        for (const img of images) {
          const src = await img.getAttribute('src');
          if (src && !allImages.some(i => i.src === src)) {
            const box = await img.boundingBox();
            const isVisible = await img.isVisible();
            const alt = await img.getAttribute('alt') || '';

            allImages.push({
              src,
              alt,
              visible: isVisible,
              dimensions: box ? { width: box.width, height: box.height } : null,
              isContentImage: src.includes('main-content') || src.includes('content/'),
            });
          }
        }
      } catch (e) {
        // Continue
      }
    }

    // Filter to actual content images (not icons, logos, etc.)
    const contentImages = allImages.filter(img =>
      img.dimensions &&
      img.dimensions.width > 100 &&
      img.dimensions.height > 100 &&
      !img.src.includes('logo') &&
      !img.src.includes('icon') &&
      !img.src.includes('avatar'),
    );

    results.contentImages.found = contentImages.length;
    results.contentImages.visible = contentImages.filter(img => img.visible).length;
    results.contentImages.images = contentImages;

    if (contentImages.length > 0) {
      console.log(`   ✅ ${contentImages.length} imagen(es) de contenido encontrada(s)`);
      contentImages.forEach((img, idx) => {
        const status = img.visible ? '✅' : '❌';
        const dims = img.dimensions ? `${Math.round(img.dimensions.width)}x${Math.round(img.dimensions.height)}px` : 'N/A';
        console.log(`      ${idx + 1}. ${status} ${img.src.split('/').pop()} (${dims})`);
      });
    } else {
      console.log('   ⚠️  No se encontraron imágenes de contenido');
      results.warnings.push('No content images found (this may be expected for some articles)');
    }

    // Check for invisible content images
    const invisibleImages = contentImages.filter(img => !img.visible);
    if (invisibleImages.length > 0) {
      results.errors.push(`${invisibleImages.length} content image(s) NOT VISIBLE`);
      results.passed = false;
    }

    console.log('');

    // ========================
    // 3. CHECK FAILED IMAGES
    // ========================
    if (failedImages.length > 0) {
      console.log('❌ Imágenes que fallaron al cargar:');
      failedImages.forEach(img => {
        console.log(`   - ${img.url} (Status: ${img.status})`);
        results.errors.push(`Image failed to load: ${img.url} (${img.status})`);
      });
      results.passed = false;
      console.log('');
    }

    // ========================
    // 4. TAKE SCREENSHOTS
    // ========================
    const screenshotDir = '/tmp/migration-screenshots';
    if (!fs.existsSync(screenshotDir)) {
      fs.mkdirSync(screenshotDir, { recursive: true });
    }

    const slug = articlePath.split('/').pop();
    const timestamp = Date.now();

    // Hero screenshot
    const heroScreenshot = path.join(screenshotDir, `${slug}-hero-${timestamp}.png`);
    await page.screenshot({
      path: heroScreenshot,
      clip: { x: 0, y: 0, width: 1280, height: 720 },
    });
    results.screenshots.hero = heroScreenshot;

    // Full page screenshot
    const fullScreenshot = path.join(screenshotDir, `${slug}-full-${timestamp}.png`);
    await page.screenshot({
      path: fullScreenshot,
      fullPage: true,
    });
    results.screenshots.fullPage = fullScreenshot;

    console.log('📷 Screenshots guardados:');
    console.log(`   - Hero: ${heroScreenshot}`);
    console.log(`   - Full: ${fullScreenshot}`);
    console.log('');

  } catch (error) {
    results.passed = false;
    results.errors.push(`Error durante la verificación: ${error.message}`);
    console.error('❌ Error:', error.message);
  } finally {
    if (browser) {
      await browser.close();
    }
  }

  // ========================
  // FINAL SUMMARY
  // ========================
  console.log('=====================================');
  console.log('📊 RESUMEN DE VERIFICACIÓN VISUAL');
  console.log('=====================================');
  console.log(`Estado: ${results.passed ? '✅ PASSED' : '❌ FAILED'}`);
  console.log(`Hero Image: ${results.hero.found ? (results.hero.visible ? '✅ Visible' : '⚠️ Encontrado pero no visible') : '❌ No encontrado'}`);
  console.log(`Content Images: ${results.contentImages.visible}/${results.contentImages.found} visibles`);

  if (results.errors.length > 0) {
    console.log('\n❌ Errores:');
    results.errors.forEach(err => console.log(`   - ${err}`));
  }

  if (results.warnings.length > 0) {
    console.log('\n⚠️  Warnings:');
    results.warnings.forEach(warn => console.log(`   - ${warn}`));
  }

  console.log('');

  // Output JSON for agent consumption
  console.log('JSON_OUTPUT_START');
  console.log(JSON.stringify(results, null, 2));
  console.log('JSON_OUTPUT_END');

  return results;
}

// Parse command line arguments
const args = process.argv.slice(2);
if (args.length < 1) {
  console.error('Usage: node verify-visual.js <article-path> [base-url]');
  console.error('Example: node verify-visual.js "/articles/legal/certificate-of-formation" "https://bizee.test"');
  process.exit(1);
}

const articlePath = args[0];
const baseUrl = args[1] || 'https://bizee.test';

verifyVisualElements(articlePath, baseUrl)
  .then(results => {
    process.exit(results.passed ? 0 : 1);
  })
  .catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
  });
