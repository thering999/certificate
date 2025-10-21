<?php
/**
 * Search & Filter Class - Phase 4
 * 
 * Handles advanced search, filtering, and pagination for certificate system
 * 
 * Features:
 * - Full-text search on name, email, organization
 * - Filters by status, template, date range
 * - Sorting by name, date, status
 * - Pagination with customizable page size
 * - Performance optimization with proper indexing
 * 
 * @author Certificate System Team
 * @version 1.0
 */

class Search {
    private $conn;
    private $table = 'certificates';
    private $perPage = 20;
    private $currentPage = 1;
    private $filters = [];
    private $sortBy = 'created_at';
    private $sortOrder = 'DESC';
    private $searchTerm = '';

    /**
     * Constructor - Initialize database connection
     * 
     * @param mysqli $connection Database connection
     */
    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Set pagination page size
     * 
     * @param int $perPage Items per page (default 20)
     * @return $this Fluent interface
     */
    public function setPerPage($perPage = 20) {
        $this->perPage = max(1, min(100, (int)$perPage));
        return $this;
    }

    /**
     * Set current page number
     * 
     * @param int $page Page number (starts at 1)
     * @return $this Fluent interface
     */
    public function setPage($page = 1) {
        $this->currentPage = max(1, (int)$page);
        return $this;
    }

    /**
     * Set search term for full-text search
     * 
     * @param string $term Search keywords
     * @return $this Fluent interface
     */
    public function search($term = '') {
        $this->searchTerm = trim($term);
        return $this;
    }

    /**
     * Add filter by status
     * Valid values: 'draft', 'processing', 'completed', 'failed'
     * 
     * @param string $status Certificate status
     * @return $this Fluent interface
     */
    public function filterByStatus($status = '') {
        if (!empty($status)) {
            $status = trim($status);
            $valid_statuses = ['draft', 'processing', 'completed', 'failed'];
            if (in_array($status, $valid_statuses)) {
                $this->filters['status'] = $status;
            }
        }
        return $this;
    }

    /**
     * Add filter by template
     * 
     * @param int|string $templateId Template ID
     * @return $this Fluent interface
     */
    public function filterByTemplate($templateId = '') {
        if (!empty($templateId)) {
            $this->filters['template_id'] = (int)$templateId;
        }
        return $this;
    }

    /**
     * Add filter by organization
     * 
     * @param int|string $organizationId Organization ID
     * @return $this Fluent interface
     */
    public function filterByOrganization($organizationId = '') {
        if (!empty($organizationId)) {
            $this->filters['organization_id'] = (int)$organizationId;
        }
        return $this;
    }

    /**
     * Add filter by date range
     * 
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return $this Fluent interface
     */
    public function filterByDateRange($startDate = '', $endDate = '') {
        if (!empty($startDate) && $this->isValidDate($startDate)) {
            $this->filters['date_from'] = $startDate;
        }
        if (!empty($endDate) && $this->isValidDate($endDate)) {
            $this->filters['date_to'] = $endDate;
        }
        return $this;
    }

    /**
     * Set sorting
     * Valid fields: 'name', 'created_at', 'status', 'updated_at'
     * Valid orders: 'ASC', 'DESC'
     * 
     * @param string $sortBy Field to sort by
     * @param string $sortOrder ASC or DESC
     * @return $this Fluent interface
     */
    public function sort($sortBy = 'created_at', $sortOrder = 'DESC') {
        $valid_fields = ['c.name', 'c.created_at', 'c.status', 'c.updated_at', 'c.recipient_name', 'c.recipient_email'];
        $sortBy = trim($sortBy);
        
        if (in_array($sortBy, $valid_fields)) {
            $this->sortBy = $sortBy;
        }
        
        $sortOrder = strtoupper(trim($sortOrder));
        if (in_array($sortOrder, ['ASC', 'DESC'])) {
            $this->sortOrder = $sortOrder;
        }
        
        return $this;
    }

