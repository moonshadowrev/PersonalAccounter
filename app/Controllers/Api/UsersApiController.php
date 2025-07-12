<?php

require_once __DIR__ . '/ApiController.php';
require_once __DIR__ . '/../../Models/User.php';

/**
 * @OA\Tag(
 *     name="Users",
 *     description="User management operations"
 * )
 */
class UsersApiController extends ApiController {
    
    private $userModel;
    
    public function __construct($database) {
        parent::__construct($database);
        $this->userModel = new User($database);
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/users",
     *     summary="Get all users",
     *     tags={"Users"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, default=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Users retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(ref="#/components/schemas/User")
     *                 ),
     *                 @OA\Property(property="pagination", ref="#/components/schemas/Pagination")
     *             ),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function index() {
        $this->logRequest('Get Users');
        
        if (!$this->hasPermission('users.read')) {
            $this->forbidden('Permission denied: users.read required');
        }
        
        $pagination = $this->getPagination();
        
        try {
            $users = $this->userModel->db->select('users', [
                'id', 'name', 'email', 'role', 'two_factor_enabled', 'created_at', 'updated_at'
            ], [
                'LIMIT' => [$pagination['offset'], $pagination['limit']],
                'ORDER' => ['created_at' => 'DESC']
            ]);
            
            $total = $this->userModel->db->count('users');
            
            $response = $this->paginatedResponse($users, $total, $pagination);
            $this->success($response, 'Users retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Users', ['error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve users');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/users/{id}",
     *     summary="Get user by ID",
     *     tags={"Users"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/User"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function show($id) {
        $this->logRequest('Get User', ['user_id' => $id]);
        
        if (!$this->hasPermission('users.read')) {
            $this->forbidden('Permission denied: users.read required');
        }
        
        try {
            $user = $this->userModel->db->get('users', [
                'id', 'name', 'email', 'role', 'two_factor_enabled', 'created_at', 'updated_at'
            ], ['id' => $id]);
            
            if (!$user) {
                $this->notFound('User not found');
            }
            
            $this->success($user, 'User retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get User', ['user_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve user');
        }
    }
    
    /**
     * @OA\Post(
     *     path="/api/v1/users",
     *     summary="Create a new user",
     *     tags={"Users"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "role"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", minLength=8, example="password123"),
     *             @OA\Property(property="role", type="string", enum={"admin", "user"}, example="user")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/User"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function store() {
        $this->logRequest('Create User');
        
        if (!$this->hasPermission('users.create')) {
            $this->forbidden('Permission denied: users.create required');
        }
        
        $data = $this->sanitize($this->request['body']);
        
        // Validate required fields
        $errors = $this->validateRequired($data, ['name', 'email', 'password', 'role']);
        
        // Additional validations
        if (isset($data['email']) && !$this->validateEmail($data['email'])) {
            $errors['email'] = 'Please provide a valid email address.';
        }
        
        if (isset($data['password']) && strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long.';
        }
        
        if (isset($data['role']) && !in_array($data['role'], ['admin', 'user'])) {
            $errors['role'] = 'Role must be either admin or user.';
        }
        
        // Check if email already exists
        if (isset($data['email'])) {
            $existingUser = $this->userModel->findByEmail($data['email']);
            if ($existingUser) {
                $errors['email'] = 'Email address is already in use.';
            }
        }
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role' => $data['role'],
                'two_factor_enabled' => false,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->userModel->db->insert('users', $userData);
            
            if ($result->rowCount() > 0) {
                $userId = $this->userModel->db->id();
                $user = $this->userModel->db->get('users', [
                    'id', 'name', 'email', 'role', 'two_factor_enabled', 'created_at', 'updated_at'
                ], ['id' => $userId]);
                
                $this->success($user, 'User created successfully', 201);
            } else {
                $this->serverError('Failed to create user');
            }
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Create User', ['error' => $e->getMessage()]);
            $this->serverError('Failed to create user');
        }
    }
    
    /**
     * @OA\Put(
     *     path="/api/v1/users/{id}",
     *     summary="Update user",
     *     tags={"Users"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", minLength=8, example="newpassword123"),
     *             @OA\Property(property="role", type="string", enum={"admin", "user"}, example="user")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/User"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function update($id) {
        $this->logRequest('Update User', ['user_id' => $id]);
        
        if (!$this->hasPermission('users.update')) {
            $this->forbidden('Permission denied: users.update required');
        }
        
        // Check if user exists
        $existingUser = $this->userModel->find($id);
        if (!$existingUser) {
            $this->notFound('User not found');
        }
        
        $data = $this->sanitize($this->request['body']);
        $errors = [];
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];
        
        // Validate and prepare update data
        if (isset($data['name']) && !empty($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        
        if (isset($data['email']) && !empty($data['email'])) {
            if (!$this->validateEmail($data['email'])) {
                $errors['email'] = 'Please provide a valid email address.';
            } else {
                // Check if email is already in use by another user
                $emailUser = $this->userModel->findByEmail($data['email']);
                if ($emailUser && $emailUser['id'] != $id) {
                    $errors['email'] = 'Email address is already in use.';
                } else {
                    $updateData['email'] = $data['email'];
                }
            }
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                $errors['password'] = 'Password must be at least 8 characters long.';
            } else {
                $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
        }
        
        if (isset($data['role']) && !empty($data['role'])) {
            if (!in_array($data['role'], ['admin', 'user'])) {
                $errors['role'] = 'Role must be either admin or user.';
            } else {
                $updateData['role'] = $data['role'];
            }
        }
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $result = $this->userModel->db->update('users', $updateData, ['id' => $id]);
            
            if ($result->rowCount() > 0) {
                $user = $this->userModel->db->get('users', [
                    'id', 'name', 'email', 'role', 'two_factor_enabled', 'created_at', 'updated_at'
                ], ['id' => $id]);
                
                $this->success($user, 'User updated successfully');
            } else {
                $this->success($existingUser, 'No changes made');
            }
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Update User', ['user_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to update user');
        }
    }
    
    /**
     * @OA\Delete(
     *     path="/api/v1/users/{id}",
     *     summary="Delete user",
     *     tags={"Users"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User deleted successfully"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function delete($id) {
        $this->logRequest('Delete User', ['user_id' => $id]);
        
        if (!$this->hasPermission('users.delete')) {
            $this->forbidden('Permission denied: users.delete required');
        }
        
        // Check if user exists
        $user = $this->userModel->find($id);
        if (!$user) {
            $this->notFound('User not found');
        }
        
        try {
            $result = $this->userModel->db->delete('users', ['id' => $id]);
            
            if ($result->rowCount() > 0) {
                $this->success(null, 'User deleted successfully');
            } else {
                $this->serverError('Failed to delete user');
            }
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Delete User', ['user_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to delete user');
        }
    }
} 