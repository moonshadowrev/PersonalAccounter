<?php

/**
 * Database Faker - Generate realistic test data
 * 
 * Usage:
 * php database/faker.php --users=10 --cards=20 --subscriptions=50
 * php database/faker.php --reset (clears all data first)
 * php database/faker.php --help
 */

// Define APP_RAN to allow bootstrap loading
define('APP_RAN', true);

// Bootstrap the application to get database connection
try {
    $services = require_once __DIR__ . '/../bootstrap/app.php';
    $database = $services['database'];
} catch (Exception $e) {
    echo "Error: Could not bootstrap application: " . $e->getMessage() . "\n";
    echo "Make sure your .env file is configured properly.\n";
    exit(1);
}

require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/CreditCard.php';
require_once __DIR__ . '/../app/Models/Subscription.php';
require_once __DIR__ . '/../app/Models/Category.php';
require_once __DIR__ . '/../app/Models/Tag.php';
require_once __DIR__ . '/../app/Models/BankAccount.php';
require_once __DIR__ . '/../app/Models/CryptoWallet.php';
require_once __DIR__ . '/../app/Models/Expense.php';

class DatabaseFaker {
    
    private $db;
    private $userModel;
    private $creditCardModel;
    private $subscriptionModel;
    private $categoryModel;
    private $tagModel;
    private $bankAccountModel;
    private $cryptoWalletModel;
    private $expenseModel;
    
    // Realistic data arrays
    private $firstNames = [
        'John', 'Jane', 'Michael', 'Sarah', 'David', 'Emily', 'Robert', 'Jessica',
        'William', 'Ashley', 'James', 'Amanda', 'Christopher', 'Stephanie', 'Daniel',
        'Melissa', 'Matthew', 'Nicole', 'Anthony', 'Elizabeth', 'Mark', 'Helen',
        'Donald', 'Deborah', 'Steven', 'Rachel', 'Paul', 'Carolyn', 'Andrew', 'Janet'
    ];
    
    private $lastNames = [
        'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis',
        'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson',
        'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Perez', 'Thompson',
        'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson'
    ];
    
    private $subscriptionServices = [
        // Streaming Services
        ['name' => 'Netflix', 'category' => 'Entertainment', 'typical_cost' => [9.99, 15.99, 19.99]],
        ['name' => 'Disney+', 'category' => 'Entertainment', 'typical_cost' => [7.99, 13.99]],
        ['name' => 'HBO Max', 'category' => 'Entertainment', 'typical_cost' => [14.99, 19.99]],
        ['name' => 'Amazon Prime Video', 'category' => 'Entertainment', 'typical_cost' => [8.99, 12.99]],
        ['name' => 'Hulu', 'category' => 'Entertainment', 'typical_cost' => [5.99, 11.99, 17.99]],
        ['name' => 'Apple TV+', 'category' => 'Entertainment', 'typical_cost' => [4.99, 6.99]],
        ['name' => 'Paramount+', 'category' => 'Entertainment', 'typical_cost' => [5.99, 11.99]],
        ['name' => 'Peacock Premium', 'category' => 'Entertainment', 'typical_cost' => [4.99, 9.99]],
        
        // Music Services
        ['name' => 'Spotify Premium', 'category' => 'Music', 'typical_cost' => [9.99, 14.99, 19.99]],
        ['name' => 'Apple Music', 'category' => 'Music', 'typical_cost' => [9.99, 14.99]],
        ['name' => 'YouTube Music', 'category' => 'Music', 'typical_cost' => [9.99, 14.99]],
        ['name' => 'Amazon Music Unlimited', 'category' => 'Music', 'typical_cost' => [7.99, 9.99]],
        ['name' => 'Tidal', 'category' => 'Music', 'typical_cost' => [9.99, 19.99]],
        
        // Software & Productivity
        ['name' => 'Microsoft 365', 'category' => 'Productivity', 'typical_cost' => [6.99, 12.99, 22.99]],
        ['name' => 'Adobe Creative Cloud', 'category' => 'Design', 'typical_cost' => [20.99, 52.99, 79.99]],
        ['name' => 'Canva Pro', 'category' => 'Design', 'typical_cost' => [12.99, 14.99]],
        ['name' => 'Notion', 'category' => 'Productivity', 'typical_cost' => [8.00, 16.00]],
        ['name' => 'Slack', 'category' => 'Communication', 'typical_cost' => [6.67, 12.50]],
        ['name' => 'Zoom Pro', 'category' => 'Communication', 'typical_cost' => [14.99, 19.99]],
        ['name' => 'Dropbox Plus', 'category' => 'Storage', 'typical_cost' => [9.99, 16.58]],
        ['name' => 'Google Workspace', 'category' => 'Productivity', 'typical_cost' => [6.00, 12.00, 18.00]],
        
        // Development & Tech
        ['name' => 'GitHub Pro', 'category' => 'Development', 'typical_cost' => [4.00, 21.00]],
        ['name' => 'JetBrains IntelliJ', 'category' => 'Development', 'typical_cost' => [16.90, 53.90]],
        ['name' => 'AWS', 'category' => 'Cloud', 'typical_cost' => [25.00, 50.00, 100.00, 250.00]],
        ['name' => 'DigitalOcean', 'category' => 'Cloud', 'typical_cost' => [5.00, 10.00, 20.00, 40.00]],
        ['name' => 'Vercel Pro', 'category' => 'Cloud', 'typical_cost' => [20.00, 50.00]],
        ['name' => 'Netlify Pro', 'category' => 'Cloud', 'typical_cost' => [19.00, 99.00]],
        
        // News & Learning
        ['name' => 'New York Times', 'category' => 'News', 'typical_cost' => [4.25, 17.00]],
        ['name' => 'Wall Street Journal', 'category' => 'News', 'typical_cost' => [12.00, 38.99]],
        ['name' => 'Medium', 'category' => 'Reading', 'typical_cost' => [5.00, 50.00]],
        ['name' => 'Coursera Plus', 'category' => 'Education', 'typical_cost' => [39.00, 59.90]],
        ['name' => 'Udemy Pro', 'category' => 'Education', 'typical_cost' => [29.99, 57.99]],
        ['name' => 'LinkedIn Premium', 'category' => 'Professional', 'typical_cost' => [29.99, 59.99]],
        
        // Health & Fitness
        ['name' => 'Peloton App', 'category' => 'Fitness', 'typical_cost' => [12.99, 39.00]],
        ['name' => 'MyFitnessPal Premium', 'category' => 'Health', 'typical_cost' => [9.99, 19.99]],
        ['name' => 'Headspace', 'category' => 'Wellness', 'typical_cost' => [5.83, 12.99]],
        ['name' => 'Calm', 'category' => 'Wellness', 'typical_cost' => [6.25, 14.99]],
        
        // Gaming
        ['name' => 'Xbox Game Pass', 'category' => 'Gaming', 'typical_cost' => [9.99, 14.99]],
        ['name' => 'PlayStation Plus', 'category' => 'Gaming', 'typical_cost' => [9.99, 14.99, 17.99]],
        ['name' => 'Nintendo Switch Online', 'category' => 'Gaming', 'typical_cost' => [3.99, 7.99]],
        ['name' => 'Steam Deck', 'category' => 'Gaming', 'typical_cost' => [399.00, 529.00, 649.00]],
        
        // Security & VPN
        ['name' => '1Password', 'category' => 'Security', 'typical_cost' => [2.99, 7.99]],
        ['name' => 'NordVPN', 'category' => 'Security', 'typical_cost' => [3.71, 11.95]],
        ['name' => 'ExpressVPN', 'category' => 'Security', 'typical_cost' => [8.32, 12.95]],
        ['name' => 'LastPass Premium', 'category' => 'Security', 'typical_cost' => [3.00, 4.00]]
    ];
    
