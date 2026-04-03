// Construction ERP JavaScript - Enhanced with DataTables and AJAX

// Initialize DataTable
function initDataTable(selector, ajaxUrl, columns, order = [[0, 'desc']], extraData = {}) {
    if ($(selector).length === 0) return null;

    return $(selector).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: ajaxUrl,
            type: 'POST',
            dataSrc: 'data',
            data: function(d) {
                return $.extend({}, d, extraData);
            }
        },
        columns: columns,
        order: order,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search...",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "No entries found",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        responsive: true,
        pagingType: 'full_numbers'
    });
}

// Toggle sidebar on mobile
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('active');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
    const sidebar = document.querySelector('.sidebar');
    const menuToggle = document.querySelector('.menu-toggle');

    if (window.innerWidth <= 992) {
        if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    }
});

// Modal functions
function openModal(modalId) {
    $('#' + modalId).fadeIn(300);
    $('body').css('overflow', 'hidden');
}

function closeModal(modalId) {
    $('#' + modalId).fadeOut(200);
    $('body').css('overflow', 'auto');
}

// Close modal when clicking outside
$('.modal').on('click', function(e) {
    if (e.target === this) {
        $(this).fadeOut(200);
        $('body').css('overflow', 'auto');
    }
});

// Confirm dialog
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// Auto-hide alerts
$(document).ready(function() {
    $('.alert').delay(5000).fadeOut(500);

    // Form validation
    $('form[data-validate]').on('submit', function(e) {
        let isValid = true;
        $(this).find('input[required], select[required], textarea[required]').each(function() {
            if (!$(this).val().trim()) {
                isValid = false;
                $(this).css('border-color', '#e74c3c');
            } else {
                $(this).css('border-color', '#e0e0e0');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields');
        }
    });

    // Number input validation
    $('input[type="number"]').on('input', function() {
        if (this.value < 0) this.value = 0;
    });
});

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR'
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// AJAX helpers
function ajaxPost(url, data, callback) {
    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (callback) callback(response);
        },
        error: function(xhr, status, error) {
            alert('An error occurred: ' + error);
        }
    });
}

function showToast(message, type = 'success') {
    const toast = $(`
        <div class="alert alert-${type} alert-dismissible" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    $('body').append(toast);
    setTimeout(() => toast.fadeOut(500, () => toast.remove()), 3000);
}

// Custom DataTables initialization
$.fn.dataTable.ext.errMode = 'throw';

$(document).ready(function() {
    // Add loading spinner styles
    $('<style>.dataTables_processing { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); }</style>').appendTo('head');

    // Reinitialize tooltips
    $('[data-bs-toggle="tooltip"]').each(function() {
        new bootstrap.Tooltip(this);
    });
});

// Mobile menu toggle
$('.menu-toggle').on('click', toggleSidebar);

// Initialize DataTables with default settings
$.extend(true, $.fn.dataTable.defaults, {
    autoWidth: false,
    dom: '<"row mt-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
    language: {
        search: "_INPUT_",
        searchPlaceholder: "Search...",
        emptyTable: "No data available",
        zeroRecords: "No matching records found"
    },
    drawCallback: function() {
        // Re-add hover effects
        $('.dataTables_wrapper tr').hover(function() {
            $(this).css('cursor', 'pointer');
        });
    }
});

// Handle window resize
let resizeTimer;
$(window).on('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
        // Redraw DataTables on resize
        $.each($.fn.dataTable.tables(true), function() {
            $(this).DataTable().columns.adjust();
        });
    }, 250);
});