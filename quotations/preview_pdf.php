<?php
/**
 * Quotation PDF Preview Endpoint
 *
 * Generates HTML preview of quotation for printing/saving as PDF
 */

require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'quotation', 'view');

// Get quotation ID
$quotation_id = $_GET['id'] ?? 0;

if (!$quotation_id) {
    die('Invalid quotation ID');
}

// Get quotation details
$quotation = fetchOne("
    SELECT q.*, e.client_name as enquiry_client, e.phone as enquiry_phone, e.project_type, e.site_location
    FROM project_quotations q
    LEFT JOIN enquiries e ON q.enquiry_id = e.id
    WHERE q.id = ?
", "i", [$quotation_id]);

if (!$quotation) {
    die('Quotation not found');
}

// Include PDF generator
require_once '../lib/quotation_pdf.php';

$pdfGenerator = new QuotationPDFGenerator();

// Output HTML preview
header('Content-Type: text/html; charset=utf-8');
echo $pdfGenerator->generateHTML($quotation);