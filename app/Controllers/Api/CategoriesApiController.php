<?php

require_once __DIR__ . '/ApiController.php';
require_once __DIR__ . '/../../Models/Category.php';

/**
 * @OA\Tag(
 *     name="Categories",
 *     description="Category management operations"
 * )
 */
class CategoriesApiController extends ApiController {
    
    private $categoryModel;
    
    public function __construct($database) {
        parent::__construct($database);
        $this->categoryModel = new Category($database);
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/categories",
     *     summary="Get all categories",
     *     tags={"Categories"},
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
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search categories by name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categories retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Categories retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(ref="#/components/schemas/Category")
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
        $this->logRequest('Get Categories');
        
        if (!$this->hasPermission('categories.read')) {
            $this->forbidden('Permission denied: categories.read required');
        }
        
        $pagination = $this->getPagination();
        $search = $this->request['query']['search'] ?? null;
        
        try {
            $conditions = [];
            
            if ($search) {
                $conditions['name[~]'] = $search;
            }
            
            $categories = $this->categoryModel->db->select('categories', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'categories.id',
                'categories.name',
                'categories.description',
                'categories.color',
                'categories.icon',
                'categories.created_at',
                'categories.updated_at',
                'users.name(creator_name)',
                'users.email(creator_email)'
            ], array_merge($conditions, [
                'LIMIT' => [$pagination['offset'], $pagination['limit']],
                'ORDER' => ['categories.name' => 'ASC']
            ]));
            
            // Add expense count for each category
            foreach ($categories as &$category) {
                $category['expense_count'] = $this->categoryModel->db->count('expenses', [
                    'category_id' => $category['id']
                ]);
            }
            
            $total = $this->categoryModel->db->count('categories', $conditions);
            
            $response = $this->paginatedResponse($categories, $total, $pagination);
            $this->success($response, 'Categories retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Categories', ['error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve categories');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/categories/{id}",
     *     summary="Get category by ID",
     *     tags={"Categories"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Category retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Category"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function show($id) {
        $this->logRequest('Get Category', ['category_id' => $id]);
        
        if (!$this->hasPermission('categories.read')) {
            $this->forbidden('Permission denied: categories.read required');
        }
        
        try {
            $category = $this->categoryModel->db->get('categories', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'categories.id',
                'categories.name',
                'categories.description',
                'categories.color',
                'categories.icon',
                'categories.created_at',
                'categories.updated_at',
                'users.name(creator_name)',
                'users.email(creator_email)'
            ], ['categories.id' => $id]);
            
            if (!$category) {
                $this->notFound('Category not found');
            }
            
            // Add expense count
            $category['expense_count'] = $this->categoryModel->db->count('expenses', [
                'category_id' => $id
            ]);
            
            $this->success($category, 'Category retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Category', ['category_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve category');
        }
    }
    
    /**
     * @OA\Post(
     *     path="/api/v1/categories",
     *     summary="Create a new category",
     *     tags={"Categories"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "user_id"},
     *             @OA\Property(property="name", type="string", example="Office Supplies"),
     *             @OA\Property(property="description", type="string", example="Office and business supplies"),
     *             @OA\Property(property="color", type="string", example="#3B82F6"),
     *             @OA\Property(property="icon", type="string", example="fas fa-briefcase"),
     *             @OA\Property(property="user_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Category created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Category"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function store() {
        $this->logRequest('Create Category');
        
        if (!$this->hasPermission('categories.create')) {
            $this->forbidden('Permission denied: categories.create required');
        }
        
        $data = $this->sanitize($this->request['body']);
        
        // Validate required fields
        $errors = $this->validateRequired($data, ['name', 'user_id']);
        
        // Additional validations
        if (isset($data['color']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color'])) {
            $errors['color'] = 'Color must be a valid hex color code (e.g., #3B82F6).';
        }
        
        if (isset($data['user_id'])) {
            $userExists = $this->categoryModel->db->has('users', ['id' => $data['user_id']]);
            if (!$userExists) {
                $errors['user_id'] = 'User does not exist.';
            }
        }
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $categoryData = [
                'user_id' => $data['user_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'color' => $data['color'] ?? '#3B82F6',
                'icon' => $data['icon'] ?? 'fas fa-folder',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $categoryId = $this->categoryModel->create($categoryData);
            
            if ($categoryId) {
                $category = $this->categoryModel->find($categoryId);
                $this->success($category, 'Category created successfully', 201);
            } else {
                $this->serverError('Failed to create category');
            }
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Create Category', ['error' => $e->getMessage(), 'data' => $data]);
            $this->serverError('Failed to create category');
        }
    }
    
    /**
     * @OA\Put(
     *     path="/api/v1/categories/{id}",
     *     summary="Update a category",
     *     tags={"Categories"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Office Supplies"),
     *             @OA\Property(property="description", type="string", example="Office and business supplies"),
     *             @OA\Property(property="color", type="string", example="#3B82F6"),
     *             @OA\Property(property="icon", type="string", example="fas fa-briefcase")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Category updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Category"),
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
        $this->logRequest('Update Category', ['category_id' => $id]);
        
        if (!$this->hasPermission('categories.update')) {
            $this->forbidden('Permission denied: categories.update required');
        }
        
        $data = $this->sanitize($this->request['body']);
        
        // Check if category exists
        $existingCategory = $this->categoryModel->find($id);
        if (!$existingCategory) {
            $this->notFound('Category not found');
        }
        
        // Validate fields if provided
        $errors = [];
        if (isset($data['color']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color'])) {
            $errors['color'] = 'Color must be a valid hex color code (e.g., #3B82F6).';
        }
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $updateData = array_merge($data, [
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            $result = $this->categoryModel->update($id, $updateData);
            
            if ($result) {
                $category = $this->categoryModel->find($id);
                $this->success($category, 'Category updated successfully');
            } else {
                $this->serverError('Failed to update category');
            }
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Update Category', ['category_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to update category');
        }
    }
    
    /**
     * @OA\Delete(
     *     path="/api/v1/categories/{id}",
     *     summary="Delete a category",
     *     tags={"Categories"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Category deleted successfully"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function delete($id) {
        $this->logRequest('Delete Category', ['category_id' => $id]);
        
        if (!$this->hasPermission('categories.delete')) {
            $this->forbidden('Permission denied: categories.delete required');
        }
        
        try {
            $existingCategory = $this->categoryModel->find($id);
            if (!$existingCategory) {
                $this->notFound('Category not found');
            }
            
            // Check if category is used in expenses
            $expenseCount = $this->categoryModel->db->count('expenses', ['category_id' => $id]);
            if ($expenseCount > 0) {
                $this->error('Cannot delete category that is used in expenses. Please reassign or delete the expenses first.', 400);
            }
            
            $result = $this->categoryModel->delete($id);
            
            if ($result) {
                $this->success(null, 'Category deleted successfully');
            } else {
                $this->serverError('Failed to delete category');
            }
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Delete Category', ['category_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to delete category');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/categories/popular",
     *     summary="Get popular categories",
     *     tags={"Categories"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of categories to return",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=20, default=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Popular categories retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Popular categories retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Category")),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function popular() {
        $this->logRequest('Get Popular Categories');
        
        if (!$this->hasPermission('categories.read')) {
            $this->forbidden('Permission denied: categories.read required');
        }
        
        $limit = min(20, max(1, (int)($this->request['query']['limit'] ?? 5)));
        
        try {
            $categories = $this->categoryModel->db->select('categories', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'categories.id',
                'categories.name',
                'categories.description',
                'categories.color',
                'categories.icon',
                'categories.created_at',
                'users.name(creator_name)'
            ], [
                'ORDER' => ['categories.created_at' => 'DESC']
            ]);
            
            // Add expense count and filter/sort
            $categoriesWithCount = [];
            foreach ($categories as $category) {
                $category['expense_count'] = $this->categoryModel->db->count('expenses', [
                    'category_id' => $category['id']
                ]);
                if ($category['expense_count'] > 0) {
                    $categoriesWithCount[] = $category;
                }
            }
            
            // Sort by expense count descending
            usort($categoriesWithCount, function($a, $b) {
                return $b['expense_count'] - $a['expense_count'];
            });
            
            // Limit results
            $popularCategories = array_slice($categoriesWithCount, 0, $limit);
            
            $this->success($popularCategories, 'Popular categories retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Popular Categories', ['error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve popular categories');
        }
    }
} 