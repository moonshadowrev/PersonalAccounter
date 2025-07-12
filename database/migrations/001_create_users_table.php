<?php

require_once __DIR__ . '/../Migration.php';

class CreateUsersTable extends Migration
{
    public function getName()
    {
        return '001_create_users_table';
    }
    
    public function up()
    {
        $this->createTable('users', function($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique('email');
            $table->string('password');
            $table->enum('role', ['admin', 'superadmin'])->default('admin');
            $table->string('two_factor_secret')->nullable();
            $table->integer('two_factor_enabled')->default(0);
            $table->text('two_factor_backup_codes')->nullable();
            $table->timestamps();
        });
    }
    
    public function down()
    {
        $this->dropTable('users');
    }
} 