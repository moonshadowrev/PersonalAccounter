<?php

require_once __DIR__ . '/Model.php';

class CryptoWallet extends Model {

    protected $table = 'crypto_wallets';

    public function getByUserId($userId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId) {
            return $this->db->select('crypto_wallets', '*', [
                'user_id' => $userId,
                'ORDER' => ['created_at' => 'DESC']
            ]);
        }, []);
    }

    public function find($id) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id) {
            return $this->db->get('crypto_wallets', '*', ['id' => $id]);
        }, null);
    }

    public function create($data) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($data) {
            // Auto-generate shortened address for display
            if (isset($data['address'])) {
                $data['address_short'] = $this->generateShortAddress($data['address']);
            }
            
            $result = $this->db->insert('crypto_wallets', $data);
            if ($result) {
                return $this->db->id();
            }
            return false;
        }, false);
    }

    public function update($id, $data) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id, $data) {
            // Update shortened address if full address is being updated
            if (isset($data['address'])) {
                $data['address_short'] = $this->generateShortAddress($data['address']);
            }
            
            $result = $this->db->update('crypto_wallets', $data, ['id' => $id]);
            if ($result && $result->rowCount() > 0) {
                return true;
            }
            return false;
        }, false);
    }

    public function delete($id) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($id) {
            $result = $this->db->delete('crypto_wallets', ['id' => $id]);
            if ($result && $result->rowCount() > 0) {
                return true;
            }
            return false;
        }, false);
    }

    /**
     * Get all crypto wallets with user information
     */
    public function getAllWithUserInfo() {
        return ErrorHandler::wrapDatabaseOperation(function() {
            return $this->db->select('crypto_wallets', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'crypto_wallets.id',
                'crypto_wallets.name',
                'crypto_wallets.currency',
                'crypto_wallets.network',
                'crypto_wallets.address_short',
                'crypto_wallets.created_at',
                'users.name(user_name)',
                'users.email(user_email)'
            ], [
                'ORDER' => ['crypto_wallets.created_at' => 'DESC']
            ]);
        }, []);
    }

    /**
     * Get wallets by currency
     */
    public function getByCurrency($userId, $currency) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $currency) {
            return $this->db->select('crypto_wallets', '*', [
                'user_id' => $userId,
                'currency' => $currency,
                'ORDER' => ['name' => 'ASC']
            ]);
        }, []);
    }

    /**
     * Get wallets by network
     */
    public function getByNetwork($userId, $network) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $network) {
            return $this->db->select('crypto_wallets', '*', [
                'user_id' => $userId,
                'network' => $network,
                'ORDER' => ['name' => 'ASC']
            ]);
        }, []);
    }

    /**
     * Get all wallets by currency (admin access - all users)
     */
    public function getAllByCurrency($currency) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($currency) {
            return $this->db->select('crypto_wallets', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'crypto_wallets.id',
                'crypto_wallets.name',
                'crypto_wallets.currency',
                'crypto_wallets.network',
                'crypto_wallets.address_short',
                'crypto_wallets.created_at',
                'users.name(user_name)',
                'users.email(user_email)'
            ], [
                'crypto_wallets.currency' => $currency,
                'ORDER' => ['crypto_wallets.name' => 'ASC']
            ]);
        }, []);
    }

    /**
     * Get all wallets by network (admin access - all users)
     */
    public function getAllByNetwork($network) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($network) {
            return $this->db->select('crypto_wallets', [
                '[>]users' => ['user_id' => 'id']
            ], [
                'crypto_wallets.id',
                'crypto_wallets.name',
                'crypto_wallets.currency',
                'crypto_wallets.network',
                'crypto_wallets.address_short',
                'crypto_wallets.created_at',
                'users.name(user_name)',
                'users.email(user_email)'
            ], [
                'crypto_wallets.network' => $network,
                'ORDER' => ['crypto_wallets.name' => 'ASC']
            ]);
        }, []);
    }

    /**
     * Check if wallet is used in expenses
     */
    public function isUsedInExpenses($walletId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($walletId) {
            $count = $this->db->count('expenses', ['crypto_wallet_id' => $walletId]);
            return $count > 0;
        }, false);
    }

    /**
     * Check if wallet is used in subscriptions
     */
    public function isUsedInSubscriptions($walletId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($walletId) {
            $count = $this->db->count('subscriptions', ['crypto_wallet_id' => $walletId]);
            return $count > 0;
        }, false);
    }

    /**
     * Get usage statistics for a crypto wallet
     */
    public function getUsageStats($walletId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($walletId) {
            $expenseCount = $this->db->count('expenses', ['crypto_wallet_id' => $walletId]);
            $subscriptionCount = $this->db->count('subscriptions', ['crypto_wallet_id' => $walletId]);
            $transactionCount = $this->db->count('transactions', ['crypto_wallet_id' => $walletId]);
            
            // Get total spent through this wallet
            $totalExpenses = $this->db->sum('expenses', 'amount', ['crypto_wallet_id' => $walletId]) ?: 0;
            $totalSubscriptions = $this->db->sum('subscriptions', 'amount', ['crypto_wallet_id' => $walletId]) ?: 0;
            
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
     * Get popular currencies
     */
    public function getPopularCurrencies($userId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId) {
            return $this->db->select('crypto_wallets', [
                'currency',
                'count' => 'COUNT(*)'
            ], [
                'user_id' => $userId,
                'GROUP' => 'currency',
                'ORDER' => ['count' => 'DESC']
            ]);
        }, []);
    }

    /**
     * Get popular networks
     */
    public function getPopularNetworks($userId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId) {
            return $this->db->select('crypto_wallets', [
                'network',
                'count' => 'COUNT(*)'
            ], [
                'user_id' => $userId,
                'GROUP' => 'network',
                'ORDER' => ['count' => 'DESC']
            ]);
        }, []);
    }

    /**
     * Generate shortened address for display
     */
    private function generateShortAddress($address) {
        if (strlen($address) <= 20) {
            return $address;
        }
        
        return substr($address, 0, 8) . '...' . substr($address, -8);
    }

    /**
     * Validate crypto address format (basic validation)
     */
    public static function validateAddress($address, $network = null) {
        // Remove spaces
        $address = trim($address);
        
        if (empty($address)) {
            return false;
        }
        
        // Basic length validation
        if (strlen($address) < 20 || strlen($address) > 100) {
            return false;
        }
        
        // Network-specific validation
        switch (strtoupper($network)) {
            case 'TRC20':
                // TRON addresses start with 'T' and are 34 characters
                return preg_match('/^T[A-Za-z0-9]{33}$/', $address);
                
            case 'BEP20':
            case 'ERC20':
                // Ethereum-style addresses start with '0x' and are 42 characters
                return preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
                
            case 'BTC':
                // Bitcoin addresses can start with 1, 3, or bc1
                return preg_match('/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$|^bc1[a-z0-9]{39,59}$/', $address);
                
            default:
                // Generic validation - alphanumeric with some special characters
                return preg_match('/^[a-zA-Z0-9\-_\.]+$/', $address);
        }
    }

    /**
     * Get supported cryptocurrencies
     */
    public static function getSupportedCurrencies() {
        return [
            'USDT' => 'Tether USD',
            'USDC' => 'USD Coin',
            'BTC' => 'Bitcoin',
            'ETH' => 'Ethereum',
            'TRX' => 'TRON',
            'BNB' => 'Binance Coin',
            'BUSD' => 'Binance USD',
            'DAI' => 'Dai Stablecoin',
            'LINK' => 'Chainlink',
            'ADA' => 'Cardano',
            'DOT' => 'Polkadot',
            'UNI' => 'Uniswap'
        ];
    }

    /**
     * Get supported networks
     */
    public static function getSupportedNetworks() {
        return [
            'TRC20' => 'TRON (TRC-20)',
            'BEP20' => 'Binance Smart Chain (BEP-20)',
            'ERC20' => 'Ethereum (ERC-20)',
            'BTC' => 'Bitcoin Network',
            'ETH' => 'Ethereum Native',
            'TRX' => 'TRON Native',
            'BNB' => 'Binance Smart Chain Native',
            'POLYGON' => 'Polygon Network',
            'ARBITRUM' => 'Arbitrum Network',
            'OPTIMISM' => 'Optimism Network'
        ];
    }

    /**
     * Get network currency compatibility
     */
    public static function getNetworkCurrencyCompatibility() {
        return [
            'TRC20' => ['USDT', 'USDC', 'TRX'],
            'BEP20' => ['USDT', 'USDC', 'BNB', 'BUSD'],
            'ERC20' => ['USDT', 'USDC', 'ETH', 'DAI', 'LINK', 'UNI'],
            'BTC' => ['BTC'],
            'ETH' => ['ETH'],
            'TRX' => ['TRX'],
            'BNB' => ['BNB'],
            'POLYGON' => ['USDT', 'USDC', 'DAI'],
            'ARBITRUM' => ['USDT', 'USDC', 'ETH'],
            'OPTIMISM' => ['USDT', 'USDC', 'ETH']
        ];
    }

    /**
     * Check if currency is compatible with network
     */
    public static function isCurrencyCompatibleWithNetwork($currency, $network) {
        $compatibility = self::getNetworkCurrencyCompatibility();
        return isset($compatibility[$network]) && in_array($currency, $compatibility[$network]);
    }

    /**
     * Search crypto wallets
     */
    public function search($userId, $query) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId, $query) {
            return $this->db->select('crypto_wallets', '*', [
                'user_id' => $userId,
                'OR' => [
                    'name[~]' => $query,
                    'currency[~]' => $query,
                    'network[~]' => $query,
                    'address[~]' => $query
                ],
                'ORDER' => ['name' => 'ASC']
            ]);
        }, []);
    }

    /**
     * Get wallet statistics grouped by currency
     */
    public function getCurrencyStats($userId) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($userId) {
            return $this->db->select('crypto_wallets', [
                'currency',
                'network',
                'wallet_count' => 'COUNT(*)'
            ], [
                'user_id' => $userId,
                'GROUP' => ['currency', 'network'],
                'ORDER' => ['currency' => 'ASC', 'network' => 'ASC']
            ]);
        }, []);
    }

    /**
     * Check if wallet address already exists for user
     */
    public function addressExistsForUser($address, $userId, $excludeId = null) {
        return ErrorHandler::wrapDatabaseOperation(function() use ($address, $userId, $excludeId) {
            $conditions = [
                'address' => $address,
                'user_id' => $userId
            ];
            
            if ($excludeId) {
                $conditions['id[!]'] = $excludeId;
            }
            
            $wallet = $this->db->get('crypto_wallets', 'id', $conditions);
            return !empty($wallet);
        }, false);
    }

    /**
     * Get masked address for display
     */
    public static function getMaskedAddress($address) {
        if (strlen($address) <= 20) {
            return $address;
        }
        
        return substr($address, 0, 8) . '...' . substr($address, -8);
    }
} 