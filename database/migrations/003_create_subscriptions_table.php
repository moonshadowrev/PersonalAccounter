<?php

require_once __DIR__ . '/../Migration.php';

class CreateSubscriptionsTable extends Migration
{
    public function getName()
    {
        return '003_create_subscriptions_table';
    }
    
    public function up()
    {
        $this->createTable('subscriptions', function($table) {
            $table->id();
            $table->integer('user_id')->index('user_id');
            $table->integer('credit_card_id')->nullable()->index('credit_card_id');
            $table->integer('bank_account_id')->nullable()->index('bank_account_id');
            $table->integer('crypto_wallet_id')->nullable()->index('crypto_wallet_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('billing_cycle', ['monthly', 'yearly', 'one-time', 'weekly', 'quarterly']);
            $table->string('payment_method_type', 20)->nullable();
            $table->date('next_payment_date')->nullable();
            $table->enum('status', ['active', 'cancelled', 'expired'])->default('active');
            $table->timestamps();
            $table->foreign('user_id', 'users', 'id', 'CASCADE');
            $table->foreign('credit_card_id', 'credit_cards', 'id', 'SET NULL');
        });
    }
    
    public function down()
    {
        $this->dropTable('subscriptions');
    }
} 