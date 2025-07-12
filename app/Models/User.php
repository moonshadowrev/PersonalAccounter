<?php

require_once __DIR__ . '/Model.php';

class User extends Model {

    protected $table = 'users';

    public function findByEmail($email) {
        return $this->db->get('users', '*', ['email' => $email]);
    }

    public function createUser($name, $email, $password, $role = 'admin') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        return $this->db->insert('users', [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role
        ]);
    }

    public function update($id, $data) {
        $originalData = $data;
        
        if (isset($data['password']) && !empty($data['password'])) {
            $plainPassword = $data['password'];
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            
            AppLogger::info('Password hashing in User model', [
                'user_id' => $id,
                'plain_password_length' => strlen($plainPassword),
                'hashed_password_length' => strlen($data['password']),
                'hash_starts_with' => substr($data['password'], 0, 10)
            ]);
        } else {
            unset($data['password']);
            AppLogger::info('No password update in User model', [
                'user_id' => $id,
                'original_data_keys' => array_keys($originalData)
            ]);
        }
        
        $result = $this->db->update('users', $data, ['id' => $id]);
        
        AppLogger::info('Database update result in User model', [
            'user_id' => $id,
            'update_result' => $result,
            'affected_rows' => $this->db->info()['affected_rows'] ?? 'unknown',
            'data_keys' => array_keys($data)
        ]);
        
        return $result;
    }

    public function delete($id) {
        return $this->db->delete('users', ['id' => $id]);
    }
    
    public function enable2FA($id, $secret, $backupCodes) {
        return $this->db->update('users', [
            'two_factor_secret' => $secret,
            'two_factor_enabled' => true,
            'two_factor_backup_codes' => json_encode($backupCodes)
        ], ['id' => $id]);
    }
    
    public function disable2FA($id) {
        return $this->db->update('users', [
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_backup_codes' => null
        ], ['id' => $id]);
    }
    
    public function updateBackupCodes($id, $backupCodes) {
        return $this->db->update('users', [
            'two_factor_backup_codes' => $backupCodes
        ], ['id' => $id]);
    }
} 