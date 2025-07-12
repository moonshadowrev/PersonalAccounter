<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/Category.php';

class CategoryController extends Controller {

    private $categoryModel;

    public function __construct($db) {
        $this->categoryModel = new Category($db);
    }

    private function checkAuthentication() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user']['id'])) {
            header('Location: /login');
            exit();
        }
    }

    public function index() {
        $this->checkAuthentication();
        
        // Show all categories in centralized system - no user filtering
        $categories = $this->categoryModel->getAllCategoriesWithExpenseCountAndUser();
        
        $this->view('dashboard/categories/index', [
            'categories' => $categories,
            'load_datatable' => true,
            'datatable_target' => '#categories-table'
        ]);
    }

    public function create() {
        $this->checkAuthentication();
        
        $this->view('dashboard/categories/create', [
            'defaultCategories' => Category::getDefaultCategories()
        ]);
    }

    public function store() {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /categories/create');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        
        // Validate input
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#3B82F6');
        $icon = trim($_POST['icon'] ?? '');
        
        if (empty($name)) {
            FlashMessage::error('Category name is required.');
            header('Location: /categories/create');
            exit();
        }
        
        // Check if category name already exists for user
        if ($this->categoryModel->nameExistsForUser($name, $userId)) {
            FlashMessage::error('A category with this name already exists.');
            header('Location: /categories/create');
            exit();
        }
        
        // Validate color format
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#3B82F6'; // Default color
        }
        
        $data = [
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'color' => $color,
            'icon' => $icon,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $categoryId = $this->categoryModel->create($data);
            
            if ($categoryId) {
                AppLogger::info('Category created', [
                    'user_id' => $userId,
                    'category_id' => $categoryId,
                    'name' => $name
                ]);
                FlashMessage::success('Category created successfully!');
                header('Location: /categories');
            } else {
                FlashMessage::error('Failed to create category. Please try again.');
                header('Location: /categories/create');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to create category', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to create category. Please try again.');
            header('Location: /categories/create');
        }
        
        exit();
    }

    public function edit($id) {
        $this->checkAuthentication();
        
        $category = $this->categoryModel->find($id);
        
        if (!$category) {
            FlashMessage::error('Category not found.');
            header('Location: /categories');
            exit();
        }
        
        $this->view('dashboard/categories/edit', [
            'category' => $category
        ]);
    }

    public function update($id) {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /categories/' . $id . '/edit');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        $category = $this->categoryModel->find($id);
        
        if (!$category) {
            FlashMessage::error('Category not found.');
            header('Location: /categories');
            exit();
        }
        
        // Validate input
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#3B82F6');
        $icon = trim($_POST['icon'] ?? '');
        
        if (empty($name)) {
            FlashMessage::error('Category name is required.');
            header('Location: /categories/' . $id . '/edit');
            exit();
        }
        
        // Check if category name already exists for user (excluding current category)
        if ($this->categoryModel->nameExistsForUser($name, $userId, $id)) {
            FlashMessage::error('A category with this name already exists.');
            header('Location: /categories/' . $id . '/edit');
            exit();
        }
        
        // Validate color format
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#3B82F6'; // Default color
        }
        
        $data = [
            'name' => $name,
            'description' => $description,
            'color' => $color,
            'icon' => $icon,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $result = $this->categoryModel->update($id, $data);
            
            if ($result) {
                AppLogger::info('Category updated', [
                    'user_id' => $userId,
                    'category_id' => $id,
                    'name' => $name
                ]);
                FlashMessage::success('Category updated successfully!');
            } else {
                FlashMessage::error('No changes were made.');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to update category', [
                'user_id' => $userId,
                'category_id' => $id,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to update category. Please try again.');
        }
        
        header('Location: /categories');
        exit();
    }

    public function delete($id) {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /categories');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        $category = $this->categoryModel->find($id);
        
        if (!$category) {
            FlashMessage::error('Category not found.');
            header('Location: /categories');
            exit();
        }
        
        try {
            // Check if category is used in expenses
            require_once __DIR__ . '/../Models/Expense.php';
            $expenseModel = new Expense($this->categoryModel->getDB());
            $expensesInCategory = $expenseModel->getByCategoryId($id);
            
            if (!empty($expensesInCategory)) {
                FlashMessage::error('Cannot delete category that is used in expenses. Please reassign or delete the expenses first.');
                header('Location: /categories');
                exit();
            }
            
            $result = $this->categoryModel->delete($id);
            
            if ($result) {
                AppLogger::info('Category deleted', [
                    'user_id' => $userId,
                    'category_id' => $id,
                    'name' => $category['name']
                ]);
                FlashMessage::success('Category deleted successfully!');
            } else {
                FlashMessage::error('Failed to delete category. Please try again.');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to delete category', [
                'user_id' => $userId,
                'category_id' => $id,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to delete category. Please try again.');
        }
        
        header('Location: /categories');
        exit();
    }

    /**
     * Create default categories for user (AJAX endpoint)
     */
    public function createDefaults() {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        
        try {
            $created = $this->categoryModel->createDefaultCategories($userId);
            
            if ($created > 0) {
                AppLogger::info('Default categories created', [
                    'user_id' => $userId,
                    'count' => $created
                ]);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true, 
                    'message' => "Created default 'General' category successfully!",
                    'count' => $created
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'No categories were created']);
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to create default categories', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to create default categories']);
        }
        
        exit();
    }

    /**
     * Get categories for AJAX requests (for expense forms)
     */
    public function ajaxList() {
        $this->checkAuthentication();
        
        // Return all categories for centralized system
        $categories = $this->categoryModel->getAllWithUserInfo();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
        exit();
    }

    /**
     * Quick create category (AJAX endpoint for expense forms)
     */
    public function quickCreate() {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#3B82F6');
        
        if (empty($name)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Category name is required']);
            exit();
        }
        
        // Check if category already exists
        if ($this->categoryModel->nameExistsForUser($name, $userId)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Category already exists']);
            exit();
        }
        
        $data = [
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'color' => $color,
            'icon' => 'fas fa-folder',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $categoryId = $this->categoryModel->create($data);
            
            if ($categoryId) {
                $category = $this->categoryModel->find($categoryId);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Category created successfully!',
                    'category' => $category
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to create category']);
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to create category']);
        }
        
        exit();
    }
} 