    private $creditCardTypes = [
        'Visa' => ['4000', '4111', '4532', '4916'],
        'Mastercard' => ['5555', '5105', '5200', '5454'],
        'American Express' => ['3782', '3714', '3787'],
        'Discover' => ['6011', '6500', '6501']
    ];
    
    private $currencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY'];
    private $billingCycles = ['monthly', 'yearly', 'one-time', 'weekly', 'quarterly'];
    private $statuses = ['active', 'expired', 'cancelled'];
    
    // New data arrays for expense system
    private $categories = [
        ['name' => 'Office Supplies', 'color' => '#007bff', 'icon' => 'fas fa-briefcase'],
        ['name' => 'Travel', 'color' => '#28a745', 'icon' => 'fas fa-plane'],
        ['name' => 'Meals & Entertainment', 'color' => '#fd7e14', 'icon' => 'fas fa-utensils'],
        ['name' => 'Professional Services', 'color' => '#6610f2', 'icon' => 'fas fa-handshake'],
        ['name' => 'Software & Subscriptions', 'color' => '#17a2b8', 'icon' => 'fas fa-laptop'],
        ['name' => 'Marketing', 'color' => '#e83e8c', 'icon' => 'fas fa-bullhorn'],
        ['name' => 'Utilities', 'color' => '#ffc107', 'icon' => 'fas fa-bolt'],
        ['name' => 'Equipment', 'color' => '#dc3545', 'icon' => 'fas fa-tools'],
        ['name' => 'Training', 'color' => '#20c997', 'icon' => 'fas fa-graduation-cap'],
        ['name' => 'Miscellaneous', 'color' => '#6c757d', 'icon' => 'fas fa-question']
    ];
    
    private $tags = [
        ['name' => 'Business', 'color' => '#007bff'],
        ['name' => 'Personal', 'color' => '#28a745'],
        ['name' => 'Emergency', 'color' => '#dc3545'],
        ['name' => 'Recurring', 'color' => '#ffc107'],
        ['name' => 'One-time', 'color' => '#17a2b8'],
        ['name' => 'Reimbursable', 'color' => '#6610f2'],
        ['name' => 'Tax Deductible', 'color' => '#fd7e14'],
        ['name' => 'Client Project', 'color' => '#e83e8c'],
        ['name' => 'Research', 'color' => '#20c997'],
        ['name' => 'Maintenance', 'color' => '#6c757d']
    ];
    
    private $bankNames = [
        'Chase Bank', 'Bank of America', 'Wells Fargo', 'Citibank', 'US Bank',
        'PNC Bank', 'Capital One', 'TD Bank', 'Truist Bank', 'Fifth Third Bank'
    ];
    
    private $accountTypes = ['checking', 'savings', 'business_checking', 'money_market'];
    
    private $cryptoNetworks = [
        'TRX' => ['TRX', 'USDT'],
        'BEP' => ['BNB', 'USDT', 'BUSD'],
        'ERC' => ['ETH', 'USDT', 'USDC']
    ];
    
    private $expenseStatuses = ['pending', 'approved', 'rejected'];
    
    private $expenseTitles = [
        'Office supplies purchase',
        'Business lunch meeting',
        'Conference attendance',
        'Software license renewal',
        'Marketing campaign',
        'Equipment maintenance',
        'Professional development',
        'Client entertainment',
        'Travel expenses',
        'Utilities bill',
        'Consulting services',
        'Training materials',
        'Website hosting',
        'Advertising costs',
        'Legal fees'
    ];
    
    public function __construct($database) {
        $this->db = $database;
        $this->userModel = new User($database);
        $this->creditCardModel = new CreditCard($database);
        $this->subscriptionModel = new Subscription($database);
        $this->categoryModel = new Category($database);
        $this->tagModel = new Tag($database);
        $this->bankAccountModel = new BankAccount($database);
        $this->cryptoWalletModel = new CryptoWallet($database);
        $this->expenseModel = new Expense($database);
    }
    
