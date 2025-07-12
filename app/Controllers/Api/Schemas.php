<?php

/**
 * OpenAPI Schema Definitions
 * 
 * This class contains all the schema and response definitions for the API documentation.
 * Each method defines a reusable component that can be referenced in API endpoints.
 * 
 * @OA\Info(
 *     version="1.0.0",
 *     title="Accounting Panel API",
 *     description="API for Accounting Panel - Manage users, subscriptions, credit cards, transactions, expenses, and more."
 * )
 * 
 * @OA\Server(
 *     url="/api/v1",
 *     description="API Server"
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
 */
class OpenApiDocumentation
{
    /**
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
     */
    public function userSchema() {}
    
    /**
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
     */
    public function bankAccountSchema() {}
    
    /**
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
     */
    public function categorySchema() {}
    
    /**
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
     */
    public function tagSchema() {}
    
    /**
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
     */
    public function expenseSchema() {}
    
    /**
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
     */
    public function cryptoWalletSchema() {}
    
    /**
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
     */
    public function paginationSchema() {}
    
    /**
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
     */
    public function unauthorizedResponse() {}
    
    /**
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
     */
    public function forbiddenResponse() {}
    
    /**
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
     */
    public function notFoundResponse() {}
    
    /**
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
     */
    public function validationErrorResponse() {}
    
    /**
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
    public function badRequestResponse() {}
} 