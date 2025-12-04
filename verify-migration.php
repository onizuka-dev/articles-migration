<?php

/**
 * Script de Verificación Automática Post-Migración
 *
 * Este script verifica automáticamente todos los puntos críticos que
 * comúnmente requieren revisión después de migrar un artículo.
 *
 * Usage: php verify-migration.php [ARTICLE_FILE] [PRODUCTION_URL]
 * Example: php verify-migration.php content/collections/articles/2024-11-19.similar-business-names-heres-what-to-do.md https://bizee.com/articles/similar-business-names-heres-what-to-do
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\Storage;
use Statamic\Facades\AssetContainer;

// Load Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

class MigrationVerifier
{
    private $articleFile;
    private $productionUrl;
    private $articleData;
    private $productionHtml;
    private $errors = [];
    private $warnings = [];
    private $info = [];

    public function __construct($articleFile, $productionUrl)
    {
        $this->articleFile = $articleFile;
        $this->productionUrl = $productionUrl;

        if (!file_exists($articleFile)) {
            throw new Exception("Article file not found: {$articleFile}");
        }
    }

    public function verify(): array
    {
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║     Migration Verification Script                        ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n\n";

        echo "Article: {$this->articleFile}\n";
        echo "Production URL: {$this->productionUrl}\n\n";

        // Load article
        $this->loadArticle();

        // Download production HTML
        $this->downloadProductionHtml();

        // Run all verifications
        $this->verifyUUID();
        $this->verifyPublishedStatus();
        $this->verifySEOFields();
        $this->verifyImages();
        $this->verifyLinks();
        $this->verifyVideos();
        $this->verifyCTAs();
        $this->verifyTables();
        $this->verifyRouting();
        $this->verifyQuotes();
        $this->verifyBlockStructure();
        $this->verifyRichTextCombination();
        $this->verifyIntroStructure();
        $this->verifySubtitle();

        // Print results
        $this->printResults();

        return [
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'info' => $this->info,
        ];
    }

    private function loadArticle(): void
    {
        $content = file_get_contents($this->articleFile);
        $parts = explode('---', $content, 3);

        if (count($parts) < 3) {
            throw new Exception("Invalid article format");
        }

        $this->articleData = Yaml::parse($parts[1]);
        $this->articleData['_content'] = $parts[2] ?? '';
        $this->articleData['_yaml'] = $parts[1];
    }

    private function downloadProductionHtml(): void
    {
        echo "Downloading production HTML...\n";

        $ch = curl_init($this->productionUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        $this->productionHtml = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$this->productionHtml) {
            $this->warnings[] = "Could not download production HTML (HTTP {$httpCode})";
        }
    }

    // 1. Verify UUID is unique
    private function verifyUUID(): void
    {
        echo "\n[1/12] Verifying UUID uniqueness...\n";

        $uuid = $this->articleData['id'] ?? null;

        if (!$uuid) {
            $this->errors[] = "UUID is missing";
            return;
        }

        // Check if UUID format is valid
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
            $this->errors[] = "UUID format is invalid: {$uuid}";
            return;
        }

        // Check for duplicates in other articles
        $articlesDir = dirname($this->articleFile);
        $files = glob($articlesDir . '/*.md');
        $duplicates = [];

        foreach ($files as $file) {
            if ($file === $this->articleFile) continue;

            $content = file_get_contents($file);
            if (preg_match('/^id:\s*' . preg_quote($uuid, '/') . '/m', $content)) {
                $duplicates[] = basename($file);
            }
        }

        if (!empty($duplicates)) {
            $this->errors[] = "UUID is duplicated in: " . implode(', ', $duplicates);
        } else {
            echo "  ✓ UUID is unique\n";
        }
    }

    // 2. Verify published status
    private function verifyPublishedStatus(): void
    {
        echo "\n[2/13] Verifying published status...\n";

        $published = $this->articleData['published'] ?? null;

        if ($published === null) {
            $this->errors[] = "published field is missing";
        } elseif ($published === false) {
            $this->errors[] = "published is set to false. Migrated articles MUST have published: true";
        } elseif ($published === true) {
            echo "  ✓ Article is published\n";
        } else {
            $this->warnings[] = "published has unexpected value: " . var_export($published, true);
        }
    }

    // 3. Verify SEO fields
    private function verifySEOFields(): void
    {
        echo "\n[3/13] Verifying SEO fields...\n";

        $requiredFields = [
            'seo_title',
            'seo_meta_description',
            'seo_custom_meta_title',
            'seo_custom_meta_description',
            'seo_canonical',
            'seo_og_description',
            'seo_og_title',
            'seo_tw_title',
            'seo_tw_description',
        ];

        $missing = [];
        foreach ($requiredFields as $field) {
            if (!isset($this->articleData[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            $this->errors[] = "Missing SEO fields: " . implode(', ', $missing);
        } else {
            echo "  ✓ All SEO fields present\n";
        }

        // Verify SEO data matches production
        if ($this->productionHtml) {
            // Extract title from production
            if (preg_match('/<title>([^<]+)<\/title>/i', $this->productionHtml, $matches)) {
                $productionTitle = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5);
                $articleTitle = $this->articleData['seo_custom_meta_title'] ?? '';

                if ($productionTitle !== $articleTitle) {
                    $this->warnings[] = "SEO title mismatch:\n    Production: {$productionTitle}\n    Article: {$articleTitle}";
                }
            }

            // Extract meta description from production
            if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']+)["\']/i', $this->productionHtml, $matches)) {
                $productionDesc = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5);
                $articleDesc = $this->articleData['seo_custom_meta_description'] ?? '';

                if ($productionDesc !== $articleDesc) {
                    $this->warnings[] = "SEO description mismatch:\n    Production: {$productionDesc}\n    Article: {$articleDesc}";
                }
            }
        }
    }

    // 3. Verify images
    private function verifyImages(): void
    {
        echo "\n[4/13] Verifying images...\n";

        // Check featured_image
        $featuredImage = $this->articleData['featured_image'] ?? null;
        if (!$featuredImage) {
            $this->errors[] = "featured_image is missing";
        } else {
            // Verify it's an S3 path, not local
            if (strpos($featuredImage, 'public/assets/') !== false || strpos($featuredImage, '/tmp/') !== false) {
                $this->errors[] = "featured_image appears to be local: {$featuredImage}";
            } else {
                echo "  ✓ Featured image: {$featuredImage}\n";
            }
        }

        // Check content images in main_blocks
        $mainBlocks = $this->articleData['main_blocks'] ?? [];
        $contentImages = [];

        foreach ($mainBlocks as $block) {
            if (($block['type'] ?? '') === 'article_image') {
                $imagePath = $block['image'] ?? null;
                if ($imagePath) {
                    $contentImages[] = $imagePath;

                    // Verify it's an S3 path
                    if (strpos($imagePath, 'public/assets/') !== false || strpos($imagePath, '/tmp/') !== false) {
                        $this->errors[] = "Content image appears to be local: {$imagePath}";
                    }
                }
            }
        }

        if (!empty($contentImages)) {
            echo "  ✓ Found " . count($contentImages) . " content image(s)\n";
        }

        // Check if production has images that are missing
        if ($this->productionHtml) {
            preg_match_all('/<img[^>]+(?:src|srcSet)=["\']([^"\']+)["\'][^>]*>/i', $this->productionHtml, $matches);
            $productionImages = array_unique($matches[1]);

            // Filter out decorative images
            $contentImagePatterns = ['blog_top-image', 'BLOG_IMG', 'similar-business', 'statistics', 'table'];
            $relevantImages = array_filter($productionImages, function($url) use ($contentImagePatterns) {
                foreach ($contentImagePatterns as $pattern) {
                    if (stripos($url, $pattern) !== false) {
                        return true;
                    }
                }
                return false;
            });

            if (count($relevantImages) > count($contentImages) + 1) { // +1 for featured
                $this->warnings[] = "Production page may have more images than migrated article";
            }
        }
    }

    // 5. Verify links
    private function verifyLinks(): void
    {
        echo "\n[5/13] Verifying links (content only, excluding layout)...\n";

        // Extract links from article
        $articleLinks = $this->extractLinksFromArticle();

        // Extract links from production
        $productionLinks = $this->extractLinksFromProduction();

        // Get CTA URLs from article_button blocks (these shouldn't be counted as missing links)
        $ctaUrls = [];
        $mainBlocks = $this->articleData['main_blocks'] ?? [];
        foreach ($mainBlocks as $block) {
            if (($block['type'] ?? '') === 'article_button') {
                $url = $block['url'] ?? '';
                if ($url) {
                    // Normalize URL
                    if ($url[0] === '/') {
                        $url = 'https://bizee.com' . $url;
                    }
                    $ctaUrls[] = $url;
                }
            }
        }

        // Compare (exclude CTA URLs from missing links)
        $missingLinks = array_diff($productionLinks, $articleLinks, $ctaUrls);
        $extraLinks = array_diff($articleLinks, $productionLinks);

        // Filter out external links from extra links (they may be valid additions)
        $extraLinks = array_filter($extraLinks, function($link) {
            return strpos($link, 'https://bizee.com') === 0 ||
                   strpos($link, 'https://www.bizee.com') === 0;
        });

        if (!empty($missingLinks)) {
            $this->errors[] = "Missing links (" . count($missingLinks) . "):\n    " . implode("\n    ", array_slice($missingLinks, 0, 10));
            if (count($missingLinks) > 10) {
                $this->errors[] = "    ... and " . (count($missingLinks) - 10) . " more";
            }
        }

        if (!empty($extraLinks)) {
            $this->warnings[] = "Extra internal links in article (" . count($extraLinks) . "):\n    " . implode("\n    ", array_slice($extraLinks, 0, 5));
        }

        if (empty($missingLinks) && empty($extraLinks)) {
            echo "  ✓ All links match production\n";
        } else {
            echo "  ✓ Found " . count($articleLinks) . " links in article, " . count($productionLinks) . " in production\n";
        }
    }

    private function extractLinksFromArticle(): array
    {
        $links = [];
        $mainBlocks = $this->articleData['main_blocks'] ?? [];

        foreach ($mainBlocks as $block) {
            if (($block['type'] ?? '') === 'rich_text') {
                $content = $block['content'] ?? [];
                $this->extractLinksFromBardContent($content, $links);
            }
        }

        return array_unique($links);
    }

    private function extractLinksFromBardContent($content, &$links): void
    {
        foreach ($content as $item) {
            if (isset($item['marks'])) {
                foreach ($item['marks'] as $mark) {
                    if (($mark['type'] ?? '') === 'link') {
                        $href = $mark['attrs']['href'] ?? '';
                        if ($href) {
                            $links[] = $href;
                        }
                    }
                }
            }

            if (isset($item['content'])) {
                $this->extractLinksFromBardContent($item['content'], $links);
            }
        }
    }

    private function extractLinksFromProduction(): array
    {
        if (!$this->productionHtml) return [];

        $links = [];

        // Extract links ONLY from the main article content area
        // Exclude: header, footer, sidebar, featured articles section, navigation

        // First, extract the main content area
        if (preg_match('/<main[^>]*id=["\']main-webpage-content["\'][^>]*>(.*?)<\/main>/is', $this->productionHtml, $mainMatch)) {
            $mainContent = $mainMatch[1];

            // Remove header section if present
            $mainContent = preg_replace('/<header[^>]*>.*?<\/header>/is', '', $mainContent);

            // Remove footer section if present
            $mainContent = preg_replace('/<footer[^>]*>.*?<\/footer>/is', '', $mainContent);

            // Remove aside/sidebar sections (Featured Articles, etc.)
            $mainContent = preg_replace('/<aside[^>]*>.*?<\/aside>/is', '', $mainContent);

            // Remove navigation sections
            $mainContent = preg_replace('/<nav[^>]*>.*?<\/nav>/is', '', $mainContent);

            // Remove "Featured Articles" section - look for common patterns
            $mainContent = preg_replace('/<div[^>]*class="[^"]*featured[^"]*article[^"]*"[^>]*>.*?<\/div>/is', '', $mainContent);
            $mainContent = preg_replace('/<section[^>]*class="[^"]*featured[^"]*"[^>]*>.*?<\/section>/is', '', $mainContent);

            // Remove podcast sections
            $mainContent = preg_replace('/<div[^>]*class="[^"]*podcast[^"]*"[^>]*>.*?<\/div>/is', '', $mainContent);
            $mainContent = preg_replace('/<section[^>]*class="[^"]*podcast[^"]*"[^>]*>.*?<\/section>/is', '', $mainContent);

            // Remove author bio sections
            $mainContent = preg_replace('/<div[^>]*class="[^"]*author[^"]*"[^>]*>.*?<\/div>/is', '', $mainContent);

            // Now extract links from the cleaned content
            preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $mainContent, $matches);

            foreach ($matches[1] as $href) {
                // Normalize internal links
                if ($href[0] === '/') {
                    $href = 'https://bizee.com' . $href;
                }

                // Filter out unwanted links (layout, social sharing, etc.)
                $exclude = [
                    '#',
                    'javascript:',
                    'mailto:',
                    'tel:',
                    '/author/',
                    '/get-bizee-podcast',
                    'sharer.php',
                    'share-offsite',
                    'intent/tweet',
                    '_next',
                    'static',
                    'twitter.com',
                    'facebook.com',
                    'linkedin.com',
                    'x.com',
                    '/articles/austin-startup-scene',  // Featured article
                    '/articles/how-to-start-a-consulting-business',  // Featured article
                    '/articles/business-formation/can-cpa-set-up-llc-for-client',  // Featured article
                ];

                $shouldExclude = false;
                foreach ($exclude as $pattern) {
                    if (stripos($href, $pattern) !== false) {
                        $shouldExclude = true;
                        break;
                    }
                }

                // Only include bizee.com internal links and external links that are clearly content links
                if (!$shouldExclude) {
                    if (strpos($href, 'https://bizee.com') === 0 ||
                        strpos($href, 'https://www.bizee.com') === 0 ||
                        strpos($href, 'https://www.uspto.gov') === 0 ||
                        strpos($href, 'https://orders.bizee.com') === 0 ||
                        strpos($href, 'https://www.care.com') === 0 ||
                        strpos($href, 'https://www.taskrabbit.com') === 0 ||
                        strpos($href, 'https://www.canva.com') === 0 ||
                        strpos($href, 'https://kdp.amazon.com') === 0 ||
                        strpos($href, 'https://www.shopify.com') === 0 ||
                        strpos($href, 'https://www.amazon.com') === 0) {
                        $links[] = $href;
                    }
                }
            }
        }

        return array_unique($links);
    }

    // 5. Verify videos
    private function verifyVideos(): void
    {
        echo "\n[6/13] Verifying videos...\n";

        // Extract videos from article
        $articleVideos = [];
        $mainBlocks = $this->articleData['main_blocks'] ?? [];

        foreach ($mainBlocks as $block) {
            if (($block['type'] ?? '') === 'video') {
                $videoUrl = $block['video_url'] ?? '';
                if ($videoUrl) {
                    $articleVideos[] = $videoUrl;
                }
            }
        }

        // Extract videos from production
        $productionVideos = [];
        if ($this->productionHtml) {
            // Look for Wistia videos
            preg_match_all('/incfile\.wistia\.com\/medias\/([a-z0-9]+)/i', $this->productionHtml, $matches);
            foreach ($matches[1] as $videoId) {
                $productionVideos[] = "https://incfile.wistia.com/medias/{$videoId}";
            }
        }

        $missingVideos = array_diff($productionVideos, $articleVideos);

        if (!empty($missingVideos)) {
            $this->errors[] = "Missing videos (" . count($missingVideos) . "):\n    " . implode("\n    ", $missingVideos);
        }

        if (empty($productionVideos) && empty($articleVideos)) {
            echo "  ✓ No videos found (none expected)\n";
        } elseif (empty($missingVideos)) {
            echo "  ✓ All videos migrated (" . count($articleVideos) . ")\n";
        }
    }

    // 6. Verify CTAs (article_button blocks)
    private function verifyCTAs(): void
    {
        echo "\n[7/14] Verifying CTAs (article_button blocks)...\n";

        $articleCTAs = [];
        $mainBlocks = $this->articleData['main_blocks'] ?? [];
        $ctaPositions = [];

        foreach ($mainBlocks as $index => $block) {
            if (($block['type'] ?? '') === 'article_button') {
                $label = $block['label'] ?? [];
                $url = $block['url'] ?? '';

                // Extract text from label
                $text = '';
                foreach ($label as $para) {
                    foreach ($para['content'] ?? [] as $item) {
                        if (isset($item['text'])) {
                            $text .= $item['text'] . ' ';
                        }
                    }
                }

                $articleCTAs[] = [
                    'text' => trim($text),
                    'url' => $url,
                    'position' => $index,
                ];

                $ctaPositions[] = $index;
            }
        }

        // Extract CTAs from production (content only, excluding layout) with their positions with their positions
        $productionCTAs = [];
        $productionCTAPositions = [];
        if ($this->productionHtml) {
            // Extract main content area first
            if (preg_match('/<main[^>]*id=["\']main-webpage-content["\'][^>]*>(.*?)<\/main>/is', $this->productionHtml, $mainMatch)) {
                $mainContent = $mainMatch[1];

                // Remove aside/sidebar sections (Featured Articles, etc.)
                $mainContent = preg_replace('/<aside[^>]*>.*?<\/aside>/is', '', $mainContent);

                // Split content into sections to determine relative positions
                // Look for content blocks (rich-text sections) and CTAs
                $contentBlocks = [];

                // Find all rich-text sections (they have class "D9XFaD0g rich-t")
                preg_match_all('/<div[^>]*class="[^"]*D9XFaD0g[^"]*rich-t[^"]*"[^>]*>(.*?)<\/div>/is', $mainContent, $richTextMatches, PREG_OFFSET_CAPTURE);

                // Find all CTA sections
                preg_match_all('/<div[^>]*class="[^"]*(?:rounded-8|bg-black|bg-primary-600)[^"]*"[^>]*>.*?<h2[^>]*>(.*?)<\/h2>.*?<p[^>]*>(.*?)<\/p>.*?<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>.*?<\/div>/is', $mainContent, $ctaMatches, PREG_OFFSET_CAPTURE);

                // Build ordered list of content elements with their positions
                $allElements = [];
                foreach ($richTextMatches[0] as $match) {
                    $allElements[] = ['type' => 'content', 'offset' => $match[1], 'content' => $match[0]];
                }
                foreach ($ctaMatches[0] as $index => $match) {
                    $title = strip_tags($ctaMatches[1][$index][0] ?? '');
                    $subtitle = strip_tags($ctaMatches[2][$index][0] ?? '');
                    $url = $ctaMatches[3][$index][0] ?? '';
                    $buttonText = strip_tags($ctaMatches[4][$index][0] ?? '');

                    // Normalize URL
                    if ($url && $url[0] === '/') {
                        $url = 'https://bizee.com' . $url;
                    }

                    // Only count if it has CTA characteristics
                    if ($url && (
                        stripos($title, 'Form Your LLC') !== false ||
                        stripos($title, 'PROTECT') !== false ||
                        stripos($title, 'ORDER') !== false ||
                        stripos($buttonText, 'GET STARTED') !== false ||
                        stripos($buttonText, 'PROTECT') !== false ||
                        stripos($buttonText, 'ORDER') !== false ||
                        stripos($url, 'orders.bizee.com') !== false ||
                        stripos($url, 'trademark-name-search') !== false
                    )) {
                        $allElements[] = [
                            'type' => 'cta',
                            'offset' => $match[1],
                            'title' => trim($title),
                            'subtitle' => trim($subtitle),
                            'button' => trim($buttonText),
                            'url' => $url,
                        ];
                    }
                }

                // Sort by offset position
                usort($allElements, function($a, $b) {
                    return $a['offset'] <=> $b['offset'];
                });

                // Extract CTAs with their relative positions
                $contentBlockIndex = 0;
                foreach ($allElements as $element) {
                    if ($element['type'] === 'content') {
                        $contentBlockIndex++;
                    } elseif ($element['type'] === 'cta') {
                        $productionCTAs[] = [
                            'title' => $element['title'],
                            'subtitle' => $element['subtitle'],
                            'button' => $element['button'],
                            'url' => $element['url'],
                            'position_after_content_block' => $contentBlockIndex, // Position after which content block
                        ];
                        $productionCTAPositions[] = $contentBlockIndex;
                    }
                }
            }
        }

        // Compare CTAs and verify positions
        $missingCTAs = [];
        $positionMismatches = [];

        // Calculate article CTA positions relative to content blocks
        $articleCTAPositions = [];
        $contentBlockIndex = 0;
        foreach ($mainBlocks as $index => $block) {
            $type = $block['type'] ?? '';
            if ($type === 'rich_text') {
                $contentBlockIndex++;
            } elseif ($type === 'article_button') {
                $articleCTAPositions[] = [
                    'block_index' => $index,
                    'position_after_content_block' => $contentBlockIndex,
                ];
            }
        }

        // Match production CTAs with article CTAs by URL and verify positions
        foreach ($productionCTAs as $prodIndex => $prodCTA) {
            $found = false;
            $matchedArticleIndex = null;

            foreach ($articleCTAs as $articleIndex => $articleCTA) {
                // Match by URL (most reliable)
                if ($prodCTA['url'] === $articleCTA['url']) {
                    $found = true;
                    $matchedArticleIndex = $articleIndex;

                    // Verify position matches
                    $prodPosition = $prodCTA['position_after_content_block'] ?? null;
                    $articlePosition = $articleCTAPositions[$articleIndex]['position_after_content_block'] ?? null;

                    if ($prodPosition !== null && $articlePosition !== null && $prodPosition !== $articlePosition) {
                        $positionMismatches[] = "CTA \"{$prodCTA['title']}\" is at position after content block #{$articlePosition} in article, but should be after content block #{$prodPosition} (as in production)";
                    }
                    break;
                }
                // Or match by title text
                if (stripos($articleCTA['text'], $prodCTA['title']) !== false) {
                    $found = true;
                    $matchedArticleIndex = $articleIndex;

                    // Verify position matches
                    $prodPosition = $prodCTA['position_after_content_block'] ?? null;
                    $articlePosition = $articleCTAPositions[$articleIndex]['position_after_content_block'] ?? null;

                    if ($prodPosition !== null && $articlePosition !== null && $prodPosition !== $articlePosition) {
                        $positionMismatches[] = "CTA \"{$prodCTA['title']}\" is at position after content block #{$articlePosition} in article, but should be after content block #{$prodPosition} (as in production)";
                    }
                    break;
                }
            }

            if (!$found) {
                $missingCTAs[] = $prodCTA;
            }
        }

        if (!empty($missingCTAs)) {
            $this->errors[] = "Missing CTAs (" . count($missingCTAs) . "):\n    " .
                implode("\n    ", array_map(function($cta) {
                    return "Title: \"{$cta['title']}\" | Button: \"{$cta['button']}\" | URL: {$cta['url']}";
                }, array_slice($missingCTAs, 0, 5)));
        }

        if (!empty($positionMismatches)) {
            $this->warnings[] = "CTA position mismatches:\n    " . implode("\n    ", $positionMismatches);
        }

        if (empty($missingCTAs) && empty($positionMismatches) && !empty($articleCTAs)) {
            $positionInfo = count($ctaPositions) >= 2 ? " (positions: #" . ($ctaPositions[0] + 1) . " and #" . ($ctaPositions[count($ctaPositions)-1] + 1) . ")" : "";
            echo "  ✓ Found " . count($articleCTAs) . " CTA(s) - all migrated and in correct positions{$positionInfo}\n";
        } elseif (empty($articleCTAs) && empty($productionCTAs)) {
            echo "  ✓ No CTAs found (none expected)\n";
        } else {
            $positionInfo = count($ctaPositions) >= 2 ? " (positions: #" . ($ctaPositions[0] + 1) . " and #" . ($ctaPositions[count($ctaPositions)-1] + 1) . ")" : "";
            echo "  ✓ Found " . count($articleCTAs) . " CTA(s) in article, " . count($productionCTAs) . " in production{$positionInfo}\n";
        }
    }

    // 7. Verify tables
    private function verifyTables(): void
    {
        echo "\n[8/13] Verifying tables...\n";

        $mainBlocks = $this->articleData['main_blocks'] ?? [];
        $tableBlocks = 0;

        foreach ($mainBlocks as $block) {
            if (($block['type'] ?? '') === 'info_table') {
                $tableBlocks++;
            }
        }

        // Check if production has tables that might need migration
        if ($this->productionHtml) {
            // Look for table-like structures in production
            $tablePatterns = ['<table', 'border=', 'cellpadding', 'cellspacing'];
            $hasTables = false;
            foreach ($tablePatterns as $pattern) {
                if (stripos($this->productionHtml, $pattern) !== false) {
                    $hasTables = true;
                    break;
                }
            }

            if ($hasTables && $tableBlocks === 0) {
                $this->warnings[] = "Production page may contain tables that need to be migrated as info_table blocks";
            }
        }

        if ($tableBlocks > 0) {
            echo "  ✓ Found {$tableBlocks} table(s) migrated as info_table blocks\n";
        } else {
            echo "  ✓ No tables found (none expected)\n";
        }
    }

    // 8. Verify routing
    private function verifyRouting(): void
    {
        echo "\n[9/13] Verifying routing...\n";

        $slug = $this->articleData['slug_category'] ?? '';
        $title = $this->articleData['title'] ?? '';

        if (!$slug) {
            $this->errors[] = "slug_category is missing";
            return;
        }

        // Extract slug from filename
        $filename = basename($this->articleFile);
        preg_match('/\d{4}-\d{2}-\d{2}\.(.+)\.md/', $filename, $matches);
        $articleSlug = $matches[1] ?? '';

        if (!$articleSlug) {
            $this->warnings[] = "Could not extract slug from filename";
            return;
        }

        $expectedRoute = "/articles/{$slug}/{$articleSlug}";
        $expectedRedirect = "/articles/{$articleSlug}";

        // Check released-articles.php
        $releasedArticlesFile = __DIR__ . '/../app/Routing/migration/released-articles.php';
        if (file_exists($releasedArticlesFile)) {
            $releasedContent = file_get_contents($releasedArticlesFile);
            if (strpos($releasedContent, $expectedRoute) === false) {
                $this->errors[] = "Route not found in released-articles.php: {$expectedRoute}";
            } else {
                echo "  ✓ Route found in released-articles.php\n";
            }
        }

        // Check redirects.php
        $redirectsFile = __DIR__ . '/../app/Routing/redirects.php';
        if (file_exists($redirectsFile)) {
            $redirectsContent = file_get_contents($redirectsFile);
            if (strpos($redirectsContent, $expectedRedirect) === false) {
                $this->warnings[] = "Redirect may be missing in redirects.php: {$expectedRedirect} => {$expectedRoute}";
            } else {
                echo "  ✓ Redirect found in redirects.php\n";
            }
        }
    }

    // 9. Verify quotes (double quotes rule)
    private function verifyQuotes(): void
    {
        echo "\n[10/13] Verifying YAML quotes...\n";

        $yamlContent = $this->articleData['_yaml'] ?? '';

        // Check for single quotes in string values (should use double quotes)
        // Look for patterns like: text: 'something'
        if (preg_match_all("/^\s+(\w+):\s+'([^']+)'/m", $yamlContent, $matches)) {
            $singleQuoted = [];
            foreach ($matches[1] as $i => $key) {
                $value = $matches[2][$i];
                // Only warn if the value contains apostrophes (which would cause issues)
                if (strpos($value, "'") !== false || preg_match("/\b(you'll|won't|can't|don't|it's|that's|here's|what's|there's|let's|I'm|we're|they're|she's|he's)\b/i", $value)) {
                    $singleQuoted[] = "{$key}: '{$value}'";
                }
            }

            if (!empty($singleQuoted)) {
                $this->warnings[] = "Found single-quoted strings that may contain apostrophes:\n    " . implode("\n    ", array_slice($singleQuoted, 0, 5));
            }
        }

        echo "  ✓ Quote format check completed\n";
    }

    // 10. Verify block structure (type and enabled)
    private function verifyBlockStructure(): void
    {
        echo "\n[11/13] Verifying block structure...\n";

        $mainBlocks = $this->articleData['main_blocks'] ?? [];
        $missingFields = [];

        foreach ($mainBlocks as $index => $block) {
            if (!isset($block['type'])) {
                $missingFields[] = "Block #{$index} missing 'type' field";
            }
            if (!isset($block['enabled'])) {
                $missingFields[] = "Block #{$index} missing 'enabled' field";
            }
        }

        if (!empty($missingFields)) {
            $this->errors[] = "Block structure issues:\n    " . implode("\n    ", $missingFields);
        } else {
            echo "  ✓ All blocks have type and enabled fields\n";
        }
    }

    // 11. Verify rich_text blocks are combined
    private function verifyRichTextCombination(): void
    {
        echo "\n[12/13] Verifying rich_text block combination...\n";

        $mainBlocks = $this->articleData['main_blocks'] ?? [];
        $consecutiveRichText = [];
        $currentRichTextIndex = null;

        foreach ($mainBlocks as $index => $block) {
            $type = $block['type'] ?? '';

            if ($type === 'rich_text') {
                if ($currentRichTextIndex !== null && $currentRichTextIndex === $index - 1) {
                    $consecutiveRichText[] = "Blocks #{$currentRichTextIndex} and #{$index}";
                }
                $currentRichTextIndex = $index;
            } else {
                $currentRichTextIndex = null;
            }
        }

        if (!empty($consecutiveRichText)) {
            $this->warnings[] = "Consecutive rich_text blocks found (should be combined):\n    " . implode("\n    ", array_slice($consecutiveRichText, 0, 5));
        } else {
            echo "  ✓ No consecutive rich_text blocks found\n";
        }
    }

    // 12. Verify intro structure
    private function verifyIntroStructure(): void
    {
        echo "\n[13/14] Verifying intro structure...\n";

        $intro = $this->articleData['intro'] ?? [];

        if (empty($intro)) {
            $this->warnings[] = "intro is empty";
        } else {
            // Check if intro has more than one paragraph
            $paragraphCount = 0;
            foreach ($intro as $item) {
                if (($item['type'] ?? '') === 'paragraph') {
                    $paragraphCount++;
                }
            }

            if ($paragraphCount > 1) {
                $this->warnings[] = "intro contains {$paragraphCount} paragraphs (should only contain the first paragraph)";
            } else {
                echo "  ✓ Intro structure is correct\n";
            }
        }
    }

    // 13. Verify subtitle
    private function verifySubtitle(): void
    {
        echo "\n[14/14] Verifying subtitle...\n";

        $articleSubtitle = $this->articleData['subtitle'] ?? null;

        // Extract subtitle from production HTML
        $productionSubtitle = null;
        if ($this->productionHtml) {
            // Look for subtitle after the title - usually in a <p> tag with class "bOeUtvGC" or similar
            if (preg_match('/<h1[^>]*class="[^"]*mxOiBotP[^"]*"[^>]*>(.*?)<\/h1>.*?<p[^>]*class="[^"]*bOeUtvGC[^"]*"[^>]*>(.*?)<\/p>/is', $this->productionHtml, $matches)) {
                $productionSubtitle = trim(strip_tags($matches[2]));
            }
        }

        if ($productionSubtitle && !$articleSubtitle) {
            $this->errors[] = "Missing subtitle. Production has: \"{$productionSubtitle}\"";
        } elseif ($articleSubtitle && $productionSubtitle && $articleSubtitle !== $productionSubtitle) {
            $this->warnings[] = "Subtitle mismatch. Article: \"{$articleSubtitle}\" | Production: \"{$productionSubtitle}\"";
        } elseif ($articleSubtitle && $productionSubtitle) {
            echo "  ✓ Subtitle matches production: \"{$articleSubtitle}\"\n";
        } elseif (!$productionSubtitle && !$articleSubtitle) {
            echo "  ✓ No subtitle (none expected)\n";
        } elseif ($articleSubtitle && !$productionSubtitle) {
            $this->warnings[] = "Article has subtitle but production doesn't: \"{$articleSubtitle}\"";
        }
    }

    private function printResults(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "VERIFICATION RESULTS\n";
        echo str_repeat("=", 60) . "\n\n";

        if (empty($this->errors) && empty($this->warnings)) {
            echo "✅ ALL CHECKS PASSED!\n";
            echo "\nThe article migration appears to be complete and correct.\n";
        } else {
            if (!empty($this->errors)) {
                echo "❌ ERRORS (" . count($this->errors) . "):\n\n";
                foreach ($this->errors as $i => $error) {
                    echo "  " . ($i + 1) . ". {$error}\n\n";
                }
            }

            if (!empty($this->warnings)) {
                echo "⚠️  WARNINGS (" . count($this->warnings) . "):\n\n";
                foreach ($this->warnings as $i => $warning) {
                    echo "  " . ($i + 1) . ". {$warning}\n\n";
                }
            }
        }

        if (!empty($this->info)) {
            echo "ℹ️  INFO:\n\n";
            foreach ($this->info as $info) {
                echo "  - {$info}\n";
            }
        }

        echo "\n" . str_repeat("=", 60) . "\n";
    }
}

// Main execution
if ($argc < 3) {
    echo "Usage: php verify-migration.php [ARTICLE_FILE] [PRODUCTION_URL]\n";
    echo "Example: php verify-migration.php content/collections/articles/2024-11-19.similar-business-names-heres-what-to-do.md https://bizee.com/articles/similar-business-names-heres-what-to-do\n";
    exit(1);
}

$articleFile = $argv[1];
$productionUrl = $argv[2];

try {
    $verifier = new MigrationVerifier($articleFile, $productionUrl);
    $results = $verifier->verify();

    exit(empty($results['errors']) ? 0 : 1);
} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
    exit(1);
}
