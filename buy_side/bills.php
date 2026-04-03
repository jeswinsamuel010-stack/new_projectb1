<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'bills', 'list');

$page_title = 'Bills';

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2><i class="fas fa-file-invoice-dollar"></i> Bills</h2>
        <button class="btn btn-primary" onclick="openModal('billModal')">
            <i class="fas fa-plus"></i> New Bill
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="billTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>Bill Number</th>
                        <th>Supplier</th>
                        <th>Total Amount</th>
                        <th>Pending</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- View Bill Modal -->
<div id="viewBillModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>Bill Details</h3>
            <button class="modal-close" onclick="closeModal('viewBillModal')">&times;</button>
        </div>
        <div class="modal-body" id="viewBillBody">
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Payment</h3>
            <button class="modal-close" onclick="closeModal('paymentModal')">&times;</button>
        </div>
        <form id="paymentForm">
            <div class="modal-body">
                <input type="hidden" name="bill_id" id="paymentBillId">
                <div class="form-group">
                    <label>Bill Amount</label>
                    <p class="form-control-static" id="billAmount"></p>
                </div>
                <div class="form-group">
                    <label>Pending Amount</label>
                    <p class="form-control-static text-danger" id="pendingAmount"></p>
                </div>
                <div class="form-group">
                    <label>Payment Amount *</label>
                    <input type="number" name="amount" id="paymentAmount" class="form-control" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" class="form-control">
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash</option>
                        <option value="cheque">Cheque</option>
                        <option value="upi">UPI</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reference Number</label>
                    <input type="text" name="reference_number" class="form-control">
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeModal('paymentModal')">Cancel</button>
                <button type="submit" class="btn btn-success">Record Payment</button>
            </div>
        </form>
    </div>
</div>

<!-- New Bill Modal -->
<div id="billModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3>New Bill</h3>
            <button class="modal-close" onclick="closeModal('billModal')">&times;</button>
        </div>
        <form id="billForm">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Supplier *</label>
                            <select name="supplier_id" id="billSupplierId" class="form-control" required>
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
                            <label>Due Date</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>

                <h5>Items</h5>
                <table class="table table-bordered" id="billItemsTable">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Rate</th>
                            <th>Amount</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="billItemsBody">
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-secondary" onclick="addBillItem()">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeModal('billModal')">Cancel</button>
                <button type="submit" class="btn btn-success">Create Bill</button>
            </div>
        </form>
    </div>
</div>

<script>
let billTable;

$(document).ready(function() {
    billTable = $('#billTable').DataTable({
        ajax: {
            url: '../api/buy_side.php?module=bill',
            type: 'POST',
            data: function(d) { d.action = 'list'; }
        },
        columns: [
            { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 }, { data: 5 }, { data: 6 }
        ]
    });

    // Bill form
    $('#billForm').on('submit', function(e) {
        e.preventDefault();
        const items = [];
        $('#billItemsBody tr').each(function() {
            const row = $(this);
            items.push({
                item_name: row.find('.item-name').val(),
                quantity: parseFloat(row.find('.item-qty').val()) || 0,
                unit: row.find('.item-unit').val(),
                rate: parseFloat(row.find('.item-rate').val()) || 0
            });
        });

        if (items.length === 0) { alert('Please add at least one item'); return; }

        $.post('../api/buy_side.php?module=bill', {
            action: 'create',
            supplier_id: $('#billSupplierId').val(),
            due_date: $('input[name="due_date"]').val(),
            notes: $('textarea[name="notes"]').val(),
            items: JSON.stringify(items)
        }, function(response) {
            if (response.success) {
                closeModal('billModal');
                billTable.ajax.reload();
                alert(response.message);
            } else {
                alert(response.message);
            }
        }, 'json');
    });

    // Payment form
    $('#paymentForm').on('submit', function(e) {
        e.preventDefault();
        $.post('../api/buy_side.php?module=payment', {
            action: 'create',
            bill_id: $('#paymentBillId').val(),
            amount: $('#paymentAmount').val(),
            payment_method: $('select[name="payment_method"]').val(),
            reference_number: $('input[name="reference_number"]').val(),
            notes: $('textarea[name="notes"]').val()
        }, function(response) {
            if (response.success) {
                closeModal('paymentModal');
                billTable.ajax.reload();
                alert(response.message);
            } else {
                alert(response.message);
            }
        }, 'json');
    });

    addBillItem();
});

