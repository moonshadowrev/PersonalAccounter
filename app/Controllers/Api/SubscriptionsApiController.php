<?php

require_once __DIR__ . '/ApiController.php';
require_once __DIR__ . '/../../Models/Subscription.php';

/**
 * @OA\Tag(
 *     name="Subscriptions",
 *     description="Subscription management operations"
 * )
 */
class SubscriptionsApiController extends ApiController {
    
    private $subscriptionModel;
    
    public function __construct($database) {
        parent::__construct($database);
        $this->subscriptionModel = new Subscription($database);
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/subscriptions",
     *     summary="Get all subscriptions",
     *     tags={"Subscriptions"},
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
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "inactive", "cancelled", "expired"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subscriptions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Subscriptions retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(ref="#/components/schemas/Subscription")
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
        $this->logRequest('Get Subscriptions');
        
        if (!$this->hasPermission('subscriptions.read')) {
            $this->forbidden('Permission denied: subscriptions.read required');
        }
        
        $pagination = $this->getPagination();
        $userId = $this->request['query']['user_id'] ?? null;
        $status = $this->request['query']['status'] ?? null;
        
        try {
            $conditions = [];
            if ($userId) {
                $conditions['user_id'] = $userId;
            }
            if ($status) {
                $conditions['status'] = $status;
            }
            
            $subscriptions = $this->subscriptionModel->db->select('subscriptions', [
                'id', 'user_id', 'plan_name', 'amount', 'currency', 'billing_cycle', 
                'status', 'start_date', 'end_date', 'next_billing_date', 'created_at', 'updated_at'
            ], array_merge($conditions, [
                'LIMIT' => [$pagination['offset'], $pagination['limit']],
                'ORDER' => ['created_at' => 'DESC']
            ]));
            
            $total = $this->subscriptionModel->db->count('subscriptions', $conditions);
            
            $response = $this->paginatedResponse($subscriptions, $total, $pagination);
            $this->success($response, 'Subscriptions retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Subscriptions', ['error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve subscriptions');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/subscriptions/{id}",
     *     summary="Get subscription by ID",
     *     tags={"Subscriptions"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Subscription ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subscription retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Subscription retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Subscription"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function show($id) {
        $this->logRequest('Get Subscription', ['subscription_id' => $id]);
        
        if (!$this->hasPermission('subscriptions.read')) {
            $this->forbidden('Permission denied: subscriptions.read required');
        }
        
        try {
            $subscription = $this->subscriptionModel->db->get('subscriptions', [
                'id', 'user_id', 'plan_name', 'amount', 'currency', 'billing_cycle', 
                'status', 'start_date', 'end_date', 'next_billing_date', 'created_at', 'updated_at'
            ], ['id' => $id]);
            
            if (!$subscription) {
                $this->notFound('Subscription not found');
            }
            
            $this->success($subscription, 'Subscription retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Subscription', ['subscription_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve subscription');
        }
    }
    
    /**
     * @OA\Post(
     *     path="/api/v1/subscriptions",
     *     summary="Create a new subscription",
     *     tags={"Subscriptions"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "plan_name", "amount", "currency", "billing_cycle", "start_date"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="plan_name", type="string", example="Premium Plan"),
     *             @OA\Property(property="amount", type="number", format="float", example=29.99),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="billing_cycle", type="string", enum={"monthly", "yearly"}, example="monthly"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2024-12-31"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "cancelled", "expired"}, example="active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Subscription created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Subscription created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Subscription"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function store() {
        $this->logRequest('Create Subscription');
        
        if (!$this->hasPermission('subscriptions.create')) {
            $this->forbidden('Permission denied: subscriptions.create required');
        }
        
        $data = $this->sanitize($this->request['body']);
        
        // Validate required fields
        $errors = $this->validateRequired($data, [
            'user_id', 'plan_name', 'amount', 'currency', 'billing_cycle', 'start_date'
        ]);
        
        // Additional validations
        if (isset($data['amount']) && (!is_numeric($data['amount']) || $data['amount'] <= 0)) {
            $errors['amount'] = 'Amount must be a positive number.';
        }
        
        if (isset($data['billing_cycle']) && !in_array($data['billing_cycle'], ['monthly', 'yearly'])) {
            $errors['billing_cycle'] = 'Billing cycle must be either monthly or yearly.';
        }
        
        if (isset($data['status']) && !in_array($data['status'], ['active', 'inactive', 'cancelled', 'expired'])) {
            $errors['status'] = 'Status must be one of: active, inactive, cancelled, expired.';
        }
        
        if (isset($data['start_date']) && !strtotime($data['start_date'])) {
            $errors['start_date'] = 'Start date must be a valid date.';
        }
        
        if (isset($data['end_date']) && !strtotime($data['end_date'])) {
            $errors['end_date'] = 'End date must be a valid date.';
        }
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            // Set default status if not provided
            if (!isset($data['status'])) {
                $data['status'] = 'active';
            }
            
            $subscriptionId = $this->subscriptionModel->create($data);
            
            $subscription = $this->subscriptionModel->find($subscriptionId);
            
            $this->success($subscription, 'Subscription created successfully', 201);
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Create Subscription', ['error' => $e->getMessage(), 'data' => $data]);
            $this->serverError('Failed to create subscription');
        }
    }
    
    /**
     * @OA\Put(
     *     path="/api/v1/subscriptions/{id}",
     *     summary="Update a subscription",
     *     tags={"Subscriptions"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Subscription ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="plan_name", type="string", example="Premium Plan"),
     *             @OA\Property(property="amount", type="number", format="float", example=29.99),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="billing_cycle", type="string", enum={"monthly", "yearly"}, example="monthly"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "cancelled", "expired"}, example="active"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2024-12-31"),
     *             @OA\Property(property="next_billing_date", type="string", format="date", example="2024-02-15")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subscription updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Subscription updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Subscription"),
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
        $this->logRequest('Update Subscription', ['subscription_id' => $id]);
        
        if (!$this->hasPermission('subscriptions.update')) {
            $this->forbidden('Permission denied: subscriptions.update required');
        }
        
        $data = $this->sanitize($this->request['body']);
        
        // Check if subscription exists
        $existingSubscription = $this->subscriptionModel->find($id);
        if (!$existingSubscription) {
            $this->notFound('Subscription not found');
        }
        
        // Validate fields if provided
        $errors = [];
        if (isset($data['amount']) && (!is_numeric($data['amount']) || $data['amount'] <= 0)) {
            $errors['amount'] = 'Amount must be a positive number.';
        }
        
        if (isset($data['billing_cycle']) && !in_array($data['billing_cycle'], ['monthly', 'yearly'])) {
            $errors['billing_cycle'] = 'Billing cycle must be either monthly or yearly.';
        }
        
        if (isset($data['status']) && !in_array($data['status'], ['active', 'inactive', 'cancelled', 'expired'])) {
            $errors['status'] = 'Status must be one of: active, inactive, cancelled, expired.';
        }
        
        if (isset($data['end_date']) && !strtotime($data['end_date'])) {
            $errors['end_date'] = 'End date must be a valid date.';
        }
        
        if (isset($data['next_billing_date']) && !strtotime($data['next_billing_date'])) {
            $errors['next_billing_date'] = 'Next billing date must be a valid date.';
        }
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $this->subscriptionModel->update($id, $data);
            
            $subscription = $this->subscriptionModel->find($id);
            
            $this->success($subscription, 'Subscription updated successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Update Subscription', ['subscription_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to update subscription');
        }
    }
    
    /**
     * @OA\Delete(
     *     path="/api/v1/subscriptions/{id}",
     *     summary="Delete a subscription",
     *     tags={"Subscriptions"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Subscription ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subscription deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Subscription deleted successfully"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function delete($id) {
        $this->logRequest('Delete Subscription', ['subscription_id' => $id]);
        
        if (!$this->hasPermission('subscriptions.delete')) {
            $this->forbidden('Permission denied: subscriptions.delete required');
        }
        
        try {
            $existingSubscription = $this->subscriptionModel->find($id);
            if (!$existingSubscription) {
                $this->notFound('Subscription not found');
            }
            
            $this->subscriptionModel->delete($id);
            
            $this->success(null, 'Subscription deleted successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Delete Subscription', ['subscription_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to delete subscription');
        }
    }
} 