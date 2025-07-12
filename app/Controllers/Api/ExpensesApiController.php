<?php

require_once __DIR__ . '/ApiController.php';
require_once __DIR__ . '/../../Models/Expense.php';

/**
 * @OA\Tag(
 *     name="Expenses",
 *     description="Expense management operations"
 * )
 */
class ExpensesApiController extends ApiController {
    
    private $expenseModel;
    
    public function __construct($database) {
        parent::__construct($database);
        $this->expenseModel = new Expense($database);
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/expenses",
     *     summary="Get all expenses",
     *     tags={"Expenses"},
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
     *         @OA\Schema(type="string", enum={"pending", "approved", "rejected"})
     *     ),
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by category ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Expenses retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expenses retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(ref="#/components/schemas/Expense")
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
        $this->logRequest('Get Expenses');
        
        if (!$this->hasPermission('expenses.read')) {
            $this->forbidden('Permission denied: expenses.read required');
        }
        
        $pagination = $this->getPagination();
        $filters = [
            'user_id' => $this->request['query']['user_id'] ?? null,
            'status' => $this->request['query']['status'] ?? null,
            'category_id' => $this->request['query']['category_id'] ?? null,
            'search' => $this->request['query']['search'] ?? null
        ];
        
        try {
            $expenses = $this->expenseModel->getExpensesWithFilters(
                $filters['user_id'], 
                $filters, 
                $pagination['page'], 
                $pagination['limit']
            );
            
            $total = $this->expenseModel->countExpensesWithFilters($filters['user_id'], $filters);
            
            $response = $this->paginatedResponse($expenses, $total, $pagination);
            $this->success($response, 'Expenses retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Expenses', ['error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve expenses');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/expenses/{id}",
     *     summary="Get expense by ID",
     *     tags={"Expenses"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Expense ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Expense retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expense retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Expense"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function show($id) {
        $this->logRequest('Get Expense', ['expense_id' => $id]);
        
        if (!$this->hasPermission('expenses.read')) {
            $this->forbidden('Permission denied: expenses.read required');
        }
        
        try {
            $expense = $this->expenseModel->find($id);
            
            if (!$expense) {
                $this->notFound('Expense not found');
            }
            
            // Get expense tags
            $expense['tags'] = $this->expenseModel->getExpenseTags($id);
            
            $this->success($expense, 'Expense retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Expense', ['expense_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve expense');
        }
    }
    
    /**
     * @OA\Post(
     *     path="/api/v1/expenses",
     *     summary="Create a new expense",
     *     tags={"Expenses"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "title", "amount", "payment_method", "payment_id", "expense_date"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Office Supplies"),
     *             @OA\Property(property="description", type="string", example="Monthly office supplies purchase"),
     *             @OA\Property(property="amount", type="number", format="float", example=150.75),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="tag_ids", type="array", @OA\Items(type="integer")),
     *             @OA\Property(property="payment_method", type="string", enum={"credit_card", "bank_account", "crypto_wallet"}),
     *             @OA\Property(property="payment_id", type="integer", example=1),
     *             @OA\Property(property="expense_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="due_date", type="string", format="date", example="2024-02-15"),
     *             @OA\Property(property="tax_rate", type="number", format="float", example=8.5),
     *             @OA\Property(property="notes", type="string", example="Business expense"),
     *             @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="pending")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Expense created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expense created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Expense"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function store() {
        $this->logRequest('Create Expense');
        
        if (!$this->hasPermission('expenses.create')) {
            $this->forbidden('Permission denied: expenses.create required');
        }
        
        $data = $this->sanitize($this->request['body']);
        
        // Validate required fields
        $errors = $this->validateRequired($data, [
            'user_id', 'title', 'amount', 'payment_method', 'payment_id', 'expense_date'
        ]);
        
        // Additional validations
        if (isset($data['amount']) && $data['amount'] <= 0) {
            $errors['amount'] = 'Amount must be greater than 0.';
        }
        
        if (isset($data['payment_method']) && !in_array($data['payment_method'], ['credit_card', 'bank_account', 'crypto_wallet'])) {
            $errors['payment_method'] = 'Invalid payment method.';
        }
        
        if (isset($data['status']) && !in_array($data['status'], ['pending', 'approved', 'rejected'])) {
            $errors['status'] = 'Invalid status.';
        }
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            // Extract tag IDs
            $tagIds = $data['tag_ids'] ?? [];
            unset($data['tag_ids']);
            
            // Calculate tax amount
            $taxRate = floatval($data['tax_rate'] ?? 0);
            $amount = floatval($data['amount']);
            $taxAmount = ($taxRate > 0) ? ($amount * $taxRate / 100) : 0;
            
            $data['tax_amount'] = $taxAmount;
            $data['total_amount'] = $amount + $taxAmount;
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $expenseId = $this->expenseModel->create($data);
            
            // Add tags if provided
            if (!empty($tagIds)) {
                $this->expenseModel->addTags($expenseId, $tagIds);
            }
            
            // Generate transaction if approved
            if (($data['status'] ?? 'pending') === 'approved') {
                $this->expenseModel->generateTransaction($expenseId);
            }
            
            $expense = $this->expenseModel->find($expenseId);
            $expense['tags'] = $this->expenseModel->getExpenseTags($expenseId);
            
            $this->success($expense, 'Expense created successfully', 201);
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Create Expense', ['error' => $e->getMessage(), 'data' => $data]);
            $this->serverError('Failed to create expense');
        }
    }
    
    /**
     * @OA\Put(
     *     path="/api/v1/expenses/{id}",
     *     summary="Update an expense",
     *     tags={"Expenses"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Expense ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Office Supplies"),
     *             @OA\Property(property="description", type="string", example="Monthly office supplies purchase"),
     *             @OA\Property(property="amount", type="number", format="float", example=150.75),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="tag_ids", type="array", @OA\Items(type="integer")),
     *             @OA\Property(property="expense_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="due_date", type="string", format="date", example="2024-02-15"),
     *             @OA\Property(property="tax_rate", type="number", format="float", example=8.5),
     *             @OA\Property(property="notes", type="string", example="Business expense"),
     *             @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="pending")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Expense updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expense updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Expense"),
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
        $this->logRequest('Update Expense', ['expense_id' => $id]);
        
        if (!$this->hasPermission('expenses.update')) {
            $this->forbidden('Permission denied: expenses.update required');
        }
        
        $data = $this->sanitize($this->request['body']);
        
        // Check if expense exists
        $existingExpense = $this->expenseModel->find($id);
        if (!$existingExpense) {
            $this->notFound('Expense not found');
        }
        
        // Validate fields if provided
        $errors = [];
        if (isset($data['amount']) && $data['amount'] <= 0) {
            $errors['amount'] = 'Amount must be greater than 0.';
        }
        
        if (isset($data['status']) && !in_array($data['status'], ['pending', 'approved', 'rejected'])) {
            $errors['status'] = 'Invalid status.';
        }
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            // Extract tag IDs
            $tagIds = $data['tag_ids'] ?? null;
            unset($data['tag_ids']);
            
            // Recalculate tax if amount or tax_rate changed
            if (isset($data['amount']) || isset($data['tax_rate'])) {
                $amount = floatval($data['amount'] ?? $existingExpense['amount']);
                $taxRate = floatval($data['tax_rate'] ?? $existingExpense['tax_rate']);
                $taxAmount = ($taxRate > 0) ? ($amount * $taxRate / 100) : 0;
                
                $data['tax_amount'] = $taxAmount;
                $data['total_amount'] = $amount + $taxAmount;
            }
            
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $this->expenseModel->update($id, $data);
            
            // Update tags if provided
            if ($tagIds !== null) {
                $this->expenseModel->removeTags($id);
                if (!empty($tagIds)) {
                    $this->expenseModel->addTags($id, $tagIds);
                }
            }
            
            // Handle transaction generation based on status change
            if (isset($data['status'])) {
                if ($data['status'] === 'approved' && $existingExpense['status'] !== 'approved') {
                    $this->expenseModel->generateTransaction($id);
                } elseif ($data['status'] !== 'approved' && $existingExpense['status'] === 'approved') {
                    $this->expenseModel->removeTransaction($id);
                }
            }
            
            $expense = $this->expenseModel->find($id);
            $expense['tags'] = $this->expenseModel->getExpenseTags($id);
            
            $this->success($expense, 'Expense updated successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Update Expense', ['expense_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to update expense');
        }
    }
    
    /**
     * @OA\Delete(
     *     path="/api/v1/expenses/{id}",
     *     summary="Delete an expense",
     *     tags={"Expenses"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Expense ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Expense deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expense deleted successfully"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function delete($id) {
        $this->logRequest('Delete Expense', ['expense_id' => $id]);
        
        if (!$this->hasPermission('expenses.delete')) {
            $this->forbidden('Permission denied: expenses.delete required');
        }
        
        try {
            $existingExpense = $this->expenseModel->find($id);
            if (!$existingExpense) {
                $this->notFound('Expense not found');
            }
            
            $this->expenseModel->delete($id);
            
            $this->success(null, 'Expense deleted successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Delete Expense', ['expense_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to delete expense');
        }
    }
    
    /**
     * @OA\Post(
     *     path="/api/v1/expenses/{id}/approve",
     *     summary="Approve an expense",
     *     tags={"Expenses"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Expense ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Expense approved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expense approved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Expense"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function approve($id) {
        $this->logRequest('Approve Expense', ['expense_id' => $id]);
        
        if (!$this->hasPermission('expenses.approve')) {
            $this->forbidden('Permission denied: expenses.approve required');
        }
        
        try {
            $existingExpense = $this->expenseModel->find($id);
            if (!$existingExpense) {
                $this->notFound('Expense not found');
            }
            
            $this->expenseModel->approve($id);
            
            $expense = $this->expenseModel->find($id);
            $expense['tags'] = $this->expenseModel->getExpenseTags($id);
            
            $this->success($expense, 'Expense approved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Approve Expense', ['expense_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to approve expense');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/expenses/analytics",
     *     summary="Get expense analytics",
     *     tags={"Expenses"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filter by user ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for analytics",
     *         required=false,
     *         @OA\Schema(type="string", enum={"week", "month", "quarter", "year"}, default="month")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Analytics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Analytics retrieved successfully"),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function analytics() {
        $this->logRequest('Get Expense Analytics');
        
        if (!$this->hasPermission('expenses.analytics')) {
            $this->forbidden('Permission denied: expenses.analytics required');
        }
        
        $userId = $this->request['query']['user_id'] ?? null;
        $period = $this->request['query']['period'] ?? 'month';
        
        try {
            $analytics = $this->expenseModel->getAnalytics($userId, $period);
            
            $this->success($analytics, 'Analytics retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Expense Analytics', ['error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve analytics');
        }
    }
} 