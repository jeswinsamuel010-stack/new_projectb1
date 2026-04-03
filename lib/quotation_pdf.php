<?php
require_once __DIR__ . '/tcpdf_setup.php';

/**
 * QuotationPDFGenerator
 *
 * Generates professional PDF quotations for the Construction ERP
 */
class QuotationPDFGenerator
{
    private $companyName = 'Construction ERP';
    private $companyTagline = 'Professional Construction Solutions';
    private $companyAddress = '123 Construction Lane, Building City, State 123456';
    private $companyPhone = '+91 9876543210';
    private $companyEmail = 'info@constructionerp.com';

    /**
     * Generate PDF for a quotation
     *
     * @param array $quotation Quotation data
     * @param bool $download True to download, false to output to browser
     * @return string|bool PDF content or false on failure
     */
    public function generate($quotation, $download = true)
    {
        if (!TCPDF_AVAILABLE) {
            return $this->generateSimplePDF($quotation, $download);
        }

        require_once TCPDF_DIR . '/tcpdf.php';

        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Construction ERP');
        $pdf->SetAuthor($this->companyName);
        $pdf->SetTitle('Quotation - ' . $quotation['quotation_number']);
        $pdf->SetSubject('Quotation for ' . $quotation['project_title']);

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // Add a page
        $pdf->AddPage();

        // Company Header
        $this->addHeader($pdf);

        // Quotation Title
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetTextColor(30, 60, 114);
        $pdf->Cell(0, 10, 'QUOTATION', 0, true, 'C', false);
        $pdf->Ln(5);

        // Quotation Details
        $this->addQuotationDetails($pdf, $quotation);

        // Client Information
        $this->addClientInfo($pdf, $quotation);

        // Project Details
        $this->addProjectDetails($pdf, $quotation);

        // Scope of Work
        if (!empty($quotation['scope_description'])) {
            $this->addScopeOfWork($pdf, $quotation);
        }

        // Financial Summary
        $this->addFinancialSummary($pdf, $quotation);

        // Terms and Conditions
        $this->addTermsConditions($pdf);

        // Signature Section
        $this->addSignatureSection($pdf);

        // Footer
        $this->addFooter($pdf);

        // Output
        if ($download) {
            $filename = 'Quotation_' . $quotation['quotation_number'] . '.pdf';
            $pdf->Output($filename, 'D');
            return true;
        } else {
            return $pdf->Output('quotation.pdf', 'S');
        }
    }

    /**
     * Add company header to PDF
     */
    private function addHeader($pdf)
    {
        // Header background
        $pdf->SetFillColor(30, 60, 114);
        $pdf->Rect(0, 0, 210, 35, 'F');

        // Company Name
        $pdf->SetFont('helvetica', 'B', 24);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 15, $this->companyName, 0, true, 'C', false);

        // Tagline
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(200, 200, 200);
        $pdf->Cell(0, 8, $this->companyTagline, 0, true, 'C', false);

