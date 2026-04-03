<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'issues', 'list');

$page_title = 'PDF Import for GRN';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2><i class="fas fa-file-pdf"></i> PDF Import for GRN</h2>
        <a href="../issues/index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Material Issues
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Upload Section -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-upload"></i> Upload PDF</h3>
                </div>
                <div class="card-body">
                    <div id="uploadArea" class="upload-area">
                        <div class="upload-content">
                            <i class="fas fa-cloud-upload-alt fa-3x"></i>
                            <p>Drag & Drop PDF here</p>
                            <span>or</span>
                            <input type="file" id="pdfFile" accept=".pdf,application/pdf" style="display: none;">
                            <button class="btn btn-primary" onclick="document.getElementById('pdfFile').click()">
                                Browse Files
                            </button>
                            <p class="text-muted small">Supported: PDF files up to 10MB</p>
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
                </div>
            </div>

            <!-- Raw Text Preview -->
            <div class="card mt-3" id="rawTextCard" style="display: none;">
                <div class="card-header">
                    <h3><i class="fas fa-file-alt"></i> Extracted Text Preview</h3>
                </div>
                <div class="card-body">
                    <pre id="rawTextPreview" class="raw-text-preview"></pre>
                </div>
            </div>
        </div>

        <!-- Form Section -->
        <div class="col-md-7">
            <div class="card" id="formCard">
                <div class="card-header">
                    <h3><i class="fas fa-edit"></i> Extracted Data</h3>
                </div>
                <div class="card-body">
                    <form id="grnForm" method="POST" action="../api/grn.php">
                        <input type="hidden" name="action" value="create_from_pdf">
                        <input type="hidden" name="supplier_id" id="supplierId">

                        <!-- Supplier Section -->
                        <div class="form-section">
                            <h4>Supplier Information</h4>
                            <div class="form-group">
                                <label for="supplierSearch">Supplier Name *</label>
                                <div class="supplier-input-wrapper">
                                    <input type="text" id="supplierSearch" class="form-control" placeholder="Search supplier..." autocomplete="off">
                                    <div id="supplierSuggestions" class="supplier-suggestions"></div>
                                </div>
                                <input type="hidden" name="supplier_name" id="supplierName">
                                <div id="supplierStatus" class="supplier-status"></div>
                            </div>
                        </div>

                        <!-- Product Section -->
                        <div class="form-section">
                            <h4>Product Information</h4>
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label for="productName">Product Name *</label>
                                    <input type="text" id="productName" name="product_name" class="form-control" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="productUnit">Unit</label>
                                    <select id="productUnit" name="unit" class="form-control">
                                        <option value="pieces">Pieces</option>
                                        <option value="bags">Bags</option>
                                        <option value="kg">Kg</option>
                                        <option value="ton">Ton</option>
                                        <option value="cu.mt">Cu.mt</option>
                                        <option value="liter">Liter</option>
                                        <option value="sq.ft">Sq.ft</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="productQty">Quantity *</label>
                                <input type="number" id="productQty" name="quantity" class="form-control" step="0.01" min="0.01" required>
                            </div>
                        </div>

                        <!-- Invoice Section -->
                        <div class="form-section">
                            <h4>Invoice Details</h4>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="invoiceNumber">Invoice Number</label>
                                    <input type="text" id="invoiceNumber" name="invoice_number" class="form-control">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="invoiceDate">Invoice Date</label>
                                    <input type="date" id="invoiceDate" name="invoice_date" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- PO Link Section -->
                        <div class="form-section">
                            <h4>Link to Purchase Order (Optional)</h4>
                            <div class="form-group">
                                <label for="poSearch">Purchase Order</label>
                                <div class="supplier-input-wrapper">
                                    <input type="text" id="poSearch" class="form-control" placeholder="Search PO..." autocomplete="off">
                                    <div id="poSuggestions" class="supplier-suggestions"></div>
                                </div>
                                <input type="hidden" name="po_id" id="poId">
                                <small class="text-muted">Leave empty if not linking to a PO</small>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="form-section">
                            <div class="form-group">
                                <label for="remarks">Remarks</label>
                                <textarea id="remarks" name="remarks" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="form-actions">
                            <button type="button" class="btn btn-danger" onclick="resetForm()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-success" id="submitBtn">
                                <i class="fas fa-check"></i> Create GRN
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
                        <p>Upload a supplier invoice/delivery PDF to auto-fill GRN data</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.upload-area {
    border: 2px dashed #ccc;
    border-radius: 8px;
    padding: 40px 20px;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}
