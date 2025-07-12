<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/User.php';

class UserController extends Controller {

    private $userModel;

    public function __construct($db) {
        $this->userModel = new User($db);
        // Role-based access control moved to individual methods
        // since session may not be available during constructor
    }

    private function checkSuperAdminAccess() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Role-based access control
        if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'superadmin') {
            header('Location: /'); // Redirect non-superadmins to dashboard
            exit();
        }
    }

    public function index() {
        $this->checkSuperAdminAccess();
        // For now, get all records since DataTables handles pagination client-side
        // In the future, this could be optimized with server-side pagination for very large datasets
        $users = $this->userModel->getAll(['id', 'name', 'email', 'created_at', 'role']);
        $this->view('dashboard/users/index', [
            'users' => $users,
            'load_datatable' => true,
            'datatable_target' => '#users-table'
        ]);
    }

    public function create() {
        $this->checkSuperAdminAccess();
        $this->view('dashboard/users/create');
    }

    public function store() {
        $this->checkSuperAdminAccess();
        
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /users/create');
            exit();
        }
        
        // Basic validation
        if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['password']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            FlashMessage::error('Please fill in all required fields with valid information.');
            header('Location: /users/create');
            exit();
        }

        try {
            $this->userModel->createUser($_POST['name'], $_POST['email'], $_POST['password'], $_POST['role']);
            FlashMessage::success('Admin user created successfully!');
        } catch (Exception $e) {
            FlashMessage::error('Failed to create admin user. Please try again.');
        }
        
        header('Location: /users');
        exit();
    }

    public function edit($id) {
        $this->checkSuperAdminAccess();
        $user = $this->userModel->find($id);
        
        if (!$user) {
            FlashMessage::error('Admin user not found.');
            header('Location: /users');
            exit();
        }
        
        $this->view('dashboard/users/edit', ['user' => $user]);
    }

    public function update($id) {
        $this->checkSuperAdminAccess();
        
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /users/' . $id . '/edit');
            exit();
        }
        
        // Basic validation
        if (empty($_POST['name']) || empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            FlashMessage::error('Please fill in all required fields with valid information.');
            header('Location: /users/' . $id . '/edit');
            exit();
        }
        
        $data = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'password' => $_POST['password'], // Model handles empty check
            'role' => $_POST['role']
        ];
        
        try {
            $this->userModel->update($id, $data);
            FlashMessage::success('Admin user updated successfully!');
        } catch (Exception $e) {
            FlashMessage::error('Failed to update admin user. Please try again.');
        }
        
        header('Location: /users');
        exit();
    }

    public function delete($id) {
        $this->checkSuperAdminAccess();
        
        // Validate CSRF token
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /users');
            exit();
        }
        
        // Prevent user from deleting themselves
        if ($id == $_SESSION['user']['id']) {
            FlashMessage::warning('You cannot delete your own account.');
            header('Location: /users');
            exit();
        }
        
        try {
            $user = $this->userModel->find($id);
            if (!$user) {
                FlashMessage::error('Admin user not found.');
            } else {
                $this->userModel->delete($id);
                FlashMessage::success('Admin user deleted successfully!');
            }
        } catch (Exception $e) {
            FlashMessage::error('Failed to delete admin user. Please try again.');
        }
        
        header('Location: /users');
        exit();
    }
} 