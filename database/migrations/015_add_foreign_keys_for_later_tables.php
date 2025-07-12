<?php

require_once __DIR__ . '/../Migration.php';

class AddForeignKeysForLaterTables extends Migration
{
    public function getName()
    {
        return '015_add_foreign_keys_for_later_tables';
    }
    
    public function up()
    {
        // Add foreign key constraints for tables created after transactions table
        // These tables (expenses, bank_accounts, crypto_wallets) are created after transactions
        // so we need to add their foreign keys in a separate migration
        
        // Check if constraints don't already exist before adding them
        $this->execute("
            SET @constraint_exists = (
                SELECT COUNT(*) 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'transactions' 
                AND CONSTRAINT_NAME = 'transactions_expense_id_foreign'
            );
            
            SET @sql = IF(@constraint_exists = 0, 
                'ALTER TABLE `transactions` ADD CONSTRAINT `transactions_expense_id_foreign` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE SET NULL',
                'SELECT \"Constraint already exists\" as message'
            );
            
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
        
        $this->execute("
            SET @constraint_exists = (
                SELECT COUNT(*) 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'transactions' 
                AND CONSTRAINT_NAME = 'transactions_bank_account_id_foreign'
            );
            
            SET @sql = IF(@constraint_exists = 0, 
                'ALTER TABLE `transactions` ADD CONSTRAINT `transactions_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL',
                'SELECT \"Constraint already exists\" as message'
            );
            
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
        
        $this->execute("
            SET @constraint_exists = (
                SELECT COUNT(*) 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'transactions' 
                AND CONSTRAINT_NAME = 'transactions_crypto_wallet_id_foreign'
            );
            
            SET @sql = IF(@constraint_exists = 0, 
                'ALTER TABLE `transactions` ADD CONSTRAINT `transactions_crypto_wallet_id_foreign` FOREIGN KEY (`crypto_wallet_id`) REFERENCES `crypto_wallets` (`id`) ON DELETE SET NULL',
                'SELECT \"Constraint already exists\" as message'
            );
            
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
        
        // Add foreign keys for subscriptions to later tables
        $this->execute("
            SET @constraint_exists = (
                SELECT COUNT(*) 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'subscriptions' 
                AND CONSTRAINT_NAME = 'subscriptions_bank_account_id_foreign'
            );
            
            SET @sql = IF(@constraint_exists = 0, 
                'ALTER TABLE `subscriptions` ADD CONSTRAINT `subscriptions_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE SET NULL',
                'SELECT \"Constraint already exists\" as message'
            );
            
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
        
        $this->execute("
            SET @constraint_exists = (
                SELECT COUNT(*) 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'subscriptions' 
                AND CONSTRAINT_NAME = 'subscriptions_crypto_wallet_id_foreign'
            );
            
            SET @sql = IF(@constraint_exists = 0, 
                'ALTER TABLE `subscriptions` ADD CONSTRAINT `subscriptions_crypto_wallet_id_foreign` FOREIGN KEY (`crypto_wallet_id`) REFERENCES `crypto_wallets` (`id`) ON DELETE SET NULL',
                'SELECT \"Constraint already exists\" as message'
            );
            
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
    }
    
    public function down()
    {
        // Remove foreign key constraints - use IF EXISTS for safer rollback
        $this->execute("
            SET @constraint_exists = (
                SELECT COUNT(*) 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'subscriptions' 
                AND CONSTRAINT_NAME = 'subscriptions_crypto_wallet_id_foreign'
            );
            
            SET @sql = IF(@constraint_exists > 0, 
                'ALTER TABLE `subscriptions` DROP FOREIGN KEY `subscriptions_crypto_wallet_id_foreign`',
                'SELECT \"Constraint does not exist\" as message'
            );
            
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
        
        $this->execute("
            SET @constraint_exists = (
                SELECT COUNT(*) 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'subscriptions' 
                AND CONSTRAINT_NAME = 'subscriptions_bank_account_id_foreign'
            );
            
            SET @sql = IF(@constraint_exists > 0, 
                'ALTER TABLE `subscriptions` DROP FOREIGN KEY `subscriptions_bank_account_id_foreign`',
                'SELECT \"Constraint does not exist\" as message'
            );
            
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
        
        $this->execute("
            SET @constraint_exists = (
                SELECT COUNT(*) 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'transactions' 
                AND CONSTRAINT_NAME = 'transactions_crypto_wallet_id_foreign'
            );
            
            SET @sql = IF(@constraint_exists > 0, 
                'ALTER TABLE `transactions` DROP FOREIGN KEY `transactions_crypto_wallet_id_foreign`',
                'SELECT \"Constraint does not exist\" as message'
            );
            
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
        
        $this->execute("
            SET @constraint_exists = (
                SELECT COUNT(*) 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'transactions' 
                AND CONSTRAINT_NAME = 'transactions_bank_account_id_foreign'
            );
            
            SET @sql = IF(@constraint_exists > 0, 
                'ALTER TABLE `transactions` DROP FOREIGN KEY `transactions_bank_account_id_foreign`',
                'SELECT \"Constraint does not exist\" as message'
            );
            
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
        
        $this->execute("
            SET @constraint_exists = (
                SELECT COUNT(*) 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'transactions' 
                AND CONSTRAINT_NAME = 'transactions_expense_id_foreign'
            );
            
            SET @sql = IF(@constraint_exists > 0, 
                'ALTER TABLE `transactions` DROP FOREIGN KEY `transactions_expense_id_foreign`',
                'SELECT \"Constraint does not exist\" as message'
            );
            
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
    }
} 