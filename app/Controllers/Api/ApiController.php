<?php

require_once __DIR__ . '/../../Services/Logger.php';

/**
 * Base API Controller
 * 
 * @OA\Info(
 *     title="Accounting Panel API",
 *     version="1.0.0",
 *     description="API for  Accounting Panel",
 *     @OA\Contact(
 *         email="admin@example.com"
 *     )
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="ApiKeyAuth",
 *     type="apiKey",
 *     in="header",
 *     name="X-API-Key"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="BearerAuth",
 *     type="http",
 *     scheme="bearer"
 * )
 * 
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="role", type="string", enum={"admin", "user"}, example="user"),
 *     @OA\Property(property="two_factor_enabled", type="boolean", example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 * )
 * 
 * @OA\Schema(
 *     schema="BankAccount",
 *     type="object",
 *     title="BankAccount",
 *     description="Bank account model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Primary Checking"),
 *     @OA\Property(property="bank_name", type="string", example="Bank of America"),
 *     @OA\Property(property="account_type", type="string", enum={"checking", "savings", "business", "investment"}, example="checking"),
 *     @OA\Property(property="account_number_last4", type="string", example="1234"),
 *     @OA\Property(property="routing_number_last4", type="string", example="5678"),
 *     @OA\Property(property="currency", type="string", example="USD"),
 *     @OA\Property(property="swift_code", type="string", example="BOFAUS3N"),
 *     @OA\Property(property="iban", type="string", example="GB29 NWBK 6016 1331 9268 19"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="notes", type="string", example="Primary business account"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *     @OA\Property(property="owner_name", type="string", example="John Doe"),
 *     @OA\Property(property="owner_email", type="string", format="email", example="john@example.com")
 * )
 * 
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     title="Category",
 *     description="Category model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Office Supplies"),
 *     @OA\Property(property="description", type="string", example="All office-related purchases"),
 *     @OA\Property(property="color", type="string", example="#FF5733"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 * )
 * 
 * @OA\Schema(
 *     schema="Tag",
 *     type="object",
 *     title="Tag",
 *     description="Tag model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="urgent"),
 *     @OA\Property(property="color", type="string", example="#FF0000"),
 *     @OA\Property(property="description", type="string", example="Urgent expenses requiring immediate attention"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 * )
 * 
 * @OA\Schema(
 *     schema="Expense",
 *     type="object",
 *     title="Expense", 
 *     description="Expense model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="category_id", type="integer", example=2),
 *     @OA\Property(property="bank_account_id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Office Supplies Purchase"),
 *     @OA\Property(property="description", type="string", example="Monthly office supplies for the team"),
 *     @OA\Property(property="amount", type="number", format="float", example=156.78),
 *     @OA\Property(property="currency", type="string", example="USD"),
 *     @OA\Property(property="expense_date", type="string", format="date", example="2024-01-15"),
 *     @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="pending"),
 *     @OA\Property(property="receipt_url", type="string", example="/uploads/receipts/receipt_123.pdf"),
 *     @OA\Property(property="notes", type="string", example="Purchased from Staples"),
 *     @OA\Property(property="is_reimbursable", type="boolean", example=true),
 *     @OA\Property(property="is_billable", type="boolean", example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *     @OA\Property(property="category_name", type="string", example="Office Supplies"),
 *     @OA\Property(property="user_name", type="string", example="John Doe"),
 *     @OA\Property(property="bank_account_name", type="string", example="Primary Checking")
 * )
 * 
 * @OA\Schema(
 *     schema="CryptoWallet",
 *     type="object",
 *     title="CryptoWallet",
 *     description="Crypto wallet model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Bitcoin Wallet"),
 *     @OA\Property(property="currency", type="string", example="BTC"),
 *     @OA\Property(property="network", type="string", example="mainnet"),
 *     @OA\Property(property="address", type="string", example="1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa"),
 *     @OA\Property(property="balance", type="number", format="float", example=0.025),
 *     @OA\Property(property="wallet_type", type="string", enum={"hot", "cold", "hardware"}, example="hardware"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="notes", type="string", example="Hardware wallet for Bitcoin storage"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00Z"),
 *     @OA\Property(property="owner_name", type="string", example="John Doe"),
 *     @OA\Property(property="owner_email", type="string", format="email", example="john@example.com")
 * )
 * 
 * @OA\Schema(
 *     schema="Pagination",
 *     type="object",
 *     title="Pagination",
 *     description="Pagination metadata",
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="per_page", type="integer", example=20),
 *     @OA\Property(property="total", type="integer", example=150),
 *     @OA\Property(property="total_pages", type="integer", example=8),
 *     @OA\Property(property="has_next", type="boolean", example=true),
 *     @OA\Property(property="has_prev", type="boolean", example=false)
 * )
 * 
 * @OA\Response(
 *     response="Unauthorized",
 *     description="Unauthorized access - API key missing or invalid",
 *     @OA\JsonContent(
 *         @OA\Property(property="success", type="boolean", example=false),
 *         @OA\Property(property="error", type="string", example="Unauthorized"),
 *         @OA\Property(property="message", type="string", example="Invalid or missing API key"),
 *         @OA\Property(property="timestamp", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 *     )
 * )
 * 
 * @OA\Response(
 *     response="Forbidden",
 *     description="Forbidden - Insufficient permissions",
 *     @OA\JsonContent(
 *         @OA\Property(property="success", type="boolean", example=false),
 *         @OA\Property(property="error", type="string", example="Forbidden"),
 *         @OA\Property(property="message", type="string", example="You do not have permission to access this resource"),
 *         @OA\Property(property="timestamp", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 *     )
 * )
 * 
 * @OA\Response(
 *     response="NotFound",
 *     description="Resource not found",
 *     @OA\JsonContent(
 *         @OA\Property(property="success", type="boolean", example=false),
 *         @OA\Property(property="error", type="string", example="Not Found"),
 *         @OA\Property(property="message", type="string", example="The requested resource was not found"),
 *         @OA\Property(property="timestamp", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 *     )
 * )
 * 
 * @OA\Response(
 *     response="ValidationError",
 *     description="Validation Error",
 *     @OA\JsonContent(
 *         @OA\Property(property="success", type="boolean", example=false),
 *         @OA\Property(property="error", type="string", example="Validation Error"),
 *         @OA\Property(property="message", type="string", example="Validation failed"),
 *         @OA\Property(property="errors", type="object", 
 *             @OA\Property(property="field_name", type="string", example="This field is required")
 *         ),
 *         @OA\Property(property="timestamp", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 *     )
 * )
 * 
 * @OA\Response(
 *     response="BadRequest",
 *     description="Bad Request",
 *     @OA\JsonContent(
 *         @OA\Property(property="success", type="boolean", example=false),
 *         @OA\Property(property="error", type="string", example="Bad Request"),
 *         @OA\Property(property="message", type="string", example="The request was invalid or cannot be served"),
 *         @OA\Property(property="timestamp", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 *     )
 * )
 */
