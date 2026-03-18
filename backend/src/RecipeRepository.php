<?php
/**
 * Recipe Repository
 * 
 * Handles all file operations for recipes (CRUD operations).
 * Manages Markdown files with YAML frontmatter.
 */

declare(strict_types=1);

namespace RecipeManager;

use DateTime;
use Exception;

class RecipeRepository
{
    private string $recipesDir;

    public function __construct()
    {
        $this->recipesDir = __DIR__ . '/../../recipes';
        
        // Ensure recipes directory exists
        if (!is_dir($this->recipesDir)) {
            mkdir($this->recipesDir, 0755, true);
        }
    }

    /**
     * List all recipes with their metadata
     */
    public function listAll(): array
    {
        $recipes = [];
        
        if (!is_dir($this->recipesDir)) {
            return $recipes;
        }
        
        $files = glob($this->recipesDir . '/*.md');
        
        foreach ($files as $file) {
            $recipe = $this->parseRecipe($file);
            if ($recipe) {
                $recipes[] = $recipe;
            }
        }
        
        // Sort by createdAt descending
        usort($recipes, fn($a, $b) => strtotime($b['createdAt']) - strtotime($a['createdAt']));
        
        return $recipes;
    }

    /**
     * Get a recipe by ID
     */
    public function getById(string $id): ?array
    {
        $file = $this->getFilePath($id);
        
        if (!file_exists($file)) {
            return null;
        }
        
        return $this->parseRecipe($file);
    }

    /**
     * Create a new recipe
     */
    public function create(array $data): array
    {
        $id = $data['id'] ?? $this->generateId($data['title'] ?? 'recipe');
        $data['id'] = $id;
        
        // Set timestamps
        $now = (new DateTime())->format('Y-m-d\TH:i:s\Z');
        $data['createdAt'] = $now;
        $data['updatedAt'] = $now;
        
        $file = $this->getFilePath($id);
        
        if (file_exists($file)) {
            throw new Exception("Recipe with ID '{$id}' already exists", 409);
        }
        
        $content = $this->buildMarkdownFile($data);
        
        if (file_put_contents($file, $content) === false) {
            throw new Exception("Failed to create recipe file", 500);
        }
        
        return $this->parseRecipe($file);
    }

    /**
     * Update an existing recipe
     */
    public function update(string $id, array $data): ?array
    {
        $file = $this->getFilePath($id);
        
        if (!file_exists($file)) {
            return null;
        }
        
        // Preserve original creation time
        $existing = $this->parseRecipe($file);
        $data['id'] = $id;
        $data['createdAt'] = $existing['createdAt'];
        $data['updatedAt'] = (new DateTime())->format('Y-m-d\TH:i:s\Z');
        
        $content = $this->buildMarkdownFile($data);
        
        if (file_put_contents($file, $content) === false) {
            throw new Exception("Failed to update recipe file", 500);
        }
        
        return $this->parseRecipe($file);
    }

    /**
     * Delete a recipe
     */
    public function delete(string $id): bool
    {
        $file = $this->getFilePath($id);
        
        if (!file_exists($file)) {
            return false;
        }
        
        return unlink($file);
    }

    /**
     * Parse a Markdown file into structured data
     */
    private function parseRecipe(string $filePath): ?array
    {
        $content = file_get_contents($filePath);
        
        if (!$content) {
            return null;
        }
        
        // Extract YAML frontmatter
        if (preg_match('/^---\n(.*?)\n---\n(.*)/s', $content, $matches)) {
            $frontmatter = $matches[1];
            $body = $matches[2];
            
            $metadata = $this->parseYaml($frontmatter);
            $metadata['body'] = trim($body);
            
            return $metadata;
        }
        
        return null;
    }

    /**
     * Build Markdown file content from recipe data
     */
    private function buildMarkdownFile(array $data): string
    {
        $frontmatter = $this->buildYamlFrontmatter($data);
        $body = $data['body'] ?? '';
        
        return "---\n{\$frontmatter}---\n\n{\$body}";
    }

    /**
     * Build YAML frontmatter from metadata
     */
    private function buildYamlFrontmatter(array $data): string
    {
        $lines = [];
        
        $yamlFields = ['id', 'title', 'duration', 'servings', 'category', 'difficulty', 'createdAt', 'updatedAt'];
        
        foreach ($yamlFields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];
                
                if (is_string($value)) {
                    $value = '"' . addslashes($value) . '"';
                } elseif (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                
                $lines[] = "{$field}: {$value}";
            }
        }
        
        // Handle tags array
        if (isset($data['tags']) && is_array($data['tags'])) {
            $lines[] = 'tags:';
            foreach ($data['tags'] as $tag) {
                $lines[] = "  - \"{$tag}\"";
            }
        }
        
        return implode("\n", $lines) . "\n";
    }

    /**
     * Parse simple YAML frontmatter
     */
    private function parseYaml(string $yaml): array
    {
        $data = [];
        $lines = explode("\n", trim($yaml));
        $currentArray = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }
            
            // Handle array items (tags)
            if (str_starts_with($line, '- ')) {
                if ($currentArray !== null) {
                    $value = trim(substr($line, 2), '"\' . '");
                    $data[$currentArray][] = $value;
                }
                continue;
            }
            
            // Handle key-value pairs
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Parse value type
                if ($value === 'true') {
                    $data[$key] = true;
                } elseif ($value === 'false') {
                    $data[$key] = false;
                } elseif (is_numeric($value)) {
                    $data[$key] = (int)$value;
                } elseif (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                    $data[$key] = substr($value, 1, -1);
                } else {
                    // Array declaration
                    $data[$key] = [];
                    $currentArray = $key;
                }
            }
        }
        
        return $data;
    }

    /**
     * Generate ID from title
     */
    private function generateId(string $title): string
    {
        $id = strtolower(trim($title));
        $id = preg_replace('/[^a-z0-9]+/', '-', $id);
        $id = trim($id, '-');
        $id .= '-' . substr(md5(time()), 0, 6);
        
        return $id;
    }

    /**
     * Get file path for recipe ID
     */
    private function getFilePath(string $id): string
    {
        return $this->recipesDir . '/' . basename($id) . '.md';
    }
}