<?php
session_start();
include("config/query.php");
include("components/header.php");

$database = new Database();
$conn = $database->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';

// ==================== VIEW: assigned_pc_details ====================
$sql = "SELECT * FROM assigned_pc_details ORDER BY pc_number";
$stmt = $conn->prepare($sql);
$stmt->execute();
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
 //---------------------------
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle"></i> ' . $_SESSION['success_message'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle"></i> ' . $_SESSION['error_message'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    unset($_SESSION['error_message']);
}
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">PC Management /</span> Assigned PCs
        </h4>

        <div class="mb-3">
            <a href="view_all_pcs.php" class="btn btn-secondary">
                <i class="bx bx-desktop"></i> View All PCs
            </a>
            <?php if($userRole == 'admin'): ?>
            <a href="add_pc.php" class="btn btn-primary">
                <i class="bx bx-plus-circle"></i> Add New PC
            </a>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>PC #</th>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Batch</th>
                            <th>Department</th>
                            <th>Lab</th>
                            <th>Assigned By</th>
                            <th>Status</th>
                            <th>Student Tickets</th>
                            <th>Last Ticket</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <?php if(count($assignments) > 0): ?>
                            <?php foreach($assignments as $row): ?>
                            <tr>
                                <td><strong><?php echo $row['pc_number']; ?></strong></td>
                                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td><?php echo $row['student_email']; ?></td>
                                <td><?php echo $row['batch']; ?></td>
                                <td>
                                    <?php
                                    $deptClass = '';
                                    switch($row['department']) {
                                        case 'CS': $deptClass = 'bg-label-primary'; break;
                                        case 'SE': $deptClass = 'bg-label-success'; break;
                                        case 'AI': $deptClass = 'bg-label-info'; break;
                                        case 'DS': $deptClass = 'bg-label-warning'; break;
                                        default: $deptClass = 'bg-label-secondary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $deptClass; ?>">
                                        <?php echo $row['department']; ?>
                                    </span>
                                </td>
                                <td><?php echo $row['lab_name']; ?></td>
                                <td><?php echo $row['assigned_by_technician'] ?? 'N/A'; ?></td>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    switch($row['status']) {
                                        case 'Assigned': $statusClass = 'bg-label-success'; break;
                                        case 'Available': $statusClass = 'bg-label-primary'; break;
                                        case 'Under Maintenance': $statusClass = 'bg-label-warning'; break;
                                        case 'Retired': $statusClass = 'bg-label-danger'; break;
                                        default: $statusClass = 'bg-label-secondary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-label-info">
                                        <?php echo $row['student_ticket_count']; ?> tickets
                                    </span>
                                </td>
                                <td>
                                    <?php if($row['last_ticket_status']): ?>
                                        <span class="badge bg-label-warning">
                                            <?php echo $row['last_ticket_status']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-label-secondary">No tickets</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $row['pc_id']; ?>">
                                        <i class="bx bx-show-alt"></i> View
                                    </button>
                                </td>
                            </tr>

                            <!-- VIEW MODAL -->
                            <div class="modal fade" id="viewModal<?php echo $row['pc_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">PC Assignment Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6 class="text-primary">PC Information</h6>
                                                    <p><strong>PC Number:</strong> <?php echo $row['pc_number']; ?></p>
                                                    <p><strong>Status:</strong> <?php echo $row['status']; ?></p>
                                                    <p><strong>Lab:</strong> <?php echo $row['lab_name']; ?></p>
                                                    <p><strong>Location:</strong> <?php echo $row['location']; ?></p>
                                                    <p><strong>Assigned By:</strong> <?php echo $row['assigned_by_technician'] ?? 'N/A'; ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="text-success">Student Information</h6>
                                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($row['student_name']); ?></p>
                                                    <p><strong>Email:</strong> <?php echo $row['student_email']; ?></p>
                                                    <p><strong>Batch:</strong> <?php echo $row['batch']; ?></p>
                                                    <p><strong>Department:</strong> <?php echo $row['department']; ?></p>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6 class="text-info">Assignment Dates</h6>
                                                    <p><strong>Start Date:</strong> <?php echo $row['assignment_start'] ?? 'N/A'; ?></p>
                                                    <p><strong>End Date:</strong> <?php echo $row['assignment_end'] ?? 'N/A'; ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="text-warning">Ticket Statistics</h6>
                                                    <p><strong>Student's Total Tickets:</strong> <?php echo $row['student_ticket_count']; ?></p>
                                                    <p><strong>Last Ticket Status:</strong> <?php echo $row['last_ticket_status'] ?? 'No tickets'; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center py-4">
                                    <div class="alert alert-info mb-0">
                                        <i class="bx bx-info-circle"></i> No assigned PCs found in the system.
                                        <br>
                                        <a href="view_all_pcs.php" class="btn btn-primary mt-2">View All PCs</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Summary Card -->
        <?php if(count($assignments) > 0): ?>
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Assigned PCs</h5>
                        <h2><?php echo count($assignments); ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Avg Tickets/Student</h5>
                        <h2><?php 
                            $totalTickets = array_sum(array_column($assignments, 'student_ticket_count'));
                            $studentCount = count(array_unique(array_column($assignments, 'student_name')));
                            echo $studentCount > 0 ? round($totalTickets / $studentCount, 1) : 0;
                        ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<?php
include("components/footer.php");
?>