function addBillItem() {
    const html = `
        <tr>
            <td><input type="text" class="form-control form-control-sm item-name" placeholder="Item name"></td>
            <td><input type="number" class="form-control form-control-sm item-qty" step="0.01" placeholder="Qty" onchange="calcBillRow(this)"></td>
            <td><select class="form-control form-control-sm item-unit">
                <option value="pieces">Pieces</option>
                <option value="bags">Bags</option>
                <option value="kg">Kg</option>
                <option value="ton">Ton</option>
                <option value="cu.mt">Cu.mt</option>
            </select></td>
            <td><input type="number" class="form-control form-control-sm item-rate" step="0.01" placeholder="Rate" onchange="calcBillRow(this)"></td>
            <td><input type="text" class="form-control form-control-sm item-amount" readonly></td>
            <td><button type="button" class="btn btn-sm btn-danger" onclick="$(this).closest('tr').remove()"><i class="fas fa-times"></i></button></td>
        </tr>
    `;
    $('#billItemsBody').append(html);
}

function calcBillRow(input) {
    const row = $(input).closest('tr');
    const qty = parseFloat(row.find('.item-qty').val()) || 0;
    const rate = parseFloat(row.find('.item-rate').val()) || 0;
    row.find('.item-amount').val((qty * rate).toFixed(2));
}

function viewBill(id) {
    $.post('../api/buy_side.php?module=bill', { action: 'get', bill_id: id }, function(response) {
        if (response.success) {
            const b = response.data;
            let paymentsHtml = '';
            if (b.payments && b.payments.length > 0) {
                paymentsHtml = `
                    <h5 class="mt-3">Payments</h5>
                    <table class="table table-sm table-striped">
                        <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Ref</th></tr></thead>
                        <tbody>${b.payments.map(p => `<tr><td>${p.payment_date}</td><td>${p.amount}</td><td>${p.payment_method}</td><td>${p.reference_number || '-'}</td></tr>`).join('')}</tbody>
                    </table>
                `;
            }

            $('#viewBillBody').html(`
                <table class="table table-bordered">
                    <tr><th>Bill Number</th><td>${b.bill_number}</td></tr>
                    <tr><th>Supplier</th><td>${b.supplier_name}</td></tr>
                    <tr><th>Total Amount</th><td>${b.total_amount}</td></tr>
                    <tr><th>Paid Amount</th><td>${b.paid_amount}</td></tr>
                    <tr><th>Pending</th><td class="text-danger">${b.pending_amount}</td></tr>
                    <tr><th>Date</th><td>${b.bill_date}</td></tr>
                    <tr><th>Status</th><td>${b.status}</td></tr>
                </table>
                <h5>Items</h5>
                <table class="table table-striped">
                    <thead><tr><th>Item</th><th>Qty</th><th>Unit</th><th>Rate</th><th>Amount</th></tr></thead>
                    <tbody>${b.items.map(i => `<tr><td>${i.item_name}</td><td>${i.quantity}</td><td>${i.unit}</td><td>${i.rate}</td><td>${i.amount}</td></tr>`).join('')}</tbody>
                </table>
                ${paymentsHtml}
            `);
            openModal('viewBillModal');
        }
    }, 'json');
}

function addPayment(id, pending) {
    $('#paymentBillId').val(id);
    $('#pendingAmount').text(pending.toFixed(2));
    $('#paymentAmount').val(pending.toFixed(2));
    $('#paymentAmount').max = pending;
    openModal('paymentModal');
}
</script>

<?php require_once '../includes/footer.php'; ?>