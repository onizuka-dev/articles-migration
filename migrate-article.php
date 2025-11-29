<?php

/**
 * Article migration script
 *
 * This script helps convert HTML/plain text content to the Statamic article
 * structure used in this project.
 *
 * IMPORTANT FORMATTING RULES:
 * - Use double quotes for text containing apostrophes (contractions)
 * - Use single quotes for text without apostrophes
 * - All links in rich_text must use Bard format with marks and attrs
 * - Combine consecutive rich_text blocks into one (unless separated by another component)
 * - Ensure exactly 1 line break (hardBreak) between paragraphs, headings, and lists
 *
 * See README-FORMATTING.md and README-STRUCTURE.md for complete formatting guidelines.
 *
 * Usage: php migrate-article.php
 */

require_once __DIR__ . '/formatting-helper.php';

class ArticleMigrator
{
    private $articleData = [];

    public function __construct()
    {
        // Article data to migrate
        $this->articleData = [
            'title' => 'Can a Minor Own a Business?',
            'subtitle' => 'Challenges, and opportunities for young entrepreneurs',
            'slug' => 'can-a-minor-own-a-business',
            'date' => '2024-11-21',
            'author_id' => '64daab56-842c-4efb-8002-b496df244091', // Carrie Buchholz-Powers
            'category_id' => 'aaed7ffe-ddd1-4a17-a623-dee2dbea7750', // Legal and Compliance
            'slug_category' => 'legal-and-compliance',
            'featured_image' => 'articles/featured/can-a-minor-own-a-business.png', // You'll need to create this image
        ];
    }

