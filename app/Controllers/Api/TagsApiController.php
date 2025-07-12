<?php

require_once __DIR__ . '/ApiController.php';
require_once __DIR__ . '/../../Models/Tag.php';

/**
 * @OA\Tag(
 *     name="Tags",
 *     description="Tag management operations"
 * )
 */
class TagsApiController extends ApiController {
    
    private $tagModel;
    
    public function __construct($database) {
        parent::__construct($database);
        $this->tagModel = new Tag($database);
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/tags",
     *     summary="Get all tags",
     *     tags={"Tags"},
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
     *         description="Search tags by name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tags retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tags retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(ref="#/components/schemas/Tag")
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
        $this->logRequest('Get Tags');
        
        if (!$this->hasPermission('tags.read')) {
            $this->forbidden('Permission denied: tags.read required');
        }
        
        $pagination = $this->getPagination();
        $search = $this->request['query']['search'] ?? null;
        
        try {
            $conditions = [];
            
            if ($search) {
                $conditions['name[~]'] = $search;
            }
            
            $tags = $this->tagModel->db->select('tags', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'tags.id',
                'tags.name',
                'tags.description',
                'tags.color',
                'tags.created_at',
                'tags.updated_at',
                'users.name(creator_name)',
                'users.email(creator_email)'
            ], array_merge($conditions, [
                'LIMIT' => [$pagination['offset'], $pagination['limit']],
                'ORDER' => ['tags.name' => 'ASC']
            ]));
            
            // Add usage count for each tag
            foreach ($tags as &$tag) {
                $tag['usage_count'] = $this->tagModel->db->count('expense_tags', [
                    'tag_id' => $tag['id']
                ]);
            }
            
            $total = $this->tagModel->db->count('tags', $conditions);
            
            $response = $this->paginatedResponse($tags, $total, $pagination);
            $this->success($response, 'Tags retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Tags', ['error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve tags');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/tags/{id}",
     *     summary="Get tag by ID",
     *     tags={"Tags"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Tag ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tag retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tag retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Tag"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function show($id) {
        $this->logRequest('Get Tag', ['tag_id' => $id]);
        
        if (!$this->hasPermission('tags.read')) {
            $this->forbidden('Permission denied: tags.read required');
        }
        
        try {
            $tag = $this->tagModel->db->get('tags', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'tags.id',
                'tags.name',
                'tags.description',
                'tags.color',
                'tags.created_at',
                'tags.updated_at',
                'users.name(creator_name)',
                'users.email(creator_email)'
            ], ['tags.id' => $id]);
            
            if (!$tag) {
                $this->notFound('Tag not found');
            }
            
            // Add usage count
            $tag['usage_count'] = $this->tagModel->db->count('expense_tags', [
                'tag_id' => $id
            ]);
            
            $this->success($tag, 'Tag retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Tag', ['tag_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve tag');
        }
    }
    
    /**
     * @OA\Post(
     *     path="/api/v1/tags",
     *     summary="Create a new tag",
     *     tags={"Tags"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "user_id"},
     *             @OA\Property(property="name", type="string", example="Business"),
     *             @OA\Property(property="description", type="string", example="Business related expenses"),
     *             @OA\Property(property="color", type="string", example="#3B82F6"),
     *             @OA\Property(property="user_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tag created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tag created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Tag"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function store() {
        $this->logRequest('Create Tag');
        
        if (!$this->hasPermission('tags.create')) {
            $this->forbidden('Permission denied: tags.create required');
        }
        
        $data = $this->sanitize($this->request['body']);
        
        // Validate required fields
        $errors = $this->validateRequired($data, ['name', 'user_id']);
        
        // Additional validations
        if (isset($data['color']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color'])) {
            $errors['color'] = 'Color must be a valid hex color code (e.g., #3B82F6).';
        }
        
        if (isset($data['user_id'])) {
            $userExists = $this->tagModel->db->has('users', ['id' => $data['user_id']]);
            if (!$userExists) {
                $errors['user_id'] = 'User does not exist.';
            }
        }
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $tagData = [
                'user_id' => $data['user_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'color' => $data['color'] ?? '#3B82F6',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $tagId = $this->tagModel->create($tagData);
            
            if ($tagId) {
                $tag = $this->tagModel->find($tagId);
                $this->success($tag, 'Tag created successfully', 201);
            } else {
                $this->serverError('Failed to create tag');
            }
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Create Tag', ['error' => $e->getMessage(), 'data' => $data]);
            $this->serverError('Failed to create tag');
        }
    }
    
    /**
     * @OA\Put(
     *     path="/api/v1/tags/{id}",
     *     summary="Update a tag",
     *     tags={"Tags"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Tag ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Business"),
     *             @OA\Property(property="description", type="string", example="Business related expenses"),
     *             @OA\Property(property="color", type="string", example="#3B82F6")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tag updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tag updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Tag"),
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
        $this->logRequest('Update Tag', ['tag_id' => $id]);
        
        if (!$this->hasPermission('tags.update')) {
            $this->forbidden('Permission denied: tags.update required');
        }
        
        $data = $this->sanitize($this->request['body']);
        
        // Check if tag exists
        $existingTag = $this->tagModel->find($id);
        if (!$existingTag) {
            $this->notFound('Tag not found');
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
            
            $result = $this->tagModel->update($id, $updateData);
            
            if ($result) {
                $tag = $this->tagModel->find($id);
                $this->success($tag, 'Tag updated successfully');
            } else {
                $this->serverError('Failed to update tag');
            }
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Update Tag', ['tag_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to update tag');
        }
    }
    
    /**
     * @OA\Delete(
     *     path="/api/v1/tags/{id}",
     *     summary="Delete a tag",
     *     tags={"Tags"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Tag ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tag deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tag deleted successfully"),
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
        $this->logRequest('Delete Tag', ['tag_id' => $id]);
        
        if (!$this->hasPermission('tags.delete')) {
            $this->forbidden('Permission denied: tags.delete required');
        }
        
        try {
            $existingTag = $this->tagModel->find($id);
            if (!$existingTag) {
                $this->notFound('Tag not found');
            }
            
            // Check if tag is used in expenses
            $usageCount = $this->tagModel->db->count('expense_tags', ['tag_id' => $id]);
            if ($usageCount > 0) {
                $this->error('Cannot delete tag that is used in expenses. Please remove the tag from expenses first.', 400);
            }
            
            $result = $this->tagModel->delete($id);
            
            if ($result) {
                $this->success(null, 'Tag deleted successfully');
            } else {
                $this->serverError('Failed to delete tag');
            }
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Delete Tag', ['tag_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to delete tag');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/tags/popular",
     *     summary="Get popular tags",
     *     tags={"Tags"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of tags to return",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=20, default=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Popular tags retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Popular tags retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Tag")),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function popular() {
        $this->logRequest('Get Popular Tags');
        
        if (!$this->hasPermission('tags.read')) {
            $this->forbidden('Permission denied: tags.read required');
        }
        
        $limit = min(20, max(1, (int)($this->request['query']['limit'] ?? 5)));
        
        try {
            $tags = $this->tagModel->db->select('tags', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'tags.id',
                'tags.name',
                'tags.description',
                'tags.color',
                'tags.created_at',
                'users.name(creator_name)'
            ], [
                'ORDER' => ['tags.created_at' => 'DESC']
            ]);
            
            // Add usage count and filter/sort
            $tagsWithCount = [];
            foreach ($tags as $tag) {
                $tag['usage_count'] = $this->tagModel->db->count('expense_tags', [
                    'tag_id' => $tag['id']
                ]);
                if ($tag['usage_count'] > 0) {
                    $tagsWithCount[] = $tag;
                }
            }
            
            // Sort by usage count descending
            usort($tagsWithCount, function($a, $b) {
                return $b['usage_count'] - $a['usage_count'];
            });
            
            // Limit results
            $popularTags = array_slice($tagsWithCount, 0, $limit);
            
            $this->success($popularTags, 'Popular tags retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Popular Tags', ['error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve popular tags');
        }
    }
} 