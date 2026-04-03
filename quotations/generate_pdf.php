<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

checkAccess(null, 'quotation', 'view');

$id = $_GET['id'] ?? 0;

if (!$id) {
    die("Invalid quotation ID");
}

$quotation = fetchOne("
    SELECT q.*, e.client_name as enquiry_client, e.phone as enquiry_phone, e.email as enquiry_email,
           e.project_type, e.site_location
    FROM project_quotations q
    LEFT JOIN enquiries e ON q.enquiry_id = e.id
    WHERE q.id = ?
", "i", [$id]);

if (!$quotation) {
    die("Quotation not found");
}

/* -----------------------------
   BUILD HTML FOR PDF
--------------------------------*/

$html = '
<style>
body{
    font-family: DejaVu Sans, sans-serif;
    font-size: 14px;
}

.header{
    text-align:center;
    margin-bottom:20px;
}

.title{
    font-size:24px;
    font-weight:bold;
}

table{
    width:100%;
    border-collapse: collapse;
    margin-top:15px;
}

table th, table td{
    border:1px solid #ddd;
    padding:8px;
}

th{
    background:#f5f5f5;
}

.total{
    font-size:18px;
    font-weight:bold;
    color:green;
}

.footer{
    margin-top:40px;
    text-align:right;
}
</style>

<div class="header">
    <div class="title">Quotation</div>
</div>

<table>
<tr>
<th width="30%">Quotation Number</th>
<td>'.$quotation['quotation_number'].'</td>
</tr>

<tr>
<th>Client Name</th>
<td>'.$quotation['client_name'].'</td>
</tr>

<tr>
<th>Phone</th>
<td>'.$quotation['contact_phone'].'</td>
</tr>

<tr>
<th>Email</th>
<td>'.($quotation['contact_email'] ?: '-').'</td>
</tr>

<tr>
<th>Project Title</th>
<td>'.$quotation['project_title'].'</td>
</tr>

<tr>
<th>Project Type</th>
<td>'.($quotation['project_type'] ?: '-').'</td>
</tr>

<tr>
<th>Site Location</th>
<td>'.($quotation['site_location'] ?: '-').'</td>
</tr>

<tr>
<th>Valid Until</th>
<td>'.($quotation['valid_until'] ? date('d-m-Y', strtotime($quotation['valid_until'])) : '-').'</td>
</tr>

<tr>
<th>Created Date</th>
<td>'.date('d-m-Y', strtotime($quotation['created_at'])).'</td>
</tr>

<tr>
<th>Estimated Amount</th>
<td class="total">INR '.number_format($quotation['estimated_amount'],2).'</td>
</tr>

</table>

';

if (!empty($quotation['scope_description'])) {

$html .= '

<h3>Scope Description</h3>

<p>'.$quotation['scope_description'].'</p>

';

}

$html .= '

<div class="footer">
<br><br>
_________________________<br>
Authorized Signature
</div>

';

/* -----------------------------
   GENERATE PDF
--------------------------------*/

$options = new Options();
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

/* FORCE DOWNLOAD PDF */

$dompdf->stream(
    "Quotation_".$quotation['quotation_number'].".pdf",
    ["Attachment" => true]
);

exit;