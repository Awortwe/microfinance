<?php
/**
 * Savings Class
 * Handles all savings and susu operations
 */

class Savings {
    private $db;
    private $table = 'savings_accounts';
    private $transactionTable = 'savings_transactions';

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create savings account
     */
    public function createAccount($data) {
        try {
            $this->db->beginTransaction();

            $account_number = $this->generateAccountNumber();

            $query = "INSERT INTO {$this->table} 
                     (customer_id, account_number, account_type, interest_rate, 
                      susu_amount, susu_collection_day, opened_date, created_by) 
                     VALUES 
                     (:customer_id, :account_number, :account_type, :interest_rate,
                      :susu_amount, :susu_collection_day, :opened_date, :created_by)";

            $this->db->query($query);
            $this->db->bindMultiple([
                ':customer_id' => $data['customer_id'],
                ':account_number' => $account_number,
                ':account_type' => $data['account_type'] ?? 'regular',
                ':interest_rate' => $data['interest_rate'] ?? 0,
                ':susu_amount' => $data['susu_amount'] ?? 0,
                ':susu_collection_day' => $data['susu_collection_day'] ?? 'daily',
                ':opened_date' => $data['opened_date'] ?? date('Y-m-d'),
                ':created_by' => $_SESSION['user_id']
            ]);

            if ($this->db->execute()) {
                $account_id = $this->db->lastInsertId();
                $this->db->commit();
                
                logActivity('Savings Account Created', 'savings', $account_id, null, $data);
                
                return [
                    'success' => true, 
                    'id' => $account_id, 
                    'account_number' => $account_number,
                    'message' => 'Savings account created successfully'
                ];
            }

            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to create savings account'];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Create Savings Account Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Make deposit
     */
    public function deposit($account_id, $amount, $description = null, $payment_method = 'cash') {
        try {
            $this->db->beginTransaction();

            // Get current balance
            $account = $this->getAccountById($account_id);
            if (!$account) {
                return ['success' => false, 'message' => 'Account not found'];
            }

            if ($account['status'] != 'active') {
                return ['success' => false, 'message' => 'Account is not active'];
            }

            $balance_before = $account['balance'];
            $balance_after = $balance_before + $amount;

            // Insert transaction
            $query = "INSERT INTO {$this->transactionTable} 
                     (account_id, transaction_type, amount, balance_before, balance_after, 
                      description, transaction_date, transaction_time, payment_method, processed_by) 
                     VALUES 
                     (:account_id, 'deposit', :amount, :balance_before, :balance_after,
                      :description, CURDATE(), CURTIME(), :payment_method, :processed_by)";

            $this->db->query($query);
            $this->db->bindMultiple([
                ':account_id' => $account_id,
                ':amount' => $amount,
                ':balance_before' => $balance_before,
                ':balance_after' => $balance_after,
                ':description' => $description,
                ':payment_method' => $payment_method,
                ':processed_by' => $_SESSION['user_id']
            ]);

            if ($this->db->execute()) {
                // Update account balance
                $query = "UPDATE {$this->table} SET balance = balance + :amount WHERE id = :id";
                $this->db->query($query);
                $this->db->bindMultiple([
                    ':amount' => $amount,
                    ':id' => $account_id
                ]);
                $this->db->execute();

                $this->db->commit();
                
                logActivity('Deposit Made', 'savings', $account_id, 
                           ['balance' => $balance_before], 
                           ['balance' => $balance_after, 'amount' => $amount]);
                
                return [
                    'success' => true, 
                    'message' => 'Deposit successful',
                    'balance_before' => $balance_before,
                    'balance_after' => $balance_after
                ];
            }

            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to process deposit'];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Deposit Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Make withdrawal
     */
    public function withdraw($account_id, $amount, $description = null, $payment_method = 'cash') {
        try {
            $this->db->beginTransaction();

            // Get current balance
            $account = $this->getAccountById($account_id);
            if (!$account) {
                return ['success' => false, 'message' => 'Account not found'];
            }

            if ($account['status'] != 'active') {
                return ['success' => false, 'message' => 'Account is not active'];
            }

            if ($account['balance'] < $amount) {
                return ['success' => false, 'message' => 'Insufficient balance'];
            }

            $balance_before = $account['balance'];
            $balance_after = $balance_before - $amount;

            // Insert transaction
            $query = "INSERT INTO {$this->transactionTable} 
                     (account_id, transaction_type, amount, balance_before, balance_after,
                      description, transaction_date, transaction_time, payment_method, processed_by) 
                     VALUES 
                     (:account_id, 'withdrawal', :amount, :balance_before, :balance_after,
                      :description, CURDATE(), CURTIME(), :payment_method, :processed_by)";

            $this->db->query($query);
            $this->db->bindMultiple([
                ':account_id' => $account_id,
                ':amount' => $amount,
                ':balance_before' => $balance_before,
                ':balance_after' => $balance_after,
                ':description' => $description,
                ':payment_method' => $payment_method,
                ':processed_by' => $_SESSION['user_id']
            ]);

            if ($this->db->execute()) {
                // Update account balance
                $query = "UPDATE {$this->table} SET balance = balance - :amount WHERE id = :id";
                $this->db->query($query);
                $this->db->bindMultiple([
                    ':amount' => $amount,
                    ':id' => $account_id
                ]);
                $this->db->execute();

                $this->db->commit();
                
                logActivity('Withdrawal Made', 'savings', $account_id,
                           ['balance' => $balance_before],
                           ['balance' => $balance_after, 'amount' => $amount]);
                
                return [
                    'success' => true,
                    'message' => 'Withdrawal successful',
                    'balance_before' => $balance_before,
                    'balance_after' => $balance_after
                ];
            }

            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to process withdrawal'];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Withdrawal Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Get account by ID
     */
    public function getAccountById($id) {
        $query = "SELECT sa.*, 
                  CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                  c.phone as customer_phone,
                  c.customer_code
                  FROM {$this->table} sa
                  JOIN customers c ON sa.customer_id = c.id
                  WHERE sa.id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Get account by number
     */
    public function getAccountByNumber($account_number) {
        $query = "SELECT sa.*, 
                  CONCAT(c.first_name, ' ', c.last_name) as customer_name
                  FROM {$this->table} sa
                  JOIN customers c ON sa.customer_id = c.id
                  WHERE sa.account_number = :account_number";
        $this->db->query($query);
        $this->db->bind(':account_number', $account_number);
        return $this->db->single();
    }

    /**
     * Get account transactions
     */
    public function getTransactions($account_id, $filters = [], $pagination = []) {
        $query = "SELECT st.*, u.full_name as processed_by_name
                  FROM {$this->transactionTable} st
                  LEFT JOIN users u ON st.processed_by = u.id
                  WHERE st.account_id = :account_id";
        
        $params = [':account_id' => $account_id];

        if (isset($filters['type'])) {
            $query .= " AND st.transaction_type = :type";
            $params[':type'] = $filters['type'];
        }

        if (isset($filters['date_from'])) {
            $query .= " AND st.transaction_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        if (isset($filters['date_to'])) {
            $query .= " AND st.transaction_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $query .= " ORDER BY st.transaction_date DESC, st.transaction_time DESC";

        if (!empty($pagination)) {
            $per_page = isset($pagination['per_page']) ? $pagination['per_page'] : 20;
            $page = isset($pagination['page']) ? $pagination['page'] : 1;
            $offset = ($page - 1) * $per_page;
            $query .= " LIMIT :offset, :per_page";
            $params[':offset'] = (int)$offset;
            $params[':per_page'] = (int)$per_page;
        }

        $this->db->query($query);
        $this->db->bindMultiple($params);
        return $this->db->resultSet();
    }

    /**
     * Get customer accounts
     */
    public function getCustomerAccounts($customer_id) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE customer_id = :customer_id 
                  ORDER BY opened_date DESC";
        $this->db->query($query);
        $this->db->bind(':customer_id', $customer_id);
        return $this->db->resultSet();
    }

    /**
     * Close account
     */
    public function closeAccount($account_id) {
        try {
            $account = $this->getAccountById($account_id);
            
            if (!$account) {
                return ['success' => false, 'message' => 'Account not found'];
            }

            if ($account['balance'] > 0) {
                return ['success' => false, 'message' => 'Please withdraw all funds before closing'];
            }

            $query = "UPDATE {$this->table} SET 
                      status = 'closed', 
                      closed_date = CURDATE() 
                      WHERE id = :id";
            $this->db->query($query);
            $this->db->bind(':id', $account_id);

            if ($this->db->execute()) {
                logActivity('Account Closed', 'savings', $account_id);
                return ['success' => true, 'message' => 'Account closed successfully'];
            }

            return ['success' => false, 'message' => 'Failed to close account'];

        } catch (Exception $e) {
            error_log("Close Account Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Generate account number
     */
    private function generateAccountNumber() {
        $query = "SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM {$this->table}";
        $this->db->query($query);
        $result = $this->db->single();
        return 'SAV' . str_pad($result['next_id'], 8, '0', STR_PAD_LEFT);
    }

    /**
     * Get total savings balance
     */
    public function getTotalBalance() {
        $query = "SELECT COALESCE(SUM(balance), 0) as total FROM {$this->table} WHERE status = 'active'";
        $this->db->query($query);
        return $this->db->single()['total'];
    }
}
?>