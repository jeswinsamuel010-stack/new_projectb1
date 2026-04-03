<?php
session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../vendor/autoload.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Handle file upload and PDF parsing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'parse') {

    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        exit;
    }

    $file = $_FILES['pdf_file'];
    $allowedTypes = ['application/pdf', 'application/x-pdf'];

    if (!in_array($file['type'], $allowedTypes) && !preg_match('/\.pdf$/i', $file['name'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload a PDF file.']);
        exit;
    }

    if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
        echo json_encode(['success' => false, 'message' => 'File too large. Maximum 10MB allowed.']);
        exit;
    }

    try {
        // Parse PDF and extract text
        $extractedData = parsePDF($file['tmp_name']);

        if (empty($extractedData['text'])) {
            echo json_encode(['success' => false, 'message' => 'Could not extract text from PDF. The file may be scanned/image-based.']);
            exit;
        }

        // Extract structured data from text
        $data = extractInvoiceData($extractedData['text']);

        // Match supplier
        $supplierMatch = matchSupplier($data['supplier_name']);

        echo json_encode([
            'success' => true,
            'data' => $data,
            'raw_text' => substr($extractedData['text'], 0, 2000), // First 2000 chars for preview
            'supplier_match' => $supplierMatch,
            'text_extracted' => true
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error parsing PDF: ' . $e->getMessage()]);
    }
    exit;
}

/**
 * Parse PDF file and extract text
 */
function parsePDF($filePath) {
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($filePath);
    $pages = $pdf->getPages();

    $text = '';
    foreach ($pages as $page) {
        $text .= $page->getText() . "\n";
    }

    return [
        'text' => $text,
        'page_count' => count($pages)
    ];
}

/**
 * Extract structured data from invoice text using pattern matching
 */
function extractInvoiceData($text) {
    // Clean up text
    $text = preg_replace('/\s+/', ' ', $text);
    $lines = preg_split('/[\n\r]+/', $text);

    $data = [
        'supplier_name' => '',
        'product_name' => '',
        'quantity' => 0,
        'unit' => '',
        'date' => date('Y-m-d'),
        'invoice_number' => ''
    ];

    // Patterns for extraction
    $patterns = [
        'invoice' => '/(?:invoice|inv|reference|no|number)[\s#.:]*([A-Z0-9\-\/]+)/i',
        'date' => '/(?:date|dated|dated[:\s]+)(\d{1,2}[\-\/\.]\d{1,2}[\-\/\.]\d{2,4})/i',
        'quantity' => '/(?:qty|quantity|qny|pcs|pieces|bags|nos)[\s:]*(\d+)/i',
    ];

    // Extract invoice number
    foreach ($lines as $line) {
        if (preg_match($patterns['invoice'], $line, $match) && empty($data['invoice_number'])) {
            $data['invoice_number'] = trim($match[1]);
            if (strlen($data['invoice_number']) < 3) {
                $data['invoice_number'] = '';
            }
        }
        if (preg_match($patterns['date'], $line, $match) && empty($data['date'])) {
            $data['date'] = parseDate($match[1]);
        }
    }

    // Extract supplier name (usually at the top of invoice)
    $data['supplier_name'] = extractSupplierName($text);

    // Extract product and quantity - look for line items
    $lineItemData = extractLineItems($text);
    if (!empty($lineItemData)) {
        $data['product_name'] = $lineItemData['product_name'];
        $data['quantity'] = $lineItemData['quantity'];
        $data['unit'] = $lineItemData['unit'];
    }

    // If no line items found, try to extract quantity from anywhere
    if ($data['quantity'] == 0) {
        foreach ($lines as $line) {
            if (preg_match($patterns['quantity'], $line, $match)) {
                $data['quantity'] = intval($match[1]);
                if ($data['quantity'] > 0 && $data['quantity'] < 100000) {
                    break;
                }
            }
        }
    }

    return $data;
}

/**
 * Extract supplier name from text
 */
function extractSupplierName($text) {
    $lines = preg_split('/[\n\r]+/', trim($text));
    $text = strtolower($text);

    // Common supplier names to look for
    $knownSuppliers = [
        'abc supplies', 'river sand', 'steel masters', 'cement india',
        'bricks & tiles', 'paint world', 'hardware hub', 'aggregate solutions'
    ];

    // Check for known suppliers first
    foreach ($knownSuppliers as $supplier) {
        if (strpos($text, strtolower($supplier)) !== false) {
            return ucwords($supplier);
        }
    }

    // Look for supplier/vendor section
    $patterns = [
        '/(?:supplier|vendor|from)[\s:]+([A-Za-z][A-Za-z\s&]+?)(?:\n|,)/i',
        '/^(?:supplier|vendor)[\s:]*([A-Za-z].*?)$/im',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $match)) {
            $name = trim($match[1]);
            if (strlen($name) > 2 && strlen($name) < 100) {
                return $name;
            }
        }
    }

    // Take first non-empty line as supplier name
    foreach ($lines as $line) {
        $line = trim($line);
        if (strlen($line) > 3 && strlen($line) < 100 && !isNumericLine($line)) {
            return $line;
        }
    }

    return '';
}

