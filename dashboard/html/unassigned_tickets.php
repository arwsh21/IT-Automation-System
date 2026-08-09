<?php
session_start();
include("config/query.php");
include("components/header.php");

$database = new Database();
$conn = $database->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

$tickets = [];
$error = '';

// ==================== cry araha hey ====================
if (isset($_POST['assign_id']) && isset($_POST['technician_id'])) {
    $ticket_id = $_POST['assign_id'];
    $technician_id = $_POST['technician_id'];
    
    try {
        $conn->beginTransaction();
        
        $updateQuery = "UPDATE ticket SET assigned_to = :tech_id WHERE ticket_id = :id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindParam(':tech_id', $technician_id);
        $updateStmt->bindParam(':id', $ticket_id);
        $updateStmt->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "Ticket #$ticket_id assigned successfully!";
        
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
    echo "<script>location.assign('unassigned_tickets.php')</script>";
    exit();
}

// ==================== call stored procedure : GetUnassignedTickets====================
try {
    $stmt = $conn->prepare("CALL GetUnassignedTickets()");
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// ========================================
$technicians = [];
$techSql = "SELECT tech_id, name, shift FROM it_technician ORDER BY name";
$techStmt = $conn->prepare($techSql);
$techStmt->execute();
$technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC);

// ========================================
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
            <span class="text-muted fw-light">Tickets /</span> Unassigned Tickets
        </h4>

        <!-- Summary Card -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Unassigned Tickets</h5>
                        <h2><?php echo count($tickets); ?></h2>
                        <small>Awaiting technician assignment</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Available Technicians</h5>
                        <h2><?php echo count($technicians); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">Oldest Ticket</h5>
                        <h2>
                            <?php 
                            if(count($tickets) > 0) {
                                $maxDays = max(array_column($tickets, 'days_old'));
                                echo $maxDays . ' days';
                            } else {
                                echo '0';
                            }
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Unassigned Tickets Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Tickets Without Assigned Technician</h5>
                <small>Tickets are sorted by oldest first</small>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Ticket ID</th>
                            <th>Category</th>
                            <th>Student</th>
                            <th>Department</th>
                            <th>PC</th>
                            <th>Lab</th>
                            <th>Created</th>
                            <th>Days Old</th>
                            <th>Updates</th>
                            <th>Description</th>
                            <th>Assign</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($tickets) > 0): ?>
                            <?php foreach($tickets as $ticket): ?>
                            <tr>
                                <td>#<?php echo $ticket['ticket_id']; ?></td>
                                <td>
                                    <span class="badge bg-label-<?php 
                                        echo $ticket['category'] == 'Hardware' ? 'danger' : 
                                            ($ticket['category'] == 'Software' ? 'info' : 'warning'); 
                                    ?>">
                                        <?php echo $ticket['category']; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($ticket['student_name']); ?></strong>
                                    <br>
                                    <small><?php echo $ticket['student_email']; ?></small>
                                </td>
                                <td><?php echo $ticket['department']; ?> <br> <small><?php echo $ticket['batch']; ?></small></td>
                                <td><?php echo $ticket['pc_number'] ?? 'N/A'; ?></td>
                                <td><?php echo $ticket['lab_name'] ?? 'N/A'; ?></td>
                                <td><?php echo date('d-M-Y', strtotime($ticket['created_at'])); ?></br><small><?php echo date('H:i', strtotime($ticket['created_at'])); ?></small></td>
                                <td>
                                    <?php 
                                    $days = $ticket['days_old'];
                                    $badgeClass = $days <= 2 ? 'success' : ($days <= 5 ? 'warning' : 'danger');
                                    ?>
                                    <span class="badge bg-<?php echo $badgeClass; ?>">
                                        <?php echo $days; ?> days
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo $ticket['update_count']; ?> updates
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#descModal<?php echo $ticket['ticket_id']; ?>">
                                        <i class="bx bx-show-alt"></i> View
                                    </button>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#assignModal<?php echo $ticket['ticket_id']; ?>">
                                        <i class="bx bx-user-plus"></i> Assign
                                    </button>
                                </td>
                            </tr>

                            <!-- Description Modal -->
                            <div class="modal fade" id="descModal<?php echo $ticket['ticket_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Ticket #<?php echo $ticket['ticket_id']; ?> - Description</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Category:</strong> <?php echo $ticket['category']; ?></p>
                                            <p><strong>Student:</strong> <?php echo htmlspecialchars($ticket['student_name']); ?></p>
                                            <p><strong>PC:</strong> <?php echo $ticket['pc_number'] ?? 'N/A'; ?></p>
                                            <p><strong>Created:</strong> <?php echo $ticket['created_at']; ?></p>
                                            <hr>
                                            <p><strong>Description:</strong></p>
                                            <div class="alert alert-info">
                                                <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Assign Modal -->
                            <div class="modal fade" id="assignModal<?php echo $ticket['ticket_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Assign Ticket #<?php echo $ticket['ticket_id']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="assign_id" value="<?php echo $ticket['ticket_id']; ?>">
                                                
                                                <p><strong>Ticket:</strong> <?php echo $ticket['category']; ?> issue</p>
                                                <p><strong>Student:</strong> <?php echo htmlspecialchars($ticket['student_name']); ?></p>
                                                <p><strong>Days pending:</strong> <?php echo $ticket['days_old']; ?> days</p>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Select Technician</label>
                                                    <select name="technician_id" class="form-select" required>
                                                        <option value="">-- Select Technician --</option>
                                                        <?php foreach($technicians as $tech): ?>
                                                            <option value="<?php echo $tech['tech_id']; ?>">
                                                                <?php echo htmlspecialchars($tech['name']); ?> (<?php echo $tech['shift']; ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success">Assign Ticket</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center py-4">
                                    <div class="alert alert-success mb-0">
                                        <i class="bx bx-check-circle"></i> No unassigned tickets! All tickets have been assigned.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
include("components/footer.php");
?>