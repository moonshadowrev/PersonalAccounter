<?php

require_once __DIR__ . '/ApiController.php';
require_once __DIR__ . '/../../Models/CreditCard.php';

/**
 * @OA\Tag(
 *     name="Credit Cards",
 *     description="Credit card management operations"
 * )
 */
class CreditCardsApiController extends ApiController {
    
    private $creditCardModel;
    
    public function __construct($database) {
        parent::__construct($database);
        $this->creditCardModel = new CreditCard($database);
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/credit-cards",
     *     summary="Get all credit cards",
     *     tags={"Credit Cards"},
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
     *         name="user_id",
     *         in="query",
     *         description="Filter by user ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Credit cards retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Credit cards retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(ref="#/components/schemas/CreditCard")
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
        $this->logRequest('Get Credit Cards');
        
        if (!$this->hasPermission('credit_cards.read')) {
            $this->forbidden('Permission denied: credit_cards.read required');
        }
        
        $pagination = $this->getPagination();
        $userId = $this->request['query']['user_id'] ?? null;
        
        try {
            $conditions = [];
            if ($userId) {
                $conditions['user_id'] = $userId;
            }
            
            $creditCards = $this->creditCardModel->db->select('credit_cards', [
                'id', 'user_id', 'card_number_masked', 'card_holder_name', 
                'expiry_month', 'expiry_year', 'card_type', 'is_active', 'created_at', 'updated_at'
            ], array_merge($conditions, [
                'LIMIT' => [$pagination['offset'], $pagination['limit']],
                'ORDER' => ['created_at' => 'DESC']
            ]));
            
            $total = $this->creditCardModel->db->count('credit_cards', $conditions);
            
            $response = $this->paginatedResponse($creditCards, $total, $pagination);
            $this->success($response, 'Credit cards retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Credit Cards', ['error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve credit cards');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/credit-cards/{id}",
     *     summary="Get credit card by ID",
     *     tags={"Credit Cards"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Credit card ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Credit card retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Credit card retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/CreditCard"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function show($id) {
        $this->logRequest('Get Credit Card', ['credit_card_id' => $id]);
        
        if (!$this->hasPermission('credit_cards.read')) {
            $this->forbidden('Permission denied: credit_cards.read required');
        }
        
        try {
            $creditCard = $this->creditCardModel->db->get('credit_cards', [
                'id', 'user_id', 'card_number_masked', 'card_holder_name', 
                'expiry_month', 'expiry_year', 'card_type', 'is_active', 'created_at', 'updated_at'
            ], ['id' => $id]);
            
            if (!$creditCard) {
                $this->notFound('Credit card not found');
            }
            
            $this->success($creditCard, 'Credit card retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Credit Card', ['credit_card_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve credit card');
        }
    }
    
    /**
     * @OA\Post(
     *     path="/api/v1/credit-cards",
     *     summary="Create a new credit card",
     *     tags={"Credit Cards"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "card_number", "card_holder_name", "expiry_month", "expiry_year", "cvv"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="card_number", type="string", example="4111111111111111"),
     *             @OA\Property(property="card_holder_name", type="string", example="John Doe"),
     *             @OA\Property(property="expiry_month", type="integer", minimum=1, maximum=12, example=12),
     *             @OA\Property(property="expiry_year", type="integer", example=2025),
     *             @OA\Property(property="cvv", type="string", example="123"),
     *             @OA\Property(property="card_type", type="string", example="visa")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Credit card created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Credit card created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/CreditCard"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function store() {
        $this->logRequest('Create Credit Card');
        
        if (!$this->hasPermission('credit_cards.create')) {
            $this->forbidden('Permission denied: credit_cards.create required');
        }
        
        $data = $this->sanitize($this->request['body']);
        
        // Validate required fields
        $errors = $this->validateRequired($data, [
            'user_id', 'card_number', 'card_holder_name', 'expiry_month', 'expiry_year', 'cvv'
        ]);
        
        // Additional validations
        if (isset($data['expiry_month']) && ($data['expiry_month'] < 1 || $data['expiry_month'] > 12)) {
            $errors['expiry_month'] = 'Expiry month must be between 1 and 12.';
        }
        
        if (isset($data['expiry_year']) && $data['expiry_year'] < date('Y')) {
            $errors['expiry_year'] = 'Expiry year cannot be in the past.';
        }
        
        if (isset($data['card_number']) && !preg_match('/^\d{13,19}$/', $data['card_number'])) {
            $errors['card_number'] = 'Card number must be 13-19 digits.';
        }
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $creditCardId = $this->creditCardModel->create($data);
            
            $creditCard = $this->creditCardModel->find($creditCardId);
            
            $this->success($creditCard, 'Credit card created successfully', 201);
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Create Credit Card', ['error' => $e->getMessage(), 'data' => $data]);
            $this->serverError('Failed to create credit card');
        }
    }
    
    /**
     * @OA\Put(
     *     path="/api/v1/credit-cards/{id}",
     *     summary="Update a credit card",
     *     tags={"Credit Cards"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Credit card ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="card_holder_name", type="string", example="John Doe"),
     *             @OA\Property(property="expiry_month", type="integer", minimum=1, maximum=12, example=12),
     *             @OA\Property(property="expiry_year", type="integer", example=2025),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Credit card updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Credit card updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/CreditCard"),
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
        $this->logRequest('Update Credit Card', ['credit_card_id' => $id]);
        
        if (!$this->hasPermission('credit_cards.update')) {
            $this->forbidden('Permission denied: credit_cards.update required');
        }
        
        $data = $this->sanitize($this->request['body']);
        
        // Check if credit card exists
        $existingCard = $this->creditCardModel->find($id);
        if (!$existingCard) {
            $this->notFound('Credit card not found');
        }
        
        // Validate fields if provided
        $errors = [];
        if (isset($data['expiry_month']) && ($data['expiry_month'] < 1 || $data['expiry_month'] > 12)) {
            $errors['expiry_month'] = 'Expiry month must be between 1 and 12.';
        }
        
        if (isset($data['expiry_year']) && $data['expiry_year'] < date('Y')) {
            $errors['expiry_year'] = 'Expiry year cannot be in the past.';
        }
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $this->creditCardModel->update($id, $data);
            
            $creditCard = $this->creditCardModel->find($id);
            
            $this->success($creditCard, 'Credit card updated successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Update Credit Card', ['credit_card_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to update credit card');
        }
    }
    
    /**
     * @OA\Delete(
     *     path="/api/v1/credit-cards/{id}",
     *     summary="Delete a credit card",
     *     tags={"Credit Cards"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Credit card ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Credit card deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Credit card deleted successfully"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function delete($id) {
        $this->logRequest('Delete Credit Card', ['credit_card_id' => $id]);
        
        if (!$this->hasPermission('credit_cards.delete')) {
            $this->forbidden('Permission denied: credit_cards.delete required');
        }
        
        try {
            $existingCard = $this->creditCardModel->find($id);
            if (!$existingCard) {
                $this->notFound('Credit card not found');
            }
            
            $this->creditCardModel->delete($id);
            
            $this->success(null, 'Credit card deleted successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Delete Credit Card', ['credit_card_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to delete credit card');
        }
    }
} 