.upload-area:hover, .upload-area.dragover {
    border-color: #007bff;
    background: #f8f9fa;
}
.upload-content i {
    color: #6c757d;
    margin-bottom: 15px;
}
.upload-content p {
    margin: 10px 0 5px;
    font-size: 16px;
}
.upload-content span {
    display: block;
    color: #6c757d;
    margin: 5px 0;
}
.upload-progress {
    text-align: center;
    padding: 40px;
}
.spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.file-info {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: #e9f7ef;
    border-radius: 8px;
    margin-top: 15px;
}
.file-info i {
    color: #dc3545;
    font-size: 24px;
}
.file-info span {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
}
.form-section {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}
.form-section h4 {
    font-size: 14px;
    color: #6c757d;
    margin-bottom: 15px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.supplier-input-wrapper {
    position: relative;
}
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
.supplier-suggestions.show {
    display: block;
}
.suggestion-item {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}
.suggestion-item:hover {
    background: #f8f9fa;
}
.suggestion-item:last-child {
    border-bottom: none;
}
.supplier-status {
    margin-top: 5px;
    font-size: 12px;
}
.supplier-status.found {
    color: #28a745;
}
.supplier-status.not-found {
    color: #dc3545;
}
.raw-text-preview {
    max-height: 300px;
    overflow-y: auto;
    font-size: 12px;
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    white-space: pre-wrap;
    word-wrap: break-word;
}
.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}
</style>

<script>
let extractedData = null;

// File upload handling
const pdfFile = document.getElementById('pdfFile');
const uploadArea = document.getElementById('uploadArea');

pdfFile.addEventListener('change', handleFileSelect);

// Drag and drop
uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        handleFile(files[0]);
    }
});

function handleFileSelect(e) {
    const file = e.target.files[0];
    handleFile(file);
}

function handleFile(file) {
    if (!file || file.type !== 'application/pdf') {
        alert('Please select a PDF file');
        return;
    }

    if (file.size > 10 * 1024 * 1024) {
        alert('File too large. Maximum 10MB allowed.');
        return;
    }

    // Show file info
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileInfo').style.display = 'flex';
    uploadArea.style.display = 'none';
    document.getElementById('uploadProgress').style.display = 'block';

    // Upload and parse
    const formData = new FormData();
    formData.append('action', 'parse');
    formData.append('pdf_file', file);

    fetch('../api/pdf_import.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('uploadProgress').style.display = 'none';

        if (data.success) {
            extractedData = data.data;
            populateForm(data);
            document.getElementById('rawTextPreview').textContent = data.raw_text;
            document.getElementById('rawTextCard').style.display = 'block';
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('formCard').style.display = 'block';
        } else {
            alert(data.message || 'Failed to parse PDF');
            resetUpload();
        }
    })
    .catch(err => {
        document.getElementById('uploadProgress').style.display = 'none';
        alert('Error processing PDF: ' + err.message);
        resetUpload();
    });
}

function populateForm(data) {
    const d = data.data;
    const match = data.supplier_match;

    // Supplier
    if (match && match.found) {
        document.getElementById('supplierSearch').value = match.supplier ? match.supplier.name : d.supplier_name;
        document.getElementById('supplierName').value = match.supplier ? match.supplier.name : d.supplier_name;
        document.getElementById('supplierId').value = match.supplier ? match.supplier.id : '';
        document.getElementById('supplierStatus').innerHTML = '<span class="found"><i class="fas fa-check-circle"></i> Found in database</span>';
    } else {
        document.getElementById('supplierSearch').value = d.supplier_name;
        document.getElementById('supplierName').value = d.supplier_name;
        document.getElementById('supplierStatus').innerHTML = '<span class="not-found"><i class="fas fa-exclamation-circle"></i> Supplier not found - please select manually</span>';
    }

    // Product
    document.getElementById('productName').value = d.product_name || '';
    document.getElementById('productQty').value = d.quantity || '';
    if (d.unit) {
        document.getElementById('productUnit').value = d.unit.toLowerCase();
    }

    // Invoice
    document.getElementById('invoiceNumber').value = d.invoice_number || '';
    document.getElementById('invoiceDate').value = d.date || '';
}

