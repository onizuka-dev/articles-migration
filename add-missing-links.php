<?php

/**
 * Script to add missing links to migrated article
 * Extracts links from original HTML and adds them to the article in Bard format
 *
 * Usage: php add-missing-links.php [ORIGINAL_URL] [ARTICLE_FILE_PATH]
 */

require __DIR__ . '/../vendor/autoload.php';

class LinkExtractor
{
    private $articleUrl;
    private $articlePath;
    private $articleContent;
    private $htmlContent;
    private $linksMap = [];

    public function __construct($articleUrl, $articlePath)
    {
        $this->articleUrl = $articleUrl;
        $this->articlePath = $articlePath;
        $this->articleContent = file_get_contents($articlePath);
    }

    /**
     * Downloads HTML from the original article
     */
    public function downloadHtml(): bool
    {
        echo "Downloading HTML from: {$this->articleUrl}\n";

        $ch = curl_init($this->articleUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$html) {
            echo "✗ Error downloading HTML\n";
            return false;
        }

        $this->htmlContent = $html;
        echo "✓ HTML downloaded\n";
        return true;
    }

    /**
     * Extracts links from HTML and creates a text => URL map
     */
    public function extractLinks(): void
    {
        echo "\nExtracting links from HTML...\n";

        // Find all <a> links in main content
        // Search within article content (avoid nav, footer, etc.)
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>([^<]+)<\/a>/i', $this->htmlContent, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $url = trim($match[1]);
            $text = trim(strip_tags($match[2]));

            // Filter links that are not from content (nav, footer, etc.)
            if (strlen($text) < 3) continue;
            if (preg_match('/^(share|follow|read more|click here)$/i', $text)) continue;
            if (strpos($url, '#') === 0) continue; // Anchors

            // Normalize text (remove extra spaces, etc.)
            $text = preg_replace('/\s+/', ' ', $text);

            if (!empty($text) && !empty($url)) {
                // If text already exists, keep the first or most specific one
                if (!isset($this->linksMap[$text]) || strlen($url) > strlen($this->linksMap[$text])) {
                    $this->linksMap[$text] = $url;
                }
            }
        }