    /**
     * Generate fake users
     */
    public function generateUsers($count = 10) {
        echo "Generating {$count} fake users...\n";
        
        for ($i = 0; $i < $count; $i++) {
            $firstName = $this->randomElement($this->firstNames);
            $lastName = $this->randomElement($this->lastNames);
            $email = strtolower($firstName . '.' . $lastName . rand(1, 999) . '@example.com');
            
            $userData = [
                'name' => $firstName . ' ' . $lastName,
                'email' => $email,
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'created_at' => $this->randomDate('-2 years', 'now'),
                'updated_at' => $this->randomDate('-2 years', 'now')
            ];
            
            try {
                $userId = $this->userModel->create($userData);
                echo "✓ Created user: {$userData['name']} ({$email})\n";
            } catch (Exception $e) {
                echo "✗ Failed to create user {$userData['name']}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * Generate fake credit cards
     */
    public function generateCreditCards($count = 20) {
        echo "Generating {$count} fake credit cards...\n";
        
        // Get all users
        $users = $this->getAllUsers();
        if (empty($users)) {
            echo "No users found. Please generate users first.\n";
            return;
        }
        
        for ($i = 0; $i < $count; $i++) {
            $user = $this->randomElement($users);
            $cardType = array_rand($this->creditCardTypes);
            $cardPrefix = $this->randomElement($this->creditCardTypes[$cardType]);
            
            $cardData = [
                'user_id' => $user['id'],
                'name' => $cardType . ' ' . $this->generateCardName(),
                'card_number_last4' => str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                'expiry_month' => str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT),
                'expiry_year' => rand(2024, 2030),
                'created_at' => $this->randomDate('-1 year', 'now'),
                'updated_at' => $this->randomDate('-1 year', 'now')
            ];
            
            try {
                $cardId = $this->creditCardModel->create($cardData);
                echo "✓ Created credit card: {$cardData['name']} for user {$user['name']}\n";
            } catch (Exception $e) {
                echo "✗ Failed to create credit card: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * Generate fake subscriptions
     */
    public function generateSubscriptions($count = 50) {
        echo "Generating {$count} fake subscriptions...\n";
        
        // Get all users and credit cards
        $users = $this->getAllUsers();
        $creditCards = $this->getAllCreditCards();
        
        if (empty($users)) {
            echo "No users found. Please generate users first.\n";
            return;
        }
        
        for ($i = 0; $i < $count; $i++) {
            $user = $this->randomElement($users);
            $service = $this->randomElement($this->subscriptionServices);
            $billingCycle = $this->randomElement($this->billingCycles);
            $status = $this->randomElement($this->statuses);
            
            // Choose payment method type
            $paymentMethodType = $this->randomElement(['credit_card', 'bank_account', 'crypto_wallet']);
            
            // Get payment method based on type
            $creditCardId = null;
            $bankAccountId = null;
            $cryptoWalletId = null;
            
            switch ($paymentMethodType) {
                case 'credit_card':
                    $userCards = array_filter($creditCards, function($card) use ($user) {
                        return $card['user_id'] == $user['id'];
                    });
                    if (!empty($userCards)) {
                        $creditCardId = $this->randomElement($userCards)['id'];
                    }
                    break;
                case 'bank_account':
                    $userBankAccounts = array_filter($this->getAllBankAccounts(), function($account) use ($user) {
                        return $account['user_id'] == $user['id'];
                    });
                    if (!empty($userBankAccounts)) {
                        $bankAccountId = $this->randomElement($userBankAccounts)['id'];
                    }
                    break;
                case 'crypto_wallet':
                    $userCryptoWallets = array_filter($this->getAllCryptoWallets(), function($wallet) use ($user) {
                        return $wallet['user_id'] == $user['id'];
                    });
                    if (!empty($userCryptoWallets)) {
                        $cryptoWalletId = $this->randomElement($userCryptoWallets)['id'];
                    }
                    break;
            }
            
            // Adjust cost based on billing cycle
            $baseCost = $this->randomElement($service['typical_cost']);
            $amount = $this->adjustCostForBillingCycle($baseCost, $billingCycle);
            
            // Generate realistic dates - use more recent dates for testing
            $createdAt = $this->randomDate('-2 months', 'now');
            $nextPaymentDate = $this->calculateNextPaymentDate($createdAt, $billingCycle, $status);
            
            $subscriptionData = [
                'user_id' => $user['id'],
                'credit_card_id' => $creditCardId,
                'bank_account_id' => $bankAccountId,
                'crypto_wallet_id' => $cryptoWalletId,
                'name' => $service['name'],
                'description' => $this->generateDescription($service),
                'amount' => number_format($amount, 2, '.', ''), // Ensure proper decimal format
                'currency' => $this->randomElement($this->currencies),
                'billing_cycle' => $billingCycle, // Ensure this is always set
                'payment_method_type' => $paymentMethodType,
                'status' => $status,
                'next_payment_date' => $nextPaymentDate,
                'created_at' => $createdAt,
                'updated_at' => $this->randomDate($createdAt, 'now')
            ];
            
            try {
                $subId = $this->subscriptionModel->create($subscriptionData);
                echo "✓ Created subscription: {$service['name']} ({$billingCycle}, \${$amount}) for {$user['name']}\n";
                
                // Generate a random transaction for this subscription (simulating payment history)
                if (rand(0, 1)) {
                    $this->generateTransactionForSubscription($subId, $subscriptionData);
                }
            } catch (Exception $e) {
                echo "✗ Failed to create subscription {$service['name']}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * Generate fake categories
     */
    public function generateCategories($count = 10) {
        echo "Generating {$count} fake categories...\n";
        
        $users = $this->getAllUsers();
        if (empty($users)) {
            echo "No users found. Please generate users first.\n";
            return;
        }
        
        $created = 0;
        $attempts = 0;
        
        while ($created < $count && $attempts < $count * 3) {
            $user = $this->randomElement($users);
            $category = $this->randomElement($this->categories);
            
            // Check if category already exists for this user
            $existingCategory = $this->db->get('categories', 'id', [
                'user_id' => $user['id'],
                'name' => $category['name']
            ]);
            
            if ($existingCategory) {
                $attempts++;
                continue; // Skip if category already exists
            }
            
            $categoryData = [
                'user_id' => $user['id'],
                'name' => $category['name'],
                'description' => $this->generateDescription(['name' => $category['name'], 'category' => 'business category']),
                'color' => $category['color'],
                'icon' => $category['icon'],
                'created_at' => $this->randomDate('-6 months', 'now'),
                'updated_at' => $this->randomDate('-6 months', 'now')
            ];
            
            try {
                $categoryId = $this->categoryModel->create($categoryData);
                if ($categoryId) {
                    echo "✓ Created category: {$category['name']} for user {$user['name']}\n";
                    $created++;
                } else {
                    echo "✗ Failed to create category: {$category['name']} for user {$user['name']}\n";
                }
            } catch (Exception $e) {
                echo "✗ Failed to create category: " . $e->getMessage() . "\n";
            }
            
            $attempts++;
        }
        
        if ($created < $count) {
            echo "Note: Only created {$created} out of {$count} requested categories due to duplicates.\n";
        }
    }
    
    /**
     * Generate fake tags
     */
    public function generateTags($count = 10) {
        echo "Generating {$count} fake tags...\n";
        
        $users = $this->getAllUsers();
        if (empty($users)) {
            echo "No users found. Please generate users first.\n";
            return;
        }
        
        $created = 0;
        $attempts = 0;
        
        while ($created < $count && $attempts < $count * 3) {
            $user = $this->randomElement($users);
            $tag = $this->randomElement($this->tags);
            
            // Check if tag already exists for this user
            $existingTag = $this->db->get('tags', 'id', [
                'user_id' => $user['id'],
                'name' => $tag['name']
            ]);
            
            if ($existingTag) {
                $attempts++;
                continue; // Skip if tag already exists
            }
            
            $tagData = [
                'user_id' => $user['id'],
                'name' => $tag['name'],
                'description' => $this->generateDescription(['name' => $tag['name'], 'category' => 'business tag']),
                'color' => $tag['color'],
                'created_at' => $this->randomDate('-6 months', 'now'),
                'updated_at' => $this->randomDate('-6 months', 'now')
            ];
            
            try {
                $tagId = $this->tagModel->create($tagData);
                if ($tagId) {
                    echo "✓ Created tag: {$tag['name']} for user {$user['name']}\n";
                    $created++;
                } else {
                    echo "✗ Failed to create tag: {$tag['name']} for user {$user['name']}\n";
                }
            } catch (Exception $e) {
                echo "✗ Failed to create tag: " . $e->getMessage() . "\n";
            }
            
            $attempts++;
        }
        
        if ($created < $count) {
            echo "Note: Only created {$created} out of {$count} requested tags due to duplicates.\n";
        }
    }
    
    /**
     * Generate fake bank accounts
     */
    public function generateBankAccounts($count = 12) {
        echo "Generating {$count} fake bank accounts...\n";
        
        $users = $this->getAllUsers();
        if (empty($users)) {
            echo "No users found. Please generate users first.\n";
            return;
        }
        
        for ($i = 0; $i < $count; $i++) {
            $user = $this->randomElement($users);
            $bankName = $this->randomElement($this->bankNames);
            $accountType = $this->randomElement($this->accountTypes);
            $accountNumber = str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            
            $bankAccountData = [
                'user_id' => $user['id'],
                'name' => $bankName . ' ' . ucfirst(str_replace('_', ' ', $accountType)),
                'bank_name' => $bankName,
                'account_type' => $accountType,
                'account_number_last4' => substr($accountNumber, -4),
                'routing_number' => str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT),
                'currency' => $this->randomElement(['USD', 'EUR', 'GBP', 'CAD']),
                'iban' => rand(0, 1) ? $this->generateIBAN() : null,
                'swift_bic' => rand(0, 1) ? $this->generateSWIFT() : null,
                'notes' => rand(0, 1) ? 'Business account for ' . $user['name'] : null
            ];
            
            try {
                $bankAccountId = $this->bankAccountModel->create($bankAccountData);
                echo "✓ Created bank account: {$bankAccountData['name']} for user {$user['name']}\n";
            } catch (Exception $e) {
                echo "✗ Failed to create bank account: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * Generate fake crypto wallets
     */
    public function generateCryptoWallets($count = 20) {
        echo "Generating {$count} fake crypto wallets...\n";
        
        $users = $this->getAllUsers();
        if (empty($users)) {
            echo "No users found. Please generate users first.\n";
            return;
        }
        
        for ($i = 0; $i < $count; $i++) {
            $user = $this->randomElement($users);
            $network = array_rand($this->cryptoNetworks);
            $currency = $this->randomElement($this->cryptoNetworks[$network]);
            
            $cryptoWalletData = [
                'user_id' => $user['id'],
                'name' => $currency . ' Wallet (' . $network . ')',
                'network' => $network,
                'currency' => $currency,
                'address' => $this->generateCryptoAddress($network),
                'notes' => rand(0, 1) ? 'Personal ' . $currency . ' wallet' : null
            ];
            
            try {
                $cryptoWalletId = $this->cryptoWalletModel->create($cryptoWalletData);
                echo "✓ Created crypto wallet: {$cryptoWalletData['name']} for user {$user['name']}\n";
            } catch (Exception $e) {
                echo "✗ Failed to create crypto wallet: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * Generate fake expenses
     */
    public function generateExpenses($count = 50) {
        echo "Generating {$count} fake expenses...\n";
        
        $users = $this->getAllUsers();
        $categories = $this->getAllCategories();
        $tags = $this->getAllTags();
        $creditCards = $this->getAllCreditCards();
        $bankAccounts = $this->getAllBankAccounts();
        $cryptoWallets = $this->getAllCryptoWallets();
        
        if (empty($users)) {
            echo "No users found. Please generate users first.\n";
            return;
        }
        
        for ($i = 0; $i < $count; $i++) {
            $user = $this->randomElement($users);
            $paymentMethodType = $this->randomElement(['credit_card', 'bank_account', 'crypto_wallet']);
            
            // Get payment method ID based on type
            $creditCardId = null;
            $bankAccountId = null;
            $cryptoWalletId = null;
            
            switch ($paymentMethodType) {
                case 'credit_card':
                    $userCards = array_filter($creditCards, function($card) use ($user) {
                        return $card['user_id'] == $user['id'];
                    });
                    if (!empty($userCards)) {
                        $creditCardId = $this->randomElement($userCards)['id'];
                    }
                    break;
                case 'bank_account':
                    $userBankAccounts = array_filter($bankAccounts, function($account) use ($user) {
                        return $account['user_id'] == $user['id'];
                    });
                    if (!empty($userBankAccounts)) {
                        $bankAccountId = $this->randomElement($userBankAccounts)['id'];
                    }
                    break;
                case 'crypto_wallet':
                    $userCryptoWallets = array_filter($cryptoWallets, function($wallet) use ($user) {
                        return $wallet['user_id'] == $user['id'];
                    });
                    if (!empty($userCryptoWallets)) {
                        $cryptoWalletId = $this->randomElement($userCryptoWallets)['id'];
                    }
                    break;
            }
            
            // Skip if no payment method available
            if (!$creditCardId && !$bankAccountId && !$cryptoWalletId) {
                // Fallback to cash/other payment method
                $paymentMethodType = 'cash';
            }
            
            $amount = round(rand(1000, 200000) / 100, 2); // $10.00 to $2000.00
            $taxRate = rand(0, 1) ? round(rand(0, 1500) / 100, 2) : 0; // 0% to 15%
            $taxAmount = ($taxRate > 0) ? round($amount * $taxRate / 100, 2) : 0;
            
            $vendors = ['Amazon', 'Office Depot', 'Staples', 'Dell', 'Best Buy', 'Local Restaurant', 'Hotel Chain', 'Airline'];
            
            $expenseData = [
                'user_id' => $user['id'],
                'category_id' => (!empty($categories) && rand(0, 1)) ? $this->randomElement($categories)['id'] : null,
                'credit_card_id' => $creditCardId,
                'bank_account_id' => $bankAccountId,
                'crypto_wallet_id' => $cryptoWalletId,
                'title' => $this->randomElement($this->expenseTitles),
                'description' => rand(0, 1) ? $this->generateDescription(['name' => 'expense', 'category' => 'business expense']) : null,
                'amount' => $amount,
                'currency' => $this->randomElement(['USD', 'EUR', 'GBP']),
                'tax_amount' => $taxAmount > 0 ? $taxAmount : null,
                'tax_rate' => $taxRate > 0 ? $taxRate : null,
                'tax_type' => $taxRate > 0 ? $this->randomElement(['VAT', 'Sales Tax', 'GST']) : null,
                'expense_date' => $this->randomDate('-1 year', 'now'),
                'receipt_number' => rand(0, 1) ? 'RCP-' . strtoupper($this->generateRandomString(8)) : null,
                'vendor' => rand(0, 1) ? $this->randomElement($vendors) : null,
                'notes' => rand(0, 1) ? 'Generated expense for testing purposes' : null,
                'status' => $this->randomElement(['pending', 'approved', 'rejected', 'paid']),
                'payment_method_type' => $paymentMethodType,
                'attachments' => null // No attachments for fake data
            ];
            
            try {
                $expenseId = $this->expenseModel->create($expenseData);
                
                // Add random tags
                if (!empty($tags) && rand(0, 1)) {
                    $numTags = rand(1, 3);
                    $selectedTags = array_rand($tags, min($numTags, count($tags)));
                    if (!is_array($selectedTags)) {
                        $selectedTags = [$selectedTags];
                    }
                    
                    foreach ($selectedTags as $tagIndex) {
                        $this->db->insert('expense_tags', [
                            'expense_id' => $expenseId,
                            'tag_id' => $tags[$tagIndex]['id']
                        ]);
                    }
                }
                
                echo "✓ Created expense: {$expenseData['title']} (\${$amount}) for user {$user['name']}\n";
                
                // Generate transaction for approved expenses
                if ($expenseData['status'] === 'approved' && rand(0, 1)) {
                    $this->generateTransactionForExpense($expenseId, $expenseData);
                }
            } catch (Exception $e) {
                echo "✗ Failed to create expense: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * Clear all data from tables
     */
    public function resetDatabase() {
        echo "Resetting database...\n";
        
        try {
            // Use Medoo syntax for deletions in dependency order
            $this->db->delete("transactions", ["id[>]" => 0]);
            $this->db->delete("expense_tags", ["id[>]" => 0]);
            $this->db->delete("expenses", ["id[>]" => 0]);
            $this->db->delete("subscriptions", ["id[>]" => 0]);
            $this->db->delete("crypto_wallets", ["id[>]" => 0]);
            $this->db->delete("bank_accounts", ["id[>]" => 0]);
            $this->db->delete("credit_cards", ["id[>]" => 0]);
            $this->db->delete("tags", ["id[>]" => 0]);
            $this->db->delete("categories", ["id[>]" => 0]);
            $this->db->delete("users", ["email[~]" => "@example.com"]);
            
            echo "✓ Database reset complete\n";
        } catch (Exception $e) {
            echo "✗ Failed to reset database: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Generate comprehensive test data
     */
    public function generateAll($users = 10, $cards = 20, $subscriptions = 50, $categories = 10, $tags = 10, $bankAccounts = 12, $cryptoWallets = 20, $expenses = 50) {
        echo "=== Starting comprehensive data generation ===\n\n";
        
        $this->generateUsers($users);
        echo "\n";
        
        $this->generateCreditCards($cards);
        echo "\n";
        
        $this->generateCategories($categories);
        echo "\n";
        
        $this->generateTags($tags);
        echo "\n";
        
        $this->generateBankAccounts($bankAccounts);
        echo "\n";
        
        $this->generateCryptoWallets($cryptoWallets);
        echo "\n";
        
        $this->generateSubscriptions($subscriptions);
        echo "\n";

        $this->generateExpenses($expenses);
        echo "\n";
        
        $this->printSummary();
    }
    
    /**
     * Print data summary
     */
    public function printSummary() {
        echo "=== Data Generation Summary ===\n";
        
        try {
            $userCount = $this->db->count("users");
            $cardCount = $this->db->count("credit_cards");
            $subCount = $this->db->count("subscriptions");
            $categoryCount = $this->db->count("categories");
            $tagCount = $this->db->count("tags");
            $bankAccountCount = $this->db->count("bank_accounts");
            $cryptoWalletCount = $this->db->count("crypto_wallets");
            $expenseCount = $this->db->count("expenses");
            $transactionCount = $this->db->count("transactions");
            
            echo "Users: {$userCount}\n";
            echo "Credit Cards: {$cardCount}\n";
            echo "Subscriptions: {$subCount}\n";
            echo "Categories: {$categoryCount}\n";
            echo "Tags: {$tagCount}\n";
            echo "Bank Accounts: {$bankAccountCount}\n";
            echo "Crypto Wallets: {$cryptoWalletCount}\n";
            echo "Expenses: {$expenseCount}\n";
            echo "Transactions: {$transactionCount}\n";
            
            // Subscription breakdown - use raw query for complex aggregations
            if ($subCount > 0) {
                $statusBreakdown = $this->db->query("
                    SELECT status, COUNT(*) as count 
                    FROM subscriptions 
                    GROUP BY status
                ")->fetchAll();
                
                echo "\nSubscription Status Breakdown:\n";
                foreach ($statusBreakdown as $status) {
                    echo "  {$status['status']}: {$status['count']}\n";
                }
                
                $cycleBreakdown = $this->db->query("
                    SELECT billing_cycle, COUNT(*) as count 
                    FROM subscriptions 
                    GROUP BY billing_cycle
                ")->fetchAll();
                
                echo "\nBilling Cycle Breakdown:\n";
                foreach ($cycleBreakdown as $cycle) {
                    echo "  {$cycle['billing_cycle']}: {$cycle['count']}\n";
                }
            }
            
            // Expense breakdown
            if ($expenseCount > 0) {
                $expenseStatusBreakdown = $this->db->query("
                    SELECT status, COUNT(*) as count 
                    FROM expenses 
                    GROUP BY status
                ")->fetchAll();
                
                echo "\nExpense Status Breakdown:\n";
                foreach ($expenseStatusBreakdown as $status) {
                    echo "  {$status['status']}: {$status['count']}\n";
                }
                
                $paymentMethodBreakdown = $this->db->query("
                    SELECT payment_method_type, COUNT(*) as count 
                    FROM expenses 
                    WHERE payment_method_type IS NOT NULL
                    GROUP BY payment_method_type
                ")->fetchAll();
                
                echo "\nExpense Payment Method Breakdown:\n";
                foreach ($paymentMethodBreakdown as $method) {
                    echo "  {$method['payment_method_type']}: {$method['count']}\n";
                }
            }
            
            // Total costs
            $activeSubs = $this->db->select("subscriptions", [
                "amount",
                "billing_cycle"
            ], [
                "status" => "active"
            ]);
            
            $monthlyRecurring = 0;
            $yearlyRecurring = 0;
            $onetimeTotal = 0;
            
            foreach ($activeSubs as $sub) {
                $amount = floatval($sub['amount']);
                switch ($sub['billing_cycle']) {
                    case 'monthly':
                        $monthlyRecurring += $amount;
                        break;
                    case 'yearly':
                        $yearlyRecurring += $amount;
                        break;
                    case 'one-time':
                        $onetimeTotal += $amount;
                        break;
                }
            }
            
            echo "\nSubscription Cost Summary:\n";
            echo "  Monthly Recurring: $" . number_format($monthlyRecurring, 2) . "\n";
            echo "  Yearly Recurring: $" . number_format($yearlyRecurring, 2) . "\n";
            echo "  One-time Total: $" . number_format($onetimeTotal, 2) . "\n";
            
            // Expense costs
            $expenseAmounts = $this->db->select("expenses", [
                "amount",
                "tax_amount"
            ], [
                "status" => "approved"
            ]);
            
            $totalExpenses = 0;
            $totalTax = 0;
            
            foreach ($expenseAmounts as $expense) {
                $totalExpenses += floatval($expense['amount']);
                $totalTax += floatval($expense['tax_amount'] ?? 0);
            }
            
            echo "\nExpense Cost Summary (Approved):\n";
            echo "  Total Expenses: $" . number_format($totalExpenses, 2) . "\n";
            echo "  Total Tax: $" . number_format($totalTax, 2) . "\n";
            echo "  Grand Total: $" . number_format($totalExpenses + $totalTax, 2) . "\n";
            
            // Transaction breakdown
            if ($transactionCount > 0) {
                $transactionStatusBreakdown = $this->db->query("
                    SELECT status, COUNT(*) as count 
                    FROM transactions 
                    GROUP BY status
                ")->fetchAll();
                
                echo "\nTransaction Status Breakdown:\n";
                foreach ($transactionStatusBreakdown as $status) {
                    echo "  {$status['status']}: {$status['count']}\n";
                }
                
                $transactionTypeBreakdown = $this->db->query("
                    SELECT transaction_type, COUNT(*) as count 
                    FROM transactions 
                    GROUP BY transaction_type
                ")->fetchAll();
                
                echo "\nTransaction Type Breakdown:\n";
                foreach ($transactionTypeBreakdown as $type) {
                    echo "  {$type['transaction_type']}: {$type['count']}\n";
                }
                
                // Transaction volume summary
                $transactionSummary = $this->db->query("
                    SELECT 
                        SUM(amount) as total_amount,
                        AVG(amount) as avg_amount,
                        COUNT(*) as total_count
                    FROM transactions 
                    WHERE status = 'successful'
                ")->fetch();
                
                if ($transactionSummary) {
                    echo "\nTransaction Volume Summary (Successful):\n";
                    echo "  Total Amount: $" . number_format($transactionSummary['total_amount'] ?: 0, 2) . "\n";
                    echo "  Average Amount: $" . number_format($transactionSummary['avg_amount'] ?: 0, 2) . "\n";
                    echo "  Total Count: " . ($transactionSummary['total_count'] ?: 0) . "\n";
                }
            }
            
        } catch (Exception $e) {
            echo "Error generating summary: " . $e->getMessage() . "\n";
        }
    }
    
    // Helper methods
    private function randomElement($array) {
        return $array[array_rand($array)];
    }
    
    private function randomDate($from, $to) {
        $fromTime = strtotime($from);
        $toTime = strtotime($to);
        $randomTime = rand($fromTime, $toTime);
        return date('Y-m-d H:i:s', $randomTime);
    }
    
    private function getAllUsers() {
        return $this->db->select("users", "*");
    }
    
    private function getAllCreditCards() {
        return $this->db->select("credit_cards", "*");
    }
    
    private function getAllCategories() {
        return $this->db->select("categories", "*");
    }
    
    private function getAllTags() {
        return $this->db->select("tags", "*");
    }
    
    private function getAllBankAccounts() {
        return $this->db->select("bank_accounts", "*");
    }
    
    private function getAllCryptoWallets() {
        return $this->db->select("crypto_wallets", "*");
    }
    
    private function generateCardName() {
        $names = ['Personal', 'Business', 'Travel', 'Cashback', 'Rewards', 'Premium', 'Gold', 'Platinum'];
        return $this->randomElement($names);
    }
    
    private function generateDescription($service) {
        $descriptions = [
            "Premium {$service['category']} subscription",
            "{$service['category']} service for personal use",
            "Professional {$service['category']} plan",
            "Monthly {$service['category']} subscription",
            "{$service['name']} - {$service['category']} service"
        ];
        return $this->randomElement($descriptions);
    }
    
    private function adjustCostForBillingCycle($baseCost, $billingCycle) {
        switch ($billingCycle) {
            case 'yearly':
                return round($baseCost * 10, 2); // Yearly is usually ~10x monthly with discount
            case 'one-time':
                return round($baseCost * rand(5, 50), 2); // One-time can vary widely
            default: // monthly
                return $baseCost;
        }
    }
    
    private function calculateNextPaymentDate($createdAt, $billingCycle, $status) {
        if ($status === 'cancelled' || $status === 'expired') {
            return null; // No next payment for inactive subscriptions
        }
        
        $created = new DateTime($createdAt);
        $now = new DateTime();
        
        switch ($billingCycle) {
            case 'monthly':
                $interval = new DateInterval('P1M');
                break;
            case 'yearly':
                $interval = new DateInterval('P1Y');
                break;
            case 'one-time':
                return null; // No recurring payment
            default:
                $interval = new DateInterval('P1M');
        }
        
        $nextPayment = clone $created;
        while ($nextPayment < $now) {
            $nextPayment->add($interval);
        }
        
        return $nextPayment->format('Y-m-d');
    }
    
    private function generateCryptoAddress($network) {
        switch ($network) {
            case 'TRX':
                return 'T' . $this->generateRandomString(33);
            case 'BEP':
                return '0x' . $this->generateRandomString(40);
            case 'ERC':
                return '0x' . $this->generateRandomString(40);
            default:
                return '0x' . $this->generateRandomString(40);
        }
    }
    
    private function generateRandomString($length) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
    
    /**
     * Generate transaction for subscription
     */
    private function generateTransactionForSubscription($subscriptionId, $subscriptionData) {
        $transactionData = [
            'user_id' => $subscriptionData['user_id'],
            'subscription_id' => $subscriptionId,
            'credit_card_id' => $subscriptionData['credit_card_id'],
            'bank_account_id' => $subscriptionData['bank_account_id'],
            'crypto_wallet_id' => $subscriptionData['crypto_wallet_id'],
            'amount' => $subscriptionData['amount'],
            'currency' => $subscriptionData['currency'],
            'transaction_date' => $this->randomDate('-3 months', 'now'),
            'status' => $this->randomElement(['successful', 'failed', 'pending']),
            'payment_method_type' => $subscriptionData['payment_method_type'],
            'transaction_type' => 'subscription',
            'reference_number' => 'SUB-' . strtoupper($this->generateRandomString(8)),
            'description' => 'Payment for ' . $subscriptionData['name'],
            'notes' => 'Automated subscription payment'
        ];
        
        try {
            $this->db->insert('transactions', $transactionData);
        } catch (Exception $e) {
            // Ignore transaction creation errors
        }
    }
    
    /**
     * Generate transaction for expense
     */
    private function generateTransactionForExpense($expenseId, $expenseData) {
        $transactionData = [
            'user_id' => $expenseData['user_id'],
            'expense_id' => $expenseId,
            'credit_card_id' => $expenseData['credit_card_id'],
            'bank_account_id' => $expenseData['bank_account_id'],
            'crypto_wallet_id' => $expenseData['crypto_wallet_id'],
            'amount' => $expenseData['amount'],
            'currency' => $expenseData['currency'],
            'transaction_date' => $expenseData['expense_date'] . ' ' . $this->randomTime(),
            'status' => $this->randomElement(['successful', 'pending']),
            'payment_method_type' => $expenseData['payment_method_type'],
            'transaction_type' => 'expense',
            'reference_number' => 'EXP-' . strtoupper($this->generateRandomString(8)),
            'description' => 'Payment for ' . $expenseData['title'],
            'notes' => $expenseData['notes']
        ];
        
        try {
            $this->db->insert('transactions', $transactionData);
        } catch (Exception $e) {
            // Ignore transaction creation errors
        }
    }
    
    /**
     * Generate random time
     */
    private function randomTime() {
        return sprintf('%02d:%02d:%02d', rand(0, 23), rand(0, 59), rand(0, 59));
    }
    
    private function generateIBAN() {
        $countryCode = $this->randomElement(['GB', 'DE', 'FR', 'IT', 'ES']);
        $checkDigits = str_pad(rand(10, 99), 2, '0', STR_PAD_LEFT);
        $bankCode = strtoupper($this->generateRandomString(4));
        $accountNumber = str_pad(rand(10000000, 99999999), 18, '0', STR_PAD_LEFT);
        return $countryCode . $checkDigits . $bankCode . $accountNumber;
    }
    
    private function generateSWIFT() {
        $bankCode = strtoupper($this->generateRandomString(4));
        $countryCode = $this->randomElement(['US', 'GB', 'DE', 'FR', 'IT']);
        $locationCode = strtoupper($this->generateRandomString(2));
        $branchCode = rand(0, 1) ? strtoupper($this->generateRandomString(3)) : '';
        return $bankCode . $countryCode . $locationCode . $branchCode;
    }
    
    public function showHelp() {
        echo "Database Faker - Generate realistic test data\n\n";
        echo "Usage:\n";
        echo "  php database/faker.php [options]\n\n";
        echo "Options:\n";
        echo "  --users=N          Generate N users (default: 10)\n";
        echo "  --cards=N          Generate N credit cards (default: 20)\n";
        echo "  --subscriptions=N  Generate N subscriptions (default: 50)\n";
        echo "  --categories=N     Generate N categories (default: 10)\n";
        echo "  --tags=N           Generate N tags (default: 10)\n";
        echo "  --bank=N           Generate N bank accounts (default: 12)\n";
        echo "  --crypto=N         Generate N crypto wallets (default: 20)\n";
        echo "  --expenses=N       Generate N expenses (default: 50)\n";
        echo "  --all              Generate all data with default counts\n";
        echo "  --reset            Clear all fake data first\n";
        echo "  --summary          Show current data summary\n";
        echo "  --help             Show this help message\n\n";
        echo "Examples:\n";
        echo "  php database/faker.php --all\n";
        echo "  php database/faker.php --users=5 --cards=10 --subscriptions=25\n";
        echo "  php database/faker.php --reset --all\n";
        echo "  php database/faker.php --summary\n";
        echo "  php database/faker.php --expenses=100 --categories=15 --tags=20\n";
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $faker = new DatabaseFaker($database);
    
    $options = getopt('', [
        'users::', 'cards::', 'subscriptions::', 
        'categories::', 'tags::', 'bank::', 'crypto::', 'expenses::',
        'all', 'reset', 'summary', 'help'
    ]);
    
    if (isset($options['help'])) {
        $faker->showHelp();
        exit(0);
    }
    
    if (isset($options['reset'])) {
        $faker->resetDatabase();
    }
    
    if (isset($options['summary'])) {
        $faker->printSummary();
        exit(0);
    }
    
    if (isset($options['all'])) {
        $faker->generateAll();
    } else {
        $userCount = isset($options['users']) ? (int)$options['users'] : 0;
        $cardCount = isset($options['cards']) ? (int)$options['cards'] : 0;
        $subCount = isset($options['subscriptions']) ? (int)$options['subscriptions'] : 0;
        $catCount = isset($options['categories']) ? (int)$options['categories'] : 0;
        $tagCount = isset($options['tags']) ? (int)$options['tags'] : 0;
        $bankCount = isset($options['bank']) ? (int)$options['bank'] : 0;
        $cryptoCount = isset($options['crypto']) ? (int)$options['crypto'] : 0;
        $expCount = isset($options['expenses']) ? (int)$options['expenses'] : 0;

        
        if ($userCount > 0) {
            $faker->generateUsers($userCount);
        }
        
        if ($cardCount > 0) {
            $faker->generateCreditCards($cardCount);
        }
        
        if ($subCount > 0) {
            $faker->generateSubscriptions($subCount);
        }

        if ($catCount > 0) {
            $faker->generateCategories($catCount);
        }

        if ($tagCount > 0) {
            $faker->generateTags($tagCount);
        }

        if ($bankCount > 0) {
            $faker->generateBankAccounts($bankCount);
        }

        if ($cryptoCount > 0) {
            $faker->generateCryptoWallets($cryptoCount);
        }

        if ($expCount > 0) {
            $faker->generateExpenses($expCount);
        }

        
        
        if ($userCount === 0 && $cardCount === 0 && $subCount === 0 && $catCount === 0 && $tagCount === 0 && $bankCount === 0 && $cryptoCount === 0) {
            echo "No options specified. Use --help for usage information.\n";
        }
    }
} else {
    echo "This script should be run from the command line.\n";
} 