class ApiController {
    
    protected $db;
    protected $request;
    
    public function __construct($database) {
        $this->db = $database;
        // Always get fresh request data, as middleware may have updated it
        $this->request = $this->getCurrentRequest();
        
        AppLogger::debug('ApiController initialized', [
            'has_request' => !empty($this->request),
            'has_api_key_in_request' => isset($this->request['api_key']),
            'api_key_id' => $this->request['api_key']['id'] ?? 'none'
        ]);
    }
    
    /**
     * Get current request data
     */
    protected function getCurrentRequest() {
        // First check if middleware has already processed the request
        if (isset($GLOBALS['api_request'])) {
            AppLogger::debug('Using request data from middleware', [
                'has_api_key' => isset($GLOBALS['api_request']['api_key']),
                'api_key_id' => $GLOBALS['api_request']['api_key']['id'] ?? 'none'
            ]);
            return $GLOBALS['api_request'];
        }
        
        // Fallback to creating request data
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $body = [];
        
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = file_get_contents('php://input');
            $body = json_decode($rawBody, true) ?: [];
        } else {
            $body = $_POST;
        }
        
        $request = [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            'query' => $_GET,
            'body' => $body,
            'headers' => getallheaders() ?: []
        ];
        
        AppLogger::debug('Created new request data (no middleware)', [
            'uri' => $request['uri'],
            'method' => $request['method']
        ]);
        