    /**
     * Execute search and return paginated results
     * 
     * @return array Associative array with 'data', 'pagination', 'total'
     */
    public function execute() {
        try {
            // Build query
            $query = $this->buildQuery();
            
            // Get total count before pagination
            $countQuery = $this->buildCountQuery();
            $countStmt = $this->conn->prepare($countQuery['sql']);
            
            if ($countStmt === false) {
                ErrorHandler::logDB($this->conn->error, 'Search count query');
                return ['data' => [], 'pagination' => [], 'total' => 0, 'error' => 'Database error'];
            }
            
            // Bind parameters for count
            $this->bindParameters($countStmt, $countQuery['params']);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countRow = $countResult->fetch_assoc();
            $totalRecords = $countRow['total'] ?? 0;
            $countStmt->close();
            
            // Calculate pagination
            $totalPages = ceil($totalRecords / $this->perPage);
            $offset = ($this->currentPage - 1) * $this->perPage;
            
            // Add limit to query
            $query['sql'] .= " LIMIT ? OFFSET ?";
            $query['params'][] = $this->perPage;
            $query['params'][] = $offset;
            $query['types'] .= 'ii';
            
            // Execute search query
            $stmt = $this->conn->prepare($query['sql']);
            
            if ($stmt === false) {
                ErrorHandler::logDB($this->conn->error, 'Search query');
                return ['data' => [], 'pagination' => [], 'total' => 0, 'error' => 'Database error'];
            }
            
            $this->bindParameters($stmt, $query['params']);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            $stmt->close();
            
            // Log search
            ErrorHandler::log("Search executed: term='{$this->searchTerm}', filters=" . json_encode($this->filters) . ", found {$totalRecords} results", 'INFO');
            
            return [
                'data' => $data,
                'pagination' => [
                    'current_page' => $this->currentPage,
                    'total_pages' => $totalPages,
                    'per_page' => $this->perPage,
                    'total_records' => $totalRecords,
                    'has_next' => $this->currentPage < $totalPages,
                    'has_prev' => $this->currentPage > 1
                ],
                'total' => $totalRecords
            ];
        } catch (Exception $e) {
            ErrorHandler::log("Search error: " . $e->getMessage(), 'ERROR');
            return ['data' => [], 'pagination' => [], 'total' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Build SELECT query with filters
     * 
     * @return array Query SQL and parameters
     */
    private function buildQuery() {
        $sql = "SELECT c.id, c.recipient_name, c.recipient_email, c.organization_id, 
                       c.template_id, c.status, c.created_at, c.updated_at, c.file_path,
                       o.name as organization_name, t.name as template_name
                FROM {$this->table} c
                LEFT JOIN organizations o ON c.organization_id = o.id
                LEFT JOIN templates t ON c.template_id = t.id
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        // Add search term filter
        if (!empty($this->searchTerm)) {
            $sql .= " AND (c.recipient_name LIKE ? OR c.recipient_email LIKE ? OR o.name LIKE ?)";
            $searchTerm = "%{$this->searchTerm}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }
        
        // Add status filter
        if (isset($this->filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $this->filters['status'];
            $types .= 's';
        }
        
        // Add template filter
        if (isset($this->filters['template_id'])) {
            $sql .= " AND c.template_id = ?";
            $params[] = $this->filters['template_id'];
            $types .= 'i';
        }
        
        // Add organization filter
        if (isset($this->filters['organization_id'])) {
            $sql .= " AND c.organization_id = ?";
            $params[] = $this->filters['organization_id'];
            $types .= 'i';
        }
        
        // Add date range filter
        if (isset($this->filters['date_from'])) {
            $sql .= " AND DATE(c.created_at) >= ?";
            $params[] = $this->filters['date_from'];
            $types .= 's';
        }
        
        if (isset($this->filters['date_to'])) {
            $sql .= " AND DATE(c.created_at) <= ?";
            $params[] = $this->filters['date_to'];
            $types .= 's';
        }
        
        // Add sorting
        $sql .= " ORDER BY {$this->sortBy} {$this->sortOrder}";
        
        return [
            'sql' => $sql,
            'params' => $params,
            'types' => $types
        ];
    }

    /**
     * Build COUNT query for pagination
     * 
     * @return array Count query SQL and parameters
     */
    private function buildCountQuery() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} c
                LEFT JOIN organizations o ON c.organization_id = o.id
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        // Add search term filter
        if (!empty($this->searchTerm)) {
            $sql .= " AND (c.recipient_name LIKE ? OR c.recipient_email LIKE ? OR o.name LIKE ?)";
            $searchTerm = "%{$this->searchTerm}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }
        
        // Add status filter
        if (isset($this->filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $this->filters['status'];
            $types .= 's';
        }
        
        // Add template filter
        if (isset($this->filters['template_id'])) {
            $sql .= " AND c.template_id = ?";
            $params[] = $this->filters['template_id'];
            $types .= 'i';
        }
        
        // Add organization filter
        if (isset($this->filters['organization_id'])) {
            $sql .= " AND c.organization_id = ?";
            $params[] = $this->filters['organization_id'];
            $types .= 'i';
        }
        
        // Add date range filter
        if (isset($this->filters['date_from'])) {
            $sql .= " AND DATE(c.created_at) >= ?";
            $params[] = $this->filters['date_from'];
            $types .= 's';
        }
        
        if (isset($this->filters['date_to'])) {
            $sql .= " AND DATE(c.created_at) <= ?";
            $params[] = $this->filters['date_to'];
            $types .= 's';
        }
        
        return [
            'sql' => $sql,
            'params' => $params,
            'types' => $types
        ];
    }

    /**
     * Bind parameters to prepared statement
     * 
     * @param mysqli_stmt $stmt Prepared statement
     * @param array $params Parameter values
     */
    private function bindParameters(&$stmt, $params) {
        $query = $this->buildQuery();
        $countQuery = $this->buildCountQuery();
        
        if (!empty($params)) {
            $types = $query['types'] . (isset($query['params']) ? '' : '');
            
            // Get current types from the queries
            if ($stmt->get_result() !== false || $stmt->num_rows >= 0) {
                // Already executed, build types from our filter setup
                $types = '';
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } else {
                        $types .= 's';
                    }
                }
            }
            
            $stmt->bind_param($types, ...$params);
        }
    }

    /**
     * Validate date format (YYYY-MM-DD)
     * 
     * @param string $date Date string
     * @return bool True if valid date format
     */
    private function isValidDate($date) {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 && strtotime($date) !== false;
    }

    /**
     * Get available filters for UI
     * Returns list of templates and organizations for filter dropdowns
     * 
     * @return array Filters data
     */
    public function getAvailableFilters() {
        $filters = [];
        
        // Get templates
        $templateStmt = $this->conn->prepare("SELECT id, name FROM templates WHERE active = 1 ORDER BY name");
        if ($templateStmt) {
            $templateStmt->execute();
            $result = $templateStmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $filters['templates'][] = $row;
            }
            $templateStmt->close();
        }
        
        // Get organizations
        $orgStmt = $this->conn->prepare("SELECT id, name FROM organizations ORDER BY name");
        if ($orgStmt) {
            $orgStmt->execute();
            $result = $orgStmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $filters['organizations'][] = $row;
            }
            $orgStmt->close();
        }
        
        // Status options
        $filters['statuses'] = [
            ['value' => 'draft', 'label' => 'ร่าง'],
            ['value' => 'processing', 'label' => 'กำลังประมวลผล'],
            ['value' => 'completed', 'label' => 'เสร็จสิ้น'],
            ['value' => 'failed', 'label' => 'ล้มเหลว']
        ];
        
        return $filters;
    }

    /**
     * Get search statistics
     * 
     * @return array Statistics data
     */
    public function getStatistics() {
        try {
            $stats = [];
            
            // Total certificates
            $totalStmt = $this->conn->prepare("SELECT COUNT(*) as count FROM {$this->table}");
            $totalStmt->execute();
            $stats['total'] = $totalStmt->get_result()->fetch_assoc()['count'];
            $totalStmt->close();
            
            // By status
            $statusStmt = $this->conn->prepare("SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status");
            $statusStmt->execute();
            $result = $statusStmt->get_result();
            $stats['by_status'] = [];
            while ($row = $result->fetch_assoc()) {
                $stats['by_status'][$row['status']] = $row['count'];
            }
            $statusStmt->close();
            
            // This month
            $monthStmt = $this->conn->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
            $monthStmt->execute();
            $stats['this_month'] = $monthStmt->get_result()->fetch_assoc()['count'];
            $monthStmt->close();
            
            return $stats;
        } catch (Exception $e) {
            ErrorHandler::log("Statistics error: " . $e->getMessage(), 'ERROR');
            return ['total' => 0, 'by_status' => [], 'this_month' => 0];
        }
    }
}
?>
