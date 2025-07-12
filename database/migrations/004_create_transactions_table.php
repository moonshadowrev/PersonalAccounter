<?php

require_once __DIR__ . '/../Migration.php';

class CreateTransactionsTable extends Migration
{
    public function getName()
    {
        return '004_create_transactions_table';
    }
    
    public function up()
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS `transactions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `subscription_id` int(11) NULL,
                `expense_id` int(11) NULL,
                `credit_card_id` int(11) NULL,
                `bank_account_id` int(11) NULL,
                `crypto_wallet_id` int(11) NULL,
                `amount` decimal(12,2) NOT NULL,
                `currency` varchar(3) NOT NULL DEFAULT 'USD',
                `transaction_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `status` enum('successful','failed','pending') NOT NULL DEFAULT 'pending',
                `payment_method_type` varchar(20) NULL,
                `transaction_type` enum('subscription', 'expense') NOT NULL,
                `reference_number` varchar(100) NULL,
                `description` text NULL,
                `notes` text NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `user_id` (`user_id`),
                KEY `subscription_id` (`subscription_id`),
                KEY `expense_id` (`expense_id`),
                KEY `credit_card_id` (`credit_card_id`),
                KEY `bank_account_id` (`bank_account_id`),
                KEY `crypto_wallet_id` (`crypto_wallet_id`),
                KEY `transaction_type` (`transaction_type`),
                KEY `transaction_date` (`transaction_date`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Add foreign key constraints for existing tables
        $this->execute("ALTER TABLE `transactions` 
            ADD CONSTRAINT `transactions_user_id_foreign` 
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE");
        
        $this->execute("ALTER TABLE `transactions` 
            ADD CONSTRAINT `transactions_subscription_id_foreign` 
            FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL");
        
        $this->execute("ALTER TABLE `transactions` 
            ADD CONSTRAINT `transactions_credit_card_id_foreign` 
            FOREIGN KEY (`credit_card_id`) REFERENCES `credit_cards` (`id`) ON DELETE SET NULL");
    }
    
    public function down()
    {
        $this->dropTable('transactions');
    }
} 