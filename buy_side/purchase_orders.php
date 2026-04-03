<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'purchase', 'list');

$page_title = 'Purchase Orders';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2><i class="fas fa-shopping-cart"></i> Purchase Orders</h2>
        <button class="btn btn-primary" onclick="openModal('poModal')">
            <i class="fas fa-plus"></i> New PO
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="poTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>Project</th>
                        <th>Supplier</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- PO Modal -->
<div id="poModal" class="modal">
    <div class="modal-content modal-xl">
        <div class="modal-header">
            <h3 id="poModalTitle">New Purchase Order</h3>
            <button class="modal-close" onclick="closeModal('poModal')">&times;</button>
        </div>
        <form id="poForm">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" id="csrfToken" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="po_id" id="poId">

                <!-- PO Header Section -->
                <div class="po-header-section">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Supplier Name</label>
                                <div class="supplier-input-wrapper">
                                    <input type="text" id="supplierSearch" class="form-control" placeholder="Type supplier name..." autocomplete="off">
                                    <div id="supplierSuggestions" class="supplier-suggestions"></div>
                                </div>
                                <input type="hidden" name="supplier_id" id="supplierId">
                                <small class="text-muted">Type to search existing supplier or enter a new name</small>
                                <div class="invalid-feedback" id="supplierError"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>PO Date</label>
                                <input type="date" name="po_date" id="poDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Reference Number</label>
                                <input type="text" name="reference_number" id="referenceNumber" class="form-control" placeholder="Optional">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="notes" id="poNotes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PO Items Section -->
                <div class="items-section">
                    <div class="items-section-header">
                        <h4>Items</h4>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addItem()">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </div>

                    <!-- Item Entry Form -->
                    <div class="item-entry-form" id="itemEntryForm">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Item Name / Material</label>
                                    <input type="text" id="newItemName" class="form-control" placeholder="Enter item name">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Unit</label>
                                    <select id="newItemUnit" class="form-control">
                                        <option value="pieces">Pieces</option>
                                        <option value="kg">Kg</option>
                                        <option value="liter">Liter</option>
                                        <option value="bags">Bags</option>
                                        <option value="ton">Ton</option>
                                        <option value="cu.mt">Cu.mt</option>
                                        <option value="sq.ft">Sq.ft</option>
                                        <option value="meter">Meter</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Quantity</label>
                                    <input type="number" id="newItemQty" class="form-control" placeholder="0" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Rate (Per Unit)</label>
                                    <input type="number" id="newItemRate" class="form-control" placeholder="0.00" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Amount</label>
                                    <input type="text" id="newItemAmount" class="form-control" readonly placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-success btn-block" id="addItemBtn">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="items-table-container">
                        <table class="table items-table" id="itemsTable">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Unit</th>
                                    <th>Quantity</th>
                                    <th>Rate (Per Unit)</th>
                                    <th>Amount</th>
                                    <th style="width: 80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="itemsContainer">
                                <tr class="no-items-row">
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <p>No items added yet. Click "Add Item" to add items.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="invalid-feedback" id="itemsError"></div>
                </div>

                <!-- Total Section -->
                <div class="total-section">
                    <div class="total-label">Total Amount:</div>
                    <div class="total-value" id="poTotal">0.00</div>
                </div>

                <!-- Success Message -->
                <div class="alert alert-success d-none" id="successMessage">
                    <i class="fas fa-check-circle"></i> <span id="successText"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('poModal')">Cancel</button>
                <button type="submit" class="btn btn-success" id="saveBtn">
                    <i class="fas fa-save"></i> Save Purchase Order
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Items Modal -->
<div id="viewItemsModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>PO Items</h3>
            <button class="modal-close" onclick="closeModal('viewItemsModal')">&times;</button>
        </div>
        <div class="modal-body">
            <table class="table table-striped" id="viewItemsTable">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Rate (Per Unit)</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody id="viewItemsBody">
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    /* PO Header Section */
    .po-header-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .items-section {
        margin-top: 20px;
    }

    .items-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .items-section-header h4 {
        margin: 0;
        color: #333;
        font-weight: 600;
    }

    .item-entry-form {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }

    .items-table-container {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
    }

    .items-table {
        margin-bottom: 0;
    }

    .items-table thead {
        background: #f8f9fa;
    }

    .items-table thead th {
        font-weight: 600;
        color: #555;
        border-bottom: 2px solid #dee2e6;
        padding: 12px 15px;
    }

    .items-table tbody td {
        padding: 12px 15px;
        vertical-align: middle;
    }

    .items-table .item-amount {
        font-weight: 600;
        color: #333;
    }

    .no-items-row td {
        background: #fafafa;
    }

    .btn-remove-item {
        padding: 5px 10px;
        font-size: 14px;
    }

    .total-section {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        margin-top: 20px;
        padding: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 8px;
        color: white;
    }

    .total-label {
        font-size: 18px;
        font-weight: 600;
        margin-right: 20px;
    }

    .total-value {
        font-size: 28px;
        font-weight: 700;
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
        max-height: 250px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .supplier-suggestions.show {
        display: block;
    }

    .suggestion-item {
        padding: 12px 15px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.2s;
    }

    .suggestion-item:hover {
        background: #f0f4ff;
    }

    .suggestion-item:last-child {
        border-bottom: none;
    }

    .suggestion-item strong {
        color: #333;
    }

    .create-new-supplier {
        background: #e8f5e9;
        color: #2e7d32;
        font-weight: 500;
    }

    .create-new-supplier:hover {
        background: #c8e6c9;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        font-weight: 500;
        color: #555;
        margin-bottom: 5px;
    }

    .invalid-feedback {
        display: none;
        color: #dc3545;
        font-size: 13px;
        margin-top: 5px;
    }

    .form-group.error .form-control {
        border-color: #dc3545;
    }

    .form-group.error .invalid-feedback {
        display: block;
    }

    .alert {
        border-radius: 8px;
        padding: 15px 20px;
        margin-top: 15px;
    }

    .alert-success {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
    }

    .alert-success i {
        margin-right: 8px;
    }

    .modal-xl {
        max-width: 900px;
    }

    .modal-footer .btn {
        padding: 10px 25px;
    }

    .text-muted {
        color: #6c757d !important;
        font-size: 12px;
    }

    .items-table input[type="text"],
    .items-table input[type="number"] {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .items-table input[type="text"]:focus,
    .items-table input[type="number"]:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
    }
</style>

<script>
    let poTable;
    let editMode = false;
    let poItems = [];

   $(document).ready(function() {
    
    // ✅ 1. DataTable init
    poTable = $('#poTable').DataTable({
        ajax: {
            url: '../api/buy_side.php?module=purchase_order',
            type: 'POST',
            data: function(d) {
                d.action = 'list';
            }
        },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 }
        ]
    });

    // ✅ 2. 👉 ADD THIS HERE (IMPORTANT)
    $('#newItemQty, #newItemRate').on('input', function () {
        calculateNewItemAmount();
    });

    // ✅ 3. Add button click
    $('#addItemBtn').on('click', function(e) {
        e.preventDefault();
        addItemToList();
    });

        // Supplier search with create new option
        $('#supplierSearch').on('input', debounce(function() {
            const search = this.value.trim();
            if (search.length < 2) {
                $('#supplierSuggestions').removeClass('show');
                return;
            }

           $.getJSON('../api/suppliers.php?search=' + encodeURIComponent(search), function(data) {
                let html = '';

                if (data.suppliers && data.suppliers.length > 0) {
                    html += data.suppliers.map(s =>
                        '<div class="suggestion-item" onclick="selectSupplier(' + s.id + ', \'' + escapeHtml(s.name) + '\')">' +
                        '<strong>' + escapeHtml(s.name) + '</strong>' +
                        (s.contact_person ? '<br><small class="text-muted">' + escapeHtml(s.contact_person) + '</small>' : '') +
                        '</div>'
                    ).join('');
                }

                // Add "Create new" option
                html += '<div class="suggestion-item create-new-supplier" onclick="createNewSupplier(\'' + escapeHtml(search) + '\')">' +
                    '<i class="fas fa-plus-circle"></i> Create new supplier: <strong>' + escapeHtml(search) + '</strong></div>';

                $('#supplierSuggestions').html(html);
                $('#supplierSuggestions').addClass('show');
            });
        }, 300));

        // Close suggestions on click outside
        $(document).on('click', function(e) {
            if (!e.target.closest('.supplier-input-wrapper')) {
                $('#supplierSuggestions').removeClass('show');
            }
        });

      

       


        // Add item button click
        $(document).on('click', '#addItemBtn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            addItemToList();
        });

        // Form submit
        $('#poForm').on('submit', function(e) {
            e.preventDefault();
            savePO();
        });
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function calculateNewItemAmount() {
    const qty = parseFloat($('#newItemQty').val()) || 0;
    const rate = parseFloat($('#newItemRate').val()) || 0;

    const amount = qty * rate;

    $('#newItemAmount').val(amount.toFixed(2));
}


    function selectSupplier(id, name) {
        $('#supplierId').val(id);
        $('#supplierSearch').val(name);
        $('#supplierSuggestions').removeClass('show');
        clearFieldError('supplierSearch');
    }

    function createNewSupplier(name) {
        $.post('../api/suppliers.php', {
            action: 'create',
            name: name
        }, function(response) {
            if (response.status === 'success') {
                selectSupplier(response.supplier_id, name);
            } else {
                showFieldError('supplierSearch', response.message);
            }
        }, 'json').fail(function() {
            showFieldError('supplierSearch', 'Failed to create supplier');
        });
    }

    function addItem() {
        // Focus on the first input
        $('#newItemName').focus();
    }

    function addItemToList() {
        const itemName = $('#newItemName').val().trim();
        const unit = $('#newItemUnit').val();
        const qtyInput = $('#newItemQty').val();
        const rateInput = $('#newItemRate').val();

        // Validate Quantity is a number
        const qty = parseFloat(qtyInput);
        if (isNaN(qty) || qtyInput === '') {
            showFieldError('newItemQty', 'Please enter a valid quantity');
            $('#newItemQty').focus();
            return;
        }
        if (qty <= 0) {
            showFieldError('newItemQty', 'Please enter quantity');
            $('#newItemQty').focus();
            return;
        }

        // Validate Rate is a number
        let rate = 0;
        if (rateInput !== '') {
            rate = parseFloat(rateInput);
            if (isNaN(rate)) {
                showFieldError('newItemRate', 'Please enter a valid rate');
                $('#newItemRate').focus();
                return;
            }
        }

        const amount = qty * rate;

        // Clear previous errors
        clearFieldError('newItemName');
        clearFieldError('newItemQty');
        clearFieldError('newItemRate');

        // Validation - Item Name is required
        if (!itemName) {
            showFieldError('newItemName', 'Please enter item name');
            $('#newItemName').focus();
            return;
        }

        // Add item to array
        poItems.push({
            item_name: itemName,
            unit: unit,
            quantity: qty,
            rate: rate,
            amount: amount
        });

        // Clear input fields after adding
        $('#newItemName').val('');
        $('#newItemQty').val('');
        $('#newItemRate').val('');
        $('#newItemAmount').val('0.00');
        $('#newItemUnit').val('pieces');

        // Focus on first field for next entry
        $('#newItemName').focus();

        // Render table
        renderItemsTable();
    }

    function renderItemsTable() {
        if (poItems.length === 0) {
            $('#itemsContainer').html(`
                <tr class="no-items-row">
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        <p>No items added yet. Click "Add Item" to add items.</p>
                    </td>
                </tr>
            `);
        } else {
            $('#itemsContainer').html(poItems.map(function(item, index) {
                return `
                    <tr>
                        <td>${escapeHtml(item.item_name)}</td>
                        <td>${item.unit}</td>
                        <td>${item.quantity}</td>
                        <td>${parseFloat(item.rate).toFixed(2)}</td>
                        <td class="item-amount">${parseFloat(item.amount).toFixed(2)}</td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm btn-remove-item" onclick="removeItem(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join(''));
        }

        calcTotal();
    }

    function removeItem(index) {
        poItems.splice(index, 1);
        renderItemsTable();
    }

    function calcTotal() {
        let total = 0;
        poItems.forEach(function(item) {
            total += item.amount;
        });
        $('#poTotal').text(total.toFixed(2));
    }

    function showFieldError(fieldId, message) {
        const field = $('#' + fieldId);
        field.closest('.form-group').addClass('error');
        field.closest('.form-group').find('.invalid-feedback').text(message);
    }

    function clearFieldError(fieldId) {
        $('#' + fieldId).closest('.form-group').removeClass('error');
    }

    function validateForm() {
        let isValid = true;

        // Validate supplier (required but not as dropdown)
        const supplierName = $('#supplierSearch').val().trim();
        if (!supplierName) {
            showFieldError('supplierSearch', 'Please enter supplier name');
            isValid = false;
        } else {
            clearFieldError('supplierSearch');
        }

        // Validate items
        if (poItems.length === 0) {
            showFieldError('itemsError', 'Please add at least one item');
            $('#itemsError').text('Please add at least one item').show();
            isValid = false;
        } else {
            $('#itemsError').hide();
        }

        return isValid;
    }

    function savePO() {
        if (!validateForm()) {
            return;
        }

        const supplierName = $('#supplierSearch').val().trim();
        let supplierId = $('#supplierId').val();

        // If supplier exists but not selected, create it
        if (!supplierId && supplierName) {
            $.ajax({
                url: '../api/suppliers.php',
                type: 'POST',
                data: { action: 'create', name: supplierName },
                async: false,
                success: function(response) {
                    if (response.status === 'success') {
                        supplierId = response.supplier_id;
                    }
                }
            });
        }

        if (!supplierId) {
            showFieldError('supplierSearch', 'Please provide supplier name');
            return;
        }

        const formData = {
            action: editMode ? 'update' : 'create',
            supplier_id: supplierId,
            po_date: $('#poDate').val(),
            reference_number: $('#referenceNumber').val(),
            notes: $('#poNotes').val(),
            items: JSON.stringify(poItems),
            csrf_token: $('input[name="csrf_token"]').val()
        };

        if (editMode) formData.po_id = $('#poId').val();

        // Disable submit button
        $('#saveBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.post('../api/buy_side.php?module=purchase_order', formData, function(response) {
            $('#saveBtn').prop('disabled', false).html('<i class="fas fa-save"></i> Save Purchase Order');

            if (response.status === 'success') {
                // Show success message
                $('#successText').text('Purchase Order Saved Successfully');
                $('#successMessage').removeClass('d-none');

                setTimeout(function() {
                    closeModal('poModal');
                    poTable.ajax.reload();
                    resetForm();
                }, 1500);
            } else {
                $('#successMessage').addClass('d-none');
                alert(response.message || 'Failed to save Purchase Order');
            }
        }, 'json').fail(function(xhr, status, error) {
            $('#saveBtn').prop('disabled', false).html('<i class="fas fa-save"></i> Save Purchase Order');
            $('#successMessage').addClass('d-none');
            try {
                const response = JSON.parse(xhr.responseText);
                alert(response.message || 'Error: ' + error);
            } catch (e) {
                alert('Error: ' + error + ' - ' + xhr.statusText);
            }
        });
    }

    function editPO(id) {
        editMode = true;
        $.post('../api/buy_side.php?module=purchase_order', {
            action: 'get',
            po_id: id
        }, function(response) {
            if (response.status === 'success') {
                const po = response.data;
                $('#poId').val(po.id);
                $('#poModalTitle').text('Edit Purchase Order');
                $('#supplierId').val(po.supplier_id);
                $('#supplierSearch').val(po.supplier ? po.supplier.name : '');
                $('#poDate').val(po.order_date || '<?php echo date('Y-m-d'); ?>');
                $('#referenceNumber').val(po.reference_number || '');
                $('#poNotes').val(po.notes || '');

                // Load items
                poItems = [];
                if (po.items && po.items.length > 0) {
                    po.items.forEach(function(item) {
                        poItems.push({
                            item_name: item.item_name,
                            unit: item.unit,
                            quantity: parseFloat(item.quantity),
                            rate: parseFloat(item.rate),
                            amount: parseFloat(item.quantity) * parseFloat(item.rate)
                        });
                    });
                }
                renderItemsTable();
                openModal('poModal');
            } else {
                alert(response.message || 'Failed to load PO');
            }
        }, 'json').fail(function(xhr, status, error) {
            alert('Error loading PO: ' + error);
        });
    }

    function viewPOItems(id) {
        $.post('../api/buy_side.php?module=purchase_order', {
            action: 'get',
            po_id: id
        }, function(response) {
            if (response.status === 'success') {
                $('#viewItemsBody').html(response.data.items.map(item => `
                <tr>
                    <td>${item.item_name}</td>
                    <td>${item.quantity}</td>
                    <td>${item.unit}</td>
                    <td>${item.rate}</td>
                    <td>${item.amount}</td>
                </tr>
            `).join(''));
                openModal('viewItemsModal');
            }
        }, 'json');
    }

    function deletePO(id) {
        if (confirm('Are you sure you want to delete this PO?')) {
            $.post('../api/buy_side.php?module=purchase_order', {
                action: 'delete',
                po_id: id,
                csrf_token: $('#csrfToken').val()
            }, function(response) {
                if (response.status === 'success') {
                    poTable.ajax.reload();
                    alert(response.message);
                } else {
                    alert(response.message || 'Failed to delete');
                }
            }, 'json').fail(function(xhr, status, error) {
                alert('Error: ' + error);
            });
        }
    }

    function resetForm() {
        editMode = false;
        poItems = [];
        $('#poForm')[0].reset();
        $('#poId').val('');
        $('#supplierId').val('');
        $('#supplierSearch').val('');
        $('#poModalTitle').text('New Purchase Order');
        $('#poDate').val('<?php echo date('Y-m-d'); ?>');
        renderItemsTable();
        $('#successMessage').addClass('d-none');
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