<?php
/**
 * Customer Class
 * Handles all customer-related operations
 */

class Customer {
    private $db;
    private $table = 'customers';

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create new customer
     */
    public function create($data) {
        try {
            $this->db->beginTransaction();

            // Generate customer code
            $customer_code = $this->generateCode();

            $query = "INSERT INTO {$this->table} 
                     (customer_code, first_name, last_name, email, phone, alternate_phone, 
                      address, city, region, id_type, id_number, occupation, business_name, 
                      business_address, date_of_birth, gender, marital_status, created_by) 
                     VALUES 
                     (:customer_code, :first_name, :last_name, :email, :phone, :alternate_phone,
                      :address, :city, :region, :id_type, :id_number, :occupation, :business_name,
                      :business_address, :date_of_birth, :gender, :marital_status, :created_by)";

            $this->db->query($query);
            $this->db->bindMultiple([
                ':customer_code' => $customer_code,
                ':first_name' => $data['first_name'],
                ':last_name' => $data['last_name'],
                ':email' => $data['email'] ?? null,
                ':phone' => $data['phone'],
                ':alternate_phone' => $data['alternate_phone'] ?? null,
                ':address' => $data['address'] ?? null,
                ':city' => $data['city'] ?? null,
                ':region' => $data['region'] ?? null,
                ':id_type' => $data['id_type'] ?? 'Ghana Card',
                ':id_number' => $data['id_number'] ?? null,
                ':occupation' => $data['occupation'] ?? null,
                ':business_name' => $data['business_name'] ?? null,
                ':business_address' => $data['business_address'] ?? null,
                ':date_of_birth' => $data['date_of_birth'] ?? null,
                ':gender' => $data['gender'] ?? null,
                ':marital_status' => $data['marital_status'] ?? null,
                ':created_by' => $_SESSION['user_id']
            ]);

            if ($this->db->execute()) {
                $customer_id = $this->db->lastInsertId();
                $this->db->commit();
                
                logActivity('Customer Created', 'customers', $customer_id, null, $data);
                
                return [
                    'success' => true, 
                    'id' => $customer_id, 
                    'customer_code' => $customer_code,
                    'message' => 'Customer created successfully'
                ];
            }

            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to create customer'];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Create Customer Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Update customer
     */
    public function update($id, $data) {
        try {
            $old_values = $this->getById($id);

            $query = "UPDATE {$this->table} SET 
                     first_name = :first_name,
                     last_name = :last_name,
                     email = :email,
                     phone = :phone,
                     alternate_phone = :alternate_phone,
                     address = :address,
                     city = :city,
                     region = :region,
                     id_type = :id_type,
                     id_number = :id_number,
                     occupation = :occupation,
                     business_name = :business_name,
                     business_address = :business_address,
                     date_of_birth = :date_of_birth,
                     gender = :gender,
                     marital_status = :marital_status,
                     status = :status
                     WHERE id = :id";

            $this->db->query($query);
            $this->db->bindMultiple([
                ':first_name' => $data['first_name'],
                ':last_name' => $data['last_name'],
                ':email' => $data['email'] ?? null,
                ':phone' => $data['phone'],
                ':alternate_phone' => $data['alternate_phone'] ?? null,
                ':address' => $data['address'] ?? null,
                ':city' => $data['city'] ?? null,
                ':region' => $data['region'] ?? null,
                ':id_type' => $data['id_type'] ?? 'Ghana Card',
                ':id_number' => $data['id_number'] ?? null,
                ':occupation' => $data['occupation'] ?? null,
                ':business_name' => $data['business_name'] ?? null,
                ':business_address' => $data['business_address'] ?? null,
                ':date_of_birth' => $data['date_of_birth'] ?? null,
                ':gender' => $data['gender'] ?? null,
                ':marital_status' => $data['marital_status'] ?? null,
                ':status' => $data['status'] ?? 'active',
                ':id' => $id
            ]);

            if ($this->db->execute()) {
                logActivity('Customer Updated', 'customers', $id, $old_values, $data);
                return ['success' => true, 'message' => 'Customer updated successfully'];
            }

            return ['success' => false, 'message' => 'Failed to update customer'];

        } catch (Exception $e) {
            error_log("Update Customer Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Delete customer
     */
    public function delete($id) {
        try {
            // Check if customer has active loans or savings
            if ($this->hasActiveAccounts($id)) {
                return ['success' => false, 'message' => 'Cannot delete customer with active accounts'];
            }

            $old_values = $this->getById($id);

            // Soft delete - change status to inactive
            $query = "UPDATE {$this->table} SET status = 'inactive' WHERE id = :id";
            $this->db->query($query);
            $this->db->bind(':id', $id);

            if ($this->db->execute()) {
                logActivity('Customer Deleted', 'customers', $id, $old_values, ['status' => 'inactive']);
                return ['success' => true, 'message' => 'Customer deleted successfully'];
            }

            return ['success' => false, 'message' => 'Failed to delete customer'];

        } catch (Exception $e) {
            error_log("Delete Customer Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Get customer by ID
     */
    public function getById($id) {
        $query = "SELECT c.*, 
                  CONCAT(u.full_name) as created_by_name
                  FROM {$this->table} c
                  LEFT JOIN users u ON c.created_by = u.id
                  WHERE c.id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Get customer by code
     */
    public function getByCode($customer_code) {
        $query = "SELECT * FROM {$this->table} WHERE customer_code = :code";
        $this->db->query($query);
        $this->db->bind(':code', $customer_code);
        return $this->db->single();
    }

    /**
     * Get all customers
     */
    public function getAll($filters = [], $pagination = []) {
        $query = "SELECT c.*, 
                  CONCAT(u.full_name) as created_by_name
                  FROM {$this->table} c
                  LEFT JOIN users u ON c.created_by = u.id
                  WHERE 1=1";
        
        $params = [];

        // Apply filters
        if (isset($filters['status'])) {
            $query .= " AND c.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (isset($filters['search'])) {
            $query .= " AND (c.first_name LIKE :search1 OR c.last_name LIKE :search2 
                       OR c.phone LIKE :search3 OR c.customer_code LIKE :search4
                       OR c.email LIKE :search5)";
            $search = '%' . $filters['search'] . '%';
            $params[':search1'] = $search;
            $params[':search2'] = $search;
            $params[':search3'] = $search;
            $params[':search4'] = $search;
            $params[':search5'] = $search;
        }

        if (isset($filters['created_by'])) {
            $query .= " AND c.created_by = :created_by";
            $params[':created_by'] = $filters['created_by'];
        }

        if (isset($filters['gender'])) {
            $query .= " AND c.gender = :gender";
            $params[':gender'] = $filters['gender'];
        }

        // Get total count for pagination
        $countQuery = str_replace("SELECT c.*, CONCAT(u.full_name) as created_by_name", "SELECT COUNT(*) as total", $query);
        $this->db->query($countQuery);
        if (!empty($params)) {
            $this->db->bindMultiple($params);
        }
        $total = $this->db->single()['total'];

        // Add ordering
        $query .= " ORDER BY c.created_at DESC";

        // Add pagination
        if (!empty($pagination)) {
            $per_page = isset($pagination['per_page']) ? $pagination['per_page'] : 20;
            $page = isset($pagination['page']) ? $pagination['page'] : 1;
            $offset = ($page - 1) * $per_page;
            $query .= " LIMIT :offset, :per_page";
            $params[':offset'] = (int)$offset;
            $params[':per_page'] = (int)$per_page;
        }

        $this->db->query($query);
        if (!empty($params)) {
            $this->db->bindMultiple($params);
        }
        $customers = $this->db->resultSet();

        return [
            'data' => $customers,
            'total' => $total
        ];
    }

    /**
     * Search customers
     */
    public function search($term) {
        return $this->getAll(['search' => $term]);
    }

    /**
     * Get customer accounts summary
     */
    public function getAccountsSummary($customer_id) {
        // Savings accounts
        $query = "SELECT COUNT(*) as total_accounts, 
                  COALESCE(SUM(balance), 0) as total_balance
                  FROM savings_accounts 
                  WHERE customer_id = :customer_id AND status = 'active'";
        $this->db->query($query);
        $this->db->bind(':customer_id', $customer_id);
        $savings = $this->db->single();

        // Active loans
        $query = "SELECT COUNT(*) as total_loans,
                  COALESCE(SUM(principal_amount), 0) as total_principal,
                  COALESCE(SUM(balance), 0) as total_outstanding
                  FROM loans 
                  WHERE customer_id = :customer_id AND status IN ('active', 'disbursed')";
        $this->db->query($query);
        $this->db->bind(':customer_id', $customer_id);
        $loans = $this->db->single();

        return [
            'savings_accounts' => $savings['total_accounts'],
            'total_savings' => $savings['total_balance'],
            'active_loans' => $loans['total_loans'],
            'total_loan_principal' => $loans['total_principal'],
            'total_loan_outstanding' => $loans['total_outstanding']
        ];
    }

    /**
     * Check if customer has active accounts
     */
    public function hasActiveAccounts($customer_id) {
        // Check savings
        $query = "SELECT COUNT(*) as count FROM savings_accounts 
                  WHERE customer_id = :id AND status = 'active'";
        $this->db->query($query);
        $this->db->bind(':id', $customer_id);
        if ($this->db->single()['count'] > 0) return true;

        // Check loans
        $query = "SELECT COUNT(*) as count FROM loans 
                  WHERE customer_id = :id AND status IN ('active', 'disbursed')";
        $this->db->query($query);
        $this->db->bind(':id', $customer_id);
        if ($this->db->single()['count'] > 0) return true;

        return false;
    }

    /**
     * Blacklist customer
     */
    public function blacklist($id, $reason = null) {
        try {
            $old_values = $this->getById($id);

            $query = "UPDATE {$this->table} SET status = 'blacklisted' WHERE id = :id";
            $this->db->query($query);
            $this->db->bind(':id', $id);

            if ($this->db->execute()) {
                logActivity('Customer Blacklisted', 'customers', $id, $old_values, ['status' => 'blacklisted', 'reason' => $reason]);
                return ['success' => true, 'message' => 'Customer blacklisted successfully'];
            }

            return ['success' => false, 'message' => 'Failed to blacklist customer'];

        } catch (Exception $e) {
            error_log("Blacklist Customer Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Generate customer code
     */
    private function generateCode() {
        $query = "SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM {$this->table}";
        $this->db->query($query);
        $result = $this->db->single();
        return 'CUS' . str_pad($result['next_id'], 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get customer count
     */
    public function getCount($status = 'active') {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE status = :status";
        $this->db->query($query);
        $this->db->bind(':status', $status);
        return $this->db->single()['count'];
    }
}
?>