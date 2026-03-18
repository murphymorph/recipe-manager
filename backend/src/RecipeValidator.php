<?php

declare(strict_types=1);

namespace App;

/**
 * RecipeValidator - Validates recipe data
 * 
 * Ensures all required fields are present and have valid values.
 */
class RecipeValidator
{
    private const REQUIRED_FIELDS = ['title', 'duration', 'servings', 'category'];
    private const VALID_CATEGORIES = ['Appetizer', 'Main Course', 'Dessert', 'Beverage', 'Breakfast', 'Snack', 'Sauce'];
    private const MIN_TITLE_LENGTH = 3;
    private const MAX_TITLE_LENGTH = 200;
    private const MIN_DURATION = 1;
    private const MIN_SERVINGS = 1;

    /**
     * Validate recipe data
     * 
     * @param array|null $data
     * @return array Array of error messages, empty if valid
     */
    public function validate(?array $data): array
    {
        $errors = [];

        if ($data === null) {
            return ['Invalid JSON provided'];
        }

        // Check required fields
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "Field '{$field}' is required";
            }
        }

        // Validate title
        if (isset($data['title'])) {
            $title = $data['title'];
            if (!is_string($title)) {
                $errors[] = "Field 'title' must be a string";
            } elseif (strlen($title) < self::MIN_TITLE_LENGTH) {
                $errors[] = "Field 'title' must be at least " . self::MIN_TITLE_LENGTH . " characters long";
            } elseif (strlen($title) > self::MAX_TITLE_LENGTH) {
                $errors[] = "Field 'title' must not exceed " . self::MAX_TITLE_LENGTH . " characters";
            }
        }

        // Validate duration
        if (isset($data['duration'])) {
            $duration = $data['duration'];
            if (!is_int($duration) && !is_numeric($duration)) {
                $errors[] = "Field 'duration' must be a number";
            } elseif ((int)$duration < self::MIN_DURATION) {
                $errors[] = "Field 'duration' must be at least " . self::MIN_DURATION . " minute";
            }
        }

        // Validate servings
        if (isset($data['servings'])) {
            $servings = $data['servings'];
            if (!is_int($servings) && !is_numeric($servings)) {
                $errors[] = "Field 'servings' must be a number";
            } elseif ((int)$servings < self::MIN_SERVINGS) {
                $errors[] = "Field 'servings' must be at least " . self::MIN_SERVINGS;
            }
        }

        // Validate category
        if (isset($data['category'])) {
            $category = $data['category'];
            if (!is_string($category)) {
                $errors[] = "Field 'category' must be a string";
            } elseif (!in_array($category, self::VALID_CATEGORIES, true)) {
                $errors[] = "Field 'category' must be one of: " . implode(', ', self::VALID_CATEGORIES);
            }
        }

        // Validate ingredients
        if (isset($data['ingredients'])) {
            if (!is_array($data['ingredients'])) {
                $errors[] = "Field 'ingredients' must be an array";
            } elseif (empty($data['ingredients'])) {
                $errors[] = "Field 'ingredients' must not be empty";
            }
        }

        // Validate steps
        if (isset($data['steps'])) {
            if (!is_array($data['steps'])) {
                $errors[] = "Field 'steps' must be an array";
            } elseif (empty($data['steps'])) {
                $errors[] = "Field 'steps' must not be empty";
            }
        }

        return $errors;
    }

    /**
     * Get list of valid categories
     * 
     * @return array
     */
    public static function getValidCategories(): array
    {
        return self::VALID_CATEGORIES;
    }
}