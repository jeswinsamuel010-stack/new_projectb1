<?php
/**
 * Reusable Approval System Helper
 * Use for: quotation, material_request, purchase_order, billing, payment
 */

class ApprovalHelper {
    private $conn;

    public function __construct() {
        $this->conn = getDB();
    }

    /**
     * Submit an item for approval
     * @param string $module_type - quotation, material_request, etc.
     * @param int $module_id - The ID of the item
     * @param string $initial_status - Status that triggers approval (e.g., 'Sent')
     * @param string $table_name - The main table to update
     * @param string $status_field - The status column name
     * @return array
     */
    public function submitForApproval($module_type, $module_id, $initial_status, $table_name, $status_field = 'status') {
        // Check if already has pending approval
        $existing = $this->getApproval($module_type, $module_id);
        if ($existing && $existing['status'] == 'pending') {
            return ['success' => false, 'message' => 'Already has pending approval'];
        }

        // Get current status of the module
        $row = fetchOne("SELECT $status_field FROM $table_name WHERE id = ?", "i", [$module_id]);
        if (!$row) {
            return ['success' => false, 'message' => 'Record not found'];
        }

        if ($row[$status_field] != $initial_status) {
            return ['success' => false, 'message' => "Item must be in '$initial_status' status"];
        }

        // Insert or update approval record
        $stmt = $this->conn->prepare("
            INSERT INTO approvals (module_type, module_id, status)
            VALUES (?, ?, 'pending')
            ON DUPLICATE KEY UPDATE status = 'pending', approved_by = NULL, approval_date = NULL, notes = NULL, updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->bind_param("si", $module_type, $module_id);

        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Submitted for approval'];
        }

        $stmt->close();
        return ['success' => false, 'message' => $this->conn->error];
    }

    /**
     * Approve an item
     * @param string $module_type
     * @param int $module_id
     * @param int $user_id
     * @param string $notes
     * @param string $table_name - Table to update
     * @param string $approved_status - Status to set after approval (e.g., 'Approved')
     * @param string $status_field
     * @return array
     */
    public function approve($module_type, $module_id, $user_id, $notes = '', $table_name, $approved_status = 'Approved', $status_field = 'status') {
        $approval = $this->getApproval($module_type, $module_id);

        // Auto-create approval record if not exists
        if (!$approval) {
            $stmt = $this->conn->prepare("
                INSERT INTO approvals (module_type, module_id, status, created_at)
                VALUES (?, ?, 'pending', NOW())
            ");
            $stmt->bind_param("si", $module_type, $module_id);
            $stmt->execute();
            $stmt->close();

            // Fetch the newly created approval record
            $approval = $this->getApproval($module_type, $module_id);
        }

        if ($approval['status'] != 'pending') {
            return ['success' => false, 'message' => 'Already processed'];
        }

        $this->conn->begin_transaction();

        try {
            // Update approval record
            $stmt = $this->conn->prepare("
                UPDATE approvals
                SET status = 'approved', approved_by = ?, approval_date = NOW(), notes = ?
                WHERE module_type = ? AND module_id = ?
            ");
            $stmt->bind_param("issi", $user_id, $notes, $module_type, $module_id);
            $stmt->execute();
            $stmt->close();

            // Update the module status
            $stmt = $this->conn->prepare("UPDATE $table_name SET $status_field = ?, approved_by = ?, approved_date = NOW(), approval_notes = ? WHERE id = ?");
            $stmt->bind_param("sisi", $approved_status, $user_id, $notes, $module_id);
            $stmt->execute();
            $stmt->close();

            $this->conn->commit();
            return ['success' => true, 'message' => 'Approved successfully'];

        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Reject an item
     * @param string $module_type
     * @param int $module_id
     * @param int $user_id
     * @param string $notes
     * @param string $table_name
     * @param string $rejected_status
     * @param string $status_field
     * @return array
     */
    public function reject($module_type, $module_id, $user_id, $notes = '', $table_name, $rejected_status = 'Rejected', $status_field = 'status') {
        $approval = $this->getApproval($module_type, $module_id);

        if (!$approval) {
            return ['success' => false, 'message' => 'No approval record found'];
        }

        if ($approval['status'] != 'pending') {
            return ['success' => false, 'message' => 'Already processed'];
        }

        $this->conn->begin_transaction();

        try {
            // Update approval record
            $stmt = $this->conn->prepare("
                UPDATE approvals
                SET status = 'rejected', approved_by = ?, approval_date = NOW(), notes = ?
                WHERE module_type = ? AND module_id = ?
            ");
            $stmt->bind_param("issi", $user_id, $notes, $module_type, $module_id);
            $stmt->execute();
            $stmt->close();

            // Update the module status
            $stmt = $this->conn->prepare("UPDATE $table_name SET $status_field = ? WHERE id = ?");
            $stmt->bind_param("si", $rejected_status, $module_id);
            $stmt->execute();
            $stmt->close();

            $this->conn->commit();
            return ['success' => true, 'message' => 'Rejected'];

        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get approval record for a module
     */
    public function getApproval($module_type, $module_id) {
        return fetchOne("
            SELECT a.*, u.full_name as approver_name
            FROM approvals a
            LEFT JOIN users u ON a.approved_by = u.id
            WHERE a.module_type = ? AND a.module_id = ?
        ", "si", [$module_type, $module_id]);
    }

    /**
     * Get all approvals for a module type
     */
    public function getApprovals($module_type, $status = null) {
        $sql = "SELECT a.*, u.full_name as approver_name FROM approvals a
                LEFT JOIN users u ON a.approved_by = u.id
                WHERE a.module_type = ?";
        $params = [$module_type];
        $types = "s";

        if ($status) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
            $types .= "s";
        }

        $sql .= " ORDER BY a.created_at DESC";

        return fetchAll($sql, $types, $params);
    }

    /**
     * Check if module is approved
     */
    public function isApproved($module_type, $module_id) {
        $approval = $this->getApproval($module_type, $module_id);
        return $approval && $approval['status'] == 'approved';
    }
}