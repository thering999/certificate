<?php
/**
 * Search API Endpoint - Phase 4
 * 
 * RESTful API for searching and filtering certificates
 * 
 * Endpoints:
 * - GET /api/search.php?q=search_term&page=1&status=completed&sort=name&order=ASC
 * - GET /api/search.php?template_id=5&organization_id=2
 * - GET /api/search.php?date_from=2025-01-01&date_to=2025-12-31
 * - GET /api/search.php?action=filters (get available filters)
 * - GET /api/search.php?action=stats (get statistics)
 * 
 * @author Certificate System Team
 * @version 1.0
 */

header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';
require_once 'assets/error_handler.php';
require_once 'assets/search.class.php';

session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Please login first']);
    exit;
}

try {
    $search = new Search($conn);
    
    // Get action parameter
    $action = $_GET['action'] ?? 'search';
    
    switch ($action) {
        case 'filters':
            // Get available filters for UI
            $filters = $search->getAvailableFilters();
            echo json_encode(['success' => true, 'filters' => $filters]);
            break;
            
        case 'stats':
            // Get search statistics
            $stats = $search->getStatistics();
            echo json_encode(['success' => true, 'statistics' => $stats]);
            break;
            
        case 'search':
        default:
            // Execute search with filters and pagination
            
            // Get search parameters
            $searchTerm = $_GET['q'] ?? '';
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
            $status = $_GET['status'] ?? '';
            $templateId = $_GET['template_id'] ?? '';
            $organizationId = $_GET['organization_id'] ?? '';
            $dateFrom = $_GET['date_from'] ?? '';
            $dateTo = $_GET['date_to'] ?? '';
            $sortBy = $_GET['sort'] ?? 'c.created_at';
            $sortOrder = $_GET['order'] ?? 'DESC';
            
            // Sanitize search term
            $searchTerm = trim($searchTerm);
            if (strlen($searchTerm) > 100) {
                $searchTerm = substr($searchTerm, 0, 100);
            }
            
            // Build search query
            $result = $search
                ->search($searchTerm)
                ->setPage($page)
                ->setPerPage($perPage)
                ->filterByStatus($status)
                ->filterByTemplate($templateId)
                ->filterByOrganization($organizationId)
                ->filterByDateRange($dateFrom, $dateTo)
                ->sort($sortBy, $sortOrder)
                ->execute();
            
            // Log successful search
            ErrorHandler::log("API Search: user_id={$_SESSION['user_id']}, term='{$searchTerm}', page={$page}, found {$result['total']} results", 'INFO');
            
            echo json_encode([
                'success' => true,
                'data' => $result['data'],
                'pagination' => $result['pagination'],
                'total' => $result['total']
            ]);
            break;
    }
    
} catch (Exception $e) {
    ErrorHandler::log("Search API error: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>