        $pdf->Ln(5);
    }

    /**
     * Add quotation details
     */
    private function addQuotationDetails($pdf, $quotation)
    {
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 10);

        // Quotation Number and Date
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(95, 8, 'Quotation Number: ' . $quotation['quotation_number'], 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(95, 8, 'Date: ' . date('d-m-Y', strtotime($quotation['created_at'])), 0, 1, 'R');

        // Valid Until
        $validUntil = !empty($quotation['valid_until']) ? date('d-m-Y', strtotime($quotation['valid_until'])) : 'Not specified';
        $pdf->Cell(95, 8, 'Valid Until: ' . $validUntil, 0, 1, 'L');

        $pdf->Ln(5);
        $pdf->SetDrawColor(30, 60, 114);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);
    }

    /**
     * Add client information
     */
    private function addClientInfo($pdf, $quotation)
    {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(30, 60, 114);
        $pdf->Cell(0, 8, 'Client Information', 0, true, 'L');
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->Cell(95, 7, 'Name: ' . $quotation['client_name'], 0, 0, 'L');
        $pdf->Cell(95, 7, 'Phone: ' . $quotation['contact_phone'], 0, 1, 'R');

        $email = !empty($quotation['contact_email']) ? $quotation['contact_email'] : 'N/A';
        $pdf->Cell(95, 7, 'Email: ' . $email, 0, 1, 'L');

        $pdf->Ln(5);
    }

    /**
     * Add project details
     */
    private function addProjectDetails($pdf, $quotation)
    {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(30, 60, 114);
        $pdf->Cell(0, 8, 'Project Details', 0, true, 'L');
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(0, 7, 'Project Title: ' . $quotation['project_title'], 0, 'L');

        $pdf->Ln(3);
    }

    /**
     * Add scope of work
     */
    private function addScopeOfWork($pdf, $quotation)
    {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(30, 60, 114);
        $pdf->Cell(0, 8, 'Scope of Work', 0, true, 'L');
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(248, 249, 250);
        $pdf->MultiCell(0, 7, $quotation['scope_description'], 0, 'L', true);

        $pdf->Ln(5);
    }

    /**
     * Add financial summary
     */
    private function addFinancialSummary($pdf, $quotation)
    {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(30, 60, 114);
        $pdf->Cell(0, 8, 'Financial Summary', 0, true, 'L');
        $pdf->Ln(2);

        // Amount box
        $pdf->SetFillColor(17, 152, 142);
        $pdf->Rect(15, $pdf->GetY(), 180, 25, 'F');

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(90, 10, 'Estimated Amount', 0, 0, 'C', false);
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(90, 10, 'INR ' . number_format($quotation['estimated_amount'], 2), 0, 1, 'C', false);

        $pdf->Ln(10);
    }

    /**
     * Add terms and conditions
     */
    private function addTermsConditions($pdf)
    {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(30, 60, 114);
        $pdf->Cell(0, 8, 'Terms and Conditions', 0, true, 'L');
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(100, 100, 100);

        $terms = [
            '1. This quotation is valid until the date specified above.',
            '2. Prices are inclusive of all applicable taxes unless stated otherwise.',
            '3. Advance payment of 25-30% is required to start the project.',
            '4. Final payment shall be made upon completion of work.',
            '5. Any changes in scope may affect the final cost.',
            '6. This quotation is subject to site inspection and feasibility study.',
            '7. Construction timeline will be confirmed after site visit.',
        ];

        foreach ($terms as $term) {
            $pdf->Cell(5, 6, '•', 0, 0, 'L');
            $pdf->MultiCell(0, 6, $term, 0, 'L');
        }

        $pdf->Ln(5);
    }

    /**
     * Add signature section
     */
    private function addSignatureSection($pdf)
    {
        $pdf->Ln(10);

        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(10);

        // Two columns for signatures
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        // Company Signature
        $pdf->Cell(90, 8, 'For ' . $this->companyName, 0, 0, 'L');
        $pdf->Cell(90, 8, 'Client Acknowledgment', 0, 1, 'R');

        $pdf->Ln(20);

        $pdf->Cell(90, 8, '____________________________', 0, 0, 'L');
        $pdf->Cell(90, 8, '____________________________', 0, 1, 'R');

        $pdf->Cell(90, 6, 'Authorized Signatory', 0, 0, 'L');
        $pdf->Cell(90, 6, 'Client Signature', 0, 1, 'R');

        $pdf->Cell(90, 6, 'Date: ________________', 0, 0, 'L');
        $pdf->Cell(90, 6, 'Date: ________________', 0, 1, 'R');
    }

    /**
     * Add footer
     */
    private function addFooter($pdf)
    {
        $pdf->SetY(-25);
        $pdf->SetFillColor(30, 60, 114);
        $pdf->Rect(0, $pdf->GetY(), 210, 25, 'F');

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(255, 255, 255);

        $pdf->Cell(0, 8, 'Thank you for your business!', 0, true, 'C', false);
        $pdf->Cell(0, 5, $this->companyAddress . ' | Phone: ' . $this->companyPhone . ' | Email: ' . $this->companyEmail, 0, true, 'C', false);
        $pdf->Cell(0, 5, 'Powered by Construction ERP', 0, true, 'C', false);
    }

    /**
     * Generate a simple PDF using basic HTML (fallback when TCPDF not available)
     */
    private function generateSimplePDF($quotation, $download = true)
    {
        // Create a simple HTML-based PDF using basic PHP
        // This is a fallback when TCPDF is not available

        $html = $this->generateHTML($quotation);

        // For now, output as HTML (in production, would use dompdf or similar)
        if ($download) {
            header('Content-Type: text/html');
            header('Content-Disposition: attachment; filename="Quotation_' . $quotation['quotation_number'] . '.html"');
            echo $html;
            exit;
        }

        return $html;
    }

    /**
     * Generate HTML representation of quotation
     */
    public function generateHTML($quotation)
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quotation - ' . $quotation['quotation_number'] . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #333; }
        .header { background: #1e3c72; color: white; padding: 20px; text-align: center; }
        .header h1 { margin: 0; }
        .header p { margin: 5px 0 0; opacity: 0.8; }
        .section { margin: 20px 0; }
        .section h3 { color: #1e3c72; border-bottom: 1px solid #1e3c72; padding-bottom: 5px; }
        .details-table { width: 100%; border-collapse: collapse; }
        .details-table td { padding: 8px; border-bottom: 1px solid #eee; }
        .amount-box { background: linear-gradient(135deg, #11998e, #38ef7d); color: white; padding: 20px; text-align: center; border-radius: 8px; }
        .amount-box .amount { font-size: 28px; font-weight: bold; }
        .terms { background: #f8f9fa; padding: 15px; border-radius: 5px; }
        .terms ul { margin: 0; padding-left: 20px; }
        .signature-section { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature-box { width: 45%; }
        .signature-line { border-top: 1px solid #333; margin-top: 40px; padding-top: 5px; }
        .footer { background: #1e3c72; color: white; text-align: center; padding: 15px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . $this->companyName . '</h1>
        <p>' . $this->companyTagline . '</p>
    </div>

    <div style="text-align: center; margin: 20px 0;">
        <h2>QUOTATION</h2>
        <p><strong>' . $quotation['quotation_number'] . '</strong></p>
    </div>

    <table class="details-table">
        <tr>
            <td><strong>Date:</strong> ' . date('d-m-Y', strtotime($quotation['created_at'])) . '</td>
            <td><strong>Valid Until:</strong> ' . (!empty($quotation['valid_until']) ? date('d-m-Y', strtotime($quotation['valid_until'])) : 'Not specified') . '</td>
        </tr>
    </table>

    <div class="section">
        <h3>Client Information</h3>
        <table class="details-table">
            <tr><td><strong>Name:</strong> ' . htmlspecialchars($quotation['client_name']) . '</td></tr>
            <tr><td><strong>Phone:</strong> ' . htmlspecialchars($quotation['contact_phone']) . '</td></tr>
            <tr><td><strong>Email:</strong> ' . htmlspecialchars($quotation['contact_email'] ?? 'N/A') . '</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>Project Details</h3>
        <p><strong>Project Title:</strong> ' . htmlspecialchars($quotation['project_title']) . '</p>
    </div>';

        if (!empty($quotation['scope_description'])) {
            $html .= '
    <div class="section">
        <h3>Scope of Work</h3>
        <p>' . nl2br(htmlspecialchars($quotation['scope_description'])) . '</p>
    </div>';
        }

        $html .= '
    <div class="section" style="text-align: center;">
        <div class="amount-box">
            <div>Estimated Amount</div>
            <div class="amount">INR ' . number_format($quotation['estimated_amount'], 2) . '</div>
        </div>
    </div>

    <div class="section">
        <h3>Terms and Conditions</h3>
        <div class="terms">
            <ul>
                <li>This quotation is valid until the date specified above.</li>
                <li>Prices are inclusive of all applicable taxes unless stated otherwise.</li>
                <li>Advance payment of 25-30% is required to start the project.</li>
                <li>Final payment shall be made upon completion of work.</li>
                <li>Any changes in scope may affect the final cost.</li>
                <li>This quotation is subject to site inspection and feasibility study.</li>
            </ul>
        </div>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <p>For ' . $this->companyName . '</p>
            <div class="signature-line">Authorized Signatory</div>
        </div>
        <div class="signature-box">
            <p>Client Acknowledgment</p>
            <div class="signature-line">Client Signature</div>
        </div>
    </div>

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>' . $this->companyAddress . ' | Phone: ' . $this->companyPhone . '</p>
    </div>
</body>
</html>';

        return $html;
    }
}