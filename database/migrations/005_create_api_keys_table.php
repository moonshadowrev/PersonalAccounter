<?php

require_once __DIR__ . '/../Migration.php';

class CreateApiKeysTable extends Migration
{
    public function getName()
    {
        return '005_create_api_keys_table';
    }
    
    public function up()
    {
        $this->createTable('api_keys', function($table) {
            $table->id();
            $table->string('name');
            $table->string('api_key', 64)->unique('api_key');
            $table->string('api_key_prefix', 8);
            $table->integer('user_id');
            $table->text('permissions')->nullable();
            $table->integer('rate_limit_per_minute')->default(60);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('is_active')->default(1);
            $table->integer('failed_attempts')->default(0);
            $table->timestamp('blocked_until')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id', 'users', 'id', 'CASCADE');
            $table->index('api_key_prefix');
            $table->index('user_id');
            $table->index('is_active');
            $table->index('expires_at');
        });
    }
    
    public function down()
    {
        $this->dropTable('api_keys');
    }
} 