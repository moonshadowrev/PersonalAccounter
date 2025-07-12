<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/Tag.php';

class TagController extends Controller {

    private $tagModel;

    public function __construct($db) {
        $this->tagModel = new Tag($db);
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
        
        // Show all tags in centralized system - no user filtering
        $tags = $this->tagModel->getAllTagsWithExpenseCountAndUser();
        
        $this->view('dashboard/tags/index', [
            'tags' => $tags,
            'load_datatable' => true,
            'datatable_target' => '#tags-table'
        ]);
    }

    public function create() {
        $this->checkAuthentication();
        
        $this->view('dashboard/tags/create', [
            'defaultTags' => Tag::getDefaultTags()
        ]);
    }

    public function store() {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /tags/create');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        
        // Validate input
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#10B981');
        
        if (empty($name)) {
            FlashMessage::error('Tag name is required.');
            header('Location: /tags/create');
            exit();
        }
        
        // Check if tag name already exists for user
        if ($this->tagModel->nameExistsForUser($name, $userId)) {
            FlashMessage::error('A tag with this name already exists.');
            header('Location: /tags/create');
            exit();
        }
        
        // Validate color format
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#10B981'; // Default color
        }
        
        $data = [
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'color' => $color,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $tagId = $this->tagModel->create($data);
            
            if ($tagId) {
                AppLogger::info('Tag created', [
                    'user_id' => $userId,
                    'tag_id' => $tagId,
                    'name' => $name
                ]);
                FlashMessage::success('Tag created successfully!');
                header('Location: /tags');
            } else {
                FlashMessage::error('Failed to create tag. Please try again.');
                header('Location: /tags/create');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to create tag', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to create tag. Please try again.');
            header('Location: /tags/create');
        }
        
        exit();
    }

    public function edit($id) {
        $this->checkAuthentication();
        
        $tag = $this->tagModel->find($id);
        
        if (!$tag) {
            FlashMessage::error('Tag not found.');
            header('Location: /tags');
            exit();
        }
        
        $this->view('dashboard/tags/edit', [
            'tag' => $tag
        ]);
    }

    public function update($id) {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /tags/' . $id . '/edit');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        $tag = $this->tagModel->find($id);
        
        if (!$tag) {
            FlashMessage::error('Tag not found.');
            header('Location: /tags');
            exit();
        }
        
        // Validate input
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#10B981');
        
        if (empty($name)) {
            FlashMessage::error('Tag name is required.');
            header('Location: /tags/' . $id . '/edit');
            exit();
        }
        
        // Check if tag name already exists for user (excluding current tag)
        if ($this->tagModel->nameExistsForUser($name, $userId, $id)) {
            FlashMessage::error('A tag with this name already exists.');
            header('Location: /tags/' . $id . '/edit');
            exit();
        }
        
        // Validate color format
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#10B981'; // Default color
        }
        
        $data = [
            'name' => $name,
            'description' => $description,
            'color' => $color,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $result = $this->tagModel->update($id, $data);
            
            if ($result) {
                AppLogger::info('Tag updated', [
                    'user_id' => $userId,
                    'tag_id' => $id,
                    'name' => $name
                ]);
                FlashMessage::success('Tag updated successfully!');
            } else {
                FlashMessage::error('No changes were made.');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to update tag', [
                'user_id' => $userId,
                'tag_id' => $id,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to update tag. Please try again.');
        }
        
        header('Location: /tags');
        exit();
    }

    public function delete($id) {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /tags');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        $tag = $this->tagModel->find($id);
        
        if (!$tag) {
            FlashMessage::error('Tag not found.');
            header('Location: /tags');
            exit();
        }
        
        try {
            $result = $this->tagModel->delete($id);
            
            if ($result) {
                AppLogger::info('Tag deleted', [
                    'user_id' => $userId,
                    'tag_id' => $id,
                    'name' => $tag['name']
                ]);
                FlashMessage::success('Tag deleted successfully! All associated expense tags have been removed.');
            } else {
                FlashMessage::error('Failed to delete tag. Please try again.');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to delete tag', [
                'user_id' => $userId,
                'tag_id' => $id,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to delete tag. Please try again.');
        }
        
        header('Location: /tags');
        exit();
    }

    /**
     * Create default tags for user (AJAX endpoint)
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
            $created = $this->tagModel->createDefaultTags($userId);
            
            if ($created > 0) {
                AppLogger::info('Default tags created', [
                    'user_id' => $userId,
                    'count' => $created
                ]);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true, 
                    'message' => "Created default 'General' tag successfully!",
                    'count' => $created
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'No tags were created']);
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to create default tags', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to create default tags']);
        }
        
        exit();
    }

    /**
     * Get tags for AJAX requests (for expense forms)
     */
    public function ajaxList() {
        $this->checkAuthentication();
        
        // Return all tags for centralized system
        $tags = $this->tagModel->getAllWithUserInfo();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'tags' => $tags
        ]);
        exit();
    }

    /**
     * Search tags (AJAX endpoint)
     */
    public function search() {
        $this->checkAuthentication();
        
        $query = trim($_GET['q'] ?? '');
        
        if (empty($query)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'tags' => []]);
            exit();
        }
        
        // Search all tags for centralized system
        $tags = $this->tagModel->searchByName(null, $query, 10);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'tags' => $tags
        ]);
        exit();
    }

    /**
     * Quick create tag (AJAX endpoint for expense forms)
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
        $color = trim($_POST['color'] ?? '#10B981');
        
        if (empty($name)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Tag name is required']);
            exit();
        }
        
        // Check if tag already exists
        if ($this->tagModel->nameExistsForUser($name, $userId)) {
            // If exists, return the existing tag
            $existingTags = $this->tagModel->getByUserId($userId);
            $existingTag = array_filter($existingTags, function($tag) use ($name) {
                return strtolower($tag['name']) === strtolower($name);
            });
            $existingTag = reset($existingTag);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Tag already exists',
                'tag' => $existingTag
            ]);
            exit();
        }
        
        $data = [
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'color' => $color,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $tagId = $this->tagModel->create($data);
            
            if ($tagId) {
                $tag = $this->tagModel->find($tagId);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Tag created successfully!',
                    'tag' => $tag
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to create tag']);
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to create tag']);
        }
        
        exit();
    }

    /**
     * Get popular tags (AJAX endpoint)
     */
    public function popular() {
        $this->checkAuthentication();
        
        $limit = min(20, max(5, (int)($_GET['limit'] ?? 10)));
        
        // Get popular tags from all users for centralized system
        $tags = $this->tagModel->getPopularTags(null, $limit);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'tags' => $tags
        ]);
        exit();
    }
} 