        echo "Found " . count($this->linksMap) . " unique links\n";
    }

    /**
     * Searches for specific texts that should have links
     */
    public function findMissingLinks(): array
    {
        $missingLinks = [];

        // Specific texts mentioned by the user
        $targetTexts = [
            'Limited Liability Company (LLC).' => null,
            'get your teenage business up and running' => null,
            'start an online business' => null,
            'come up with a killer name' => null,
        ];

        // Search for these texts in the links map
        foreach ($this->linksMap as $text => $url) {
            foreach ($targetTexts as $target => $foundUrl) {
                if ($foundUrl === null && (
                    stripos($text, $target) !== false ||
                    stripos($target, $text) !== false ||
                    $this->textsMatch($text, $target)
                )) {
                    $targetTexts[$target] = $url;
                    $missingLinks[$target] = $url;
                    break;
                }
            }
        }

        // Also search for variations
        foreach ($this->linksMap as $text => $url) {
            if (stripos($text, 'LLC') !== false && stripos($text, 'Limited Liability') !== false) {
                if (!isset($missingLinks['Limited Liability Company (LLC).'])) {
                    $missingLinks['Limited Liability Company (LLC).'] = $url;
                }
            }
            if (stripos($text, 'teenage business') !== false && stripos($text, 'running') !== false) {
                if (!isset($missingLinks['get your teenage business up and running'])) {
                    $missingLinks['get your teenage business up and running'] = $url;
                }
            }
            if (stripos($text, 'online business') !== false) {
                if (!isset($missingLinks['start an online business'])) {
                    $missingLinks['start an online business'] = $url;
                }
            }
            if (stripos($text, 'killer name') !== false || stripos($text, 'name for it') !== false) {
                if (!isset($missingLinks['come up with a killer name'])) {
                    $missingLinks['come up with a killer name'] = $url;
                }
            }
        }

        return $missingLinks;
    }

    /**
     * Checks if two texts are similar (for flexible matching)
     */
    private function textsMatch($text1, $text2): bool
    {
        // Normalize both texts
        $t1 = strtolower(preg_replace('/[^a-z0-9\s]/i', '', $text1));
        $t2 = strtolower(preg_replace('/[^a-z0-9\s]/i', '', $text2));

        // Check if one contains the other or if they share important keywords
        $words1 = explode(' ', $t1);
        $words2 = explode(' ', $t2);

        $commonWords = array_intersect($words1, $words2);
        $minWords = min(count($words1), count($words2));

        // If they share at least 60% of words
        return count($commonWords) >= ($minWords * 0.6);
    }

    /**
     * Adds missing links to the article
     */
    public function addLinksToArticle(array $missingLinks): bool
    {
        if (empty($missingLinks)) {
            echo "\n✓ No missing links found\n";
            return false;
        }

        echo "\nAdding missing links:\n";
        $updated = false;

        foreach ($missingLinks as $text => $url) {
            echo "  - \"{$text}\" => {$url}\n";

            // Search for text in the article
            $pattern = '/(' . preg_quote($text, '/') . ')/i';

            // Search in Bard content
            if (preg_match($pattern, $this->articleContent, $matches, PREG_OFFSET_CAPTURE)) {
                $pos = $matches[1][1];
                $matchedText = $matches[1][0];

                // Check if it already has a link
                $context = substr($this->articleContent, max(0, $pos - 200), 400);
                if (strpos($context, 'type: link') !== false && strpos($context, $matchedText) !== false) {
                    echo "    ℹ Already has link, skipping\n";
                    continue;
                }

                // Find the text block containing this text
                // We need to find the type: text block that contains this text
                $beforePos = strrpos(substr($this->articleContent, 0, $pos), 'type: text');
                $afterPos = strpos($this->articleContent, "\n", $pos);

                if ($beforePos !== false) {
                    // Encontrar el inicio del bloque text
                    $textBlockStart = strrpos(substr($this->articleContent, 0, $beforePos), '          -');
                    if ($textBlockStart === false) {
                        $textBlockStart = strrpos(substr($this->articleContent, 0, $beforePos), '        -');
                    }

                    if ($textBlockStart !== false) {
                        // Extract the complete block
                        $blockEnd = strpos($this->articleContent, "\n      -", $textBlockStart);
                        if ($blockEnd === false) {
                            $blockEnd = strpos($this->articleContent, "\n    -", $textBlockStart);
                        }

                        if ($blockEnd !== false) {
                            $block = substr($this->articleContent, $textBlockStart, $blockEnd - $textBlockStart);

                            // Split text into parts: before link, link, after link
                            $textBefore = substr($matchedText, 0, strpos($matchedText, $text));
                            $textAfter = substr($matchedText, strlen($text));

                            // Create block with link in Bard format
                            $newBlock = $this->createLinkedTextBlock($textBefore, $text, $url, $textAfter);

                            if ($newBlock) {
                                $this->articleContent = substr_replace(
                                    $this->articleContent,
                                    $newBlock,
                                    $textBlockStart,
                                    $blockEnd - $textBlockStart
                                );
                                $updated = true;
                                echo "    ✓ Link added\n";
                            }
                        }
                    }
                }
            } else {
                echo "    ✗ Text not found in article\n";
            }
        }

        return $updated;
    }

    /**
     * Creates a text block with link in Bard format
     */
    private function createLinkedTextBlock($before, $linkText, $url, $after): string
    {
        $parts = [];

        if (!empty(trim($before))) {
            $parts[] = "            type: text\n            text: " . $this->yamlEscape(trim($before));
        }

        $parts[] = "          -\n            type: text\n            marks:\n              -\n                type: link\n                attrs:\n                  href: '{$url}'\n                  rel: null\n                  target: null\n                  title: null\n            text: " . $this->yamlEscape($linkText);

        if (!empty(trim($after))) {
            $parts[] = "          -\n            type: text\n            text: " . $this->yamlEscape(trim($after));
        }

        return "          -\n" . implode("\n", $parts);
    }

    /**
     * Escapes text for YAML
     */
    private function yamlEscape($text): string
    {
        if (strpos($text, "'") !== false || strpos($text, ':') !== false) {
            return "'" . str_replace("'", "''", $text) . "'";
        }
        return $text;
    }

    /**
     * Saves the updated article
     */
    public function save(): bool
    {
        return file_put_contents($this->articlePath, $this->articleContent) !== false;
    }

    /**
     * Runs the complete process
     */
    public function run(): void
    {
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║     Add Missing Links                                      ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n\n";

        if (!$this->downloadHtml()) {
            return;
        }

        $this->extractLinks();
        $missingLinks = $this->findMissingLinks();

        if (!empty($missingLinks)) {
            echo "\nMissing links found:\n";
            foreach ($missingLinks as $text => $url) {
                echo "  - \"{$text}\" => {$url}\n";
            }
        }

        if ($this->addLinksToArticle($missingLinks)) {
            if ($this->save()) {
                echo "\n✓ Article updated successfully\n";
            } else {
                echo "\n✗ Error saving article\n";
            }
        }
    }
}

// Execute
if ($argc < 3) {
    echo "Usage: php add-missing-links.php [ORIGINAL_URL] [ARTICLE_FILE_PATH]\n\n";
    echo "Example:\n";
    echo "php add-missing-links.php \\\n";
    echo "  https://bizee.com/articles/can-a-minor-own-a-business \\\n";
    echo "  ../content/collections/articles/2024-11-21.can-a-minor-own-a-business.md\n";
    exit(1);
}

$articleUrl = $argv[1];
$articlePath = $argv[2];

if (!file_exists($articlePath)) {
    echo "✗ Error: File does not exist: {$articlePath}\n";
    exit(1);
}

try {
    $extractor = new LinkExtractor($articleUrl, $articlePath);
    $extractor->run();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