/**
 * Extract line items (product, quantity, unit) from invoice
 */
function extractLineItems($text) {
    $lines = preg_split('/[\n\r]+/', $text);
    $result = [
        'product_name' => '',
        'quantity' => 0,
        'unit' => ''
    ];

    // Common construction items
    $constructionItems = [
        'cement', 'steel', 'bars', 'bricks', 'sand', 'aggregate',
        'paint', 'tiles', 'pipe', 'wire', 'rod', 'mesh'
    ];

    $units = ['bags', 'pieces', 'pcs', 'nos', 'kg', 'ton', 'cu.mt', 'cubic', 'liter', 'sq.ft'];

    foreach ($lines as $line) {
        $line = trim($line);
        if (strlen($line) < 5) continue;

        $lineLower = strtolower($line);

        // Check if line contains a construction item
        $hasItem = false;
        foreach ($constructionItems as $item) {
            if (strpos($lineLower, $item) !== false) {
                $hasItem = true;
                $result['product_name'] = preg_replace('/[^\w\s]/', '', $line);
                $result['product_name'] = trim($result['product_name']);
                break;
            }
        }

        if ($hasItem) {
            // Try to extract quantity from this line
            if (preg_match('/(\d+)\s*(' . implode('|', $units) . ')/i', $line, $match)) {
                $result['quantity'] = intval($match[1]);
                $result['unit'] = strtolower($match[2]);
                if ($result['unit'] == 'pcs' || $result['unit'] == 'nos') {
                    $result['unit'] = 'pieces';
                }
                return $result;
            }

            // Try just number
            if (preg_match('/(\d+)/', $line, $match)) {
                $qty = intval($match[1]);
                if ($qty > 0 && $qty < 100000) {
                    $result['quantity'] = $qty;
                    return $result;
                }
            }
        }
    }

    return $result;
}

/**
 * Check if line is mostly numeric
 */
function isNumericLine($line) {
    $numericChars = preg_match_all('/[\d\.\,\-]/', $line);
    $totalChars = strlen($line);
    return $numericChars > ($totalChars * 0.5);
}

/**
 * Parse date from various formats
 */
function parseDate($dateStr) {
    $dateStr = trim($dateStr);
    $dateStr = str_replace('.', '-', $dateStr);

    // Try different formats
    $formats = ['d-m-Y', 'd-m-y', 'm-d-Y', 'Y-m-d', 'd/m/Y', 'd/m/y'];
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $dateStr);
        if ($date !== false) {
            return $date->format('Y-m-d');
        }
    }

    // Try strtotime as fallback
    $timestamp = strtotime($dateStr);
    if ($timestamp) {
        return date('Y-m-d', $timestamp);
    }

    return date('Y-m-d');
}

/**
 * Match supplier with database
 */
function matchSupplier($supplierName) {
    if (empty($supplierName)) {
        return ['found' => false, 'message' => 'No supplier name extracted'];
    }

    // Exact match
    $supplier = fetchOne("SELECT * FROM suppliers WHERE name = ? AND status = 'active'", "s", [$supplierName]);

    if ($supplier) {
        return ['found' => true, 'exact' => true, 'supplier' => $supplier];
    }

    // Partial match
    $searchParam = '%' . $supplierName . '%';
    $suppliers = fetchAll("SELECT * FROM suppliers WHERE name LIKE ? AND status = 'active' LIMIT 5", "s", [$searchParam]);

    if (!empty($suppliers)) {
        return ['found' => true, 'exact' => false, 'suggestions' => $suppliers];
    }

    return ['found' => false, 'message' => 'No matching supplier found'];
}