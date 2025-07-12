<?php

require_once __DIR__ . '/Model.php';

class BankAccount extends Model {

    protected $table = 'bank_accounts';

    public function getByUserId($userId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId) {
            return $this->db->select('bank_accounts', '*', [
                'user_id' => $userId,
                'ORDER' => ['created_at' => 'DESC']
            ]);
        }, []);
    }

    public function find($id) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id) {
            return $this->db->get('bank_accounts', '*', ['id' => $id]);
        }, null);
    }

    public function create($data) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($data) {
            $result = $this->db->insert('bank_accounts', $data);
            if ($result) {
                return $this->db->id();
            }
            return false;
        }, false);
    }

    public function update($id, $data) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id, $data) {
            $result = $this->db->update('bank_accounts', $data, ['id' => $id]);
            if ($result && $result->rowCount() > 0) {
                return true;
            }
            return false;
        }, false);
    }

    public function delete($id) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id) {
            $result = $this->db->delete('bank_accounts', ['id' => $id]);
            if ($result && $result->rowCount() > 0) {
                return true;
            }
            return false;
        }, false);
    }

    /**
     * Get all bank accounts with user information (for admin access)
     */
    public function getAllWithUserInfo() {
        return ErrorHandler::wrapDatabaseOperation(function() {
            return $this->db->select('bank_accounts', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'bank_accounts.id',
                'bank_accounts.name',
                'bank_accounts.bank_name',
                'bank_accounts.account_type',
                'bank_accounts.account_number_last4',
                'bank_accounts.currency',
                'bank_accounts.created_at',
                'bank_accounts.updated_at',
                'users.name(user_name)',
                'users.email(user_email)'
            ], [
                'ORDER' => ['bank_accounts.created_at' => 'DESC']
            ]);
        }, []);
    }

    /**
     * Get all bank accounts by currency (admin access - all users)
     */
    public function getAllByCurrency($currency) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($currency) {
            return $this->db->select('bank_accounts', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'bank_accounts.id',
                'bank_accounts.name',
                'bank_accounts.bank_name',
                'bank_accounts.account_type',
                'bank_accounts.account_number_last4',
                'bank_accounts.currency',
                'bank_accounts.created_at',
                'users.name(user_name)',
                'users.email(user_email)'
            ], [
                'bank_accounts.currency' => $currency,
                'ORDER' => ['bank_accounts.name' => 'ASC']
            ]);
        }, []);
    }

    /**
     * Get all bank accounts by type (admin access - all users)
     */
    public function getAllByType($accountType) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($accountType) {
            return $this->db->select('bank_accounts', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'bank_accounts.id',
                'bank_accounts.name',
                'bank_accounts.bank_name',
                'bank_accounts.account_type',
                'bank_accounts.account_number_last4',
                'bank_accounts.currency',
                'bank_accounts.created_at',
                'users.name(user_name)',
                'users.email(user_email)'
            ], [
                'bank_accounts.account_type' => $accountType,
                'ORDER' => ['bank_accounts.name' => 'ASC']
            ]);
        }, []);
    }

    /**
     * Get bank accounts by currency
     */
    public function getByCurrency($userId, $currency) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $currency) {
            return $this->db->select('bank_accounts', '*', [
                'user_id' => $userId,
                'currency' => $currency,
                'ORDER' => ['name' => 'ASC']
            ]);
        }, []);
    }

    /**
     * Get bank accounts by type
     */
    public function getByType($userId, $accountType) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $accountType) {
            return $this->db->select('bank_accounts', '*', [
                'user_id' => $userId,
                'account_type' => $accountType,
                'ORDER' => ['name' => 'ASC']
            ]);
        }, []);
    }

    /**
     * Check if bank account is used in expenses
     */
    public function isUsedInExpenses($accountId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($accountId) {
            $count = $this->db->count('expenses', ['bank_account_id' => $accountId]);
            return $count > 0;
        }, false);
    }

    /**
     * Check if bank account is used in subscriptions
     */
    public function isUsedInSubscriptions($accountId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($accountId) {
            $count = $this->db->count('subscriptions', ['bank_account_id' => $accountId]);
            return $count > 0;
        }, false);
    }

    /**
     * Get usage statistics for a bank account
     */
    public function getUsageStats($accountId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($accountId) {
            $expenseCount = $this->db->count('expenses', ['bank_account_id' => $accountId]);
            $subscriptionCount = $this->db->count('subscriptions', ['bank_account_id' => $accountId]);
            $transactionCount = $this->db->count('transactions', ['bank_account_id' => $accountId]);
            
            // Get total spent through this account
            $totalExpenses = $this->db->sum('expenses', 'amount', ['bank_account_id' => $accountId]) ?: 0;
            $totalSubscriptions = $this->db->sum('subscriptions', 'amount', ['bank_account_id' => $accountId]) ?: 0;
            
            return [
                'expense_count' => $expenseCount,
                'subscription_count' => $subscriptionCount,
                'transaction_count' => $transactionCount,
                'total_expenses' => floatval($totalExpenses),
                'total_subscriptions' => floatval($totalSubscriptions),
                'total_amount' => floatval($totalExpenses + $totalSubscriptions)
            ];
        }, [
            'expense_count' => 0,
            'subscription_count' => 0,
            'transaction_count' => 0,
            'total_expenses' => 0,
            'total_subscriptions' => 0,
            'total_amount' => 0
        ]);
    }

    /**
     * Get popular account types
     */
    public function getPopularAccountTypes($userId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId) {
            return $this->db->select('bank_accounts', [
                'account_type',
                'count' => 'COUNT(*)'
            ], [
                'user_id' => $userId,
                'GROUP' => 'account_type',
                'ORDER' => ['count' => 'DESC']
            ]);
        }, []);
    }

    /**
     * Validate account number format (basic validation)
     */
    public static function validateAccountNumber($accountNumber) {
        // Remove spaces and hyphens
        $cleaned = preg_replace('/[\s\-]/', '', $accountNumber);
        
        // Check if it's all numeric and reasonable length
        return preg_match('/^\d{8,17}$/', $cleaned);
    }

    /**
     * Validate routing number (global format - more flexible)
     */
    public static function validateRoutingNumber($routingNumber) {
        // Remove spaces and hyphens
        $cleaned = preg_replace('/[\s\-]/', '', $routingNumber);
        
        // Allow routing numbers between 6-15 digits for international support
        // US: 9 digits, UK: 6 digits (sort code), EU: varies (IBAN routing), etc.
        if (!preg_match('/^\d{6,15}$/', $cleaned)) {
            return false;
        }
        
        // For now, just validate format - specific country validation could be added later
        return true;
    }

    /**
     * Get masked account number for display
     */
    public static function getMaskedAccountNumber($last4) {
        return '****' . $last4;
    }

    /**
     * Get account type options
     */
    public static function getAccountTypes() {
        return [
            'checking' => 'Checking Account',
            'savings' => 'Savings Account',
            'business' => 'Business Account',
            'money_market' => 'Money Market Account',
            'cd' => 'Certificate of Deposit',
            'other' => 'Other'
        ];
    }

    /**
     * Get supported currencies
     */
    public static function getSupportedCurrencies() {
        return [
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'CAD' => 'Canadian Dollar',
            'AUD' => 'Australian Dollar',
            'JPY' => 'Japanese Yen',
            'CHF' => 'Swiss Franc',
            'CNY' => 'Chinese Yuan',
            'SEK' => 'Swedish Krona',
            'NOK' => 'Norwegian Krone',
            'DKK' => 'Danish Krone',
            'SGD' => 'Singapore Dollar',
            'HKD' => 'Hong Kong Dollar'
        ];
    }

    /**
     * Get supported countries
     */
    public static function getSupportedCountries() {
        return [
            'US' => 'United States',
            'GB' => 'United Kingdom', 
            'DE' => 'Germany',
            'FR' => 'France',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'NL' => 'Netherlands',
            'CH' => 'Switzerland',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'JP' => 'Japan',
            'SG' => 'Singapore',
            'HK' => 'Hong Kong'
        ];
    }

    /**
     * Validate IBAN format (basic validation)
     */
    public static function validateIban($iban) {
        // Remove spaces and convert to uppercase
        $iban = strtoupper(preg_replace('/\s+/', '', $iban));
        
        // Basic IBAN format validation (2 letter country code + 2 check digits + up to 30 alphanumeric)
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/', $iban)) {
            return false;
        }
        
        // Length validation per country (basic check)
        $lengths = [
            'AD' => 24, 'AE' => 23, 'AL' => 28, 'AT' => 20, 'AZ' => 28, 'BA' => 20, 'BE' => 16,
            'BG' => 22, 'BH' => 22, 'BR' => 29, 'CH' => 21, 'CR' => 22, 'CY' => 28, 'CZ' => 24,
            'DE' => 22, 'DK' => 18, 'DO' => 28, 'EE' => 20, 'ES' => 24, 'FI' => 18, 'FO' => 18,
            'FR' => 27, 'GB' => 22, 'GE' => 22, 'GI' => 23, 'GL' => 18, 'GR' => 27, 'GT' => 28,
            'HR' => 21, 'HU' => 28, 'IE' => 22, 'IL' => 23, 'IS' => 26, 'IT' => 27, 'JO' => 30,
            'KW' => 30, 'KZ' => 20, 'LB' => 28, 'LI' => 21, 'LT' => 20, 'LU' => 20, 'LV' => 21,
            'MC' => 27, 'MD' => 24, 'ME' => 22, 'MK' => 19, 'MR' => 27, 'MT' => 31, 'MU' => 30,
            'NL' => 18, 'NO' => 15, 'PK' => 24, 'PL' => 28, 'PS' => 29, 'PT' => 25, 'QA' => 29,
            'RO' => 24, 'RS' => 22, 'SA' => 24, 'SE' => 24, 'SI' => 19, 'SK' => 24, 'SM' => 27,
            'TN' => 24, 'TR' => 26, 'UA' => 29, 'VG' => 24, 'XK' => 20
        ];
        
        $countryCode = substr($iban, 0, 2);
        if (isset($lengths[$countryCode]) && strlen($iban) !== $lengths[$countryCode]) {
            return false;
        }
        
        return true;
    }

    /**
     * Validate SWIFT/BIC code format
     */
    public static function validateSwiftBic($swiftBic) {
        // Remove spaces and convert to uppercase
        $swiftBic = strtoupper(preg_replace('/\s+/', '', $swiftBic));
        
        // SWIFT/BIC format: 4 char bank code + 2 char country + 2 char location + optional 3 char branch
        return preg_match('/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $swiftBic);
    }

    /**
     * Search bank accounts
     */
    public function search($userId, $query) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $query) {
            return $this->db->select('bank_accounts', '*', [
                'user_id' => $userId,
                'OR' => [
                    'name[~]' => $query,
                    'bank_name[~]' => $query,
                    'account_type[~]' => $query
                ],
                'ORDER' => ['name' => 'ASC']
            ]);
        }, []);
    }
} 