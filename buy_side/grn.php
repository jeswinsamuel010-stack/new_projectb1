<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'issues', 'list');

$page_title = 'GRN - Goods Receipt';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2><i class="fas fa-truck-loading"></i> GRN - Goods Receipt</h2>
        <button class="btn btn-primary" onclick="openModal('grnModal')">
            <i class="fas fa-plus"></i> Manual GRN
        </button>
    </div>

    <div class="row">
        <!-- PDF Upload Section -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-file-pdf"></i> Upload Supplier PDF</h3>
                </div>
                <div class="card-body">
                    <div id="uploadArea" class="upload-area">
                        <div class="upload-content">
                            <i class="fas fa-cloud-upload-alt fa-3x"></i>
                            <p>Drag & Drop PDF Invoice</p>
                            <span>or</span>
                            <input type="file" id="pdfFile" accept=".pdf" style="display: none;">
                            <button class="btn btn-primary" onclick="document.getElementById('pdfFile').click()">
                                Browse Files
                            </button>
                            <p class="text-muted small">Supports: Supplier Invoice / Delivery Note</p>
                        </div>
                    </div>

                    <div id="uploadProgress" class="upload-progress" style="display: none;">
                        <div class="spinner"></div>
                        <p>Processing PDF...</p>
                    </div>

                    <div id="fileInfo" class="file-info" style="display: none;">
                        <i class="fas fa-file-pdf"></i>
                        <span id="fileName"></span>
                        <button class="btn btn-sm btn-danger" onclick="resetUpload()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div id="extractedText" class="extracted-text" style="display: none;">
                        <h5>Extracted Text</h5>
                        <pre id="rawText"></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- GRN Form Section -->
        <div class="col-md-7">
            <div class="card" id="grnFormCard" style="display: none;">
                <div class="card-header">
                    <h3><i class="fas fa-edit"></i> GRN Details</h3>
                </div>
                <div class="card-body">
                    <form id="grnForm">
                        <input type="hidden" name="supplier_id" id="supplierId">
                        <input type="hidden" name="po_id" id="poId">

                        <div class="form-section">
                            <h4>Supplier</h4>
                            <div class="form-group">
                                <label>Supplier Name *</label>
                                <div class="supplier-input-wrapper">
                                    <input type="text" id="supplierSearch" class="form-control" placeholder="Search supplier..." autocomplete="off">
                                    <div id="supplierSuggestions" class="supplier-suggestions"></div>
                                </div>
                                <input type="hidden" name="supplier_name" id="supplierName">
                                <div id="supplierStatus" class="supplier-status"></div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h4>Invoice Details</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Invoice Number</label>
                                        <input type="text" name="invoice_number" id="invoiceNumber" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Invoice Date</label>
                                        <input type="date" name="invoice_date" id="invoiceDate" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h4>Items
                                <button type="button" class="btn btn-sm btn-secondary float-end" onclick="addGrnItem()">
                                    <i class="fas fa-plus"></i> Add Item
                                </button>
                            </h4>
                            <table class="table table-bordered" id="grnItemsTable">
                                <thead>
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Qty</th>
                                        <th>Unit</th>
                                        <th>Accepted</th>
                                        <th>Rejected</th>
                                        <th>Rate</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="grnItemsBody">
                                </tbody>
                            </table>
                        </div>

                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-danger" onclick="resetAll()">Cancel</button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> Create GRN & Update Stock
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Empty State -->
            <div class="card" id="emptyState">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-file-pdf fa-4x"></i>
                        <p>Upload a supplier PDF invoice to auto-fill GRN</p>
                        <p class="text-muted">Or click "Manual GRN" to enter manually</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- GRN List -->
    <div class="card mt-4">
        <div class="card-header">
            <h3>Recent GRNs</h3>
        </div>
        <div class="card-body">
            <table id="grnTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>GRN Number</th>
                        <th>PO Number</th>
                        <th>Supplier</th>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- View GRN Modal -->
<div id="viewGrnModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>GRN Details</h3>
            <button class="modal-close" onclick="closeModal('viewGrnModal')">&times;</button>
        </div>
        <div class="modal-body" id="viewGrnBody">
        </div>
    </div>
</div>

