<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'milestones', 'list');

$page_title = 'Project Milestones';

// Get all projects for filter
$projects = fetchAll("SELECT id, project_name FROM projects WHERE status = 'active' ORDER BY project_name");

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Project Milestones</h2>
        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'project_manager'): ?>
        <a href="add_milestone.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Milestone
        </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <!-- Project Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label>Filter by Project</label>
                    <select id="projectFilter" class="form-control">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['project_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-container">
                <table id="milestonesTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Project</th>
                            <th>Milestone</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Execution Plan</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Execution Plan Modal -->
<div class="modal fade" id="planModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Execution Plan Details</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="planModalBody">
            </div>
        </div>
    </div>
</div>

<style>
.status-badge {
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}
.status-pending { background: #ffc107; color: #000; }
.status-in_progress { background: #17a2b8; color: #fff; }
.status-completed { background: #28a745; color: #fff; }

.plan-preview {
    max-height: 80px;
    overflow: hidden;
    font-size: 12px;
    color: #666;
}
.plan-preview-item {
    padding: 2px 0;
    border-bottom: 1px solid #eee;
}
.plan-preview-item:last-child {
    border-bottom: none;
}
.plan-type-badge {
    display: inline-block;
    padding: 2px 6px;
    background: #e9ecef;
    border-radius: 3px;
    font-size: 10px;
    margin-right: 5px;
}
</style>

<script>
$(document).ready(function() {
    loadMilestones();

    // Filter change
    $('#projectFilter').on('change', function() {
        loadMilestones();
    });

    function loadMilestones() {
        $.ajax({
            url: 'api.php',
            type: 'POST',
            data: {
                action: 'get_milestones',
                project_id: $('#projectFilter').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    renderTable(response.milestones);
                }
            }
        });
    }

    function renderTable(milestones) {
        const tbody = $('#milestonesTable tbody');
        tbody.empty();

        if (milestones.length === 0) {
            tbody.html('<tr><td colspan="7" class="text-center">No milestones found</td></tr>');
            return;
        }

        milestones.forEach(function(m) {
            const statusClass = 'status-' + m.status;
            const statusLabel = m.status.replace('_', ' ');

            // Build plan preview
            let planPreview = '';
            if (m.plans && m.plans.length > 0) {
                const displayPlans = m.plans.slice(0, 2);
                planPreview = displayPlans.map(function(p) {
                    const typeLabel = p.plan_type === 'month' ? 'M' : 'W';
                    return '<div class="plan-preview-item">' +
                           '<span class="plan-type-badge">' + typeLabel + p.plan_number + '</span>' +
                           p.work_description.substring(0, 30) + (p.work_description.length > 30 ? '...' : '') +
                           '</div>';
                }).join('');
                if (m.plans.length > 2) {
                    planPreview += '<div class="plan-preview-item text-muted">+' + (m.plans.length - 2) + ' more plans</div>';
                }
            } else {
                // planPreview = '<span class="text-muted">No plans</span>';
            }

            const row = `
                <tr>
                    <td>${m.id}</td>
                    <td>${m.project_name || '-'}</td>
                    <td>
                        <strong>${m.phase_name}</strong>
                        ${m.description ? '<br><small class="text-muted">' + m.description.substring(0, 50) + '</small>' : ''}
                    </td>
                    <td>${m.start_date} to ${m.end_date}</td>
                    <td><span class="status-badge ${statusClass}">${statusLabel}</span></td>
                    <td>
                        <div class="plan-preview">${planPreview}</div>
                        <button class="btn btn-sm btn-link" onclick="viewPlans(${m.id})">View All</button>
                    </td>
                    <td>
                        <a href="edit_milestone.php?id=${m.id}" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-sm btn-danger" onclick="deleteMilestone(${m.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // View all plans
    window.viewPlans = function(milestoneId) {
        $.ajax({
            url: 'api.php',
            type: 'POST',
            data: { action: 'get_milestone', id: milestoneId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    currentMilestoneId = response.milestone.id;
                    $('#planModalBody').data('currentMilestoneId', response.milestone.id);
                    renderPlanModal(response.milestone, response.plans);
                }
            }
        });
    };

    // function renderPlanModal(milestone, plans) {
    //     let html = `
    //         <h5>${milestone.phase_name}</h5>
    //         <p class="text-muted">${milestone.description || ''}</p>
    //         <table class="table table-bordered table-sm">
    //             <thead>
    //                 <tr>
    //                     <th>#</th>
    //                     <th>Type</th>
    //                     <th>Work Description</th>
    //                     <th>Expected Date</th>
    //                     <th>Status</th>
    //                 </tr>
    //             </thead>
    //             <tbody>
    //     `;

    //     if (plans.length === 0) {
    //         html += '<tr><td colspan="5" class="text-center">No plans defined</td></tr>';
    //     } else {
    //         plans.forEach(function(p) {
    //             const typeLabel = p.plan_type === 'month' ? 'Month' : 'Week';
    //             html += `
    //                 <tr>
    //                     <td>${p.plan_number}</td>
    //                     <td>${typeLabel} ${p.plan_number}</td>
    //                     <td>${p.work_description}</td>
    //                     <td>${p.expected_completion_date || '-'}</td>
    //                     <td><span class="status-badge status-${p.status}">${p.status}</span></td>
    //                 </tr>
    //             `;
    //         });
    //     }

    //     html += '</tbody></table>';
    //     $('#planModalBody').html(html);
    //     $('#planModal').modal('show');
    // }

    function renderPlanModal(milestone, plans) {
    let monthlyPlans = plans.filter(p => p.plan_type === 'month');
    let weeklyPlans = plans.filter(p => p.plan_type === 'week');

    function getStatusBadge(status) {
        let cls = 'secondary';
        if (status === 'pending') cls = 'warning';
        if (status === 'in_progress') cls = 'info';
        if (status === 'completed') cls = 'success';

        return `<span class="badge badge-${cls} text-uppercase">${status.replace('_',' ')}</span>`;
    }

    function renderPlanSection(title, plansList) {
        if (plansList.length === 0) {
            return `
                <div class="mb-3">
                    <h6>${title}</h6>
                    <div class="text-muted">No plans available</div>
                </div>
            `;
        }

        let html = `<div class="mb-4">
                        <h5 class="mb-3">${title}</h5>
                        <div class="row">`;

        plansList.forEach(p => {
            html += `
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0">
                                    ${p.plan_type === 'month' ? 'Month' : 'Week'} ${p.plan_number}
                                </h6>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editPlan(${p.id})" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deletePlan(${p.id})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>

                            <p class="mb-2">
                                <strong>Description:</strong><br>
                                ${p.work_description}
                            </p>

                            <p class="mb-1">
                                <strong>Expected Date:</strong>
                                ${p.expected_completion_date || '-'}
                            </p>

                            <p class="mb-0">
                                <strong>Status:</strong>
                                ${getStatusBadge(p.status)}
                            </p>
                        </div>
                    </div>
                </div>
            `;
        });

        html += `</div></div>`;
        return html;
    }

    let html = `
        <div>
            <h4 class="mb-1">${milestone.phase_name}</h4>
            <p class="text-muted">${milestone.description || ''}</p>
            <hr>
            
            ${renderPlanSection('📅 Monthly Plans', monthlyPlans)}
            ${renderPlanSection('🗓 Weekly Plans', weeklyPlans)}
        </div>
    `;

    $('#planModalBody').html(html);
    $('#planModal').modal('show');
}

    // Delete individual plan
    window.deletePlan = function(planId) {
        if (confirm('Are you sure you want to delete this execution work plan?')) {
            $.ajax({
                url: 'api.php',
                type: 'POST',
                data: { action: 'delete_plan', id: planId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Refresh the modal - get current milestone ID from the modal
                        const milestoneId = $('#planModalBody').data('milestoneId') ||
                                           new URLSearchParams(window.location.search).get('id');
                        // Reload milestones table and close modal
                        loadMilestones();
                        $('#planModal').modal('hide');
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        }
    };

    // Edit individual plan - open edit modal
    window.editPlan = function(planId) {
        // Get plan data from the current modal
        $.ajax({
            url: 'api.php',
            type: 'POST',
            data: { action: 'get_milestone', id: $('#planModalBody').data('currentMilestoneId') },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const plan = response.plans.find(p => p.id === planId);
                    if (plan) {
                        showEditPlanModal(plan);
                    }
                }
            }
        });
    };

    function showEditPlanModal(plan) {
        const statusOptions = [
            { value: 'pending', label: 'Pending' },
            { value: 'in_progress', label: 'In Progress' },
            { value: 'completed', label: 'Completed' }
        ];

        let statusHtml = statusOptions.map(s =>
            `<option value="${s.value}" ${s.value === plan.status ? 'selected' : ''}>${s.label}</option>`
        ).join('');

        let html = `
            <div class="modal fade" id="editPlanModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Execution Work Plan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="editPlanId" value="${plan.id}">
                            <div class="mb-3">
                                <label class="form-label">Plan Type</label>
                                <input type="text" class="form-control" value="${plan.plan_type === 'month' ? 'Month' : 'Week'} ${plan.plan_number}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Work Description *</label>
                                <textarea class="form-control" id="editWorkDescription" rows="3" required>${plan.work_description}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Expected Completion Date</label>
                                <input type="date" class="form-control" id="editExpectedDate" value="${plan.expected_completion_date || ''}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-control" id="editStatus">
                                    ${statusHtml}
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="savePlan()">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove any existing edit modal
        $('#editPlanModal').remove();
        $('body').append(html);

        const editModal = new bootstrap.Modal(document.getElementById('editPlanModal'));
        editModal.show();
    }

    window.savePlan = function() {
        const planId = $('#editPlanId').val();
        const workDescription = $('#editWorkDescription').val();
        const expectedDate = $('#editExpectedDate').val();
        const status = $('#editStatus').val();

        if (!workDescription.trim()) {
            alert('Please enter work description');
            return;
        }

        $.ajax({
            url: 'api.php',
            type: 'POST',
            data: {
                action: 'update_plan',
                id: planId,
                work_description: workDescription,
                expected_completion_date: expectedDate,
                status: status
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editPlanModal')).hide();
                    // Refresh the view
                    loadMilestones();
                    $('#planModal').modal('hide');
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    };

    // Store current milestone ID for reference
    let currentMilestoneId = null;

    // Delete milestone
    window.deleteMilestone = function(id) {
        if (confirm('Are you sure you want to delete this milestone?')) {
            $.ajax({
                url: 'api.php',
                type: 'POST',
                data: { action: 'delete_milestone', id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        loadMilestones();
                    } else {
                        alert(response.message);
                    }
                }
            });
        }
    };
});
</script>

<?php require_once '../includes/footer.php'; ?>