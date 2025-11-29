<?php

/**
 * Helper functions for formatting article content according to migration rules.
 *
 * This helper ensures:
 * 1. Proper quote usage (double quotes for text with apostrophes)
 * 2. Proper link formatting in Bard format
 */

/**
 * Escapes and formats text for YAML output.
 * Uses double quotes if text contains apostrophes, single quotes otherwise.
 * Properly escapes double quotes within double-quoted strings.
 *
 * @param string $text The text to format
 * @return string Formatted text ready for YAML output
 */
function formatTextForYaml(string $text): string
{
    // Check if text contains apostrophes (contractions)
    $hasApostrophes = preg_match("/['']/", $text);

    // Check if text contains double quotes
    $hasDoubleQuotes = strpos($text, '"') !== false;

    if ($hasApostrophes) {
        // Use double quotes and escape any existing double quotes
        return '"' . str_replace('"', '\\"', $text) . '"';
    }

    // If text has double quotes but no apostrophes, prefer single quotes
    // to avoid needing to escape the double quotes
    if ($hasDoubleQuotes) {
        // Use single quotes and escape any existing single quotes
        return "'" . str_replace("'", "''", $text) . "'";
    }

    // Use single quotes and escape any existing single quotes
    return "'" . str_replace("'", "''", $text) . "'";
}

/**
 * Generates a text node for Bard format.
 * Automatically uses correct quotes based on content.
 *
 * @param string $text The text content
 * @return array Text node structure
 */
function generateTextNode(string $text): array
{
    return [
        'type' => 'text',
        'text' => $text  // The YAML serializer will handle quotes
    ];
}

/**
 * Generates a link node in Bard format.
 *
 * @param string $href URL of the link
 * @param string $linkText Text to display for the link
 * @param string|null $rel Rel attribute (null for internal links, 'noopener noreferrer' for external)
 * @param string|null $target Target attribute (null for same tab, '_blank' for new tab)
 * @param string|null $title Title attribute (usually null)
 * @return array Link node structure in Bard format
 */
function generateLinkNode(
    string $href,
    string $linkText,
    ?string $rel = null,
    ?string $target = null,
    ?string $title = null
): array {
    // Determine if it's an external link
    $isExternal = !str_starts_with($href, 'https://bizee.com') &&
                  !str_starts_with($href, '/') &&
                  !str_starts_with($href, 'http://bizee.com');

    // Set defaults for external links
    if ($isExternal && $rel === null) {
        $rel = 'noopener noreferrer';
    }
    if ($isExternal && $target === null) {
        $target = '_blank';
    }

    return [
        'type' => 'text',
        'marks' => [
            [
                'type' => 'link',
                'attrs' => [
                    'href' => $href,
                    'rel' => $rel,
                    'target' => $target,
                    'title' => $title
                ]
            ]
        ],
        'text' => $linkText
    ];
}

/**
 * Splits text around links and generates proper Bard format nodes.
 *
 * This function takes a paragraph text and an array of links (with their positions or text to match),
 * and generates the proper Bard format with text nodes and link nodes.
 *
 * @param string $text The full paragraph text
 * @param array $links Array of links, each with:
 *   - 'text': The text that should be linked (must appear in $text)
 *   - 'href': The URL
 *   - 'rel': Optional rel attribute
 *   - 'target': Optional target attribute
 *   - 'title': Optional title attribute
 * @return array Array of content nodes (text and link nodes)
 */
function generateParagraphWithLinks(string $text, array $links = []): array
{
    if (empty($links)) {
        // No links, just return a simple text node
        return [generateTextNode($text)];
    }

    $content = [];
    $currentPosition = 0;
    $textLength = strlen($text);

    // Sort links by position in text (if we can determine it)
    // For now, we'll match by text content
    $matchedLinks = [];
    foreach ($links as $link) {
        $linkText = $link['text'];
        $position = strpos($text, $linkText, $currentPosition);
        if ($position !== false) {
            $matchedLinks[] = [
                'position' => $position,
                'length' => strlen($linkText),
                'link' => $link
            ];
        }
    }

    // Sort by position
    usort($matchedLinks, function($a, $b) {
        return $a['position'] - $b['position'];
    });

    // Build content nodes
    foreach ($matchedLinks as $matched) {
        $position = $matched['position'];
        $length = $matched['length'];
        $link = $matched['link'];

        // Add text before link
        if ($position > $currentPosition) {
            $beforeText = substr($text, $currentPosition, $position - $currentPosition);
            if (!empty(trim($beforeText))) {
                $content[] = generateTextNode($beforeText);
            }
        }

        // Add link node
        $content[] = generateLinkNode(
            $link['href'],
            $link['text'],
            $link['rel'] ?? null,
            $link['target'] ?? null,
            $link['title'] ?? null
        );

        $currentPosition = $position + $length;
    }

    // Add remaining text after last link
    if ($currentPosition < $textLength) {
        $afterText = substr($text, $currentPosition);
        if (!empty(trim($afterText))) {
            $content[] = generateTextNode($afterText);
        }
    }

    return $content;
}

/**
 * Formats a YAML value with proper quotes.
 * This is a wrapper for formatTextForYaml but can handle other types too.
 *
 * @param mixed $value The value to format
 * @return string Formatted YAML value
 */
function formatYamlValue($value): string
{
    if (is_string($value)) {
        return formatTextForYaml($value);
    }
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_null($value)) {
        return 'null';
    }
    if (is_numeric($value)) {
        return (string)$value;
    }

    return formatTextForYaml((string)$value);
}

/*
 * Usage Examples:
 *
 * // Text with apostrophes - will use double quotes
 * $text1 = formatTextForYaml("Here's what you'll get");
 * // Result: "Here's what you'll get"
 *
 * // Text without apostrophes - will use single quotes
 * $text2 = formatTextForYaml("Selecting your business entity type");
 * // Result: 'Selecting your business entity type'
 *
 * // Generate a link node
 * $linkNode = generateLinkNode(
 *     'https://bizee.com/blog/bizee-silver-package',
 *     'Basic Package'
 * );
 *
 * // Generate paragraph with links
 * $paragraphContent = generateParagraphWithLinks(
 *     'If you need minimal support, then the Basic Package may be a great place to start.',
 *     [
 *         [
 *             'text' => 'Basic Package',
 *             'href' => 'https://bizee.com/blog/bizee-silver-package'
 *         ]
 *     ]
 * );
 */
?>
