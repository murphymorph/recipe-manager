<?php
/**
 * Recipe Manager API - Main Entry Point
 * 
 * This is the main router for all API requests.
 * Handles CRUD operations for recipes stored as Markdown files.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\ErrorHandler;
use App\RecipeRepository;
use App\RecipeValidator;

// Set error handling
ErrorHandler::setup();

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize repository and validator
$recipesDir = __DIR__ . '/../recipes';
$repository = new RecipeRepository($recipesDir);
$validator = new RecipeValidator();

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path); // Remove /api prefix if exists

// Simple routing
try {
    // GET /recipes - List all recipes
    if ($method === 'GET' && preg_match('#^/recipes/?$#', $path)) {
        $recipes = $repository->getAllRecipes();
        echo json_encode(['success' => true, 'data' => $recipes]);
    }
    // GET /recipes/{id} - Get single recipe
    elseif ($method === 'GET' && preg_match('#^/recipes/([a-zA-Z0-9_-]+)/?$#', $path, $matches)) {
        $id = $matches[1];
        $recipe = $repository->getRecipeById($id);
        echo json_encode(['success' => true, 'data' => $recipe]);
    }
    // POST /recipes - Create recipe
    elseif ($method === 'POST' && preg_match('#^/recipes/?$#', $path)) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        $errors = $validator->validate($input);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
        
        $recipe = $repository->createRecipe($input);
        http_response_code(201);
        echo json_encode(['success' => true, 'data' => $recipe]);
    }
    // PUT /recipes/{id} - Update recipe
    elseif ($method === 'PUT' && preg_match('#^/recipes/([a-zA-Z0-9_-]+)/?$#', $path, $matches)) {
        $id = $matches[1];
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        $errors = $validator->validate($input);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
        
        $recipe = $repository->updateRecipe($id, $input);
        echo json_encode(['success' => true, 'data' => $recipe]);
    }
    // DELETE /recipes/{id} - Delete recipe
    elseif ($method === 'DELETE' && preg_match('#^/recipes/([a-zA-Z0-9_-]+)/?$#', $path, $matches)) {
        $id = $matches[1];
        $repository->deleteRecipe($id);
        echo json_encode(['success' => true, 'message' => 'Recipe deleted']);
    }
    // 404 - Route not found
    else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Route not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}