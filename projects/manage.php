<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

checkAccess(null, 'projects', 'manage');

$page_title = 'Project Management';
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Get all active projects
$projects = fetchAll("SELECT id, project_name, client_name, start_date, status FROM projects WHERE status = 'active' ORDER BY project_name");

require_once '../includes/header.php';
?>

<div class="content">
    <div class="page-header">
        <h2>Project Management</h2>
    </div>

    <div id="message-container"></div>

    <!-- Project Selection -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="font-weight-bold">Select Project <span class="text-danger">*</span></label>
                    <select id="projectSelect" class="form-control">
                        <option value="">-- Select a Project --</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>">
                                <?php echo htmlspecialchars($p['project_name']); ?>
                                (<?php echo htmlspecialchars($p['client_name']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Information Card -->
    <div id="projectInfo" class="card mb-3" style="display: none;">
        <div class="card-header bg-primary text-black">
            <h5 class="mb-0"><i class="fas fa-building"></i> Project Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label class="text-muted">Client Name</label>
                    <p class="font-weight-bold" id="displayClientName">-</p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted">Project Name</label>
                    <p class="font-weight-bold" id="displayProjectName">-</p>
                </div>
                <div class="col-md-2">
                    <label class="text-muted">Start Date</label>
                    <p id="displayStartDate">-</p>
                </div>
                <div class="col-md-2">
                    <label class="text-muted">End Date</label>
                    <p id="displayEndDate">-</p>
                </div>
                <div class="col-md-2">
                    <label class="text-muted">Status</label>
                    <p><span class="badge badge-success" id="displayStatus">Active</span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div id="progressSection" class="card mb-3" style="display: none;">
        <div class="card-body">
            <label class="font-weight-bold">Overall Progress</label>
            <div class="progress" style="height: 25px;">
                <div id="progressBar" class="progress-bar bg-success" role="progressbar" style="width: 0%;">
                    <span id="progressText">0%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Milestones Section -->
    <div id="milestonesSection" style="display: none;">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-tasks"></i> Milestones</h5>
                <?php if ($user_role == 'admin' || $user_role == 'project_manager'): ?>
                <button class="btn btn-primary btn-sm" id="addMilestoneBtn">
                    <i class="fas fa-plus"></i> Add Milestone
                </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <!-- Accordion View -->
                <div id="milestonesAccordion">
                    <!-- Milestones will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Milestone Modal -->
<div class="modal fade" id="milestoneModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="milestoneModalTitle">Add Milestone</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="milestoneForm">
                <div class="modal-body">
                    <input type="hidden" id="milestoneId" value="">
                    <input type="hidden" id="milestoneProjectId" value="">

                    <div class="form-group">
                        <label>Type <span class="text-danger">*</span></label>
                        <select id="milestoneType" class="form-control" required>
                            <option value="month">Month</option>
                            <option value="week">Week</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Work Title <span class="text-danger">*</span></label>
                        <input type="text" id="milestoneTitle" class="form-control" placeholder="e.g., Civil Work, Electrical Work" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="milestoneDescription" class="form-control" rows="2" placeholder="Optional description"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Date <span class="text-danger">*</span></label>
                                <input type="date" id="milestoneStartDate" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>End Date <span class="text-danger">*</span></label>
                                <input type="date" id="milestoneEndDate" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select id="milestoneStatus" class="form-control">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="milestoneSubmitBtn">Save</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Work Status Modal (For Site Engineer) -->
<div class="modal fade" id="updateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Work Status</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="updateForm">
                <div class="modal-body">
                    <input type="hidden" id="updateMilestoneId" value="">

                    <div class="form-group">
                        <label>Status <span class="text-danger">*</span></label>
                        <select id="updateStatus" class="form-control" required>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea id="updateRemarks" class="form-control" rows="3" placeholder="Add work remarks..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Completion Date</label>
                        <input type="date" id="updateCompletionDate" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="updateSubmitBtn">Update</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.status-pending { background-color: #dc3545 !important; }
.status-in_progress { background-color: #ffc107 !important; color: #000 !important; }
.status-completed { background-color: #28a745 !important; }

.milestone-card {
    margin-bottom: 10px;
    border: 1px solid #dee2e6;
}

.milestone-card .card-header {
    padding: 10px 15px;
    background: #f8f9fa;
    cursor: pointer;
}

.milestone-card .card-header:hover {
    background: #e9ecef;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-pending { background: #dc3545; color: white; }
.badge-in_progress { background: #ffc107; color: #000; }
.badge-completed { background: #28a745; color: white; }

.update-history {
    max-height: 200px;
    overflow-y: auto;
}

.update-item {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.update-item:last-child {
    border-bottom: none;
}

.update-time {
    font-size: 11px;
    color: #999;
}
</style>

<script>
let currentProjectId = null;
let userRole = '<?php echo $user_role; ?>';

$(document).ready(function() {
    // Project select change
    $('#projectSelect').on('change', function() {
        currentProjectId = $(this).val();
        if (currentProjectId) {
            loadProjectInfo(currentProjectId);
            loadMilestones(currentProjectId);
            $('#projectInfo, #progressSection, #milestonesSection').show();
        } else {
            $('#projectInfo, #progressSection, #milestonesSection').hide();
        }
    });

    // Add milestone button
    $('#addMilestoneBtn').on('click', function() {
        resetMilestoneForm();
        $('#milestoneModalTitle').text('Add Milestone');
        $('#milestoneProjectId').val(currentProjectId);
        $('#milestoneModal').modal('show');
    });

    // Save milestone
    $('#milestoneForm').on('submit', function(e) {
        e.preventDefault();
        saveMilestone();
    });

    // Update work status
    $('#updateForm').on('submit', function(e) {
        e.preventDefault();
        updateMilestoneStatus();
    });
});

function loadProjectInfo(projectId) {
    console.log('Loading project info for ID:', projectId);

    $.ajax({
        url: '/new_projectb/api/projects.php',
        type: 'POST',
        data: { action: 'get_project', id: projectId },
        dataType: 'json',
        success: function(response) {
            console.log('Project response:', response);
            if (response.success && response.project) {
                const project = response.project;
                $('#displayClientName').text(project.client_name || '');
                $('#displayProjectName').text(project.project_name || '');
                $('#displayStartDate').text(project.start_date || '-');
                $('#displayStatus').text(project.status || 'active');
            } else {
                $('#message-container').html('<div class="alert alert-danger">Error: ' + (response.message || 'No data') + '</div>');
                console.log('No project data:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Project load error:', status, error, xhr.responseText);
            $('#message-container').html('<div class="alert alert-danger">AJAX Error: ' + error + '</div>');
        }
    });
}

function loadMilestones(projectId) {
    console.log('Loading milestones for project ID:', projectId);
    $.ajax({
        url: '/new_projectb/milestones/api.php',
        type: 'POST',
        data: { action: 'get_milestones', project_id: projectId },
        dataType: 'json',
        success: function(response) {
            console.log('Milestones response:', response);
            if (response.success && response.milestones) {
                renderMilestones(response.milestones);
                updateProgressBar(response.milestones);
            } else {
                console.log('No milestones:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Milestones load error:', status, error, xhr.responseText);
        }
    });
}

function renderMilestones(milestones) {
    const accordion = $('#milestonesAccordion');
    accordion.empty();

    if (milestones.length === 0) {
        accordion.html('<p class="text-center text-muted">No milestones found. Add one to get started.</p>');
        return;
    }

    milestones.forEach(function(m, index) {
        const typeLabel = m.plan_type === 'month' ? 'Month' : 'Week';
        const statusClass = 'badge-' + m.status;
        const isPM = userRole === 'admin' || userRole === 'project_manager';

        // Get latest update if any
        let updateHtml = '';
        if (m.latest_update) {
            updateHtml = `
                <div class="mt-2 p-2 bg-light rounded">
                    <small><strong>Latest Update:</strong> ${m.latest_update.remarks || 'No remarks'}</small><br>
                    <small class="text-muted">By: ${m.latest_update.updated_by_name} on ${m.latest_update.created_at}</small>
                </div>
            `;
        }

        const card = `
            <div class="card milestone-card">
                <div class="card-header d-flex justify-content-between align-items-center" data-toggle="collapse" data-target="#collapse${m.id}">
                    <div>
                        <span class="badge badge-secondary mr-2">${typeLabel} ${m.plan_number || index + 1}</span>
                        <strong>${m.phase_name}</strong>
                    </div>
                    <div>
                        <span class="status-badge ${statusClass}">${m.status.replace('_', ' ')}</span>
                        ${isPM ? `
                        <button class="btn btn-sm btn-primary ml-2" onclick="editMilestone(${m.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger ml-1" onclick="deleteMilestone(${m.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                        ` : ''}
                        ${userRole === 'site_engineer' ? `
                        <button class="btn btn-sm btn-success ml-2" onclick="openUpdateModal(${m.id}, '${m.status}')">
                            <i class="fas fa-sync"></i> Update
                        </button>
                        ` : ''}
                    </div>
                </div>
                <div id="collapse${m.id}" class="collapse">
                    <div class="card-body">
                        <p><strong>Description:</strong> ${m.description || 'No description'}</p>
                        <p><strong>Duration:</strong> ${m.start_date} to ${m.end_date}</p>
                        ${updateHtml}
                    </div>
                </div>
            </div>
        `;
        accordion.append(card);
    });
}

function updateProgressBar(milestones) {
    if (milestones.length === 0) {
        $('#progressBar').css('width', '0%');
        $('#progressText').text('0%');
        return;
    }

    const completed = milestones.filter(m => m.status === 'completed').length;
    const percentage = Math.round((completed / milestones.length) * 100);

    $('#progressBar').css('width', percentage + '%');
    $('#progressText').text(percentage + '%');

    if (percentage < 30) {
        $('#progressBar').removeClass('bg-success bg-warning').addClass('bg-danger');
    } else if (percentage < 70) {
        $('#progressBar').removeClass('bg-success bg-danger').addClass('bg-warning');
    } else {
        $('#progressBar').removeClass('bg-warning bg-danger').addClass('bg-success');
    }
}

function resetMilestoneForm() {
    $('#milestoneId').val('');
    $('#milestoneTitle').val('');
    $('#milestoneDescription').val('');
    $('#milestoneStartDate').val('');
    $('#milestoneEndDate').val('');
    $('#milestoneStatus').val('pending');
    $('#milestoneType').val('month');
}

function editMilestone(id) {
    $.ajax({
        url: '/new_projectb/milestones/api.php',
        type: 'POST',
        data: { action: 'get_milestone', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const m = response.milestone;
                $('#milestoneId').val(m.id);
                $('#milestoneProjectId').val(m.project_id);
                $('#milestoneTitle').val(m.phase_name);
                $('#milestoneDescription').val(m.description);
                $('#milestoneStartDate').val(m.start_date);
                $('#milestoneEndDate').val(m.end_date);
                $('#milestoneStatus').val(m.status);
                $('#milestoneType').val(m.plan_type || 'month');
                $('#milestoneModalTitle').text('Edit Milestone');
                $('#milestoneModal').modal('show');
            }
        }
    });
}

function saveMilestone() {
    const id = $('#milestoneId').val();
    const action = id ? 'update_milestone' : 'create_milestone';

    const formData = {
        action: action,
        id: id,
        project_id: $('#milestoneProjectId').val(),
        phase_name: $('#milestoneTitle').val(),
        description: $('#milestoneDescription').val(),
        start_date: $('#milestoneStartDate').val(),
        end_date: $('#milestoneEndDate').val(),
        status: $('#milestoneStatus').val(),
        plan_type: $('#milestoneType').val(),
        plan_number: 1
    };

    console.log('Saving milestone:', formData);

    $.ajax({
        url: '/new_projectb/milestones/api.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function() {
            $('#milestoneSubmitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        },
        success: function(response) {
            console.log('Save response:', response);
            if (response.success) {
                $('#milestoneModal').modal('hide');
                showMessage('success', response.message);
                console.log('Reloading milestones for project:', currentProjectId);
                loadMilestones(currentProjectId);
            } else {
                showMessage('danger', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Save error:', status, error);
            showMessage('danger', 'An error occurred: ' + error);
        },
        complete: function() {
            $('#milestoneSubmitBtn').prop('disabled', false).html('Save');
        }
    });
}

function deleteMilestone(id) {
    if (confirm('Are you sure you want to delete this milestone?')) {
        $.ajax({
            url: '/new_projectb/milestones/api.php',
            type: 'POST',
            data: { action: 'delete_milestone', id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.message);
                    loadMilestones(currentProjectId);
                } else {
                    showMessage('danger', response.message);
                }
            }
        });
    }
}

function openUpdateModal(milestoneId, currentStatus) {
    $('#updateMilestoneId').val(milestoneId);
    $('#updateStatus').val(currentStatus);
    $('#updateRemarks').val('');
    $('#updateCompletionDate').val('');
    $('#updateModal').modal('show');
}

function updateMilestoneStatus() {
    $.ajax({
        url: '/new_projectb/api/projects.php',
        type: 'POST',
        data: {
            action: 'update_milestone_status',
            milestone_id: $('#updateMilestoneId').val(),
            status: $('#updateStatus').val(),
            remarks: $('#updateRemarks').val(),
            completion_date: $('#updateCompletionDate').val(),
            user_id: <?php echo $user_id; ?>
        },
        dataType: 'json',
        beforeSend: function() {
            $('#updateSubmitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
        },
        success: function(response) {
            if (response.success) {
                $('#updateModal').modal('hide');
                showMessage('success', response.message);
                loadMilestones(currentProjectId);
            } else {
                showMessage('danger', response.message);
            }
        },
        error: function() {
            showMessage('danger', 'An error occurred');
        },
        complete: function() {
            $('#updateSubmitBtn').prop('disabled', false).html('Update');
        }
    });
}

function showMessage(type, message) {
    $('#message-container').html('<div class="alert alert-' + type + '">' + message + '</div>');
    setTimeout(function() {
        $('#message-container').fadeOut();
    }, 3000);
}
</script>

<?php require_once '../includes/footer.php'; ?>