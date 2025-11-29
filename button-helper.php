<?php

/**
 * Helper to generate article_button blocks with the correct format
 *
 * This helper ensures that all buttons are generated:
 * - Without bold formatting
 * - Left-aligned
 * - With proper quote formatting (double quotes for text with apostrophes)
 *
 * IMPORTANT: This helper uses formatting rules from formatting-helper.php
 * See README-FORMATTING.md for complete formatting guidelines.
 *
 * Usage:
 *   require_once 'button-helper.php';
 *   require_once 'formatting-helper.php';
 *   $button = generateArticleButton($id, $label, $url, $openInNewTab);
 */

/**
 * Generates an article_button block with the correct format
 * (no bold and left-aligned)
 *
 * @param string $id Unique block ID (e.g., 'mcanminor1')
 * @param string|array $label Button text (simple string or array of lines)
 * @param string $url Destination URL
 * @param bool $openInNewTab Whether it should open in a new tab (default: false)
 * @return array article_button block structure in YAML-ready format
 */
function generateArticleButton($id, $label, $url, $openInNewTab = false): array
{
    // If label is a string, convert it to an array of lines
    if (is_string($label)) {
        $labelLines = explode("\n", $label);
    } else {
        $labelLines = $label;
    }

    // Build label content in Bard format
    $labelContent = [];
    $firstLine = true;

    foreach ($labelLines as $line) {
        $line = trim($line);
        if (empty($line)) {
            // If line is empty, add a hardBreak
            if (!$firstLine) {
                $labelContent[] = [
                    'type' => 'hardBreak'
                ];
            }
            continue;
        }

        if (!$firstLine) {
            // Add hardBreak between lines
            $labelContent[] = [
                'type' => 'hardBreak'
            ];
        }

        // Add text WITHOUT bold formatting (no marks)
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
                    'textAlign' => 'left'  // Left-aligned
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
 * Usage example:
 *
 * // Simple single-line button
 * $button1 = generateArticleButton(
 *     'mcanminor1',
 *     'Take a quiz now',
 *     'https://bizee.com/business-entity-quiz/explain'
 * );
 *
 * // Multi-line button
 * $button2 = generateArticleButton(
 *     'mcanminor10',
 *     "Form Your LLC \$0 + State Fee.\nIncludes Free Registered Agent Service for a Full Year.\n\nGet Started Today",
 *     'https://bizee.com/business-formation/start-an-llc'
 * );
 *
 * // Button that opens in new tab
 * $button3 = generateArticleButton(
 *     'external-link-1',
 *     'Visit External Site',
 *     'https://example.com',
 *     true
 * );
 */
