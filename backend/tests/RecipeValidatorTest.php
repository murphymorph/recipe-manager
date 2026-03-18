<?php

declare(strict_types=1);

namespace App\Tests;

use App\RecipeValidator;
use PHPUnit\Framework\TestCase;

/**
 * RecipeValidatorTest - Unit tests for RecipeValidator class
 */
class RecipeValidatorTest extends TestCase
{
    private RecipeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new RecipeValidator();
    }

    /**
     * Test: Valid recipe data passes validation
     */
    public function testValidRecipeDataPasses(): void
    {
        $validData = [
            'title' => 'Chocolate Cake',
            'duration' => 90,
            'servings' => 8,
            'category' => 'Dessert',
            'ingredients' => ['flour', 'sugar', 'eggs'],
            'steps' => ['Mix ingredients', 'Bake at 175C'],
        ];

        $errors = $this->validator->validate($validData);
        $this->assertEmpty($errors, 'Valid data should produce no errors');
    }

    /**
     * Test: Missing required fields are detected
     */
    public function testMissingRequiredFieldsDetected(): void
    {
        $invalidData = [
            'title' => 'Chocolate Cake',
            'duration' => 90,
            // Missing: servings, category
        ];

        $errors = $this->validator->validate($invalidData);
        $this->assertNotEmpty($errors, 'Missing fields should produce errors');
        $this->assertCount(2, $errors);
    }

    /**
     * Test: Null data is detected
     */
    public function testNullDataDetected(): void
    {
        $errors = $this->validator->validate(null);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid JSON', $errors[0]);
    }

    /**
     * Test: Title validation - too short
     */
    public function testTitleTooShort(): void
    {
        $data = [
            'title' => 'Ab', // Only 2 characters
            'duration' => 90,
            'servings' => 8,
            'category' => 'Dessert',
        ];

        $errors = $this->validator->validate($data);
        $this->assertNotEmpty($errors);
        $this->assertTrue(
            $this->hasErrorMessage($errors, 'title', 'at least 3 characters'),
            'Should reject title shorter than 3 characters'
        );
    }

    /**
     * Test: Title validation - too long
     */
    public function testTitleTooLong(): void
    {
        $data = [
            'title' => str_repeat('a', 201), // 201 characters
            'duration' => 90,
            'servings' => 8,
            'category' => 'Dessert',
        ];

        $errors = $this->validator->validate($data);
        $this->assertNotEmpty($errors);
        $this->assertTrue(
            $this->hasErrorMessage($errors, 'title', 'not exceed 200 characters'),
            'Should reject title longer than 200 characters'
        );
    }

    /**
     * Test: Duration validation - invalid type
     */
    public function testDurationInvalidType(): void
    {
        $data = [
            'title' => 'Chocolate Cake',
            'duration' => 'ninety', // Invalid: string instead of number
            'servings' => 8,
            'category' => 'Dessert',
        ];

        $errors = $this->validator->validate($data);
        $this->assertNotEmpty($errors);
        $this->assertTrue(
            $this->hasErrorMessage($errors, 'duration', 'must be a number'),
            'Should reject non-numeric duration'
        );
    }

    /**
     * Test: Duration validation - too small
     */
    public function testDurationTooSmall(): void
    {
        $data = [
            'title' => 'Chocolate Cake',
            'duration' => 0,
            'servings' => 8,
            'category' => 'Dessert',
        ];

        $errors = $this->validator->validate($data);
        $this->assertNotEmpty($errors);
        $this->assertTrue(
            $this->hasErrorMessage($errors, 'duration', 'at least 1 minute'),
            'Should reject duration less than 1'
        );
    }

    /**
     * Test: Servings validation - invalid type
     */
    public function testServingsInvalidType(): void
    {
        $data = [
            'title' => 'Chocolate Cake',
            'duration' => 90,
            'servings' => 'eight', // Invalid: string instead of number
            'category' => 'Dessert',
        ];

        $errors = $this->validator->validate($data);
        $this->assertNotEmpty($errors);
        $this->assertTrue(
            $this->hasErrorMessage($errors, 'servings', 'must be a number'),
            'Should reject non-numeric servings'
        );
    }

    /**
     * Test: Category validation - invalid category
     */
    public function testInvalidCategory(): void
    {
        $data = [
            'title' => 'Chocolate Cake',
            'duration' => 90,
            'servings' => 8,
            'category' => 'InvalidCategory',
        ];

        $errors = $this->validator->validate($data);
        $this->assertNotEmpty($errors);
        $this->assertTrue(
            $this->hasErrorMessage($errors, 'category', 'must be one of'),
            'Should reject invalid category'
        );
    }

    /**
     * Test: Valid categories are accepted
     */
    public function testValidCategoriesAccepted(): void
    {
        $validCategories = RecipeValidator::getValidCategories();
        
        foreach ($validCategories as $category) {
            $data = [
                'title' => 'Test Recipe',
                'duration' => 60,
                'servings' => 4,
                'category' => $category,
            ];

            $errors = $this->validator->validate($data);
            $this->assertEmpty($errors, "Category '{$category}' should be valid");
        }
    }

    /**
     * Test: Ingredients validation - must be array
     */
    public function testIngredientsNotArray(): void
    {
        $data = [
            'title' => 'Chocolate Cake',
            'duration' => 90,
            'servings' => 8,
            'category' => 'Dessert',
            'ingredients' => 'flour, sugar, eggs', // Invalid: string instead of array
            'steps' => ['Mix', 'Bake'],
        ];

        $errors = $this->validator->validate($data);
        $this->assertTrue(
            $this->hasErrorMessage($errors, 'ingredients', 'must be an array'),
            'Should reject non-array ingredients'
        );
    }

    /**
     * Test: Steps validation - must be array
     */
    public function testStepsNotArray(): void
    {
        $data = [
            'title' => 'Chocolate Cake',
            'duration' => 90,
            'servings' => 8,
            'category' => 'Dessert',
            'ingredients' => ['flour', 'sugar'],
            'steps' => 'Mix then bake', // Invalid: string instead of array
        ];

        $errors = $this->validator->validate($data);
        $this->assertTrue(
            $this->hasErrorMessage($errors, 'steps', 'must be an array'),
            'Should reject non-array steps'
        );
    }

    /**
     * Helper method to check if error message contains specific text
     */
    private function hasErrorMessage(array $errors, string $field, string $substring): bool
    {
        foreach ($errors as $error) {
            if (strpos($error, $field) !== false && strpos($error, $substring) !== false) {
                return true;
            }
        }
        return false;
    }
}