<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../Models/CryptoWallet.php';

class CryptoWalletController extends Controller {

    private $cryptoWalletModel;

    public function __construct($db) {
        $this->cryptoWalletModel = new CryptoWallet($db);
    }

    private function checkAuthentication() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user']['id'])) {
            header('Location: /login');
            exit();
        }
    }

    public function index() {
        $this->checkAuthentication();
        
        // Show all crypto wallets in centralized system - no user filtering
        $wallets = $this->cryptoWalletModel->getAllWithUserInfo();
        
        $this->view('dashboard/crypto-wallets/index', [
            'wallets' => $wallets,
            'load_datatable' => true,
            'datatable_target' => '#crypto-wallets-table'
        ]);
    }

    public function create() {
        $this->checkAuthentication();
        
        $this->view('dashboard/crypto-wallets/create', [
            'networks' => CryptoWallet::getSupportedNetworks(),
            'currencies' => CryptoWallet::getSupportedCurrencies()
        ]);
    }

    public function store() {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /crypto-wallets/create');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        
        // Validate input
        $name = trim($_POST['name'] ?? '');
        $network = trim($_POST['network'] ?? '');
        $currency = trim($_POST['currency'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($name)) {
            FlashMessage::error('Wallet name is required.');
            header('Location: /crypto-wallets/create');
            exit();
        }
        
        if (empty($network)) {
            FlashMessage::error('Network is required.');
            header('Location: /crypto-wallets/create');
            exit();
        }
        
        if (empty($currency)) {
            FlashMessage::error('Currency is required.');
            header('Location: /crypto-wallets/create');
            exit();
        }
        
        if (empty($address)) {
            FlashMessage::error('Wallet address is required.');
            header('Location: /crypto-wallets/create');
            exit();
        }
        
        // Validate network
        $validNetworks = array_keys(CryptoWallet::getSupportedNetworks());
        if (!in_array($network, $validNetworks)) {
            FlashMessage::error('Invalid network selected.');
            header('Location: /crypto-wallets/create');
            exit();
        }
        
        // Validate currency and network combination
        // Since currency is now a free text input, we don't validate predefined combinations
        // Users can enter any currency symbol they want
        
        // Validate address format
        if (!CryptoWallet::validateAddress($address, $network)) {
            FlashMessage::error('Invalid wallet address format for the selected network.');
            header('Location: /crypto-wallets/create');
            exit();
        }
        
        // Check if address already exists for user
        if ($this->cryptoWalletModel->addressExistsForUser($address, $userId)) {
            FlashMessage::error('A wallet with this address already exists.');
            header('Location: /crypto-wallets/create');
            exit();
        }
        
        $data = [
            'user_id' => $userId,
            'name' => $name,
            'network' => $network,
            'currency' => $currency,
            'address' => $address,
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $walletId = $this->cryptoWalletModel->create($data);
            
            if ($walletId) {
                AppLogger::info('Crypto wallet created', [
                    'user_id' => $userId,
                    'wallet_id' => $walletId,
                    'name' => $name,
                    'network' => $network,
                    'currency' => $currency
                ]);
                FlashMessage::success('Crypto wallet created successfully!');
                header('Location: /crypto-wallets');
            } else {
                FlashMessage::error('Failed to create crypto wallet. Please try again.');
                header('Location: /crypto-wallets/create');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to create crypto wallet', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to create crypto wallet. Please try again.');
            header('Location: /crypto-wallets/create');
        }
        
        exit();
    }

    public function edit($id) {
        $this->checkAuthentication();
        
        $wallet = $this->cryptoWalletModel->find($id);
        
        if (!$wallet) {
            FlashMessage::error('Crypto wallet not found.');
            header('Location: /crypto-wallets');
            exit();
        }
        
        $this->view('dashboard/crypto-wallets/edit', [
            'wallet' => $wallet,
            'networks' => CryptoWallet::getSupportedNetworks(),
            'currencies' => CryptoWallet::getSupportedCurrencies()
        ]);
    }

    public function update($id) {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /crypto-wallets/' . $id . '/edit');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        $wallet = $this->cryptoWalletModel->find($id);
        
        if (!$wallet) {
            FlashMessage::error('Crypto wallet not found.');
            header('Location: /crypto-wallets');
            exit();
        }
        
        // Validate only editable fields (name and notes)
        $name = trim($_POST['name'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($name)) {
            FlashMessage::error('Wallet name is required.');
            header('Location: /crypto-wallets/' . $id . '/edit');
            exit();
        }
        
        // Use existing values for disabled fields (network, currency, address)
        $data = [
            'name' => $name,
            'notes' => $notes
        ];
        
        try {
            $result = $this->cryptoWalletModel->update($id, $data);
            
            if ($result) {
                AppLogger::info('Crypto wallet updated', [
                    'user_id' => $userId,
                    'wallet_id' => $id,
                    'name' => $name
                ]);
                FlashMessage::success('Crypto wallet updated successfully!');
            } else {
                FlashMessage::error('No changes were made.');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to update crypto wallet', [
                'user_id' => $userId,
                'wallet_id' => $id,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to update crypto wallet. Please try again.');
        }
        
        header('Location: /crypto-wallets');
        exit();
    }

    public function delete($id) {
        $this->checkAuthentication();
        
        if (!$this->validateCsrfToken()) {
            FlashMessage::error('Invalid security token. Please try again.');
            header('Location: /crypto-wallets');
            exit();
        }
        
        $userId = $_SESSION['user']['id'];
        $wallet = $this->cryptoWalletModel->find($id);
        
        if (!$wallet) {
            FlashMessage::error('Crypto wallet not found.');
            header('Location: /crypto-wallets');
            exit();
        }
        
        try {
            // Check if wallet is used in expenses or subscriptions
            $usedInExpenses = $this->cryptoWalletModel->isUsedInExpenses($id);
            $usedInSubscriptions = $this->cryptoWalletModel->isUsedInSubscriptions($id);
            
            if ($usedInExpenses || $usedInSubscriptions) {
                $usageTypes = [];
                if ($usedInExpenses) $usageTypes[] = 'expenses';
                if ($usedInSubscriptions) $usageTypes[] = 'subscriptions';
                
                FlashMessage::error('Cannot delete crypto wallet that is used in ' . implode(' and ', $usageTypes) . '. Please reassign or delete them first.');
                header('Location: /crypto-wallets');
                exit();
            }
            
            $result = $this->cryptoWalletModel->delete($id);
            
            if ($result) {
                AppLogger::info('Crypto wallet deleted', [
                    'user_id' => $userId,
                    'wallet_id' => $id,
                    'name' => $wallet['name']
                ]);
                FlashMessage::success('Crypto wallet deleted successfully!');
            } else {
                FlashMessage::error('Failed to delete crypto wallet. Please try again.');
            }
        } catch (Exception $e) {
            AppLogger::error('Failed to delete crypto wallet', [
                'user_id' => $userId,
                'wallet_id' => $id,
                'error' => $e->getMessage()
            ]);
            FlashMessage::error('Failed to delete crypto wallet. Please try again.');
        }
        
        header('Location: /crypto-wallets');
        exit();
    }

    /**
     * Get crypto wallets for AJAX requests (for expense/subscription forms)
     */
    public function ajaxList() {
        $this->checkAuthentication();
        
        // Return all crypto wallets for centralized system
        $currency = $_GET['currency'] ?? null;
        $network = $_GET['network'] ?? null;
        
        $wallets = $this->cryptoWalletModel->getAllWithUserInfo();
        
        // Filter by currency and/or network if provided
        if ($currency || $network) {
            $wallets = array_filter($wallets, function($wallet) use ($currency, $network) {
                $currencyMatch = !$currency || $wallet['currency'] === $currency;
                $networkMatch = !$network || $wallet['network'] === $network;
                return $currencyMatch && $networkMatch;
            });
        }
        
        // Add masked address for display
        foreach ($wallets as &$wallet) {
            $wallet['address_masked'] = CryptoWallet::getMaskedAddress($wallet['address'] ?? $wallet['address_short']);
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'wallets' => $wallets
        ]);
        exit();
    }

    /**
     * Get wallet details (AJAX endpoint)
     */
    public function details($id) {
        $this->checkAuthentication();
        
        $wallet = $this->cryptoWalletModel->find($id);
        
        if (!$wallet) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Wallet not found']);
            exit();
        }
        
        // Get usage statistics
        $stats = $this->cryptoWalletModel->getUsageStats($id);
        $wallet['stats'] = $stats;
        $wallet['address_masked'] = CryptoWallet::getMaskedAddress($wallet['address']);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'wallet' => $wallet
        ]);
        exit();
    }

    /**
     * Search crypto wallets (AJAX endpoint)
     */
    public function search() {
        $this->checkAuthentication();
        
        $userId = $_SESSION['user']['id'];
        $query = trim($_GET['q'] ?? '');
        
        if (empty($query)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'wallets' => []]);
            exit();
        }
        
        $wallets = $this->cryptoWalletModel->search($userId, $query);
        
        // Add masked address for display
        foreach ($wallets as &$wallet) {
            $wallet['address_masked'] = CryptoWallet::getMaskedAddress($wallet['address']);
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'wallets' => $wallets
        ]);
        exit();
    }

    /**
     * Get wallets by currency (AJAX endpoint)
     */
    public function byCurrency($currency) {
        $this->checkAuthentication();
        
        $userId = $_SESSION['user']['id'];
        
        // Validate currency
        $validCurrencies = CryptoWallet::getSupportedCurrencies();
        if (!isset($validCurrencies[$currency])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid currency']);
            exit();
        }
        
        $wallets = $this->cryptoWalletModel->getByCurrency($userId, $currency);
        
        // Add masked address for display
        foreach ($wallets as &$wallet) {
            $wallet['address_masked'] = CryptoWallet::getMaskedAddress($wallet['address']);
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'wallets' => $wallets
        ]);
        exit();
    }

    /**
     * Get wallets by network (AJAX endpoint)
     */
    public function byNetwork($network) {
        $this->checkAuthentication();
        
        $userId = $_SESSION['user']['id'];
        
        // Validate network
        $validNetworks = array_keys(CryptoWallet::getSupportedNetworks());
        if (!in_array($network, $validNetworks)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid network']);
            exit();
        }
        
        $wallets = $this->cryptoWalletModel->getByNetwork($userId, $network);
        
        // Add masked address for display
        foreach ($wallets as &$wallet) {
            $wallet['address_masked'] = CryptoWallet::getMaskedAddress($wallet['address']);
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'wallets' => $wallets
        ]);
        exit();
    }

    /**
     * Get currencies for selected network (AJAX endpoint)
     */
    public function getCurrenciesForNetwork($network) {
        $this->checkAuthentication();
        
        $validNetworks = array_keys(CryptoWallet::getSupportedNetworks());
        if (!in_array($network, $validNetworks)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid network']);
            exit();
        }
        
        $currencies = CryptoWallet::getCurrenciesForNetwork($network);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'currencies' => $currencies
        ]);
        exit();
    }

    /**
     * Get networks for selected currency (AJAX endpoint)
     */
    public function getNetworksForCurrency($currency) {
        $this->checkAuthentication();
        
        $validCurrencies = CryptoWallet::getSupportedCurrencies();
        if (!isset($validCurrencies[$currency])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid currency']);
            exit();
        }
        
        $networks = $validCurrencies[$currency]['networks'];
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'networks' => $networks
        ]);
        exit();
    }

    /**
     * Validate wallet address (AJAX endpoint)
     */
    public function validateAddress() {
        header('Content-Type: application/json');
        
        $address = trim($_POST['address'] ?? '');
        $network = trim($_POST['network'] ?? '');
        
        $response = [
            'valid' => false,
            'message' => ''
        ];
        
        if (empty($address) || empty($network)) {
            $response['message'] = 'Address and network are required';
        } else {
            $response['valid'] = CryptoWallet::validateAddress($address, $network);
            if (!$response['valid']) {
                $response['message'] = 'Invalid address format for the selected network';
            } else {
                $response['message'] = 'Address is valid';
            }
        }
        
        $response['success'] = true;
        echo json_encode($response);
        exit();
    }
} 