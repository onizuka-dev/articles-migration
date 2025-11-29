<?php

/**
 * Script to verify and fix migrated articles
 * - Verifies that buttons have the correct format (no bold, left-aligned)
 * - Verifies if images already exist in S3 before uploading
 * - Validates article category
 * - Adds new path to released-articles.php
 * - Adds redirects from old path to new in redirects.php
 *
 * IMPORTANT: Before running this script, ensure your article follows these structure rules:
 * - Only the first paragraph should be in `intro`
 * - All remaining content should be in `main_blocks`
 * - Consecutive `rich_text` blocks should be combined into one, unless separated by another component
 *
 * See README-STRUCTURE.md for complete structure guidelines.
 *
 * Usage: php verify-and-fix-article.php [ARTICLE_FILE_PATH] [CATEGORY_SLUG] [OLD_URL]
 * Example: php verify-and-fix-article.php ../content/collections/articles/2020-12-15.why-a-series-llc-is-the-best-for-your-real-estate-investment-business.md business-formation /articles/why-a-series-llc-is-the-best-for-your-real-estate-investment-business
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Storage;

// Load Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

class ArticleVerifier
{
    private $articlePath;
    private $articleContent;
    private $categorySlug;
    private $oldUrl;
    private $needsFix = false;
    private $baseDir;

    public function __construct($articlePath, $categorySlug, $oldUrl = null)
    {
        $this->articlePath = $articlePath;
        $this->categorySlug = $categorySlug;
        $this->oldUrl = $oldUrl;
        $this->articleContent = file_get_contents($articlePath);
        // Get project root directory (where vendor/ is)
        $this->baseDir = dirname(__DIR__);
    }

    /**
     * Validates that the category exists
     */
    public function validateCategory(): bool
    {
        $categoryPath = $this->baseDir . '/content/collections/categories/' . $this->categorySlug . '.md';

        if (!file_exists($categoryPath)) {
            echo "✗ Error: Category '{$this->categorySlug}' does not exist.\n";
            echo "  Searched in: {$categoryPath}\n";
            echo "\nAvailable categories:\n";
            $categoriesDir = $this->baseDir . '/content/collections/categories/';
            if (is_dir($categoriesDir)) {
                $categories = glob($categoriesDir . '*.md');
                foreach ($categories as $cat) {
                    echo "  - " . basename($cat, '.md') . "\n";
                }
            }
            return false;
        }

        echo "✓ Category '{$this->categorySlug}' validated\n";
        return true;
    }

    /**
     * Extracts article slug from file
     */
    private function getArticleSlug(): ?string
    {
        // Try to get from file content
        if (preg_match('/^slug:\s*(.+)$/m', $this->articleContent, $matches)) {
            return trim($matches[1]);
        }

        // If not in content, extract from filename
        $filename = basename($this->articlePath, '.md');
        if (preg_match('/^\d{4}-\d{2}-\d{2}\.(.+)$/', $filename, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Generates the new article path
     */
    private function getNewPath(): string
    {
        $slug = $this->getArticleSlug();
        if (!$slug) {
            throw new Exception("Could not determine article slug");
        }

        return "/articles/{$this->categorySlug}/{$slug}";
    }

    /**
     * Updates slug_category in article if necessary
     */
    private function updateArticleCategory(): void
    {
        $slug = $this->getArticleSlug();
        if (!$slug) {
            return;
        }

        // Check if slug_category exists and update it
        if (preg_match('/^slug_category:\s*(.+)$/m', $this->articleContent, $matches)) {
            $currentCategory = trim($matches[1]);
            if ($currentCategory !== $this->categorySlug) {
                echo "\nUpdating slug_category: {$currentCategory} → {$this->categorySlug}\n";
                $this->articleContent = preg_replace(
                    '/^slug_category:\s*.+$/m',
                    "slug_category: {$this->categorySlug}",
                    $this->articleContent
                );
                $this->needsFix = true;
            }
        } else {
            // Add slug_category if it doesn't exist
            echo "\nAdding slug_category: {$this->categorySlug}\n";
            // Find where to insert (after article_category or likes)
            if (preg_match('/^(likes:\s*\d+)$/m', $this->articleContent, $matches, PREG_OFFSET_CAPTURE)) {
                $pos = $matches[0][1] + strlen($matches[0][0]);
                $this->articleContent = substr_replace(
                    $this->articleContent,
                    "\nslug_category: {$this->categorySlug}",
                    $pos,
                    0
                );
                $this->needsFix = true;
            }
        }
    }

    /**
     * Adds published: false and hold: true flags
     */
    private function addPublicationFlags(): void
    {
        echo "\n=== Adding publication flags ===\n\n";

        $needsPublished = !preg_match('/^published:\s*/m', $this->articleContent);
        $needsHold = !preg_match('/^hold:\s*/m', $this->articleContent);

        if (!$needsPublished && !$needsHold) {
            // Verify they have correct values
            if (preg_match('/^published:\s*(.+)$/m', $this->articleContent, $matches)) {
                $currentPublished = trim(strtolower($matches[1]));
                if ($currentPublished === 'false') {
                    echo "✓ published: false already exists\n";
                } else {
                    echo "Updating published: {$currentPublished} → false\n";
                    $this->articleContent = preg_replace(
                        '/^published:\s*.+$/m',
                        'published: false',
                        $this->articleContent
                    );
                    $this->needsFix = true;
                }
            }

            if (preg_match('/^hold:\s*(.+)$/m', $this->articleContent, $matches)) {
                $currentHold = trim(strtolower($matches[1]));
                if ($currentHold === 'true') {
                    echo "✓ hold: true already exists\n";
                } else {
                    echo "Updating hold: {$currentHold} → true\n";
                    $this->articleContent = preg_replace(
                        '/^hold:\s*.+$/m',
                        'hold: true',
                        $this->articleContent
                    );
                    $this->needsFix = true;
                }
            }
            return;
        }

        // Find where to insert (after slug_category or likes)
        $insertAfter = null;
        $insertPos = 0;

        if (preg_match('/^(slug_category:\s*.+)$/m', $this->articleContent, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPos = $matches[0][1] + strlen($matches[0][0]);
            $insertAfter = 'slug_category';
        } elseif (preg_match('/^(likes:\s*\d+)$/m', $this->articleContent, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPos = $matches[0][1] + strlen($matches[0][0]);
            $insertAfter = 'likes';
        } elseif (preg_match('/^(is_featured_article:\s*(true|false))$/m', $this->articleContent, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPos = $matches[0][1] + strlen($matches[0][0]);
            $insertAfter = 'is_featured_article';
        }

        if ($insertPos > 0) {
            $flagsToAdd = [];
            if ($needsPublished) {
                $flagsToAdd[] = 'published: false';
                echo "\nAdding published: false\n";
            }
            if ($needsHold) {
                $flagsToAdd[] = 'hold: true';
                echo "\nAdding hold: true\n";
            }

            if (!empty($flagsToAdd)) {
                $insertion = "\n" . implode("\n", $flagsToAdd);
                $this->articleContent = substr_replace(
                    $this->articleContent,
                    $insertion,
                    $insertPos,
                    0
                );
                $this->needsFix = true;
            }
        }
    }

    /**
     * Verifies and fixes article buttons
     */
    public function verifyAndFixButtons(): bool
    {
        echo "=== Verifying buttons ===\n\n";

        $originalContent = $this->articleContent;
        $fixedContent = $this->articleContent;

        // Find all article_button blocks
        $pattern = '/(id: [^\n]+[\s\S]*?version: article_button_1[\s\S]*?type: article_button[\s\S]*?enabled: true)/';
        preg_match_all($pattern, $this->articleContent, $matches);

        if (empty($matches[0])) {
            echo "ℹ No buttons found in article.\n\n";
            return true;
        }

        echo "Found " . count($matches[0]) . " button(s)\n\n";

        foreach ($matches[0] as $index => $buttonBlock) {
            $needsFix = false;
            $fixedBlock = $buttonBlock;

            // Check if it has textAlign: center
            if (preg_match('/textAlign:\s*center/', $buttonBlock)) {
                echo "  Button " . ($index + 1) . ": Has textAlign: center, fixing...\n";
                $fixedBlock = preg_replace('/textAlign:\s*center/', 'textAlign: left', $fixedBlock);
                $needsFix = true;
            }

            // Check if it has bold (marks with type: bold)
            if (preg_match('/marks:\s*-\s*type:\s*bold/', $buttonBlock)) {
                echo "  Button " . ($index + 1) . ": Has bold, removing...\n";
                // Remove bold marks from texts within the button
                $fixedBlock = preg_replace('/marks:\s*-\s*type:\s*bold\s*/', '', $fixedBlock);
                // Clean empty mark lines
                $fixedBlock = preg_replace('/\s*-\s*type:\s*bold\s*\n/', '', $fixedBlock);
                $needsFix = true;
            }

            if ($needsFix) {
                $fixedContent = str_replace($buttonBlock, $fixedBlock, $fixedContent);
                $this->needsFix = true;
                echo "  ✓ Button " . ($index + 1) . " fixed\n";
            } else {
                echo "  ✓ Button " . ($index + 1) . " is already correct\n";
            }
        }

        if ($this->needsFix) {
            $this->articleContent = $fixedContent;
            echo "\n✓ Buttons fixed\n";
        } else {
            echo "\n✓ All buttons are already correct\n";
        }

        return true;
    }

    /**
     * Verifies if article images already exist in S3
     */
    public function verifyImages(): void
    {
        echo "\n=== Verifying images in S3 ===\n\n";

        // Extract featured_image
        if (preg_match('/featured_image:\s*(.+)/', $this->articleContent, $matches)) {
            $featuredImage = trim($matches[1]);
            echo "Imagen destacada: {$featuredImage}\n";

            if ($this->imageExistsInS3($featuredImage)) {
                echo "  ✓ Already exists in S3\n";
            } else {
                echo "  ✗ No existe en S3\n";
            }
        }

        // Search for images in article_image blocks
        $pattern = '/image:\s*(articles\/[^\n]+)/';
        preg_match_all($pattern, $this->articleContent, $imageMatches);

        if (!empty($imageMatches[1])) {
            foreach ($imageMatches[1] as $imagePath) {
                $imagePath = trim($imagePath);
                echo "\nImagen de contenido: {$imagePath}\n";

                if ($this->imageExistsInS3($imagePath)) {
                    echo "  ✓ Already exists in S3\n";
                } else {
                    echo "  ✗ No existe en S3\n";
                }
            }
        }
    }

    /**
     * Checks if an image exists in S3
     */
    private function imageExistsInS3($imagePath): bool
    {
        try {
            return Storage::disk('s3')->exists($imagePath);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Adds new path to released-articles.php
     */
    private function addToReleasedArticles(): bool
    {
        $newPath = $this->getNewPath();
        $releasedArticlesPath = $this->baseDir . '/app/Routing/migration/released-articles.php';

        if (!file_exists($releasedArticlesPath)) {
            echo "✗ Error: released-articles.php not found\n";
            return false;
        }

        $content = file_get_contents($releasedArticlesPath);

        // Check if it already exists
        if (strpos($content, "'{$newPath}'") !== false || strpos($content, "\"{$newPath}\"") !== false) {
            echo "ℹ Path already exists in released-articles.php: {$newPath}\n";
            return true;
        }

        // Find where to insert (after category comments, before authors)
        $insertPattern = "/(\/\/ end categories\s*\n)/";
        if (preg_match($insertPattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $pos = $matches[0][1] + strlen($matches[0][0]);
            $insertion = "    '{$newPath}',\n";
            $content = substr_replace($content, $insertion, $pos, 0);

            if (file_put_contents($releasedArticlesPath, $content)) {
                echo "✓ Added to released-articles.php: {$newPath}\n";
                return true;
            } else {
                echo "✗ Error writing to released-articles.php\n";
                return false;
            }
        } else {
            echo "✗ Could not find insertion point in released-articles.php\n";
            return false;
        }
    }

    /**
     * Adds redirect to redirects.php
     */
    private function addRedirect(): bool
    {
        if (!$this->oldUrl) {
            echo "ℹ No old URL provided, skipping redirect\n";
            return true;
        }

        $newPath = $this->getNewPath();
        $redirectsPath = $this->baseDir . '/app/Routing/redirects.php';

        if (!file_exists($redirectsPath)) {
            echo "✗ Error: redirects.php not found\n";
            return false;
        }

        $content = file_get_contents($redirectsPath);

        // Normalize URLs (ensure they start with /)
        $oldUrl = $this->oldUrl;
        if (substr($oldUrl, 0, 1) !== '/') {
            $oldUrl = '/' . $oldUrl;
        }

        // Check if redirect already exists
        $redirectPattern = preg_quote($oldUrl, '/');
        if (preg_match("/{$redirectPattern}\s*=>/", $content)) {
            echo "ℹ Redirect already exists: {$oldUrl} => {$newPath}\n";
            return true;
        }

        // Find where to insert (after existing article redirects)
        // Insert before array closing, after last article redirect
        // Find the last redirect starting with '/articles/'
        $lines = explode("\n", $content);
        $lastArticleRedirectLine = -1;

        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (preg_match("/^\s*'\/articles\//", $lines[$i])) {
                $lastArticleRedirectLine = $i;
                break;
            }
        }

        if ($lastArticleRedirectLine >= 0) {
            // Insert after last article redirect line
            $insertion = "    '{$oldUrl}' => '{$newPath}',\n";
            array_splice($lines, $lastArticleRedirectLine + 1, 0, $insertion);
            $content = implode("\n", $lines);

            if (file_put_contents($redirectsPath, $content)) {
                echo "✓ Added redirect: {$oldUrl} => {$newPath}\n";
                return true;
            } else {
                echo "✗ Error writing to redirects.php\n";
                return false;
            }
        } else {
            echo "✗ Could not find insertion point in redirects.php\n";
            return false;
        }
    }

    /**
     * Saves changes if there were corrections
     */
    public function saveIfNeeded(): bool
    {
        if ($this->needsFix) {
            echo "\n=== Guardando cambios ===\n";
            if (file_put_contents($this->articlePath, $this->articleContent)) {
                echo "✓ Archivo actualizado: {$this->articlePath}\n";
                return true;
            } else {
                echo "✗ Error saving file\n";
                return false;
            }
        }
        return true;
    }

    /**
     * Ejecuta todas las verificaciones
     */
    public function run(): void
    {
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║     Article Verification and Correction                    ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n\n";

        echo "File: {$this->articlePath}\n";
        echo "Category: {$this->categorySlug}\n";
        if ($this->oldUrl) {
            echo "Old URL: {$this->oldUrl}\n";
        }
        echo "\n";

        // Validate category first
        if (!$this->validateCategory()) {
            echo "\n❌ Process cancelled: invalid category\n";
            exit(1);
        }

        // Get article information
        $slug = $this->getArticleSlug();
        $newPath = $this->getNewPath();

        echo "\n=== Article information ===\n";
        echo "Slug: {$slug}\n";
        echo "New path: {$newPath}\n\n";

        // Verify and fix buttons
        $this->verifyAndFixButtons();

        // Verify images
        $this->verifyImages();

        // Update category in article
        $this->updateArticleCategory();

        // Add publication flags
        echo "\n=== Adding publication flags ===\n";
        $this->addPublicationFlags();

        // Save article changes
        $this->saveIfNeeded();

        // Add to released-articles.php
        echo "\n=== Updating released-articles.php ===\n";
        $this->addToReleasedArticles();

        // Add redirect
        echo "\n=== Updating redirects.php ===\n";
        $this->addRedirect();

        echo "\n✅ Process completed\n";
    }
}

// Execute script
if ($argc < 3) {
    echo "Usage: php verify-and-fix-article.php [ARTICLE_FILE_PATH] [CATEGORY_SLUG] [OLD_URL]\n\n";
    echo "Parameters:\n";
    echo "  ARTICLE_FILE_PATH: Path to article .md file\n";
    echo "  CATEGORY_SLUG: Category slug (must exist in content/collections/categories/)\n";
    echo "  OLD_URL: Previous article URL to create redirect (optional)\n\n";
    echo "Example:\n";
    echo "php verify-and-fix-article.php \\\n";
    echo "  ../content/collections/articles/2020-12-15.why-a-series-llc-is-the-best-for-your-real-estate-investment-business.md \\\n";
    echo "  business-formation \\\n";
    echo "  /articles/why-a-series-llc-is-the-best-for-your-real-estate-investment-business\n";
    exit(1);
}

$articlePath = $argv[1];
$categorySlug = $argv[2];
$oldUrl = $argv[3] ?? null;

if (!file_exists($articlePath)) {
    echo "✗ Error: File does not exist: {$articlePath}\n";
    exit(1);
}

try {
    $verifier = new ArticleVerifier($articlePath, $categorySlug, $oldUrl);
    $verifier->run();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
