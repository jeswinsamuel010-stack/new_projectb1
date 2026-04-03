<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'milestones', 'create');

$page_title = 'Add Milestone';

// Get all active projects
$projects = fetchAll("SELECT id, project_name, client_name, start_date FROM projects WHERE status = 'active' ORDER BY project_name");

require_once '../includes/header';
?>

<div class="content">
    <div class="page-header">
        <h2>Add Milestone</h2>
        <a href="list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <div id="message-container"></div>

    <div class="card">
        <div class="card-body">
            <form id="milestoneForm">
                <!-- Project Selection -->
                <div class="form-group">
                    <label for="project_id">Project <span class="text-danger">*</span></label>
                    <select name="project_id" id="project_id" class="form-control" required>
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>">
                                <?php echo htmlspecialchars($project['project_name']); ?>
                                (<?php echo htmlspecialchars($project['client_name']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phase_name">Milestone Name <span class="text-danger">*</span></label>
                        <input type="text" name="phase_name" id="phase_name" class="form-control"
                               placeholder="e.g., Civil Work, Electrical Work" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="2"
                              placeholder="Describe this milestone"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="start_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" id="end_date" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>

                <!-- Execution Plan Section -->
                <div class="execution-plan-section">
                    <div class="plan-header">
                        <h4><i class="fas fa-tasks"></i> Execution Plan</h4>
                        <div class="plan-type-toggle">
                            <label class="radio-inline">
                                <input type="radio" name="plan_type" value="month" checked> Month-wise
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="plan_type" value="week"> Week-wise
                            </label>
                        </div>
                    </div>

                    <table class="table table-bordered plan-table" id="planTable">
                        <thead>
                            <tr>
                                <th style="width: 80px;">#</th>
                                <th style="width: 150px;">Type</th>
                                <th>Work Description <span class="text-danger">*</span></th>
                                <th style="width: 150px;">Expected Date</th>
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody id="planRows">
                            <!-- Dynamic rows will be added here -->
                        </tbody>
                    </table>

                    <button type="button" class="btn btn-sm btn-success" id="addPlanRow">
                        <i class="fas fa-plus"></i> Add Plan
                    </button>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Save Milestone
                    </button>
                    <a href="list.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.execution-plan-section {
    margin-top: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.plan-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.plan-header h4 {
    margin: 0;
    color: #333;
}

.plan-type-toggle {
    display: flex;
    gap: 15px;
}

.plan-type-toggle label {
    cursor: pointer;
    font-weight: 500;
}

.plan-type-toggle input[type="radio"] {
    margin-right: 5px;
}

.plan-table {
    background: white;
    margin-bottom: 10px;
}

.plan-table th {
    background: #e9ecef;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
}

.plan-table td {
    vertical-align: middle;
}

.plan-number {
    font-weight: bold;
    color: #666;
    text-align: center;
    background: #f8f9fa;
}

.work-description-input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    resize: vertical;
    min-height: 38px;
}

.plan-type-select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ced4da;
    border-radius: 4px;
}

.btn-remove-plan {
    padding: 6px 10px;
    border-radius: 4px;
}
</style>

<script>
$(document).ready(function() {
    let planCount = 0;
    let currentPlanType = 'month';

    // Add initial plan row
    addPlanRow();

    // Add plan row button
    $('#addPlanRow').on('click', function() {
        addPlanRow();
    });

    // Plan type toggle
    $('input[name="plan_type"]').on('change', function() {
        currentPlanType = $(this).val();
        updatePlanNumbers();
    });

    function addPlanRow() {
        planCount++;
        const typeLabel = currentPlanType === 'month' ? 'Month' : 'Week';

        const row = `
            <tr class="plan-row" data-plan-id="${planCount}">
                <td class="plan-number">${typeLabel} <span class="plan-num">${planCount}</span></td>
                <td>
                    <select name="plans[${planCount}][plan_type]" class="plan-type-select">
                        <option value="month" ${currentPlanType === 'month' ? 'selected' : ''}>Month</option>
                        <option value="week" ${currentPlanType === 'week' ? 'selected' : ''}>Week</option>
                    </select>
                </td>
                <td>
                    <input type="text"
                           name="plans[${planCount}][work_description]"
                           class="form-control work-description-input"
                           placeholder="Enter work description"
                           required>
                </td>
                <td>
                    <input type="date"
                           name="plans[${planCount}][expected_completion_date]"
                           class="form-control">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm btn-remove-plan" onclick="removePlanRow(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#planRows').append(row);
    }

    function updatePlanRows() {
        const typeLabel = currentPlanType === 'month' ? 'Month' : 'Week';
        $('.plan-row').each(function(index) {
            const num = index + 1;
            $(this).find('.plan-number').html(typeLabel + ' <span class="plan-num">' + num + '</span>');
            $(this).find('.plan-number .plan-num').text(num);
            $(this).find('select[name$="[plan_type]"]').val(currentPlanType);
        });
    }

    function removePlanRow(btn) {
        if ($('.plan-row').length > 1) {
            $(btn).closest('tr').remove();
            // Re-number rows
            $('.plan-row').each(function(index) {
                const num = index + 1;
                const typeLabel = currentPlanType === 'month' ? 'Month' : 'Week';
                $(this).find('.plan-number').html(typeLabel + ' <span class="plan-num">' + num + '</span>');
            });
        } else {
            $('#message-container').html('<div class="alert alert-warning">At least one plan is required</div>');
        }
    }

    // Form submission
    $('#milestoneForm').on('submit', function(e) {
        e.preventDefault();

        // Collect plans data
        const plans = [];
        $('.plan-row').each(function() {
            const $row = $(this);
            plans.push({
                plan_type: $row.find('select[name$="[plan_type]"]').val(),
                plan_number: $row.index() + 1,
                work_description: $row.find('input[name$="[work_description]"]').val(),
                expected_completion_date: $row.find('input[name$="[expected_completion_date]"]').val()
            });
        });

        // Validate at least one plan
        const validPlans = plans.filter(p => p.work_description.trim() !== '');
        if (validPlans.length === 0) {
            $('#message-container').html('<div class="alert alert-danger">Please add at least one execution plan</div>');
            return;
        }

        var formData = {
            action: 'create_milestone',
            project_id: $('#project_id').val(),
            phase_name: $('#phase_name').val(),
            description: $('#description').val(),
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            status: $('#status').val(),
            plans: JSON.stringify(validPlans)
        };

        $.ajax({
            url: 'api.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            },
            success: function(response) {
                if (response.success) {
                    $('#message-container').html('<div class="alert alert-success">' + response.message + '</div>');
                    setTimeout(function() {
                        window.location.href = 'list.php?success=' + encodeURIComponent(response.message);
                    }, 1500);
                } else {
                    $('#message-container').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#message-container').html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
            },
            complete: function() {
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save"></i> Save Milestone');
            }
        });
    });

    // Make removePlanRow available globally
    window.removePlanRow = removePlanRow;
});
</script>

<?php require_once '../includes/footer.php'; ?>