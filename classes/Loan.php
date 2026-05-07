<?php
/**
 * Loan Class
 * Handles all loan-related operations
 */

class Loan {
    private $db;
    private $loansTable = 'loans';
    private $repaymentsTable = 'loan_repayments';
    private $scheduleTable = 'loan_schedule';
    private $productsTable = 'loan_products';

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create loan application
     */
    public function create($data) {
        try {
            $this->db->beginTransaction();

            // Check if customer has too many active loans
            if ($this->getActiveLoanCount($data['customer_id']) >= MAX_ACTIVE_LOANS) {
                return ['success' => false, 'message' => 'Customer has reached maximum active loans limit'];
            }

            // Get loan product
            $product = $this->getProductById($data['product_id']);
            if (!$product) {
                return ['success' => false, 'message' => 'Loan product not found'];
            }

            // Calculate loan details
            $total_interest = $this->calculateTotalInterest(
                $data['principal_amount'],
                $product['interest_rate'],
                $data['duration_months'],
                $product['interest_type']
            );

            $processing_fee = ($data['principal_amount'] * $product['processing_fee_percentage']) / 100;
            $total_amount = $data['principal_amount'] + $total_interest + $processing_fee;
            $monthly_payment = $total_amount / $data['duration_months'];

            // Generate loan number
            $loan_number = $this->generateLoanNumber();

            // Insert loan
            $query = "INSERT INTO {$this->loansTable} 
                     (customer_id, product_id, loan_number, principal_amount, interest_rate, 
                      interest_type, total_interest, processing_fee, total_amount, 
                      monthly_payment, duration_months, balance, application_date, status, created_by) 
                     VALUES 
                     (:customer_id, :product_id, :loan_number, :principal_amount, :interest_rate,
                      :interest_type, :total_interest, :processing_fee, :total_amount,
                      :monthly_payment, :duration_months, :balance, CURDATE(), 'pending', :created_by)";

            $this->db->query($query);
            $this->db->bindMultiple([
                ':customer_id' => $data['customer_id'],
                ':product_id' => $data['product_id'],
                ':loan_number' => $loan_number,
                ':principal_amount' => $data['principal_amount'],
                ':interest_rate' => $product['interest_rate'],
                ':interest_type' => $product['interest_type'],
                ':total_interest' => $total_interest,
                ':processing_fee' => $processing_fee,
                ':total_amount' => $total_amount,
                ':monthly_payment' => $monthly_payment,
                ':duration_months' => $data['duration_months'],
                ':balance' => $total_amount,
                ':created_by' => $_SESSION['user_id']
            ]);

            if ($this->db->execute()) {
                $loan_id = $this->db->lastInsertId();
                $this->db->commit();
                
                logActivity('Loan Created', 'loans', $loan_id, null, $data);
                
                return [
                    'success' => true,
                    'id' => $loan_id,
                    'loan_number' => $loan_number,
                    'total_interest' => $total_interest,
                    'processing_fee' => $processing_fee,
                    'total_amount' => $total_amount,
                    'monthly_payment' => $monthly_payment,
                    'message' => 'Loan application submitted successfully'
                ];
            }

            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to create loan'];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Create Loan Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Approve loan
     */
    public function approve($loan_id) {
        try {
            $this->db->beginTransaction();

            $loan = $this->getById($loan_id);
            if (!$loan) {
                return ['success' => false, 'message' => 'Loan not found'];
            }

            if ($loan['status'] != 'pending') {
                return ['success' => false, 'message' => 'Loan cannot be approved in current status'];
            }

            $query = "UPDATE {$this->loansTable} SET 
                      status = 'approved', 
                      approved_by = :approved_by,
                      approval_date = CURDATE() 
                      WHERE id = :id";

            $this->db->query($query);
            $this->db->bindMultiple([
                ':approved_by' => $_SESSION['user_id'],
                ':id' => $loan_id
            ]);

            if ($this->db->execute()) {
                $this->db->commit();
                
                logActivity('Loan Approved', 'loans', $loan_id, 
                           ['status' => 'pending'], 
                           ['status' => 'approved']);
                
                return ['success' => true, 'message' => 'Loan approved successfully'];
            }

            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to approve loan'];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Approve Loan Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Disburse loan
     */
    public function disburse($loan_id, $disbursement_date = null) {
        try {
            $this->db->beginTransaction();

            $loan = $this->getById($loan_id);
            if (!$loan) {
                return ['success' => false, 'message' => 'Loan not found'];
            }

            if ($loan['status'] != 'approved') {
                return ['success' => false, 'message' => 'Loan must be approved before disbursement'];
            }

            $disbursement_date = $disbursement_date ?? date('Y-m-d');
            $expected_end_date = date('Y-m-d', strtotime($disbursement_date . ' + ' . $loan['duration_months'] . ' months'));

            // Update loan status
            $query = "UPDATE {$this->loansTable} SET 
                      status = 'disbursed', 
                      disbursed_by = :disbursed_by,
                      disbursement_date = :disbursement_date,
                      expected_end_date = :expected_end_date 
                      WHERE id = :id";

            $this->db->query($query);
            $this->db->bindMultiple([
                ':disbursed_by' => $_SESSION['user_id'],
                ':disbursement_date' => $disbursement_date,
                ':expected_end_date' => $expected_end_date,
                ':id' => $loan_id
            ]);

            if ($this->db->execute()) {
                // Generate repayment schedule
                $this->generateRepaymentSchedule($loan, $disbursement_date);
                
                $this->db->commit();
                
                logActivity('Loan Disbursed', 'loans', $loan_id,
                           ['status' => 'approved'],
                           ['status' => 'disbursed', 'disbursement_date' => $disbursement_date]);
                
                return ['success' => true, 'message' => 'Loan disbursed successfully'];
            }

            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to disburse loan'];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Disburse Loan Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Make repayment
     */
    public function makeRepayment($loan_id, $amount, $payment_method = 'cash', $reference = null) {
        try {
            $this->db->beginTransaction();

            $loan = $this->getById($loan_id);
            if (!$loan) {
                return ['success' => false, 'message' => 'Loan not found'];
            }

            if (!in_array($loan['status'], ['disbursed', 'active'])) {
                return ['success' => false, 'message' => 'Loan is not active'];
            }

            if ($amount > $loan['balance']) {
                return ['success' => false, 'message' => 'Amount exceeds loan balance'];
            }

            // Calculate principal and interest portions
            $interest_portion = ($loan['total_interest'] / $loan['total_amount']) * $amount;
            $principal_portion = $amount - $interest_portion;

            $balance_before = $loan['balance'];
            $balance_after = $balance_before - $amount;

            // Insert repayment record
            $query = "INSERT INTO {$this->repaymentsTable} 
                     (loan_id, amount, principal_paid, interest_paid, balance_before, balance_after,
                      payment_date, payment_time, payment_method, reference_number, processed_by) 
                     VALUES 
                     (:loan_id, :amount, :principal_paid, :interest_paid, :balance_before, :balance_after,
                      CURDATE(), CURTIME(), :payment_method, :reference_number, :processed_by)";

            $this->db->query($query);
            $this->db->bindMultiple([
                ':loan_id' => $loan_id,
                ':amount' => $amount,
                ':principal_paid' => $principal_portion,
                ':interest_paid' => $interest_portion,
                ':balance_before' => $balance_before,
                ':balance_after' => $balance_after,
                ':payment_method' => $payment_method,
                ':reference_number' => $reference,
                ':processed_by' => $_SESSION['user_id']
            ]);

            if ($this->db->execute()) {
                // Update loan balance
                $new_status = $balance_after <= 0 ? 'completed' : 'active';
                
                $query = "UPDATE {$this->loansTable} SET 
                          amount_paid = amount_paid + :amount,
                          balance = balance - :amount2,
                          status = :status,
                          actual_end_date = IF(:balance_after <= 0, CURDATE(), NULL)
                          WHERE id = :id";

                $this->db->query($query);
                $this->db->bindMultiple([
                    ':amount' => $amount,
                    ':amount2' => $amount,
                    ':status' => $new_status,
                    ':balance_after' => $balance_after,
                    ':id' => $loan_id
                ]);
                $this->db->execute();

                // Update schedule
                $this->updateSchedule($loan_id, $amount);

                $this->db->commit();
                
                logActivity('Repayment Made', 'loans', $loan_id,
                           ['balance' => $balance_before],
                           ['balance' => $balance_after, 'amount' => $amount]);
                
                return [
                    'success' => true,
                    'message' => 'Repayment successful',
                    'balance_before' => $balance_before,
                    'balance_after' => $balance_after
                ];
            }

            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to process repayment'];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Repayment Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Get loan by ID
     */
    public function getById($id) {
        $query = "SELECT l.*, 
                  CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                  c.phone as customer_phone,
                  c.customer_code,
                  lp.product_name,
                  lp.interest_type as product_interest_type
                  FROM {$this->loansTable} l
                  JOIN customers c ON l.customer_id = c.id
                  JOIN {$this->productsTable} lp ON l.product_id = lp.id
                  WHERE l.id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Get loan repayments
     */
    public function getRepayments($loan_id) {
        $query = "SELECT lr.*, u.full_name as processed_by_name
                  FROM {$this->repaymentsTable} lr
                  LEFT JOIN users u ON lr.processed_by = u.id
                  WHERE lr.loan_id = :loan_id
                  ORDER BY lr.payment_date DESC, lr.payment_time DESC";
        $this->db->query($query);
        $this->db->bind(':loan_id', $loan_id);
        return $this->db->resultSet();
    }

    /**
     * Get repayment schedule
     */
    public function getSchedule($loan_id) {
        $query = "SELECT * FROM {$this->scheduleTable} 
                  WHERE loan_id = :loan_id 
                  ORDER BY installment_number";
        $this->db->query($query);
        $this->db->bind(':loan_id', $loan_id);
        return $this->db->resultSet();
    }

    /**
     * Get loan products
     */
    public function getProducts($active_only = true) {
        $query = "SELECT * FROM {$this->productsTable}";
        if ($active_only) {
            $query .= " WHERE status = 'active'";
        }
        $query .= " ORDER BY product_name";
        $this->db->query($query);
        return $this->db->resultSet();
    }

    /**
     * Get product by ID
     */
    public function getProductById($id) {
        $query = "SELECT * FROM {$this->productsTable} WHERE id = :id";
        $this->db->query($query);
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Calculate total interest
     */
    private function calculateTotalInterest($principal, $rate, $months, $type) {
        if ($type == 'flat') {
            return $principal * ($rate / 100) * ($months / 12);
        } else {
            // Reducing balance (approximate)
            $monthly_principal = $principal / $months;
            $balance = $principal;
            $total_interest = 0;
            
            for ($i = 1; $i <= $months; $i++) {
                $monthly_interest = ($balance * ($rate / 100)) / 12;
                $total_interest += $monthly_interest;
                $balance -= $monthly_principal;
            }
            
            return $total_interest;
        }
    }

    /**
     * Generate repayment schedule
     */
    private function generateRepaymentSchedule($loan, $start_date) {
        $principal = $loan['principal_amount'];
        $rate = $loan['interest_rate'];
        $months = $loan['duration_months'];
        $type = $loan['interest_type'];

        $monthly_principal = $principal / $months;
        $balance = $principal;

        for ($i = 1; $i <= $months; $i++) {
            if ($type == 'flat') {
                $monthly_interest = ($principal * ($rate / 100)) / 12;
            } else {
                $monthly_interest = ($balance * ($rate / 100)) / 12;
                $balance -= $monthly_principal;
            }

            $monthly_total = $monthly_principal + $monthly_interest;
            $due_date = date('Y-m-d', strtotime($start_date . ' + ' . $i . ' months'));

            $query = "INSERT INTO {$this->scheduleTable} 
                     (loan_id, installment_number, due_date, principal_amount, 
                      interest_amount, total_amount, balance_after, status) 
                     VALUES 
                     (:loan_id, :installment, :due_date, :principal, :interest, 
                      :total, :balance, 'pending')";

            $this->db->query($query);
            $this->db->bindMultiple([
                ':loan_id' => $loan['id'],
                ':installment' => $i,
                ':due_date' => $due_date,
                ':principal' => $monthly_principal,
                ':interest' => $monthly_interest,
                ':total' => $monthly_total,
                ':balance' => $balance
            ]);
            $this->db->execute();
        }
    }

    /**
     * Update repayment schedule
     */
    private function updateSchedule($loan_id, $amount) {
        // Get pending installments
        $query = "SELECT * FROM {$this->scheduleTable} 
                  WHERE loan_id = :loan_id AND status IN ('pending', 'partial') 
                  ORDER BY installment_number LIMIT 1";
        $this->db->query($query);
        $this->db->bind(':loan_id', $loan_id);
        $schedule = $this->db->single();

        if ($schedule) {
            $new_paid = $schedule['amount_paid'] + $amount;
            $status = $new_paid >= $schedule['total_amount'] ? 'paid' : 'partial';

            $query = "UPDATE {$this->scheduleTable} SET 
                      amount_paid = :amount_paid,
                      status = :status,
                      paid_date = IF(:status = 'paid', CURDATE(), NULL)
                      WHERE id = :id";
            $this->db->query($query);
            $this->db->bindMultiple([
                ':amount_paid' => $new_paid,
                ':status' => $status,
                ':id' => $schedule['id']
            ]);
            $this->db->execute();
        }
    }

    /**
     * Generate loan number
     */
    private function generateLoanNumber() {
        $query = "SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM {$this->loansTable}";
        $this->db->query($query);
        $result = $this->db->single();
        return 'LN' . str_pad($result['next_id'], 8, '0', STR_PAD_LEFT);
    }

    /**
     * Get active loan count for customer
     */
    private function getActiveLoanCount($customer_id) {
        $query = "SELECT COUNT(*) as count FROM {$this->loansTable} 
                  WHERE customer_id = :id AND status IN ('active', 'disbursed')";
        $this->db->query($query);
        $this->db->bind(':id', $customer_id);
        return $this->db->single()['count'];
    }
}
?>