        return $request;
    }
    
    /**
     * Return JSON success response
     */
    protected function success($data = null, $message = 'Success', $statusCode = 200) {
        http_response_code($statusCode);
        
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('c')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }
    
    /**
     * Return JSON error response
     */
    protected function error($message = 'An error occurred', $statusCode = 400, $errors = null) {
        http_response_code($statusCode);
        
        $response = [
            'success' => false,
            'error' => $this->getErrorType($statusCode),
            'message' => $message,
            'timestamp' => date('c')
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit();
    }
    
    /**
     * Return validation error response
     */
    protected function validationError($errors, $message = 'Validation failed') {
        $this->error($message, 422, $errors);
    }
    
    /**
     * Return not found error response
     */
    protected function notFound($message = 'Resource not found') {
        $this->error($message, 404);
    }
    
    /**
     * Return unauthorized error response
     */
    protected function unauthorized($message = 'Unauthorized') {
        $this->error($message, 401);
    }
    
    /**
     * Return forbidden error response
     */
    protected function forbidden($message = 'Forbidden') {
        $this->error($message, 403);
    }
    
    /**
     * Return server error response
     */
    protected function serverError($message = 'Internal server error') {
        $this->error($message, 500);
    }
    
    /**
     * Get error type based on status code
     */
    private function getErrorType($statusCode) {
        $errorTypes = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Validation Error',
            429 => 'Rate Limit Exceeded',
            500 => 'Internal Server Error'
        ];
        
        return $errorTypes[$statusCode] ?? 'Error';
    }
    
    /**
     * Validate required fields
     */
    protected function validateRequired($data, $requiredFields) {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[$field] = "The {$field} field is required.";
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate email format
     */
    protected function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Sanitize input data
     */
    protected function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get pagination parameters
     */
    protected function getPagination() {
        $page = max(1, (int)($this->request['query']['page'] ?? 1));
        $limit = min(100, max(1, (int)($this->request['query']['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        
        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ];
    }
    
    /**
     * Format pagination response
     */
    protected function paginatedResponse($data, $total, $pagination) {
        $totalPages = ceil($total / $pagination['limit']);
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $pagination['page'],
                'per_page' => $pagination['limit'],
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next' => $pagination['page'] < $totalPages,
                'has_prev' => $pagination['page'] > 1
            ]
        ];
    }
    
    /**
     * Log API request
     */
    protected function logRequest($action, $data = []) {
        AppLogger::info("API Request: {$action}", array_merge([
            'method' => $this->request['method'],
            'uri' => $this->request['uri'],
            'user_agent' => $this->request['headers']['User-Agent'] ?? 'unknown'
        ], $data));
    }
    
    /**
     * Get API key data from request
     */
    protected function getApiKeyData() {
        // First check if it's in the current request (set by middleware)
        if (isset($this->request['api_key'])) {
            return $this->request['api_key'];
        }
        
        // Check global request data set by middleware
        if (isset($GLOBALS['api_request']['api_key'])) {
            return $GLOBALS['api_request']['api_key'];
        }
        
        return null;
    }
    
    /**
     * Check if API key has permission
     */
    protected function hasPermission($permission) {
        $apiKeyData = $this->getApiKeyData();
        
        AppLogger::debug('Permission check started', [
            'permission' => $permission,
            'has_api_key_data' => !empty($apiKeyData),
            'api_key_id' => $apiKeyData['id'] ?? 'none',
            'api_key_permissions' => $apiKeyData['permissions'] ?? 'none'
        ]);
        
        if (!$apiKeyData) {
            AppLogger::warning('Permission denied - no API key data found', [
                'permission' => $permission
            ]);
            return false;
        }
        
        require_once __DIR__ . '/../../Models/ApiKey.php';
        $apiKeyModel = new ApiKey($this->db);
        
        $result = $apiKeyModel->hasPermission($apiKeyData, $permission);
        
        AppLogger::debug('Permission check result', [
            'permission' => $permission,
            'result' => $result,
            'api_key_id' => $apiKeyData['id'],
            'api_key_permissions' => $apiKeyData['permissions']
        ]);
        
        return $result;
    }
    
    /**
     * Check if the API key belongs to a superadmin user
     */
    protected function requireSuperAdmin() {
        $apiKeyData = $this->getApiKeyData();
        if (!$apiKeyData) {
            $this->unauthorized('API key data not found');
        }
        
        // Get user data from API key
        require_once __DIR__ . '/../../Models/User.php';
        $userModel = new User($this->db);
        $user = $userModel->find($apiKeyData['user_id']);
        
        if (!$user || $user['role'] !== 'superadmin') {
            $this->forbidden('Access denied. API key management is restricted to super administrators.');
        }
        
        return $user;
    }
} 