<?php

/**
 * Helper to generate list blocks (bulletList) from HTML or text
 *
 * IMPORTANT: All lists (including numbered lists) should be migrated as bulletList
 * This is the project standard - numbered lists in HTML become bullet lists in Bard
 *
 * Usage:
 *   require_once 'articles-migration/list-helper.php';
 *   $list = generateBulletList($items);
 */

/**
 * Generates a bulletList block from an array of items
 *
 * @param array $items Array of strings (list items)
 * @return array bulletList block structure in YAML-ready format
 */
function generateBulletList(array $items): array
{
    $listItems = [];

    foreach ($items as $item) {
        $listItems[] = [
            'type' => 'listItem',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => trim($item)
                        ]
                    ]
                ]
            ]
        ];
    }

    return [
        'type' => 'bulletList',
        'content' => $listItems
    ];
}

/**
 * Detects if a list is numbered (contains numbers like "1.", "2.", etc.)
 * and converts it to bulletList format
 *
 * @param array $items Array of strings that may contain numbers
 * @return array bulletList block structure
 */
function generateListFromItems(array $items): array
{
    // Remove numbers from items if present (e.g., "1. Item" -> "Item")
    $cleanedItems = array_map(function($item) {
        // Remove leading numbers and dots (e.g., "1. ", "2. ", etc.)
        $item = preg_replace('/^\d+\.\s*/', '', trim($item));
        // Remove leading numbers with parentheses (e.g., "1) ", "2) ", etc.)
        $item = preg_replace('/^\d+\)\s*/', '', $item);
        return trim($item);
    }, $items);

    return generateBulletList($cleanedItems);
}

/**
 * Usage examples:
 *
 * // Simple list
 * $list = generateBulletList([
 *     'First item',
 *     'Second item',
 *     'Third item'
 * ]);
 *
 * // List with numbers (will be converted to bullets)
 * $numberedList = generateListFromItems([
 *     '1. First step',
 *     '2. Second step',
 *     '3. Third step'
 * ]);
 * // Result: bulletList with items "First step", "Second step", "Third step"
 */