function resetUpload() {
    pdfFile.value = '';
    document.getElementById('fileInfo').style.display = 'none';
    document.getElementById('uploadProgress').style.display = 'none';
    uploadArea.style.display = 'block';
    document.getElementById('rawTextCard').style.display = 'none';
    document.getElementById('emptyState').style.display = 'block';
    document.getElementById('formCard').style.display = 'none';
    extractedData = null;
}

function resetForm() {
    resetUpload();
}

// Supplier search
const supplierSearch = document.getElementById('supplierSearch');
const supplierSuggestions = document.getElementById('supplierSuggestions');

supplierSearch.addEventListener('input', debounce(function() {
    const search = this.value;
    if (search.length < 2) {
        supplierSuggestions.classList.remove('show');
        return;
    }

    fetch('../api/suppliers.php?search=' + encodeURIComponent(search))
        .then(response => response.json())
        .then(data => {
            if (data.suppliers && data.suppliers.length > 0) {
                supplierSuggestions.innerHTML = data.suppliers.map(s =>
                    '<div class="suggestion-item" onclick="selectSupplier(' + s.id + ', \'' + s.name.replace(/'/g, "\\'") + '\')">' +
                    '<strong>' + s.name + '</strong><br>' +
                    '<small>' + (s.contact_person || '') + ' ' + (s.phone || '') + '</small>' +
                    '</div>'
                ).join('');
                supplierSuggestions.classList.add('show');
            } else {
                supplierSuggestions.classList.remove('show');
            }
        });
}, 300));

function selectSupplier(id, name) {
    document.getElementById('supplierId').value = id;
    document.getElementById('supplierName').value = name;
    document.getElementById('supplierSearch').value = name;
    supplierSuggestions.classList.remove('show');
    document.getElementById('supplierStatus').innerHTML = '<span class="found"><i class="fas fa-check-circle"></i> Selected</span>';
}

// PO search
const poSearch = document.getElementById('poSearch');
const poSuggestions = document.getElementById('poSuggestions');

poSearch.addEventListener('input', debounce(function() {
    const search = this.value;
    if (search.length < 2) {
        poSuggestions.classList.remove('show');
        return;
    }

    fetch('../api/grn.php?action=get_pos&search=' + encodeURIComponent(search))
        .then(response => response.json())
        .then(data => {
            if (data.purchase_orders && data.purchase_orders.length > 0) {
                poSuggestions.innerHTML = data.purchase_orders.map(po =>
                    '<div class="suggestion-item" onclick="selectPO(' + po.id + ', \'' + po.po_number.replace(/'/g, "\\'") + '\')">' +
                    '<strong>' + po.po_number + '</strong> - ' + (po.item_name || 'N/A') +
                    '</div>'
                ).join('');
                poSuggestions.classList.add('show');
            } else {
                poSuggestions.classList.remove('show');
            }
        });
}, 300));

function selectPO(id, poNumber) {
    document.getElementById('poId').value = id;
    document.getElementById('poSearch').value = poNumber;
    poSuggestions.classList.remove('show');
}

// Close suggestions on click outside
document.addEventListener('click', (e) => {
    if (!e.target.closest('.supplier-input-wrapper')) {
        supplierSuggestions.classList.remove('show');
        poSuggestions.classList.remove('show');
    }
});

// Form submission
document.getElementById('grnForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('../api/grn.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'import_pdf.php?success=GRN #' + data.grn_id + ' created successfully! Inventory updated.';
        } else {
            alert(data.message || 'Failed to create GRN');
        }
    })
    .catch(err => {
        alert('Error: ' + err.message);
    });
});

// Utility function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>

<?php require_once '../includes/footer.php'; ?>