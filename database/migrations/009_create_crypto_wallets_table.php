<?php

require_once __DIR__ . '/../Migration.php';

class CreateCryptoWalletsTable extends Migration
{
    public function getName()
    {
        return '009_create_crypto_wallets_table';
    }
    
    public function up()
    {
        $this->createTable('crypto_wallets', function($table) {
            $table->id();
            $table->integer('user_id')->index('user_id');
            $table->string('name'); // User-friendly name like "Main USDT Wallet"
            $table->string('currency'); // USDT, BTC, ETH, TRX, etc.
            $table->string('network'); // TRC20, BEP20, ERC20, etc.
            $table->string('address'); // Wallet address
            $table->string('address_short', 20); // Shortened address for display (first 8 + last 8)
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('user_id', 'users', 'id', 'CASCADE');
            $table->index('currency');
            $table->index('network');
        });
    }
    
    public function down()
    {
        $this->dropTable('crypto_wallets');
    }
} 