    /**
     * Generates a UUID v4
     */
    private function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Converts simple HTML text to Statamic Bard format
     */
    public function convertToBard($html): array
    {
        // This is a simplified version
        // In production, you would need a more robust HTML parser
        $lines = explode("\n", strip_tags($html));
        $bard = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $bard[] = [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $line
                    ]
                ]
            ];
        }

        return $bard;
    }

    /**
     * Generates an article_button block with the correct format
     * (no bold and left-aligned)
     *
     * @param string $id Unique block ID
     * @param string|array $label Button text (simple string or array of lines)
     * @param string $url Destination URL
     * @param bool $openInNewTab Whether it should open in a new tab
     * @return array article_button block structure
     */
    public function generateArticleButton($id, $label, $url, $openInNewTab = false): array
    {
        // If label is a string, convert it to an array of lines
        if (is_string($label)) {
            $label = explode("\n", $label);
        }

        // Build label content in Bard format
        $labelContent = [];
        $firstLine = true;

        foreach ($label as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if (!$firstLine) {
                // Add hardBreak between lines
                $labelContent[] = [
                    'type' => 'hardBreak'
                ];
            }

            // Add text without bold
            $labelContent[] = [
                'type' => 'text',
                'text' => $line
            ];

            $firstLine = false;
        }

        return [
            'id' => $id,
            'version' => 'article_button_1',
            'label' => [
                [
                    'type' => 'paragraph',
                    'attrs' => [
                        'textAlign' => 'left'
                    ],
                    'content' => $labelContent
                ]
            ],
            'url' => $url,
            'open_in_new_tab' => $openInNewTab,
            'type' => 'article_button',
            'enabled' => true
        ];
    }

    /**
     * Generates article content in YAML format
     */
    public function generateArticleContent(): string
    {
        $id = $this->generateUUID();
        $date = $this->articleData['date'];
        $slug = $this->articleData['slug'];

        $content = <<<YAML
---
id: {$id}
blueprint: article
title: '{$this->articleData['title']}'
subtitle: '{$this->articleData['subtitle']}'
featured_image: {$this->articleData['featured_image']}
note_under_image:
  -
    type: paragraph
    content:
      -
        type: text
        marks:
          -
            type: bold
        text: 'Please note: '
      -
        type: text
        text: 'This post contains affiliate links and we may receive a commission if you make a purchase using these links.'
article_author:
  - {$this->articleData['author_id']}
article_category: {$this->articleData['category_id']}
is_featured_article: false
likes: 0
slug_category: {$this->articleData['slug_category']}
YAML;

        return $content;
    }

    /**
     * Shows information about the migration process
     */
    public function showMigrationInfo(): void
    {
        echo "=== Migration Information ===\n\n";
        echo "Artículo: {$this->articleData['title']}\n";
        echo "Fecha: {$this->articleData['date']}\n";
        echo "Slug: {$this->articleData['slug']}\n";
        echo "Categoría: {$this->articleData['slug_category']}\n\n";
        echo "File will be generated as: content/collections/articles/{$this->articleData['date']}.{$this->articleData['slug']}.md\n\n";
    }

    /**
     * Example usage of generateArticleButton
     */
    public function showButtonExample(): void
    {
        echo "=== Ejemplo de Botón ===\n\n";

        $button = $this->generateArticleButton(
            'example-button-1',
            "Take a quiz to decide: LLC, S Corp, C Corp, or Nonprofit?\n\nTake a quiz now",
            'https://bizee.com/business-entity-quiz/explain',
            false
        );

        echo "Estructura del botón generado:\n";
        echo json_encode($button, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo "\n\n";
        echo "Note: Use this structure when creating article_button blocks manually.\n";
        echo "Ver articles-migration/README-BUTTONS.md para más detalles.\n";
    }

    /**
     * Combines consecutive rich_text blocks into a single block.
     *
     * ⚠️ CRITICAL RULE: Consecutive rich_text blocks must be combined unless
     * separated by another component (button, image, etc.).
     *
     * @param array $mainBlocks Array of main_blocks from the article
     * @return array Array with consecutive rich_text blocks combined
     */
    public function combineConsecutiveRichTextBlocks(array $mainBlocks): array
    {
        $combined = [];
        $currentRichText = null;
        $currentRichTextId = null;

        foreach ($mainBlocks as $block) {
            // Check if this is a rich_text block
            if (isset($block['type']) && $block['type'] === 'rich_text') {
                if ($currentRichText === null) {
                    // Start a new rich_text block
                    $currentRichText = $block;
                    $currentRichTextId = $block['id'] ?? null;
                } else {
                    // Combine with current rich_text block
                    // Add a hardBreak between blocks if needed
                    if (!empty($currentRichText['content'])) {
                        $lastItem = end($currentRichText['content']);
                        // Only add hardBreak if last item is not already a hardBreak
                        if (!isset($lastItem['type']) || $lastItem['type'] !== 'hardBreak') {
                            $currentRichText['content'][] = [
                                'type' => 'paragraph',
                                'content' => [
                                    ['type' => 'hardBreak']
                                ]
                            ];
                        }
                    }
                    // Merge content from this block into current
                    if (isset($block['content']) && is_array($block['content'])) {
                        $currentRichText['content'] = array_merge(
                            $currentRichText['content'],
                            $block['content']
                        );
                    }
                }
            } else {
                // This is not a rich_text block
                // If we have a pending rich_text block, add it first
                if ($currentRichText !== null) {
                    $combined[] = $currentRichText;
                    $currentRichText = null;
                    $currentRichTextId = null;
                }
                // Add the non-rich_text block
                $combined[] = $block;
            }
        }

        // Don't forget the last rich_text block if it exists
        if ($currentRichText !== null) {
            $combined[] = $currentRichText;
        }

        return $combined;
    }

    /**
     * Ensures proper line breaks between paragraphs, headings, and lists in Bard content.
     *
     * Rule: There must be exactly 1 line break (hardBreak) between:
     * - Headings and paragraphs
     * - Paragraphs and paragraphs
     * - Paragraphs and lists
     * - Lists and paragraphs
     *
     * @param array $content Array of Bard content nodes
     * @return array Array with proper line breaks added
     */
    public function ensureProperLineBreaks(array $content): array
    {
        if (empty($content)) {
            return $content;
        }

        $result = [];
        $prevType = null;

        foreach ($content as $index => $node) {
            $currentType = $node['type'] ?? null;

            // Add hardBreak before certain elements if needed
            if ($prevType !== null) {
                $needsBreakBefore = false;

                // Add break before paragraph if previous was heading or list
                if ($currentType === 'paragraph' && ($prevType === 'heading' || $prevType === 'bulletList' || $prevType === 'orderedList')) {
                    $needsBreakBefore = true;
                }

                // Add break before list if previous was paragraph or heading
                if (($currentType === 'bulletList' || $currentType === 'orderedList') && ($prevType === 'paragraph' || $prevType === 'heading')) {
                    $needsBreakBefore = true;
                }

                // Add break before heading if previous was list
                if ($currentType === 'heading' && ($prevType === 'bulletList' || $prevType === 'orderedList')) {
                    $needsBreakBefore = true;
                }

                if ($needsBreakBefore) {
                    // Check if last item is already a hardBreak
                    $lastItem = end($result);
                    if (!isset($lastItem['type']) || $lastItem['type'] !== 'hardBreak') {
                        $result[] = [
                            'type' => 'paragraph',
                            'content' => [
                                ['type' => 'hardBreak']
                            ]
                        ];
                    }
                }
            }

            $result[] = $node;
            $prevType = $currentType;
        }

        return $result;
    }

    /**
     * Processes main_blocks to apply all structure rules:
     * - Combines consecutive rich_text blocks
     * - Ensures proper line breaks
     *
     * @param array $mainBlocks Array of main_blocks
     * @return array Processed main_blocks with rules applied
     */
    public function processMainBlocks(array $mainBlocks): array
    {
        // First, combine consecutive rich_text blocks
        $combined = $this->combineConsecutiveRichTextBlocks($mainBlocks);

        // Then, ensure proper line breaks in each rich_text block
        foreach ($combined as &$block) {
            if (isset($block['type']) && $block['type'] === 'rich_text' && isset($block['content'])) {
                $block['content'] = $this->ensureProperLineBreaks($block['content']);
            }
        }

        return $combined;
    }
}

// Execute migrator
$migrator = new ArticleMigrator();
$migrator->showMigrationInfo();

echo "Note: This script is a base template.\n";
echo "The complete article content must be created manually\n";
echo "following the structure of existing articles.\n";
