<?php

require_once __DIR__ . '/ApiController.php';
require_once __DIR__ . '/../../Models/BankAccount.php';

/**
 * @OA\Tag(
 *     name="Bank Accounts",
 *     description="Bank account management operations"
 * )
 */
class BankAccountsApiController extends ApiController {
    
    private $bankAccountModel;
    
    public function __construct($database) {
        parent::__construct($database);
        $this->bankAccountModel = new BankAccount($database);
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/bank-accounts",
     *     summary="Get all bank accounts",
     *     tags={"Bank Accounts"},
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
     *         name="currency",
     *         in="query",
     *         description="Filter by currency",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="account_type",
     *         in="query",
     *         description="Filter by account type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"checking", "savings", "business", "investment"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bank accounts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bank accounts retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(ref="#/components/schemas/BankAccount")
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
        $this->logRequest('Get Bank Accounts');
        
        if (!$this->hasPermission('bank_accounts.read')) {
            $this->forbidden('Permission denied: bank_accounts.read required');
        }
        
        $pagination = $this->getPagination();
        $currency = $this->request['query']['currency'] ?? null;
        $accountType = $this->request['query']['account_type'] ?? null;
        
        try {
            $conditions = [];
            
            if ($currency) {
                $conditions['currency'] = $currency;
            }
            
            if ($accountType) {
                $conditions['account_type'] = $accountType;
            }
            
            $bankAccounts = $this->bankAccountModel->db->select('bank_accounts', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'bank_accounts.id',
                'bank_accounts.name',
                'bank_accounts.bank_name',
                'bank_accounts.account_type',
                'bank_accounts.account_number_last4',
                'bank_accounts.currency',
                'bank_accounts.routing_number_last4',
                'bank_accounts.is_active',
                'bank_accounts.created_at',
                'bank_accounts.updated_at',
                'users.name(owner_name)',
                'users.email(owner_email)'
            ], array_merge($conditions, [
                'LIMIT' => [$pagination['offset'], $pagination['limit']],
                'ORDER' => ['bank_accounts.created_at' => 'DESC']
            ]));
            
            $total = $this->bankAccountModel->db->count('bank_accounts', $conditions);
            
            $response = $this->paginatedResponse($bankAccounts, $total, $pagination);
            $this->success($response, 'Bank accounts retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Bank Accounts', ['error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve bank accounts');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/bank-accounts/{id}",
     *     summary="Get bank account by ID",
     *     tags={"Bank Accounts"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Bank account ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bank account retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bank account retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/BankAccount"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function show($id) {
        $this->logRequest('Get Bank Account', ['bank_account_id' => $id]);
        
        if (!$this->hasPermission('bank_accounts.read')) {
            $this->forbidden('Permission denied: bank_accounts.read required');
        }
        
        try {
            $bankAccount = $this->bankAccountModel->db->get('bank_accounts', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'bank_accounts.id',
                'bank_accounts.name',
                'bank_accounts.bank_name',
                'bank_accounts.account_type',
                'bank_accounts.account_number_last4',
                'bank_accounts.currency',
                'bank_accounts.routing_number_last4',
                'bank_accounts.swift_code',
                'bank_accounts.iban',
                'bank_accounts.is_active',
                'bank_accounts.notes',
                'bank_accounts.created_at',
                'bank_accounts.updated_at',
                'users.name(owner_name)',
                'users.email(owner_email)'
            ], ['bank_accounts.id' => $id]);
            
            if (!$bankAccount) {
                $this->notFound('Bank account not found');
            }
            
            $this->success($bankAccount, 'Bank account retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Bank Account', ['bank_account_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve bank account');
        }
    }
    
    /**
     * @OA\Post(
     *     path="/api/v1/bank-accounts",
     *     summary="Create a new bank account",
     *     tags={"Bank Accounts"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "bank_name", "account_type", "account_number", "currency", "user_id"},
     *             @OA\Property(property="name", type="string", example="Primary Checking"),
     *             @OA\Property(property="bank_name", type="string", example="Bank of America"),
     *             @OA\Property(property="account_type", type="string", enum={"checking", "savings", "business", "investment"}, example="checking"),
     *             @OA\Property(property="account_number", type="string", example="1234567890"),
     *             @OA\Property(property="routing_number", type="string", example="123456789"),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="swift_code", type="string", example="BOFAUS3N"),
     *             @OA\Property(property="iban", type="string", example="GB29 NWBK 6016 1331 9268 19"),
     *             @OA\Property(property="notes", type="string", example="Primary business account"),
     *             @OA\Property(property="user_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Bank account created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bank account created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/BankAccount"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function store() {
        $this->logRequest('Create Bank Account');
        
        if (!$this->hasPermission('bank_accounts.create')) {
            $this->forbidden('Permission denied: bank_accounts.create required');
        }
        
        $data = $this->sanitize($this->request['body']);
        
        // Validate required fields
        $errors = $this->validateRequired($data, ['name', 'bank_name', 'account_type', 'account_number', 'currency', 'user_id']);
        
        // Additional validations
        $validAccountTypes = ['checking', 'savings', 'business', 'investment'];
        if (isset($data['account_type']) && !in_array($data['account_type'], $validAccountTypes)) {
            $errors['account_type'] = 'Account type must be one of: ' . implode(', ', $validAccountTypes);
        }
        
        if (isset($data['user_id'])) {
            $userExists = $this->bankAccountModel->db->has('users', ['id' => $data['user_id']]);
            if (!$userExists) {
                $errors['user_id'] = 'User does not exist.';
            }
        }
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $accountData = [
                'user_id' => $data['user_id'],
                'name' => $data['name'],
                'bank_name' => $data['bank_name'],
                'account_type' => $data['account_type'],
                'account_number' => $data['account_number'],
                'account_number_last4' => substr($data['account_number'], -4),
                'routing_number' => $data['routing_number'] ?? null,
                'routing_number_last4' => isset($data['routing_number']) ? substr($data['routing_number'], -4) : null,
                'currency' => $data['currency'],
                'swift_code' => $data['swift_code'] ?? null,
                'iban' => $data['iban'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $accountId = $this->bankAccountModel->create($accountData);
            
            if ($accountId) {
                $bankAccount = $this->bankAccountModel->find($accountId);
                $this->success($bankAccount, 'Bank account created successfully', 201);
            } else {
                $this->serverError('Failed to create bank account');
            }
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Create Bank Account', ['error' => $e->getMessage(), 'data' => $data]);
            $this->serverError('Failed to create bank account');
        }
    }
    
    /**
     * @OA\Put(
     *     path="/api/v1/bank-accounts/{id}",
     *     summary="Update a bank account",
     *     tags={"Bank Accounts"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Bank account ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Primary Checking"),
     *             @OA\Property(property="bank_name", type="string", example="Bank of America"),
     *             @OA\Property(property="account_type", type="string", enum={"checking", "savings", "business", "investment"}, example="checking"),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="swift_code", type="string", example="BOFAUS3N"),
     *             @OA\Property(property="iban", type="string", example="GB29 NWBK 6016 1331 9268 19"),
     *             @OA\Property(property="notes", type="string", example="Primary business account"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bank account updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bank account updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/BankAccount"),
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
        $this->logRequest('Update Bank Account', ['bank_account_id' => $id]);
        
        if (!$this->hasPermission('bank_accounts.update')) {
            $this->forbidden('Permission denied: bank_accounts.update required');
        }
        
        $data = $this->sanitize($this->request['body']);
        
        // Check if bank account exists
        $existingAccount = $this->bankAccountModel->find($id);
        if (!$existingAccount) {
            $this->notFound('Bank account not found');
        }
        
        // Validate fields if provided
        $errors = [];
        $validAccountTypes = ['checking', 'savings', 'business', 'investment'];
        if (isset($data['account_type']) && !in_array($data['account_type'], $validAccountTypes)) {
            $errors['account_type'] = 'Account type must be one of: ' . implode(', ', $validAccountTypes);
        }
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $updateData = array_merge($data, [
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            $result = $this->bankAccountModel->update($id, $updateData);
            
            if ($result) {
                $bankAccount = $this->bankAccountModel->find($id);
                $this->success($bankAccount, 'Bank account updated successfully');
            } else {
                $this->serverError('Failed to update bank account');
            }
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Update Bank Account', ['bank_account_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to update bank account');
        }
    }
    
    /**
     * @OA\Delete(
     *     path="/api/v1/bank-accounts/{id}",
     *     summary="Delete a bank account",
     *     tags={"Bank Accounts"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Bank account ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bank account deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bank account deleted successfully"),
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
        $this->logRequest('Delete Bank Account', ['bank_account_id' => $id]);
        
        if (!$this->hasPermission('bank_accounts.delete')) {
            $this->forbidden('Permission denied: bank_accounts.delete required');
        }
        
        try {
            $existingAccount = $this->bankAccountModel->find($id);
            if (!$existingAccount) {
                $this->notFound('Bank account not found');
            }
            
            // Check if bank account is used in expenses
            $expenseCount = $this->bankAccountModel->db->count('expenses', ['bank_account_id' => $id]);
            if ($expenseCount > 0) {
                $this->error('Cannot delete bank account that is used in expenses. Please reassign or delete the expenses first.', 400);
            }
            
            $result = $this->bankAccountModel->delete($id);
            
            if ($result) {
                $this->success(null, 'Bank account deleted successfully');
            } else {
                $this->serverError('Failed to delete bank account');
            }
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Delete Bank Account', ['bank_account_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to delete bank account');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/bank-accounts/by-currency/{currency}",
     *     summary="Get bank accounts by currency",
     *     tags={"Bank Accounts"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="currency",
     *         in="path",
     *         description="Currency code",
     *         required=true,
     *         @OA\Schema(type="string", example="USD")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bank accounts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bank accounts retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/BankAccount")),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function byCurrency($currency) {
        $this->logRequest('Get Bank Accounts by Currency', ['currency' => $currency]);
        
        if (!$this->hasPermission('bank_accounts.read')) {
            $this->forbidden('Permission denied: bank_accounts.read required');
        }
        
        try {
            $bankAccounts = $this->bankAccountModel->db->select('bank_accounts', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'bank_accounts.id',
                'bank_accounts.name',
                'bank_accounts.bank_name',
                'bank_accounts.account_type',
                'bank_accounts.account_number_last4',
                'bank_accounts.currency',
                'bank_accounts.is_active',
                'users.name(owner_name)'
            ], [
                'bank_accounts.currency' => $currency,
                'bank_accounts.is_active' => true,
                'ORDER' => ['bank_accounts.name' => 'ASC']
            ]);
            
            $this->success($bankAccounts, 'Bank accounts retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Bank Accounts by Currency', ['currency' => $currency, 'error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve bank accounts');
        }
    }
} 