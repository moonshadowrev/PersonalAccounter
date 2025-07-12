<?php

require_once __DIR__ . '/ApiController.php';
require_once __DIR__ . '/../../Models/Transaction.php';

/**
 * @OA\Tag(
 *     name="Transactions",
 *     description="Transaction management operations"
 * )
 */
class TransactionsApiController extends ApiController {
    
    private $transactionModel;
    
    public function __construct($database) {
        parent::__construct($database);
        $this->transactionModel = new Transaction($database);
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/transactions",
     *     summary="Get all transactions",
     *     tags={"Transactions"},
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
     *         name="type",
     *         in="query",
     *         description="Filter by transaction type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"income", "expense"})
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transactions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transactions retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(ref="#/components/schemas/Transaction")
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
        $this->logRequest('Get Transactions');
        
        if (!$this->hasPermission('transactions.read')) {
            $this->forbidden('Permission denied: transactions.read required');
        }
        
        $pagination = $this->getPagination();
        $filters = [];
        
        // Apply filters
        if (isset($this->request['query']['type']) && in_array($this->request['query']['type'], ['income', 'expense'])) {
            $filters['type'] = $this->request['query']['type'];
        }
        
        if (isset($this->request['query']['category']) && !empty($this->request['query']['category'])) {
            $filters['category'] = $this->request['query']['category'];
        }
        
        try {
            $whereClause = array_merge($filters, [
                'LIMIT' => [$pagination['offset'], $pagination['limit']],
                'ORDER' => ['created_at' => 'DESC']
            ]);
            
            $transactions = $this->transactionModel->db->select('transactions', '*', $whereClause);
            $total = $this->transactionModel->db->count('transactions', $filters);
            
            $response = $this->paginatedResponse($transactions, $total, $pagination);
            $this->success($response, 'Transactions retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Transactions', ['error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve transactions');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/transactions/{id}",
     *     summary="Get transaction by ID",
     *     tags={"Transactions"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Transaction ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function show($id) {
        $this->logRequest('Get Transaction', ['transaction_id' => $id]);
        
        if (!$this->hasPermission('transactions.read')) {
            $this->forbidden('Permission denied: transactions.read required');
        }
        
        try {
            $transaction = $this->transactionModel->find($id);
            
            if (!$transaction) {
                $this->notFound('Transaction not found');
            }
            
            $this->success($transaction, 'Transaction retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Transaction', ['transaction_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve transaction');
        }
    }
    
    /**
     * @OA\Post(
     *     path="/api/v1/transactions",
     *     summary="Create a new transaction",
     *     tags={"Transactions"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "amount", "description", "category", "transaction_date"},
     *             @OA\Property(property="type", type="string", enum={"income", "expense"}, example="expense"),
     *             @OA\Property(property="amount", type="number", format="float", example=99.99),
     *             @OA\Property(property="description", type="string", example="Office supplies"),
     *             @OA\Property(property="category", type="string", example="Office Expenses"),
     *             @OA\Property(property="transaction_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="reference_number", type="string", example="REF-001"),
     *             @OA\Property(property="notes", type="string", example="Additional notes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transaction created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function store() {
        $this->logRequest('Create Transaction');
        
        if (!$this->hasPermission('transactions.create')) {
            $this->forbidden('Permission denied: transactions.create required');
        }
        
        $data = $this->sanitize($this->request['body']);
        
        // Validate required fields
        $errors = $this->validateRequired($data, ['type', 'amount', 'description', 'category', 'transaction_date']);
        
        // Additional validations
        if (isset($data['type']) && !in_array($data['type'], ['income', 'expense'])) {
            $errors['type'] = 'Type must be either income or expense.';
        }
        
        if (isset($data['amount'])) {
            $amount = floatval($data['amount']);
            if ($amount <= 0) {
                $errors['amount'] = 'Amount must be greater than 0.';
            }
        }
        
        if (isset($data['transaction_date'])) {
            $transactionDate = strtotime($data['transaction_date']);
            if ($transactionDate === false) {
                $errors['transaction_date'] = 'Please provide a valid transaction date.';
            }
        }
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $transactionData = [
                'type' => $data['type'],
                'amount' => floatval($data['amount']),
                'description' => $data['description'],
                'category' => $data['category'],
                'transaction_date' => date('Y-m-d', strtotime($data['transaction_date'])),
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->transactionModel->db->insert('transactions', $transactionData);
            
            if ($result->rowCount() > 0) {
                $transactionId = $this->transactionModel->db->id();
                $transaction = $this->transactionModel->find($transactionId);
                
                $this->success($transaction, 'Transaction created successfully', 201);
            } else {
                $this->serverError('Failed to create transaction');
            }
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Create Transaction', ['error' => $e->getMessage()]);
            $this->serverError('Failed to create transaction');
        }
    }
    
    /**
     * @OA\Put(
     *     path="/api/v1/transactions/{id}",
     *     summary="Update transaction",
     *     tags={"Transactions"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Transaction ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", enum={"income", "expense"}, example="expense"),
     *             @OA\Property(property="amount", type="number", format="float", example=99.99),
     *             @OA\Property(property="description", type="string", example="Office supplies"),
     *             @OA\Property(property="category", type="string", example="Office Expenses"),
     *             @OA\Property(property="transaction_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="reference_number", type="string", example="REF-001"),
     *             @OA\Property(property="notes", type="string", example="Additional notes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction"),
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
        $this->logRequest('Update Transaction', ['transaction_id' => $id]);
        
        if (!$this->hasPermission('transactions.update')) {
            $this->forbidden('Permission denied: transactions.update required');
        }
        
        // Check if transaction exists
        $existingTransaction = $this->transactionModel->find($id);
        if (!$existingTransaction) {
            $this->notFound('Transaction not found');
        }
        
        $data = $this->sanitize($this->request['body']);
        $errors = [];
        $updateData = ['updated_at' => date('Y-m-d H:i:s')];
        
        // Validate and prepare update data
        if (isset($data['type']) && !empty($data['type'])) {
            if (!in_array($data['type'], ['income', 'expense'])) {
                $errors['type'] = 'Type must be either income or expense.';
            } else {
                $updateData['type'] = $data['type'];
            }
        }
        
        if (isset($data['amount']) && !empty($data['amount'])) {
            $amount = floatval($data['amount']);
            if ($amount <= 0) {
                $errors['amount'] = 'Amount must be greater than 0.';
            } else {
                $updateData['amount'] = $amount;
            }
        }
        
        if (isset($data['description']) && !empty($data['description'])) {
            $updateData['description'] = $data['description'];
        }
        
        if (isset($data['category']) && !empty($data['category'])) {
            $updateData['category'] = $data['category'];
        }
        
        if (isset($data['transaction_date']) && !empty($data['transaction_date'])) {
            $transactionDate = strtotime($data['transaction_date']);
            if ($transactionDate === false) {
                $errors['transaction_date'] = 'Please provide a valid transaction date.';
            } else {
                $updateData['transaction_date'] = date('Y-m-d', $transactionDate);
            }
        }
        
        if (isset($data['reference_number'])) {
            $updateData['reference_number'] = $data['reference_number'];
        }
        
        if (isset($data['notes'])) {
            $updateData['notes'] = $data['notes'];
        }
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $result = $this->transactionModel->db->update('transactions', $updateData, ['id' => $id]);
            
            if ($result->rowCount() > 0) {
                $transaction = $this->transactionModel->find($id);
                $this->success($transaction, 'Transaction updated successfully');
            } else {
                $this->success($existingTransaction, 'No changes made');
            }
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Update Transaction', ['transaction_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to update transaction');
        }
    }
    
    /**
     * @OA\Delete(
     *     path="/api/v1/transactions/{id}",
     *     summary="Delete transaction",
     *     tags={"Transactions"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Transaction ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction deleted successfully"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function delete($id) {
        $this->logRequest('Delete Transaction', ['transaction_id' => $id]);
        
        if (!$this->hasPermission('transactions.delete')) {
            $this->forbidden('Permission denied: transactions.delete required');
        }
        
        // Check if transaction exists
        $transaction = $this->transactionModel->find($id);
        if (!$transaction) {
            $this->notFound('Transaction not found');
        }
        
        try {
            $result = $this->transactionModel->db->delete('transactions', ['id' => $id]);
            
            if ($result->rowCount() > 0) {
                $this->success(null, 'Transaction deleted successfully');
            } else {
                $this->serverError('Failed to delete transaction');
            }
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Delete Transaction', ['transaction_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to delete transaction');
        }
    }
} 