<?php
/**
 * Validate YAML syntax in a markdown file
 * Usage: php articles-migration/validate-yaml.php <file-path>
 * Example: php articles-migration/validate-yaml.php content/collections/articles/2022-05-04.my-article.md
 */

if ($argc < 2) {
    echo "Usage: php articles-migration/validate-yaml.php <file-path>\n";
    echo "Example: php articles-migration/validate-yaml.php content/collections/articles/2022-05-04.my-article.md\n";
    exit(1);
}

$filePath = $argv[1];

if (!file_exists($filePath)) {
    echo "✗ ERROR: File not found: " . $filePath . PHP_EOL;
    exit(1);
}

$content = file_get_contents($filePath);

// Extract YAML front matter (between --- markers)
if (preg_match('/^---\n(.*?)\n---/s', $content, $matches)) {
    $yaml = $matches[1];

    try {
        $parsed = yaml_parse($yaml);

        if ($parsed === false) {
            echo "✗ ERROR: Invalid YAML syntax in file: " . $filePath . PHP_EOL;
            exit(1);
        }

        echo "✓ YAML is valid: " . $filePath . PHP_EOL;

        // Check for common issues
        $issues = [];

        // Check if id exists and is unique format
        if (!isset($parsed['id'])) {
            $issues[] = "Missing 'id' field";
        }

        // Check if required fields exist
        $requiredFields = ['title', 'slug', 'article_category', 'article_author'];
        foreach ($requiredFields as $field) {
            if (!isset($parsed[$field])) {
                $issues[] = "Missing required field: '$field'";
            }
        }

        // Check hold and published status
        if (!isset($parsed['hold']) || $parsed['hold'] !== true) {
            $issues[] = "Warning: 'hold' should be true";
        }
        if (!isset($parsed['published']) || $parsed['published'] !== true) {
            $issues[] = "Warning: 'published' should be true";
        }

        if (count($issues) > 0) {
            echo "\nIssues found:\n";
            foreach ($issues as $issue) {
                echo "  - " . $issue . PHP_EOL;
            }
        } else {
            echo "  All required fields present.\n";
        }

        exit(0);

    } catch (Exception $e) {
        echo "✗ ERROR: YAML parsing failed: " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
} else {
    echo "✗ ERROR: Could not find YAML front matter in file: " . $filePath . PHP_EOL;
    exit(1);
}
