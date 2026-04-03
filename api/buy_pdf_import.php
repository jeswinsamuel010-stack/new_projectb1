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

    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large. Maximum 10MB allowed.']);
        exit;
    }

    try {
        $extractedData = parsePDF($file['tmp_name']);

        if (empty($extractedData['text'])) {
            echo json_encode(['success' => false, 'message' => 'Could not extract text from PDF. The file may be scanned/image-based.']);
            exit;
        }

        $data = extractInvoiceData($extractedData['text']);
        $supplierMatch = matchSupplier($data['supplier_name']);

        echo json_encode([
            'success' => true,
            'data' => $data,
            'raw_text' => substr($extractedData['text'], 0, 3000),
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

    return ['text' => $text, 'page_count' => count($pages)];
}

/**
 * Extract structured data from invoice text
 */
function extractInvoiceData($text) {
    $text = preg_replace('/\s+/', ' ', $text);
    $lines = preg_split('/[\n\r]+/', $text);

    $data = [
        'supplier_name' => '',
        'items' => [],
        'invoice_number' => '',
        'invoice_date' => date('Y-m-d'),
        'total_amount' => 0
    ];

    // Patterns
    $patterns = [
        'invoice' => '/(?:invoice|inv|reference|no|number)[\s#.:]*([A-Z0-9\-\/]+)/i',
        'date' => '/(?:date|dated|dated[:\s]+)(\d{1,2}[\-\/\.]\d{1,2}[\-\/\.]\d{2,4})/i',
        'amount' => '/(?:total|grand total|amount|sum)[\s:]*Rs\.?\s*([\d,]+(?:\.\d{2})?)/i',
    ];

    // Extract invoice number
    foreach ($lines as $line) {
        if (preg_match($patterns['invoice'], $line, $match) && empty($data['invoice_number'])) {
            $data['invoice_number'] = trim($match[1]);
            if (strlen($data['invoice_number']) < 3) {
                $data['invoice_number'] = '';
            }
        }
        if (preg_match($patterns['date'], $line, $match) && empty($data['invoice_date'])) {
            $data['invoice_date'] = parseDate($match[1]);
        }
    }

    // Extract supplier
    $data['supplier_name'] = extractSupplierName($text);

    // Extract items
    $data['items'] = extractLineItems($text);

    // Try to get total amount
    foreach ($lines as $line) {
        if (preg_match($patterns['amount'], $line, $match)) {
            $amt = str_replace(',', '', $match[1]);
            $data['total_amount'] = floatval($amt);
            break;
        }
    }

    return $data;
}

/**
 * Extract supplier name
 */
function extractSupplierName($text) {
    $lines = preg_split('/[\n\r]+/', trim($text));
    $text = strtolower($text);

    $knownSuppliers = ['abc supplies', 'river sand', 'steel masters', 'cement india', 'bricks & tiles', 'paint world', 'hardware hub', 'aggregate solutions'];

    foreach ($knownSuppliers as $supplier) {
        if (strpos($text, strtolower($supplier)) !== false) {
            return ucwords($supplier);
        }
    }

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

    foreach ($lines as $line) {
        $line = trim($line);
        if (strlen($line) > 3 && strlen($line) < 100 && !isNumericLine($line)) {
            return $line;
        }
    }

    return '';
}

/**
 * Extract line items from invoice
 */
function extractLineItems($text) {
    $lines = preg_split('/[\n\r]+/', $text);
    $items = [];

    $constructionItems = ['cement', 'steel', 'bars', 'bricks', 'sand', 'aggregate', 'paint', 'tiles', 'pipe', 'wire', 'rod', 'mesh', 'iron', 'concrete'];
    $units = ['bags', 'pieces', 'pcs', 'nos', 'kg', 'ton', 'cu.mt', 'cubic', 'liter', 'sq.ft', 'mt', 'mm'];

    foreach ($lines as $line) {
        $line = trim($line);
        if (strlen($line) < 5) continue;

        $lineLower = strtolower($line);

        // Check if line has construction item
        $foundItem = null;
        foreach ($constructionItems as $item) {
            if (strpos($lineLower, $item) !== false) {
                $foundItem = $item;
                break;
            }
        }

        if ($foundItem) {
            $qty = 0;
            $unit = 'pieces';
            $rate = 0;
            $amount = 0;

            // Extract quantity and unit
            foreach ($units as $u) {
                if (preg_match('/(\d+(?:\.\d+)?)\s*' . $u . '/i', $line, $match)) {
                    $qty = floatval($match[1]);
                    $unit = $u;
                    break;
                }
            }

            // Try just number if no unit found
            if ($qty == 0) {
                if (preg_match('/(\d+(?:\.\d+)?)/', $line, $match)) {
                    $qty = floatval($match[1]);
                }
            }

            // Extract rate/amount
            if (preg_match('/Rs\.?\s*([\d,]+(?:\.\d{2})?)/i', $line, $match)) {
                $amount = floatval(str_replace(',', '', $match[1]));
                if ($qty > 0) {
                    $rate = $amount / $qty;
                }
            }

            if ($qty > 0) {
                $items[] = [
                    'item_name' => ucwords($foundItem),
                    'quantity' => $qty,
                    'unit' => $unit,
                    'rate' => $rate,
                    'amount' => $amount
                ];
            }
        }
    }

    // If no items extracted, try to get any quantity
    if (empty($items)) {
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/(\d+(?:\.\d+)?)\s*(?:bags|pieces|pcs|nos)/i', $line, $match)) {
                $qty = floatval($match[1]);
                if ($qty > 0 && $qty < 100000) {
                    $items[] = [
                        'item_name' => 'Material',
                        'quantity' => $qty,
                        'unit' => 'pieces',
                        'rate' => 0,
                        'amount' => 0
                    ];
                    break;
                }
            }
        }
    }

    return $items;
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

    $formats = ['d-m-Y', 'd-m-y', 'm-d-Y', 'Y-m-d', 'd/m/Y', 'd/m/y'];
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $dateStr);
        if ($date !== false) {
            return $date->format('Y-m-d');
        }
    }

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

    $supplier = fetchOne("SELECT * FROM suppliers WHERE name = ? AND status = 'active'", "s", [$supplierName]);

    if ($supplier) {
        return ['found' => true, 'exact' => true, 'supplier' => $supplier];
    }

    $searchParam = '%' . $supplierName . '%';
    $suppliers = fetchAll("SELECT * FROM suppliers WHERE name LIKE ? AND status = 'active' LIMIT 5", "s", [$searchParam]);

    if (!empty($suppliers)) {
        return ['found' => true, 'exact' => false, 'suggestions' => $suppliers];
    }

    return ['found' => false, 'message' => 'No matching supplier found'];
}