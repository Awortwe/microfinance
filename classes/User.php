<?php
/**
 * User Class
 * Handles all user-related operations
 */

class User {
    private $db;
    private $table = 'users';

    // User properties
    public $id;
    public $username;
    public $password;
    public $full_name;
    public $email;
    public $phone;
    public $user_type;
    public $is_active;
    public $last_login;
    public $created_at;
    public $updated_at;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create new user
     */
    public function create($data) {
        try {
            $this->db->beginTransaction();

            $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 10]);

            $query = "INSERT INTO {$this->table} 
                     (username, password, full_name, email, phone, user_type) 
                     VALUES 
                     (:username, :password, :full_name, :email, :phone, :user_type)";

            $this->db->query($query);
            $this->db->bindMultiple([
                ':username' => $data['username'],
                ':password' => $hashed_password,
                ':full_name' => $data['full_name'],
                ':email' => $data['email'],
                ':phone' => $data['phone'] ?? null,
                ':user_type' => $data['user_type']
            ]);

            if ($this->db->execute()) {
                $user_id = $this->db->lastInsertId();
                $this->db->commit();
                
                // Log activity
                logActivity('User Created', 'users', $user_id, null, $data);
                
                return ['success' => true, 'id' => $user_id, 'message' => 'User created successfully'];
            }

            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to create user'];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Create User Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Update user
     */
    public function update($id, $data) {
        try {
            // Get old values for audit trail
            $old_values = $this->getById($id);

            $this->db->beginTransaction();

            $query = "UPDATE {$this->table} SET 
                     full_name = :full_name,
                     email = :email,
                     phone = :phone,
                     user_type = :user_type,
                     is_active = :is_active";

            // Add password update if provided
            if (!empty($data['password'])) {
                $query .= ", password = :password";
            }

            $query .= " WHERE id = :id";

            $this->db->query($query);
            
            $params = [
                ':full_name' => $data['full_name'],
                ':email' => $data['email'],
                ':phone' => $data['phone'] ?? null,
                ':user_type' => $data['user_type'],
                ':is_active' => $data['is_active'] ?? true,
                ':id' => $id
            ];

            if (!empty($data['password'])) {
                $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            }

            $this->db->bindMultiple($params);

            if ($this->db->execute()) {
                $this->db->commit();
                
                // Log activity
                logActivity('User Updated', 'users', $id, $old_values, $data);
                
                return ['success' => true, 'message' => 'User updated successfully'];
            }

            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to update user'];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Update User Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Delete user (soft delete - deactivate)
     */
    public function delete($id) {
        try {
            $old_values = $this->getById($id);

            $query = "UPDATE {$this->table} SET is_active = 0 WHERE id = :id";
            $this->db->query($query);
            $this->db->bind(':id', $id);

            if ($this->db->execute()) {
                logActivity('User Deactivated', 'users', $id, $old_values, ['is_active' => 0]);
                return ['success' => true, 'message' => 'User deactivated successfully'];
            }

            return ['success' => false, 'message' => 'Failed to deactivate user'];

        } catch (Exception $e) {
            error_log("Delete User Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Get user by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Get user by username
     */
    public function getByUsername($username) {
        $query = "SELECT * FROM {$this->table} WHERE username = :username";
        $this->db->query($query);
        $this->db->bind(':username', $username);
        return $this->db->single();
    }

    /**
     * Get user by email
     */
    public function getByEmail($email) {
        $query = "SELECT * FROM {$this->table} WHERE email = :email";
        $this->db->query($query);
        $this->db->bind(':email', $email);
        return $this->db->single();
    }

    /**
     * Get all users
     */
    public function getAll($filters = []) {
        $query = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if (isset($filters['user_type'])) {
            $query .= " AND user_type = :user_type";
            $params[':user_type'] = $filters['user_type'];
        }

        if (isset($filters['is_active'])) {
            $query .= " AND is_active = :is_active";
            $params[':is_active'] = $filters['is_active'];
        }

        if (isset($filters['search'])) {
            $query .= " AND (full_name LIKE :search OR username LIKE :search OR email LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $query .= " ORDER BY created_at DESC";

        $this->db->query($query);
        if (!empty($params)) {
            $this->db->bindMultiple($params);
        }

        return $this->db->resultSet();
    }

    /**
     * Authenticate user
     */
    public function authenticate($username, $password) {
        try {
            $user = $this->getByUsername($username);

            if ($user && $user['is_active'] && password_verify($password, $user['password'])) {
                // Update last login
                $this->updateLastLogin($user['id']);
                
                // Log successful login
                logActivity('Login Successful', 'auth', $user['id']);
                
                return [
                    'success' => true,
                    'user' => $user
                ];
            }

            // Log failed attempt
            if ($user) {
                logActivity('Login Failed', 'auth', $user['id']);
            }

            return [
                'success' => false,
                'message' => 'Invalid username or password'
            ];

        } catch (Exception $e) {
            error_log("Authentication Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Authentication error'];
        }
    }

    /**
     * Change password
     */
    public function changePassword($user_id, $current_password, $new_password) {
        try {
            $user = $this->getById($user_id);

            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }

            if (!password_verify($current_password, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }

            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            $query = "UPDATE {$this->table} SET password = :password WHERE id = :id";
            $this->db->query($query);
            $this->db->bindMultiple([
                ':password' => $hashed_password,
                ':id' => $user_id
            ]);

            if ($this->db->execute()) {
                logActivity('Password Changed', 'auth', $user_id);
                return ['success' => true, 'message' => 'Password changed successfully'];
            }

            return ['success' => false, 'message' => 'Failed to change password'];

        } catch (Exception $e) {
            error_log("Change Password Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Update last login
     */
    private function updateLastLogin($user_id) {
        $query = "UPDATE {$this->table} SET last_login = NOW() WHERE id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $user_id);
        $this->db->execute();
    }

    /**
     * Check if username exists
     */
    public function usernameExists($username, $exclude_id = null) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE username = :username";
        $params = [':username' => $username];

        if ($exclude_id) {
            $query .= " AND id != :id";
            $params[':id'] = $exclude_id;
        }

        $this->db->query($query);
        $this->db->bindMultiple($params);
        $result = $this->db->single();
        
        return $result['count'] > 0;
    }

    /**
     * Check if email exists
     */
    public function emailExists($email, $exclude_id = null) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = :email";
        $params = [':email' => $email];

        if ($exclude_id) {
            $query .= " AND id != :id";
            $params[':id'] = $exclude_id;
        }

        $this->db->query($query);
        $this->db->bindMultiple($params);
        $result = $this->db->single();
        
        return $result['count'] > 0;
    }

    /**
     * Get user count
     */
    public function getCount($user_type = null) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE 1=1";
        $params = [];

        if ($user_type) {
            $query .= " AND user_type = :user_type";
            $params[':user_type'] = $user_type;
        }

        $this->db->query($query);
        if (!empty($params)) {
            $this->db->bindMultiple($params);
        }
        
        $result = $this->db->single();
        return $result['count'];
    }
}
?>