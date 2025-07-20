# PersonalAccounter Feature Wiki v1.0.2

This comprehensive guide covers all features available in PersonalAccounter and provides detailed implementation examples for extending the application.

## 🆕 Version 1.0.2 New Features

### Enhanced Docker & Network Management
- **Improved Network Configuration**: Customizable Docker subnets to avoid conflicts with corporate networks and VPNs
- **CLI Management Tools**: New `control-docker` script for easy container operations without Docker expertise
- **Network Diagnostics**: Advanced `network-check.sh` tool for automated conflict detection and resolution
- **Database Management**: Adminer integration for lightweight database administration
- **Auto-Permission Fixes**: Automatic resolution of log directory permission issues on container startup

### New Management Scripts
- `./control-docker` - Docker container management wrapper
- `./network-check.sh` - Network conflict diagnosis and resolution
- Enhanced `setup.sh` with improved environment variable handling

## Table of Contents

1. [v1.0.2 New Features](#-version-102-new-features)
2. [Dashboard & Analytics](#dashboard--analytics)
3. [Expense Management](#expense-management)
4. [Payment Methods](#payment-methods)
5. [Subscription Management](#subscription-management)
6. [Categories & Tags](#categories--tags)
7. [User Management & Authentication](#user-management--authentication)
8. [Reporting & Export](#reporting--export)
9. [API System](#api-system)
10. [Security Features](#security-features)
11. [Docker & Network Management](#docker--network-management-v102)
12. [Extending the Application](#extending-the-application)

---

## Dashboard & Analytics

### Overview
The dashboard provides a comprehensive financial overview with real-time statistics, visual analytics, and configurable date filtering.

### Key Features

#### **Real-time Financial Statistics**
- **Total Expenses**: Sum of all expenses with optional date filtering
- **Subscription Costs**: Monthly and annual projections for recurring services
- **Payment Method Breakdown**: Distribution across credit cards, bank accounts, and crypto wallets
- **Category Analysis**: Spending patterns by expense categories
- **Status Tracking**: Pending, approved, rejected, and paid expense counts

#### **Visual Analytics**
- **Spending Trends**: Monthly expense trends with interactive charts
- **Category Pie Charts**: Visual representation of expense distribution
- **Subscription Growth**: Timeline of subscription additions and cancellations
- **Payment Method Usage**: Frequency and amount analysis by payment type

#### **Date Filtering**
- **Flexible Periods**: Custom date ranges, preset periods (week, month, quarter, year)
- **Real-time Updates**: AJAX-powered filtering without page reloads
- **Comparative Analysis**: Year-over-year and month-over-month comparisons

### Extending Dashboard Analytics

#### Adding Custom Widgets

Create a new widget by extending the dashboard controller:

```php
// In app/Controllers/DashboardController.php
private function getCustomMetric($userId, $fromDate = null, $toDate = null) {
    $conditions = ['user_id' => $userId];
    
    if ($fromDate && $toDate) {
        $conditions['created_at[>=]'] = $fromDate . ' 00:00:00';
        $conditions['created_at[<=]'] = $toDate . ' 23:59:59';
    }
    
    // Your custom metric calculation
    $result = $this->db->select('your_table', [
        'COUNT(*) as count',
        'SUM(amount) as total'
    ], $conditions);
    
    return [
        'count' => $result[0]['count'] ?? 0,
        'total' => floatval($result[0]['total'] ?? 0)
    ];
}
```

#### Custom Chart Integration

Add new chart types by creating JavaScript chart configurations:

```javascript
// In public/js/main.js
function createCustomChart(data) {
    const ctx = document.getElementById('custom-chart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line', // or 'bar', 'pie', 'doughnut'
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Custom Metric',
                data: data.values,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
```

---

## Expense Management

### Overview
The expense management system provides comprehensive tracking, categorization, and approval workflows for all financial expenditures.

### Core Features

#### **Expense Creation & Editing**
- **Multi-field Support**: Title, description, amount, currency, dates
- **Tax Calculations**: Automatic tax amount calculation with customizable rates
- **File Attachments**: Receipt and invoice uploads with file management
- **Payment Method Association**: Link to credit cards, bank accounts, or crypto wallets
- **Category & Tag Assignment**: Flexible organization system

#### **Approval Workflow**
- **Status Management**: Pending → Approved → Paid workflow
- **Rejection Handling**: Rejected expenses with reason tracking
- **Transaction Generation**: Automatic transaction creation on approval
- **Audit Trail**: Complete history of status changes

#### **Bulk Operations**
- **Import from Excel/CSV**: Column mapping with validation
- **Export Capabilities**: Multiple format support (Excel, CSV, JSON)
- **Batch Actions**: Mass approval, rejection, or status updates
- **Template System**: Standardized import templates

### Advanced Expense Features

#### **Tax Management**
```php
// Tax calculation example
$taxRate = 8.5; // 8.5%
$amount = 100.00;
$taxAmount = ($amount * $taxRate) / 100; // $8.50
$totalAmount = $amount + $taxAmount; // $108.50
```

#### **Recurring Expenses**
Create recurring expense templates:

```php
// In app/Models/Expense.php
public function createRecurringExpense($templateId, $date) {
    $template = $this->getRecurringTemplate($templateId);
    
    $expenseData = [
        'title' => $template['title'],
        'amount' => $template['amount'],
        'category_id' => $template['category_id'],
        'expense_date' => $date,
        'status' => 'pending'
    ];
    
    return $this->create($expenseData);
}
```

### Extending Expense Management

#### **Custom Expense Types**

Add new expense types by extending the enum:

```php
// In database migrations
$table->enum('expense_type', [
    'business',
    'personal', 
    'travel',
    'entertainment',
    'medical',
    'custom_type' // Your new type
])->default('personal');
```

#### **Advanced Approval Workflows**

Implement multi-level approval:

```php
public function requiresMultiLevelApproval($expense) {
    // High-value expenses need manager approval
    if ($expense['amount'] > 1000) {
        return true;
    }
    
    // Certain categories require special approval
    $restrictedCategories = ['travel', 'equipment'];
    if (in_array($expense['category'], $restrictedCategories)) {
        return true;
    }
    
    return false;
}
```

---

## Payment Methods

### Overview
PersonalAccounter supports multiple payment method types with international banking standards and cryptocurrency integration.

### Payment Method Types

#### **Credit Cards**
- **Basic Information**: Card name, bank name, last 4 digits
- **Currency Support**: Multi-currency card tracking
- **Expiration Management**: Expiry date tracking and notifications
- **Usage Analytics**: Spending patterns and limits

#### **Bank Accounts**
- **International Support**: IBAN, SWIFT/BIC codes
- **Account Types**: Checking, savings, business, money market, CD
- **Multi-currency**: Support for 13+ major currencies
- **Validation**: IBAN format validation and checksum verification

#### **Cryptocurrency Wallets**
- **Multi-network Support**: Bitcoin, Ethereum, Polygon, BSC, etc.
- **Address Validation**: Cryptocurrency address format validation
- **Network Detection**: Automatic network identification
- **Balance Tracking**: Integration capabilities for balance monitoring

### Banking Integration Features

#### **IBAN Validation**
```php
// IBAN validation example
public function validateIBAN($iban) {
    // Remove spaces and convert to uppercase
    $iban = strtoupper(str_replace(' ', '', $iban));
    
    // Check length (15-34 characters)
    if (strlen($iban) < 15 || strlen($iban) > 34) {
        return false;
    }
    
    // Check country code and calculate checksum
    $countryCode = substr($iban, 0, 2);
    $checkDigits = substr($iban, 2, 2);
    $accountIdentifier = substr($iban, 4);
    
    // Rearrange for checksum calculation
    $rearranged = $accountIdentifier . $countryCode . '00';
    
    // Convert letters to numbers (A=10, B=11, etc.)
    $numericString = '';
    for ($i = 0; $i < strlen($rearranged); $i++) {
        $char = $rearranged[$i];
        if (ctype_alpha($char)) {
            $numericString .= (ord($char) - ord('A') + 10);
        } else {
            $numericString .= $char;
        }
    }
    
    // Calculate mod 97
    $remainder = bcmod($numericString, '97');
    $calculatedCheck = 98 - $remainder;
    
    return sprintf('%02d', $calculatedCheck) === $checkDigits;
}
```

### Extending Payment Methods

#### **Adding New Payment Types**

Create a new payment method type:

```php
// Create migration for new payment type
class CreateDigitalWalletsTable extends Migration {
    public function up() {
        $this->createTable('digital_wallets', function($table) {
            $table->id();
            $table->integer('user_id')->index();
            $table->string('name'); // PayPal, Venmo, etc.
            $table->string('service_type'); // paypal, venmo, cashapp
            $table->string('account_identifier'); // email or username
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
}
```

#### **Integration with External APIs**

Connect to banking APIs for real-time balance:

```php
class BankAccountService {
    public function getAccountBalance($bankAccount) {
        // Integration with Open Banking API
        $client = new BankingAPIClient([
            'api_key' => Config::get('banking.api_key'),
            'base_url' => Config::get('banking.base_url')
        ]);
        
        try {
            $response = $client->getBalance([
                'account_id' => $bankAccount['external_id'],
                'iban' => $bankAccount['iban']
            ]);
            
            return [
                'balance' => $response['available_balance'],
                'currency' => $response['currency'],
                'last_updated' => now()
            ];
        } catch (Exception $e) {
            AppLogger::error('Failed to fetch bank balance', [
                'account_id' => $bankAccount['id'],
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
```

---

## Subscription Management

### Overview
Track and manage all recurring subscriptions with comprehensive billing cycle support and cost projections.

### Core Features

#### **Subscription Tracking**
- **Service Details**: Name, plan, provider information
- **Billing Cycles**: Monthly, annual, weekly, daily, one-time
- **Cost Management**: Amount tracking with currency conversion
- **Status Lifecycle**: Active, paused, expired, cancelled

#### **Financial Projections**
- **Monthly Costs**: Aggregated monthly spending projections
- **Annual Forecasts**: Yearly cost calculations
- **Budget Planning**: Cost trend analysis and warnings
- **ROI Tracking**: Value assessment for business subscriptions

#### **Renewal Management**
- **Automatic Detection**: Next billing date calculations
- **Notification System**: Upcoming renewal alerts
- **Cancellation Tracking**: Cancelled service monitoring
- **Reactivation**: Easy subscription restart

### Advanced Subscription Features

#### **Cost Calculations**
```php
public function calculateProjectedCosts($subscriptions) {
    $monthlyTotal = 0;
    $annualTotal = 0;
    
    foreach ($subscriptions as $sub) {
        $amount = floatval($sub['amount']);
        
        switch ($sub['billing_cycle']) {
            case 'monthly':
                $monthlyTotal += $amount;
                $annualTotal += $amount * 12;
                break;
            case 'annual':
                $monthlyTotal += $amount / 12;
                $annualTotal += $amount;
                break;
            case 'weekly':
                $monthlyTotal += $amount * 4.33; // Average weeks per month
                $annualTotal += $amount * 52;
                break;
            case 'daily':
                $monthlyTotal += $amount * 30;
                $annualTotal += $amount * 365;
                break;
        }
    }
    
    return [
        'monthly_total' => round($monthlyTotal, 2),
        'annual_total' => round($annualTotal, 2)
    ];
}
```

### Extending Subscription Management

#### **Custom Billing Cycles**

Add new billing frequencies:

```php
// Extend billing cycle enum
$validCycles = [
    'monthly',
    'annual', 
    'weekly',
    'daily',
    'onetime',
    'quarterly', // New cycle
    'biannual'   // New cycle
];
```

#### **Integration with Subscription APIs**

Connect to service APIs for automatic updates:

```php
class SubscriptionSyncService {
    public function syncWithProvider($subscription) {
        switch ($subscription['provider']) {
            case 'spotify':
                return $this->syncSpotify($subscription);
            case 'netflix':
                return $this->syncNetflix($subscription);
            default:
                return $this->manualSync($subscription);
        }
    }
    
    private function syncSpotify($subscription) {
        // Spotify API integration
        $client = new SpotifyAPIClient();
        $account = $client->getSubscription($subscription['external_id']);
        
        return [
            'status' => $account['status'],
            'next_billing_date' => $account['next_payment_due'],
            'amount' => $account['subscription_amount']
        ];
    }
}
```

#### **Smart Notifications**

Implement intelligent notification system:

```php
public function getUpcomingRenewals($daysAhead = 7) {
    $renewals = $this->db->select('subscriptions', '*', [
        'status' => 'active',
        'next_billing_date[<=]' => date('Y-m-d', strtotime("+{$daysAhead} days"))
    ]);
    
    $notifications = [];
    foreach ($renewals as $renewal) {
        $daysUntil = ceil((strtotime($renewal['next_billing_date']) - time()) / 86400);
        
        $notifications[] = [
            'subscription' => $renewal,
            'days_until_renewal' => $daysUntil,
            'urgency' => $daysUntil <= 1 ? 'high' : ($daysUntil <= 3 ? 'medium' : 'low')
        ];
    }
    
    return $notifications;
}
```

---

## Categories & Tags

### Overview
Flexible organization system using hierarchical categories and multi-tag support for comprehensive expense classification.

### Category System

#### **Hierarchical Structure**
- **Parent Categories**: Top-level groupings (Food, Transport, Utilities)
- **Sub-categories**: Detailed classifications (Restaurants, Gas, Electricity)
- **Color Coding**: Visual identification with customizable colors
- **Icon Support**: Font Awesome icons for visual recognition

#### **Default Categories**
Built-in category templates:
- **Business**: Office supplies, travel, meals, equipment
- **Personal**: Food, entertainment, shopping, health
- **Home**: Utilities, maintenance, furniture, insurance
- **Transport**: Fuel, public transport, parking, maintenance

### Tag System

#### **Multi-tag Support**
- **Flexible Tagging**: Multiple tags per expense
- **Tag Hierarchy**: Optional parent-child relationships
- **Auto-suggestions**: Popular tag recommendations
- **Quick Creation**: Instant tag creation during expense entry

#### **Popular Tags Tracking**
- **Usage Analytics**: Most frequently used tags
- **Trending Tags**: Recently popular tags
- **User-specific**: Personalized tag suggestions
- **Global Trends**: System-wide popular tags

### Extending Categories & Tags

#### **Custom Category Types**

Create specialized category systems:

```php
// Business-specific categories
class BusinessCategorySeeder {
    public function seed() {
        $categories = [
            [
                'name' => 'R&D',
                'description' => 'Research and Development',
                'color' => '#3B82F6',
                'icon' => 'fas fa-flask',
                'parent_id' => null
            ],
            [
                'name' => 'Software Licenses',
                'description' => 'Software and SaaS subscriptions',
                'color' => '#10B981',
                'icon' => 'fas fa-code',
                'parent_id' => 1 // R&D parent
            ]
        ];
        
        foreach ($categories as $category) {
            $this->categoryModel->create($category);
        }
    }
}
```

#### **AI-Powered Category Suggestions**

Implement smart categorization:

```php
class SmartCategorizationService {
    public function suggestCategory($expenseTitle, $description = '', $vendor = '') {
        $text = strtolower($expenseTitle . ' ' . $description . ' ' . $vendor);
        
        $rules = [
            'food' => ['restaurant', 'cafe', 'food', 'lunch', 'dinner', 'starbucks'],
            'transport' => ['uber', 'taxi', 'gas', 'fuel', 'parking', 'metro'],
            'utilities' => ['electric', 'water', 'internet', 'phone', 'cable'],
            'entertainment' => ['movie', 'cinema', 'netflix', 'spotify', 'game']
        ];
        
        foreach ($rules as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    return $this->categoryModel->findByName($category);
                }
            }
        }
        
        return null; // No suggestion
    }
}
```

#### **Tag Analytics & Insights**

Advanced tag usage analytics:

```php
public function getTagInsights($userId, $period = '30 days') {
    $fromDate = date('Y-m-d', strtotime("-{$period}"));
    
    $tagUsage = $this->db->query("
        SELECT 
            t.name,
            t.color,
            COUNT(et.expense_id) as usage_count,
            SUM(e.amount) as total_amount,
            AVG(e.amount) as avg_amount
        FROM tags t
        JOIN expense_tags et ON t.id = et.tag_id
        JOIN expenses e ON et.expense_id = e.id
        WHERE e.user_id = ? AND e.expense_date >= ?
        GROUP BY t.id
        ORDER BY usage_count DESC
    ", [$userId, $fromDate])->fetchAll();
    
    return [
        'period' => $period,
        'tag_usage' => $tagUsage,
        'most_expensive_tag' => $tagUsage[0] ?? null,
        'total_tagged_expenses' => array_sum(array_column($tagUsage, 'usage_count'))
    ];
}
```

---

## User Management & Authentication

### Overview
Comprehensive user management system with role-based access control and advanced security features.

### Authentication Features

#### **Two-Factor Authentication (2FA)**
- **Google Authenticator**: TOTP-based authentication
- **Backup Codes**: Recovery codes for account access
- **QR Code Generation**: Easy setup with mobile apps
- **Forced 2FA**: Admin-configurable mandatory 2FA

#### **Role Management**
- **Admin Role**: Full system access except user management
- **Superadmin Role**: Complete system control including user management
- **Permission System**: Granular API permission control
- **Role Inheritance**: Hierarchical permission structure

#### **Session Security**
- **Secure Cookies**: HttpOnly, Secure, SameSite attributes
- **Session Regeneration**: ID regeneration on authentication
- **Timeout Management**: Configurable session lifetimes
- **Concurrent Session Control**: Multiple session management

### Security Implementation

#### **Login Protection**
```php
// Rate limiting implementation
private function isRateLimited() {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    
    $timeout = Config::get('auth.login_attempts_timeout', 300);
    $_SESSION['login_attempts'] = array_filter(
        $_SESSION['login_attempts'], 
        function($timestamp) use ($timeout) {
            return $timestamp > (time() - $timeout);
        }
    );
    
    $limit = Config::get('auth.login_attempts_limit', 5);
    return count($_SESSION['login_attempts']) >= $limit;
}
```

#### **Password Security**
- **Bcrypt Hashing**: Strong password hashing with salt
- **Timing Attack Prevention**: Consistent verification timing
- **Password Policies**: Configurable complexity requirements
- **History Tracking**: Prevent password reuse

### Extending User Management

#### **Custom User Fields**

Add additional user profile fields:

```php
// Migration for extended user profile
class AddUserProfileFields extends Migration {
    public function up() {
        $this->execute("
            ALTER TABLE users 
            ADD COLUMN department VARCHAR(100),
            ADD COLUMN employee_id VARCHAR(50),
            ADD COLUMN manager_id INT NULL,
            ADD COLUMN phone VARCHAR(20),
            ADD COLUMN timezone VARCHAR(50) DEFAULT 'UTC'
        ");
    }
}
```

#### **LDAP Integration**

Integrate with Active Directory:

```php
class LDAPAuthService {
    public function authenticate($username, $password) {
        $ldap = ldap_connect(Config::get('ldap.server'));
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        
        $userDN = "uid={$username}," . Config::get('ldap.base_dn');
        
        if (ldap_bind($ldap, $userDN, $password)) {
            // Fetch user information
            $search = ldap_search($ldap, Config::get('ldap.base_dn'), "(uid={$username})");
            $userInfo = ldap_get_entries($ldap, $search);
            
            // Create or update local user
            return $this->syncLocalUser($userInfo[0]);
        }
        
        return false;
    }
}
```

#### **Single Sign-On (SSO)**

Implement SAML SSO:

```php
class SAMLService {
    public function handleSSOResponse($samlResponse) {
        // Validate SAML response
        $assertion = $this->validateSAMLResponse($samlResponse);
        
        if ($assertion) {
            $userAttributes = $this->extractUserAttributes($assertion);
            
            return $this->createUserSession([
                'email' => $userAttributes['email'],
                'name' => $userAttributes['displayName'],
                'role' => $this->mapSAMLRole($userAttributes['role'])
            ]);
        }
        
        return false;
    }
}
```

---

## Reporting & Export

### Overview
Comprehensive reporting system with real-time analytics, flexible export options, and customizable report generation.

### Report Types

#### **Financial Reports**
- **Expense Reports**: Detailed expense breakdowns with filtering
- **Subscription Reports**: Recurring cost analysis and projections
- **Payment Method Reports**: Usage and spending by payment type
- **Category Reports**: Spending patterns by category and tag

#### **Analytics Reports**
- **Trend Analysis**: Month-over-month and year-over-year comparisons
- **Budget Variance**: Actual vs. planned spending analysis
- **ROI Reports**: Return on investment for business expenses
- **Tax Reports**: Tax-deductible expense summaries

#### **Operational Reports**
- **User Activity**: User engagement and system usage
- **Audit Trails**: Complete transaction and change histories
- **System Health**: Performance and error monitoring
- **Data Quality**: Missing or inconsistent data identification

### Export Capabilities

#### **Multiple Formats**
- **Excel (XLSX)**: Full formatting with charts and pivot tables
- **CSV**: Universal format for data analysis
- **JSON**: API-friendly structured data
- **PDF**: Formatted reports for sharing and archiving

#### **Custom Report Builder**
```php
class ReportBuilder {
    public function createCustomReport($config) {
        $report = new Report();
        
        // Apply filters
        foreach ($config['filters'] as $filter) {
            $report->addFilter($filter['field'], $filter['operator'], $filter['value']);
        }
        
        // Select columns
        $report->selectColumns($config['columns']);
        
        // Apply grouping
        if (isset($config['group_by'])) {
            $report->groupBy($config['group_by']);
        }
        
        // Apply sorting
        foreach ($config['sort'] as $sort) {
            $report->orderBy($sort['field'], $sort['direction']);
        }
        
        return $report->generate();
    }
}
```

### Extending Reporting

#### **Custom Chart Types**

Add new visualization types:

```php
class CustomChartGenerator {
    public function generateSankeyDiagram($expenseFlow) {
        // Sankey diagram for expense flow visualization
        $nodes = [];
        $links = [];
        
        foreach ($expenseFlow as $flow) {
            $nodes[] = ['id' => $flow['source'], 'name' => $flow['source_name']];
            $nodes[] = ['id' => $flow['target'], 'name' => $flow['target_name']];
            
            $links[] = [
                'source' => $flow['source'],
                'target' => $flow['target'],
                'value' => $flow['amount']
            ];
        }
        
        return [
            'type' => 'sankey',
            'data' => [
                'nodes' => array_unique($nodes, SORT_REGULAR),
                'links' => $links
            ]
        ];
    }
}
```

#### **Scheduled Reports**

Implement automated report generation:

```php
class ScheduledReportService {
    public function scheduleReport($config) {
        $schedule = [
            'report_id' => $config['report_id'],
            'frequency' => $config['frequency'], // daily, weekly, monthly
            'recipients' => $config['recipients'],
            'format' => $config['format'],
            'filters' => json_encode($config['filters'])
        ];
        
        $this->db->insert('scheduled_reports', $schedule);
        
        // Add to cron scheduler
        $this->addToCronSchedule($schedule);
    }
    
    public function generateScheduledReport($scheduleId) {
        $schedule = $this->getSchedule($scheduleId);
        $report = $this->reportBuilder->generate($schedule['filters']);
        
        // Export in specified format
        $file = $this->exportReport($report, $schedule['format']);
        
        // Send to recipients
        $this->emailReport($file, $schedule['recipients']);
    }
}
```

---

## API System

### Overview
Comprehensive RESTful API with OpenAPI documentation, authentication, and rate limiting.

### API Features

#### **Authentication Methods**
- **API Keys**: Header-based authentication with X-API-Key
- **Bearer Tokens**: OAuth-style token authentication
- **Permission System**: Granular endpoint access control
- **Rate Limiting**: Configurable request limits per API key

#### **Endpoint Coverage**
- **Expenses**: CRUD operations with filtering and search
- **Subscriptions**: Full subscription management
- **Categories/Tags**: Organization system management
- **Reports**: Analytics and export endpoints
- **Users**: User management (superadmin only)

#### **Documentation**
- **OpenAPI 3.0**: Complete API specification
- **Swagger UI**: Interactive API documentation
- **Code Examples**: Sample requests and responses
- **Authentication Guide**: Implementation examples

### API Implementation Examples

#### **Creating Custom Endpoints**

Add new API endpoints:

```php
// In app/Controllers/Api/CustomApiController.php
class CustomApiController extends ApiController {
    
    /**
     * @OA\Get(
     *     path="/api/v1/custom/analytics",
     *     summary="Get custom analytics",
     *     tags={"Custom"},
     *     security={{"ApiKeyAuth": {}}},
     *     @OA\Response(response=200, description="Analytics retrieved successfully")
     * )
     */
    public function getAnalytics() {
        if (!$this->hasPermission('analytics.read')) {
            $this->forbidden('Permission denied');
        }
        
        $analytics = $this->calculateCustomAnalytics();
        $this->success($analytics, 'Analytics retrieved successfully');
    }
    
    private function calculateCustomAnalytics() {
        // Your custom analytics logic
        return [
            'metric1' => 123,
            'metric2' => 456,
            'timestamp' => date('c')
        ];
    }
}
```

#### **Webhook Implementation**

Add webhook support for real-time integrations:

```php
class WebhookService {
    public function triggerWebhook($event, $data) {
        $webhooks = $this->getActiveWebhooks($event);
        
        foreach ($webhooks as $webhook) {
            $this->sendWebhook($webhook, $event, $data);
        }
    }
    
    private function sendWebhook($webhook, $event, $data) {
        $payload = [
            'event' => $event,
            'data' => $data,
            'timestamp' => time(),
            'signature' => $this->generateSignature($webhook['secret'], $data)
        ];
        
        $ch = curl_init($webhook['url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Webhook-Signature: ' . $payload['signature']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        $this->logWebhookDelivery($webhook['id'], $httpCode, $response);
    }
}
```

### API Security & Extensions

#### **Advanced Rate Limiting**

Implement sophisticated rate limiting:

```php
class AdvancedRateLimiter {
    public function checkRateLimit($apiKey, $endpoint) {
        $limits = $this->getRateLimits($apiKey, $endpoint);
        
        foreach ($limits as $window => $limit) {
            $usage = $this->getUsage($apiKey, $endpoint, $window);
            
            if ($usage >= $limit) {
                return [
                    'allowed' => false,
                    'reset_time' => $this->getResetTime($window),
                    'limit' => $limit,
                    'remaining' => 0
                ];
            }
        }
        
        return [
            'allowed' => true,
            'limit' => $limits['hour'],
            'remaining' => $limits['hour'] - $this->getUsage($apiKey, $endpoint, 'hour')
        ];
    }
}
```

---

## Security Features

### Overview
Multi-layered security implementation with protection against common vulnerabilities and advanced threat detection.

### Security Layers

#### **Input Security**
- **CSRF Protection**: Token-based cross-site request forgery protection
- **XSS Prevention**: Input sanitization and output encoding
- **SQL Injection**: Prepared statements and parameterized queries
- **File Upload Security**: Type validation and sandboxing

#### **Authentication Security**
- **Password Hashing**: Bcrypt with automatic salt generation
- **Session Security**: Secure cookie attributes and regeneration
- **2FA Implementation**: Time-based one-time passwords
- **Account Lockout**: Automatic protection against brute force

#### **API Security**
- **Rate Limiting**: Request throttling per API key
- **Input Validation**: Strict parameter validation
- **Authentication**: Multiple authentication methods
- **Audit Logging**: Comprehensive request logging

### Security Implementation

#### **CSRF Token Generation**
```php
class CSRFProtection {
    public static function generateToken() {
        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_token'];
    }
    
    public static function validateToken($token) {
        $sessionToken = $_SESSION['_token'] ?? '';
        return hash_equals($sessionToken, $token);
    }
}
```

#### **Input Sanitization**
```php
class InputSanitizer {
    public static function sanitizeInput($input, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
}
```

### Security Extensions

#### **Advanced Threat Detection**

Implement suspicious activity detection:

```php
class ThreatDetectionService {
    public function analyzeRequest($request) {
        $riskScore = 0;
        $threats = [];
        
        // Check for SQL injection patterns
        if ($this->containsSQLInjection($request['body'])) {
            $riskScore += 50;
            $threats[] = 'sql_injection_attempt';
        }
        
        // Check for unusual request patterns
        if ($this->isUnusualRequestPattern($request)) {
            $riskScore += 30;
            $threats[] = 'unusual_pattern';
        }
        
        // Check IP reputation
        if ($this->isMaliciousIP($request['ip'])) {
            $riskScore += 40;
            $threats[] = 'malicious_ip';
        }
        
        return [
            'risk_score' => $riskScore,
            'threats' => $threats,
            'action' => $this->determineAction($riskScore)
        ];
    }
}
```

#### **Encryption for Sensitive Data**

Implement field-level encryption:

```php
class FieldEncryption {
    private $key;
    
    public function __construct() {
        $this->key = Config::get('encryption.key');
    }
    
    public function encrypt($data) {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $this->key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    public function decrypt($encryptedData) {
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $this->key, 0, $iv);
    }
}
```

---

## Docker & Network Management (v1.0.2)

### Enhanced Network Configuration

PersonalAccounter v1.0.2 introduces advanced Docker networking capabilities to ensure compatibility across different environments and avoid network conflicts.

#### Customizable Network Settings

The application now supports customizable Docker subnet configuration through environment variables:

```env
# Docker Network Configuration
DOCKER_SUBNET=172.28.0.0/24
DOCKER_GATEWAY=172.28.0.1
```

#### Benefits:
- **Conflict Avoidance**: Prevents conflicts with corporate VPNs and existing networks
- **Flexible Configuration**: Easy to customize for different deployment environments
- **Auto-Detection**: Automatic conflict detection and resolution capabilities

### CLI Management Tools

#### control-docker Script

The new `control-docker` script provides an easy-to-use interface for managing Docker containers without requiring Docker expertise:

```bash
# User management
./control-docker user list
./control-docker user create "John Doe" "john@example.com" "password" "admin"

# Database operations
./control-docker migrate run
./control-docker db status

# Interactive access
./control-docker shell
```

#### Features:
- **Automatic Service Management**: Starts CLI service automatically when needed
- **Cross-Platform Compatibility**: Works on Linux, macOS, and Windows
- **Error Handling**: Comprehensive error checking and user feedback

### Network Diagnostic Tools

#### network-check.sh Script

Advanced network troubleshooting tool for diagnosing and resolving Docker network issues:

```bash
# Check for conflicts
./network-check.sh check

# Show available options
./network-check.sh suggest

# Auto-fix issues (maximum compatibility)
./network-check.sh auto

# Use custom subnet
./network-check.sh subnet 172.29.0.0/24 172.29.0.1

# Clean up existing networks
./network-check.sh fix
```

#### Capabilities:
- **Conflict Detection**: Identifies existing network conflicts
- **Auto-Resolution**: Automatically resolves common network issues
- **Custom Configuration**: Supports custom subnet configuration
- **Cross-Platform**: Works on different operating systems with appropriate tooling

### Database Management with Adminer

#### Lightweight Database Administration

PersonalAccounter v1.0.2 replaces phpMyAdmin with Adminer for better compatibility and performance:

- **URL**: http://localhost:8080
- **Features**:
  - Lightweight and fast
  - Better cross-platform compatibility
  - Modern interface
  - Advanced SQL capabilities

#### Connection Settings:
```
Server: database
Username: accounting_user (or root)
Password: [from .env file]
Database: accounting_panel
```

### Auto-Permission Fixes

The application now automatically resolves common permission issues that can occur with Docker volume mounting:

#### Startup Script Enhancements:
```bash
# Automatic permission fixing on container startup
chown -R www-data:www-data /var/www/html/logs
chmod -R 755 /var/www/html/logs
```

#### Benefits:
- **Seamless Volume Mounting**: Works across different host systems
- **No Manual Intervention**: Automatically fixes permission issues
- **Persistent Storage**: Maintains data integrity across container restarts

---

## Extending the Application

### Architecture Guidelines

#### **MVC Structure**
- **Models**: Handle data logic and database operations
- **Views**: Template files for user interface
- **Controllers**: Business logic and request handling
- **Services**: Reusable business logic components

#### **Database Design**
- **Migrations**: Version-controlled database changes
- **Foreign Keys**: Maintain referential integrity
- **Indexing**: Optimize query performance
- **Normalization**: Reduce data redundancy

### Development Patterns

#### **Creating New Features**

1. **Create Migration**
```php
php control make migration create_custom_feature_table
```

2. **Create Model**
```php
class CustomFeature extends Model {
    protected $table = 'custom_features';
    
    public function getWithRelations() {
        return $this->db->select($this->table, [
            '[>]users' => ['user_id' => 'id']
        ], [
            'custom_features.*',
            'users.name(user_name)'
        ]);
    }
}
```

3. **Create Controller**
```php
class CustomFeatureController extends Controller {
    private $customFeatureModel;
    
    public function __construct($db) {
        $this->customFeatureModel = new CustomFeature($db);
    }
    
    public function index() {
        $features = $this->customFeatureModel->getWithRelations();
        $this->view('custom-features/index', ['features' => $features]);
    }
}
```

4. **Add Routes**
```php
// In app/Routes/web.php
$router->get('/custom-features', function() use ($customFeatureController) {
    $customFeatureController->index();
});
```

#### **Plugin Architecture**

Create a plugin system:

```php
class PluginManager {
    private $plugins = [];
    
    public function registerPlugin($name, $plugin) {
        $this->plugins[$name] = $plugin;
    }
    
    public function executeHook($hookName, $data = null) {
        foreach ($this->plugins as $plugin) {
            if (method_exists($plugin, $hookName)) {
                $data = $plugin->$hookName($data);
            }
        }
        return $data;
    }
}

// Example plugin
class CustomAnalyticsPlugin {
    public function beforeExpenseCreate($expenseData) {
        // Add custom analytics tracking
        $this->trackExpenseCreation($expenseData);
        return $expenseData;
    }
    
    public function afterExpenseCreate($expense) {
        // Send to external analytics service
        $this->sendToAnalytics($expense);
        return $expense;
    }
}
```

### Integration Examples

#### **External Service Integration**

```php
class ExternalServiceConnector {
    public function connectToQuickBooks($credentials) {
        $client = new QuickBooksClient($credentials);
        
        // Sync chart of accounts
        $categories = $client->getChartOfAccounts();
        $this->syncCategories($categories);
        
        // Import transactions
        $transactions = $client->getTransactions();
        $this->importTransactions($transactions);
    }
    
    private function syncCategories($externalCategories) {
        foreach ($externalCategories as $extCategory) {
            $category = $this->categoryModel->findByExternalId($extCategory['id']);
            
            if (!$category) {
                $this->categoryModel->create([
                    'name' => $extCategory['name'],
                    'external_id' => $extCategory['id'],
                    'external_source' => 'quickbooks'
                ]);
            }
        }
    }
}
```

This comprehensive feature documentation provides a thorough understanding of PersonalAccounter's capabilities and extensive examples for extending the application. Each section includes practical implementation examples and best practices for development. 