<!-- Manual GRN Modal -->
<div id="grnModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>Manual GRN</h3>
            <button class="modal-close" onclick="closeModal('grnModal')">&times;</button>
        </div>
        <form id="manualGrnForm">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Supplier *</label>
                            <select name="supplier_id" id="manualSupplierId" class="form-control" required>
                                <option value="">Select Supplier</option>
                                <?php
                                $suppliers = fetchAll("SELECT * FROM suppliers WHERE status = 'active' ORDER BY name");
                                foreach ($suppliers as $s) {
                                    echo '<option value="' . $s['id'] . '">' . htmlspecialchars($s['name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>PO (Optional)</label>
                            <select name="po_id" id="manualPoId" class="form-control">
                                <option value="">No PO</option>
                                <?php
                                $pos = fetchAll("SELECT po.id, po.po_number, s.name as supplier_name FROM buy_purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.id WHERE po.status IN ('sent', 'acknowledged', 'partial') ORDER BY po.created_at DESC");
                                foreach ($pos as $po) {
                                    echo '<option value="' . $po['id'] . '">' . htmlspecialchars($po['po_number']) . ' - ' . htmlspecialchars($po['supplier_name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Invoice Number</label>
                            <input type="text" name="invoice_number" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Invoice Date</label>
                            <input type="date" name="invoice_date" class="form-control">
                        </div>
                    </div>
                </div>

                <h5>Items</h5>
                <table class="table table-bordered" id="manualItemsTable">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Rate</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="manualItemsBody">
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-secondary" onclick="addManualItem()">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeModal('grnModal')">Cancel</button>
                <button type="submit" class="btn btn-success">Create GRN</button>
            </div>
        </form>
    </div>
</div>

<style>
.upload-area {
    border: 2px dashed #ccc;
    border-radius: 8px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}
.upload-area:hover { border-color: #007bff; background: #f8f9fa; }
.upload-content i { color: #6c757d; margin-bottom: 15px; }
.upload-progress { text-align: center; padding: 40px; }
.spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
.file-info {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: #e9f7ef;
    border-radius: 8px;
    margin-top: 15px;
}
.file-info i { color: #dc3545; font-size: 24px; }
.extracted-text { margin-top: 15px; }
.extracted-text pre {
    max-height: 200px;
    overflow-y: auto;
    font-size: 12px;
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
}
.form-section { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
.form-section h4 { font-size: 14px; color: #6c757d; margin-bottom: 15px; text-transform: uppercase; }
.supplier-input-wrapper { position: relative; }
.supplier-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 0 0 4px 4px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
}
.supplier-suggestions.show { display: block; }
.suggestion-item { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
.suggestion-item:hover { background: #f8f9fa; }
.supplier-status { margin-top: 5px; font-size: 12px; }
.supplier-status.found { color: #28a745; }
.supplier-status.not-found { color: #dc3545; }
.form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
</style>

<script>
let grnTable;
let extractedItems = [];

$(document).ready(function() {
    grnTable = $('#grnTable').DataTable({
        ajax: {
            url: '../api/buy_side.php?module=grn',
            type: 'POST',
            data: function(d) { d.action = 'list'; }
        },
        columns: [
            { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 }, { data: 5 }, { data: 6 }
        ]
    });

    // PDF Upload
    $('#pdfFile').change(handleFileSelect);
    $('#uploadArea').on('dragover', function(e) { e.preventDefault(); $(this).addClass('dragover'); });
    $('#uploadArea').on('dragleave', function() { $(this).removeClass('dragover'); });
    $('#uploadArea').on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        if (e.dataTransfer.files.length > 0) handleFile(e.dataTransfer.files[0]);
    });

    // Supplier search
    $('#supplierSearch').on('input', debounce(function() {
        const search = this.value;
        if (search.length < 2) { $('#supplierSuggestions').removeClass('show'); return; }
        $.get('../api/suppliers.php?search=' + encodeURIComponent(search), function(data) {
            if (data.suppliers && data.suppliers.length > 0) {
                $('#supplierSuggestions').html(data.suppliers.map(s =>
                    '<div class="suggestion-item" onclick="selectSupplier(' + s.id + ', \'' + s.name.replace(/'/g, "\\'") + '\')">' +
                    '<strong>' + s.name + '</strong></div>'
                ).join(''));
                $('#supplierSuggestions').addClass('show');
            }
        });
    }, 300));

    $(document).click(function(e) {
        if (!e.target.closest('.supplier-input-wrapper')) {
            $('#supplierSuggestions').removeClass('show');
        }
    });

    // GRN Form submit
    $('#grnForm').on('submit', function(e) {
        e.preventDefault();
        const items = [];
        $('#grnItemsBody tr').each(function() {
            const row = $(this);
            items.push({
                item_name: row.find('.item-name').val(),
                quantity: parseFloat(row.find('.item-qty').val()) || 0,
                unit: row.find('.item-unit').val(),
                accepted_qty: parseFloat(row.find('.item-accepted').val()) || 0,
                rejected_qty: parseFloat(row.find('.item-rejected').val()) || 0,
                rate: parseFloat(row.find('.item-rate').val()) || 0
            });
        });

        if (items.length === 0) { alert('Please add at least one item'); return; }

        const formData = new FormData();
        formData.append('action', 'create');
        formData.append('supplier_id', $('#supplierId').val());
        formData.append('po_id', $('#poId').val());
        formData.append('invoice_number', $('#invoiceNumber').val());
        formData.append('invoice_date', $('#invoiceDate').val());
        formData.append('notes', $('textarea[name="notes"]').val());
        formData.append('items', JSON.stringify(items));

        $.ajax({
            url: '../api/buy_side.php?module=grn',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('GRN created! Inventory updated.');
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        resetAll();
                        grnTable.ajax.reload();
                    }
                } else {
                    alert(response.message);
                }
            },
            dataType: 'json'
        });
    });

    // Manual GRN
    $('#manualGrnForm').on('submit', function(e) {
        e.preventDefault();
        const items = [];
        $('#manualItemsBody tr').each(function() {
            const row = $(this);
            items.push({
                item_name: row.find('.item-name').val(),
                quantity: parseFloat(row.find('.item-qty').val()) || 0,
                unit: row.find('.item-unit').val(),
                accepted_qty: parseFloat(row.find('.item-qty').val()) || 0,
                rejected_qty: 0,
                rate: parseFloat(row.find('.item-rate').val()) || 0
            });
        });

        if (items.length === 0) { alert('Please add at least one item'); return; }

        const formData = new FormData();
        formData.append('action', 'create');
        formData.append('supplier_id', $('#manualSupplierId').val());
        formData.append('po_id', $('#manualPoId').val());
        formData.append('invoice_number', $('input[name="invoice_number"]', '#manualGrnForm').val());
        formData.append('invoice_date', $('input[name="invoice_date"]', '#manualGrnForm').val());
        formData.append('items', JSON.stringify(items));

        $.ajax({
            url: '../api/buy_side.php?module=grn',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    closeModal('grnModal');
                    alert('GRN created! Inventory updated.');
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        grnTable.ajax.reload();
                    }
                } else {
                    alert(response.message);
                }
            },
            dataType: 'json'
        });
    });

    addManualItem();
});

function handleFileSelect(e) {
    if (e.target.files.length > 0) handleFile(e.target.files[0]);
}

function handleFile(file) {
    if (file.type !== 'application/pdf') { alert('Please select a PDF file'); return; }
    if (file.size > 10 * 1024 * 1024) { alert('File too large (max 10MB)'); return; }

    $('#fileName').text(file.name);
    $('#fileInfo').css('display', 'flex');
    $('#uploadArea').css('display', 'none');
    $('#uploadProgress').css('display', 'block');

    const formData = new FormData();
    formData.append('action', 'parse');
    formData.append('pdf_file', file);

    $.ajax({
        url: '../api/buy_pdf_import.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#uploadProgress').css('display', 'none');
            if (response.success) {
                extractedItems = response.data.items || [];
                populateForm(response);
            } else {
                alert(response.message);
                resetUpload();
            }
        },
        dataType: 'json'
    });
}

function populateForm(response) {
    const d = response.data;
    const match = response.supplier_match;

    // Supplier
    if (match && match.found) {
        $('#supplierId').val(match.supplier ? match.supplier.id : '');
        $('#supplierName').val(match.supplier ? match.supplier.name : d.supplier_name);
        $('#supplierSearch').val(match.supplier ? match.supplier.name : d.supplier_name);
        $('#supplierStatus').html('<span class="found"><i class="fas fa-check-circle"></i> Found in database</span>');
    } else {
        $('#supplierSearch').val(d.supplier_name);
        $('#supplierName').val(d.supplier_name);
        $('#supplierStatus').html('<span class="not-found"><i class="fas fa-exclamation-circle"></i> Select supplier manually</span>');
    }

    // Invoice
    $('#invoiceNumber').val(d.invoice_number || '');
    $('#invoiceDate').val(d.invoice_date || '');

    // Items
    $('#grnItemsBody').empty();
    if (extractedItems.length > 0) {
        extractedItems.forEach(function(item) {
            addGrnItemRow(item.item_name, item.quantity, item.unit, item.rate, item.amount);
        });
    } else {
        addGrnItemRow();
    }

    $('#rawText').text(response.raw_text);
    $('#extractedText').css('display', 'block');
    $('#emptyState').css('display', 'none');
    $('#grnFormCard').css('display', 'block');
}

function addGrnItemRow(name = '', qty = 0, unit = 'pieces', rate = 0, amount = 0) {
    const html = `
        <tr>
            <td><input type="text" class="form-control form-control-sm item-name" value="${name}"></td>
            <td><input type="number" class="form-control form-control-sm item-qty" step="0.01" value="${qty}" onchange="syncQty(this)"></td>
            <td><select class="form-control form-control-sm item-unit">
                <option value="pieces" ${unit === 'pieces' ? 'selected' : ''}>Pieces</option>
                <option value="bags" ${unit === 'bags' ? 'selected' : ''}>Bags</option>
                <option value="kg" ${unit === 'kg' ? 'selected' : ''}>Kg</option>
                <option value="ton" ${unit === 'ton' ? 'selected' : ''}>Ton</option>
                <option value="cu.mt" ${unit === 'cu.mt' ? 'selected' : ''}>Cu.mt</option>
            </select></td>
            <td><input type="number" class="form-control form-control-sm item-accepted" step="0.01" value="${qty}"></td>
            <td><input type="number" class="form-control form-control-sm item-rejected" step="0.01" value="0"></td>
            <td><input type="number" class="form-control form-control-sm item-rate" step="0.01" value="${rate}"></td>
            <td><button type="button" class="btn btn-sm btn-danger" onclick="$(this).closest('tr').remove()"><i class="fas fa-times"></i></button></td>
        </tr>
    `;
    $('#grnItemsBody').append(html);
}

function addGrnItem() { addGrnItemRow(); }
function syncQty(input) { $(input).closest('tr').find('.item-accepted').val($(input).val()); }

function selectSupplier(id, name) {
    $('#supplierId').val(id);
    $('#supplierName').val(name);
    $('#supplierSearch').val(name);
    $('#supplierSuggestions').removeClass('show');
    $('#supplierStatus').html('<span class="found"><i class="fas fa-check-circle"></i> Selected</span>');
}

function addManualItem() {
    const html = `
        <tr>
            <td><input type="text" class="form-control form-control-sm item-name" placeholder="Item name"></td>
            <td><input type="number" class="form-control form-control-sm item-qty" step="0.01" placeholder="Qty"></td>
            <td><select class="form-control form-control-sm item-unit">
                <option value="pieces">Pieces</option>
                <option value="bags">Bags</option>
                <option value="kg">Kg</option>
                <option value="ton">Ton</option>
                <option value="cu.mt">Cu.mt</option>
            </select></td>
            <td><input type="number" class="form-control form-control-sm item-rate" step="0.01" placeholder="Rate"></td>
            <td><button type="button" class="btn btn-sm btn-danger" onclick="$(this).closest('tr').remove()"><i class="fas fa-times"></i></button></td>
        </tr>
    `;
    $('#manualItemsBody').append(html);
}

function viewGRN(id) {
    $.post('../api/buy_side.php?module=grn', { action: 'get', grn_id: id }, function(response) {
        if (response.success) {
            const g = response.data;
            $('#viewGrnBody').html(`
                <table class="table table-bordered">
                    <tr><th>GRN Number</th><td>${g.grn_number}</td></tr>
                    <tr><th>PO</th><td>${g.po_number || 'N/A'}</td></tr>
                    <tr><th>Supplier</th><td>${g.supplier_name}</td></tr>
                    <tr><th>Invoice</th><td>${g.invoice_number || 'N/A'}</td></tr>
                    <tr><th>Date</th><td>${g.received_date}</td></tr>
                    <tr><th>Status</th><td>${g.status}</td></tr>
                </table>
                <h5>Items</h5>
                <table class="table table-striped">
                    <thead><tr><th>Item</th><th>Qty</th><th>Unit</th><th>Accepted</th><th>Rejected</th></tr></thead>
                    <tbody>${g.items.map(i => `<tr><td>${i.item_name}</td><td>${i.quantity}</td><td>${i.unit}</td><td>${i.accepted_qty}</td><td>${i.rejected_qty}</td></tr>`).join('')}</tbody>
                </table>
            `);
            openModal('viewGrnModal');
        }
    }, 'json');
}

function resetUpload() {
    $('#pdfFile').val('');
    $('#fileInfo').css('display', 'none');
    $('#uploadProgress').css('display', 'none');
    $('#uploadArea').css('display', 'block');
    $('#extractedText').css('display', 'none');
}

function resetAll() {
    resetUpload();
    $('#grnForm')[0].reset();
    $('#grnItemsBody').empty();
    $('#grnFormCard').css('display', 'none');
    $('#emptyState').css('display', 'block');
    extractedItems = [];
}

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}
</script>

<?php require_once '../includes/footer.php'; ?>