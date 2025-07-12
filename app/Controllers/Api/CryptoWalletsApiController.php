<?php

require_once __DIR__ . '/ApiController.php';
require_once __DIR__ . '/../../Models/CryptoWallet.php';

/**
 * @OA\Tag(
 *     name="Crypto Wallets",
 *     description="Crypto wallet management operations"
 * )
 */
class CryptoWalletsApiController extends ApiController {
    
    private $cryptoWalletModel;
    
    public function __construct($database) {
        parent::__construct($database);
        $this->cryptoWalletModel = new CryptoWallet($database);
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/crypto-wallets",
     *     summary="Get all crypto wallets",
     *     tags={"Crypto Wallets"},
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
     *         name="network",
     *         in="query",
     *         description="Filter by network",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Crypto wallets retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Crypto wallets retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(ref="#/components/schemas/CryptoWallet")
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
        $this->logRequest('Get Crypto Wallets');
        
        if (!$this->hasPermission('crypto_wallets.read')) {
            $this->forbidden('Permission denied: crypto_wallets.read required');
        }
        
        $pagination = $this->getPagination();
        $currency = $this->request['query']['currency'] ?? null;
        $network = $this->request['query']['network'] ?? null;
        
        try {
            $conditions = [];
            
            if ($currency) {
                $conditions['currency'] = $currency;
            }
            
            if ($network) {
                $conditions['network'] = $network;
            }
            
            $cryptoWallets = $this->cryptoWalletModel->db->select('crypto_wallets', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'crypto_wallets.id',
                'crypto_wallets.name',
                'crypto_wallets.currency',
                'crypto_wallets.network',
                'crypto_wallets.address_masked',
                'crypto_wallets.wallet_type',
                'crypto_wallets.is_active',
                'crypto_wallets.created_at',
                'crypto_wallets.updated_at',
                'users.name(owner_name)',
                'users.email(owner_email)'
            ], array_merge($conditions, [
                'LIMIT' => [$pagination['offset'], $pagination['limit']],
                'ORDER' => ['crypto_wallets.created_at' => 'DESC']
            ]));
            
            $total = $this->cryptoWalletModel->db->count('crypto_wallets', $conditions);
            
            $response = $this->paginatedResponse($cryptoWallets, $total, $pagination);
            $this->success($response, 'Crypto wallets retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Crypto Wallets', ['error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve crypto wallets');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/crypto-wallets/{id}",
     *     summary="Get crypto wallet by ID",
     *     tags={"Crypto Wallets"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Crypto wallet ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Crypto wallet retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Crypto wallet retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/CryptoWallet"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function show($id) {
        $this->logRequest('Get Crypto Wallet', ['crypto_wallet_id' => $id]);
        
        if (!$this->hasPermission('crypto_wallets.read')) {
            $this->forbidden('Permission denied: crypto_wallets.read required');
        }
        
        try {
            $cryptoWallet = $this->cryptoWalletModel->db->get('crypto_wallets', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'crypto_wallets.id',
                'crypto_wallets.name',
                'crypto_wallets.currency',
                'crypto_wallets.network',
                'crypto_wallets.address_masked',
                'crypto_wallets.wallet_type',
                'crypto_wallets.is_active',
                'crypto_wallets.notes',
                'crypto_wallets.created_at',
                'crypto_wallets.updated_at',
                'users.name(owner_name)',
                'users.email(owner_email)'
            ], ['crypto_wallets.id' => $id]);
            
            if (!$cryptoWallet) {
                $this->notFound('Crypto wallet not found');
            }
            
            $this->success($cryptoWallet, 'Crypto wallet retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Crypto Wallet', ['crypto_wallet_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve crypto wallet');
        }
    }
    
    /**
     * @OA\Post(
     *     path="/api/v1/crypto-wallets",
     *     summary="Create a new crypto wallet",
     *     tags={"Crypto Wallets"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "currency", "network", "address", "wallet_type", "user_id"},
     *             @OA\Property(property="name", type="string", example="Main Bitcoin Wallet"),
     *             @OA\Property(property="currency", type="string", example="BTC"),
     *             @OA\Property(property="network", type="string", example="Bitcoin"),
     *             @OA\Property(property="address", type="string", example="1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa"),
     *             @OA\Property(property="wallet_type", type="string", enum={"hot", "cold", "hardware", "exchange"}, example="hardware"),
     *             @OA\Property(property="notes", type="string", example="Hardware wallet for long-term storage"),
     *             @OA\Property(property="user_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Crypto wallet created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Crypto wallet created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/CryptoWallet"),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function store() {
        $this->logRequest('Create Crypto Wallet');
        
        if (!$this->hasPermission('crypto_wallets.create')) {
            $this->forbidden('Permission denied: crypto_wallets.create required');
        }
        
        $data = $this->sanitize($this->request['body']);
        
        // Validate required fields
        $errors = $this->validateRequired($data, ['name', 'currency', 'network', 'address', 'wallet_type', 'user_id']);
        
        // Additional validations
        $validWalletTypes = ['hot', 'cold', 'hardware', 'exchange'];
        if (isset($data['wallet_type']) && !in_array($data['wallet_type'], $validWalletTypes)) {
            $errors['wallet_type'] = 'Wallet type must be one of: ' . implode(', ', $validWalletTypes);
        }
        
        if (isset($data['user_id'])) {
            $userExists = $this->cryptoWalletModel->db->has('users', ['id' => $data['user_id']]);
            if (!$userExists) {
                $errors['user_id'] = 'User does not exist.';
            }
        }
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $walletData = [
                'user_id' => $data['user_id'],
                'name' => $data['name'],
                'currency' => $data['currency'],
                'network' => $data['network'],
                'address' => $data['address'],
                'address_masked' => substr($data['address'], 0, 6) . '...' . substr($data['address'], -4),
                'wallet_type' => $data['wallet_type'],
                'notes' => $data['notes'] ?? null,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $walletId = $this->cryptoWalletModel->create($walletData);
            
            if ($walletId) {
                $cryptoWallet = $this->cryptoWalletModel->find($walletId);
                $this->success($cryptoWallet, 'Crypto wallet created successfully', 201);
            } else {
                $this->serverError('Failed to create crypto wallet');
            }
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Create Crypto Wallet', ['error' => $e->getMessage(), 'data' => $data]);
            $this->serverError('Failed to create crypto wallet');
        }
    }
    
    /**
     * @OA\Put(
     *     path="/api/v1/crypto-wallets/{id}",
     *     summary="Update a crypto wallet",
     *     tags={"Crypto Wallets"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Crypto wallet ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Main Bitcoin Wallet"),
     *             @OA\Property(property="currency", type="string", example="BTC"),
     *             @OA\Property(property="network", type="string", example="Bitcoin"),
     *             @OA\Property(property="wallet_type", type="string", enum={"hot", "cold", "hardware", "exchange"}, example="hardware"),
     *             @OA\Property(property="notes", type="string", example="Hardware wallet for long-term storage"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Crypto wallet updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Crypto wallet updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/CryptoWallet"),
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
        $this->logRequest('Update Crypto Wallet', ['crypto_wallet_id' => $id]);
        
        if (!$this->hasPermission('crypto_wallets.update')) {
            $this->forbidden('Permission denied: crypto_wallets.update required');
        }
        
        $data = $this->sanitize($this->request['body']);
        
        // Check if crypto wallet exists
        $existingWallet = $this->cryptoWalletModel->find($id);
        if (!$existingWallet) {
            $this->notFound('Crypto wallet not found');
        }
        
        // Validate fields if provided
        $errors = [];
        $validWalletTypes = ['hot', 'cold', 'hardware', 'exchange'];
        if (isset($data['wallet_type']) && !in_array($data['wallet_type'], $validWalletTypes)) {
            $errors['wallet_type'] = 'Wallet type must be one of: ' . implode(', ', $validWalletTypes);
        }
        
        if (!empty($errors)) {
            $this->validationError($errors);
        }
        
        try {
            $updateData = array_merge($data, [
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            $result = $this->cryptoWalletModel->update($id, $updateData);
            
            if ($result) {
                $cryptoWallet = $this->cryptoWalletModel->find($id);
                $this->success($cryptoWallet, 'Crypto wallet updated successfully');
            } else {
                $this->serverError('Failed to update crypto wallet');
            }
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Update Crypto Wallet', ['crypto_wallet_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to update crypto wallet');
        }
    }
    
    /**
     * @OA\Delete(
     *     path="/api/v1/crypto-wallets/{id}",
     *     summary="Delete a crypto wallet",
     *     tags={"Crypto Wallets"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Crypto wallet ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Crypto wallet deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Crypto wallet deleted successfully"),
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
        $this->logRequest('Delete Crypto Wallet', ['crypto_wallet_id' => $id]);
        
        if (!$this->hasPermission('crypto_wallets.delete')) {
            $this->forbidden('Permission denied: crypto_wallets.delete required');
        }
        
        try {
            $existingWallet = $this->cryptoWalletModel->find($id);
            if (!$existingWallet) {
                $this->notFound('Crypto wallet not found');
            }
            
            // Check if crypto wallet is used in expenses
            $expenseCount = $this->cryptoWalletModel->db->count('expenses', ['crypto_wallet_id' => $id]);
            if ($expenseCount > 0) {
                $this->error('Cannot delete crypto wallet that is used in expenses. Please reassign or delete the expenses first.', 400);
            }
            
            $result = $this->cryptoWalletModel->delete($id);
            
            if ($result) {
                $this->success(null, 'Crypto wallet deleted successfully');
            } else {
                $this->serverError('Failed to delete crypto wallet');
            }
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Delete Crypto Wallet', ['crypto_wallet_id' => $id, 'error' => $e->getMessage()]);
            $this->serverError('Failed to delete crypto wallet');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/crypto-wallets/by-currency/{currency}",
     *     summary="Get crypto wallets by currency",
     *     tags={"Crypto Wallets"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="currency",
     *         in="path",
     *         description="Currency code",
     *         required=true,
     *         @OA\Schema(type="string", example="BTC")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Crypto wallets retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Crypto wallets retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CryptoWallet")),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function byCurrency($currency) {
        $this->logRequest('Get Crypto Wallets by Currency', ['currency' => $currency]);
        
        if (!$this->hasPermission('crypto_wallets.read')) {
            $this->forbidden('Permission denied: crypto_wallets.read required');
        }
        
        try {
            $cryptoWallets = $this->cryptoWalletModel->db->select('crypto_wallets', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'crypto_wallets.id',
                'crypto_wallets.name',
                'crypto_wallets.currency',
                'crypto_wallets.network',
                'crypto_wallets.address_masked',
                'crypto_wallets.wallet_type',
                'crypto_wallets.is_active',
                'users.name(owner_name)'
            ], [
                'crypto_wallets.currency' => $currency,
                'crypto_wallets.is_active' => true,
                'ORDER' => ['crypto_wallets.name' => 'ASC']
            ]);
            
            $this->success($cryptoWallets, 'Crypto wallets retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Crypto Wallets by Currency', ['currency' => $currency, 'error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve crypto wallets');
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/v1/crypto-wallets/by-network/{network}",
     *     summary="Get crypto wallets by network",
     *     tags={"Crypto Wallets"},
     *     security={{"ApiKeyAuth": {}}, {"BearerAuth": {}}},
     *     @OA\Parameter(
     *         name="network",
     *         in="path",
     *         description="Network name",
     *         required=true,
     *         @OA\Schema(type="string", example="Ethereum")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Crypto wallets retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Crypto wallets retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CryptoWallet")),
     *             @OA\Property(property="timestamp", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function byNetwork($network) {
        $this->logRequest('Get Crypto Wallets by Network', ['network' => $network]);
        
        if (!$this->hasPermission('crypto_wallets.read')) {
            $this->forbidden('Permission denied: crypto_wallets.read required');
        }
        
        try {
            $cryptoWallets = $this->cryptoWalletModel->db->select('crypto_wallets', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'crypto_wallets.id',
                'crypto_wallets.name',
                'crypto_wallets.currency',
                'crypto_wallets.network',
                'crypto_wallets.address_masked',
                'crypto_wallets.wallet_type',
                'crypto_wallets.is_active',
                'users.name(owner_name)'
            ], [
                'crypto_wallets.network' => $network,
                'crypto_wallets.is_active' => true,
                'ORDER' => ['crypto_wallets.name' => 'ASC']
            ]);
            
            $this->success($cryptoWallets, 'Crypto wallets retrieved successfully');
            
        } catch (Exception $e) {
            AppLogger::error('API Error - Get Crypto Wallets by Network', ['network' => $network, 'error' => $e->getMessage()]);
            $this->serverError('Failed to retrieve crypto wallets');
        }
    }
} 