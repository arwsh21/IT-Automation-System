<?php
session_start();
include("config/query.php");
include("components/header.php");

$db = new Database();
$conn = $db->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

//Admin only trans
if (isset($_POST['delete_id']) && $userRole == 'admin') {
    $delete_id = $_POST['delete_id'];
    try {
        $conn->beginTransaction();
        
        $deleteLogQuery = "DELETE FROM TICKET_LOG WHERE ticket_id = :id";
        $deleteLogStmt = $conn->prepare($deleteLogQuery);
        $deleteLogStmt->bindParam(':id', $delete_id);
        $deleteLogStmt->execute();
        
        $deleteQuery = "DELETE FROM TICKET WHERE ticket_id = :id";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bindParam(':id', $delete_id);
        $deleteStmt->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "Ticket deleted successfully!";
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "Cannot delete - " . $e->getMessage();
    }
    echo "<script>location.assign('view_all_tickets.php')</script>";
    exit();
}

//  Update - Admin n Technician only - transaction
if (isset($_POST['update_id']) && ($userRole == 'admin' || $userRole == 'technician')) {
    $update_id = $_POST['update_id'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
    
    try {
        // 
        $conn->beginTransaction();
        
        $updateQuery = "UPDATE TICKET 
                        SET category = :category, 
                            description = :description, 
                            status = :status,
                            assigned_to = :assigned_to";
        
        if ($status == 'Resolved' || $status == 'Closed') {
            $updateQuery .= ", resolved_at = NOW()";
        } elseif ($status == 'Open' || $status == 'In Progress') {
            $updateQuery .= ", resolved_at = NULL";
        }
        
        $updateQuery .= " WHERE ticket_id = :id";
        
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindParam(':category', $category);
        $updateStmt->bindParam(':description', $description);
        $updateStmt->bindParam(':status', $status);
        $updateStmt->bindParam(':assigned_to', $assigned_to);
        $updateStmt->bindParam(':id', $update_id);
        $updateStmt->execute();
        
        $logQuery = "INSERT INTO TICKET_LOG (ticket_id, updated_by, old_status, new_status, note, updated_at) 
                     SELECT :ticket_id, :assigned_to, status, :new_status, 'Status updated via edit modal', NOW()
                     FROM TICKET WHERE ticket_id = :ticket_id2";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bindParam(':ticket_id', $update_id);
        $logStmt->bindParam(':assigned_to', $assigned_to);
        $logStmt->bindParam(':new_status', $status);
        $logStmt->bindParam(':ticket_id2', $update_id);
        $logStmt->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "Ticket updated successfully!";
        
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
    echo "<script>location.assign('view_all_tickets.php')</script>";
    exit();
}

//
$technicians = [];
if ($userRole == 'admin' || $userRole == 'technician') {
    $techSql = "SELECT tech_id, name FROM IT_TECHNICIAN ORDER BY name";
    $techStmt = $conn->prepare($techSql);
    $techStmt->execute();
    $technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC);
}
// USING VIEW ticket_full_details
if ($userRole == 'student') {
    $sql = "SELECT * FROM ticket_full_details WHERE raised_by = :student_id ORDER BY ticket_id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':student_id', $_SESSION['user_id']);
} else {
    $sql = "SELECT * FROM ticket_full_details ORDER BY ticket_id DESC";
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <span class="text-muted fw-light">Tickets /</span> View All Tickets
        </h4>

        <!-- Add Ticket Button - STD ONLY -->
        <?php if($userRole == 'student'): ?>
        <div class="mb-3">
            <a href="add_ticket.php" class="btn btn-primary">
                <i class="bx bx-plus-circle"></i> Raise New Ticket
            </a>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Ticket ID</th>
                            <th>Student Name</th>
                            <th>Department</th>
                            <th>PC Number</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Assigned To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <?php if(count($tickets) > 0): ?>
                            <?php foreach($tickets as $ticket): ?>
                            <tr>
                                <td><strong>#<?php echo $ticket['ticket_id']; ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($ticket['student_name']); ?>
                                    <br>
                                    <small class="text-muted"><?php echo $ticket['student_email']; ?></small>
                                </td>
                                <td>
                                    <?php echo $ticket['department']; ?>
                                    <br>
                                    <small><?php echo $ticket['batch']; ?></small>
                                </td>
                                <td><?php echo $ticket['pc_number'] ?: 'N/A'; ?></td>
                                <td>
                                    <span class="badge bg-label-info">
                                        <?php echo $ticket['category']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $desc = htmlspecialchars($ticket['description']);
                                    echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc;
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    switch($ticket['status']) {
                                        case 'Open':
                                            $statusClass = 'bg-label-danger';
                                            break;
                                        case 'In Progress':
                                            $statusClass = 'bg-label-warning';
                                            break;
                                        case 'Resolved':
                                            $statusClass = 'bg-label-primary';
                                            break;
                                        case 'Closed':
                                            $statusClass = 'bg-label-success';
                                            break;
                                        default:
                                            $statusClass = 'bg-label-secondary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo $ticket['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?>
                                    <br>
                                    <small><?php echo date('h:i A', strtotime($ticket['created_at'])); ?></small>
                                </td>
                                <td>
                                    <?php echo $ticket['assigned_technician'] ?: '<span class="text-muted">Unassigned</span>'; ?>
                                </td>
                                <td>
                                    <!-- View Button - Everyone -->
                                    <button type="button" 
                                            class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewModal<?php echo $ticket['ticket_id']; ?>">
                                        <i class="bx bx-show-alt"></i> View
                                    </button>
                                    
                                    <!-- Edit Button - Admin & Technician ONLY -->
                                    <?php if($userRole == 'admin' || $userRole == 'technician'): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?php echo $ticket['ticket_id']; ?>">
                                        <i class="bx bx-edit"></i> Edit
                                    </button>
                                    <?php endif; ?>
                                    
                                    <!-- Delete Button - Admin ONLY -->
                                    <?php if($userRole == 'admin'): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal<?php echo $ticket['ticket_id']; ?>">
                                        <i class="bx bx-trash"></i> Delete
                                    </button>
                                    <?php endif; ?>
                                 </td
                             </tr>

                            <!-- View Modal -->
                            <div class="modal fade" id="viewModal<?php echo $ticket['ticket_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Ticket Details #<?php echo $ticket['ticket_id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Student:</strong> <?php echo htmlspecialchars($ticket['student_name']); ?></p>
                                                    <p><strong>Email:</strong> <?php echo $ticket['student_email']; ?></p>
                                                    <p><strong>Department:</strong> <?php echo $ticket['department']; ?></p>
                                                    <p><strong>Batch:</strong> <?php echo $ticket['batch']; ?></p>
                                                    <p><strong>PC Number:</strong> <?php echo $ticket['pc_number'] ?: 'N/A'; ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Category:</strong> <?php echo $ticket['category']; ?></p>
                                                    <p><strong>Status:</strong> <?php echo $ticket['status']; ?></p>
                                                    <p><strong>Created:</strong> <?php echo date('F d, Y h:i A', strtotime($ticket['created_at'])); ?></p>
                                                    <p><strong>Resolved:</strong> <?php echo $ticket['resolved_at'] ? date('F d, Y h:i A', strtotime($ticket['resolved_at'])) : 'Not resolved yet'; ?></p>
                                                    <p><strong>Assigned To:</strong> <?php echo $ticket['assigned_technician'] ?: 'Unassigned'; ?></p>
                                                </div>
                                            </div>
                                            <hr>
                                            <p><strong>Description:</strong></p>
                                            <div class="alert alert-secondary">
                                                <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
                                            </div>
                                            <?php if($ticket['last_update_note']): ?>
                                            <p><strong>Last Update Note:</strong></p>
                                            <div class="alert alert-info">
                                                <?php echo nl2br(htmlspecialchars($ticket['last_update_note'])); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Modal - Admin n Technician ONLY -->
                            <?php if($userRole == 'admin' || $userRole == 'technician'): ?>
                            <div class="modal fade" id="editModal<?php echo $ticket['ticket_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form method="POST" action="">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Ticket #<?php echo $ticket['ticket_id']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="update_id" value="<?php echo $ticket['ticket_id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Student</label>
                                                    <input type="text" class="form-control" 
                                                           value="<?php echo htmlspecialchars($ticket['student_name']); ?>" disabled>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">PC Number</label>
                                                    <input type="text" class="form-control" 
                                                           value="<?php echo $ticket['pc_number'] ?: 'N/A'; ?>" disabled>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Category</label>
                                                    <select class="form-select" name="category" required>
                                                        <option value="Hardware" <?php echo $ticket['category'] == 'Hardware' ? 'selected' : ''; ?>>Hardware</option>
                                                        <option value="Software" <?php echo $ticket['category'] == 'Software' ? 'selected' : ''; ?>>Software</option>
                                                        <option value="Network" <?php echo $ticket['category'] == 'Network' ? 'selected' : ''; ?>>Network</option>
                                                        <option value="Printer" <?php echo $ticket['category'] == 'Printer' ? 'selected' : ''; ?>>Printer</option>
                                                        <option value="Account" <?php echo $ticket['category'] == 'Account' ? 'selected' : ''; ?>>Account</option>
                                                        <option value="Other" <?php echo $ticket['category'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Description</label>
                                                    <textarea class="form-control" name="description" rows="4" required><?php echo htmlspecialchars($ticket['description']); ?></textarea>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select class="form-select" name="status" required>
                                                        <option value="Open" <?php echo $ticket['status'] == 'Open' ? 'selected' : ''; ?>>Open</option>
                                                        <option value="In Progress" <?php echo $ticket['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                                        <option value="Resolved" <?php echo $ticket['status'] == 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                        <option value="Closed" <?php echo $ticket['status'] == 'Closed' ? 'selected' : ''; ?>>Closed</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Assign To (Technician)</label>
                                                    <select class="form-select" name="assigned_to">
                                                        <option value="">Unassigned</option>
                                                        <?php foreach($technicians as $tech): ?>
                                                            <option value="<?php echo $tech['tech_id']; ?>" 
                                                                <?php echo $ticket['assigned_to'] == $tech['tech_id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($tech['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Delete Modal - Admin ONLY -->
                            <?php if($userRole == 'admin'): ?>
                            <div class="modal fade" id="deleteModal<?php echo $ticket['ticket_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirm Delete</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="delete_id" value="<?php echo $ticket['ticket_id']; ?>">
                                                <p>Are you sure you want to delete <strong>Ticket #<?php echo $ticket['ticket_id']; ?></strong>?</p>
                                                <p><strong>Student:</strong> <?php echo htmlspecialchars($ticket['student_name']); ?></p>
                                                <p><strong>Category:</strong> <?php echo $ticket['category']; ?></p>
                                                <p><strong>Status:</strong> <?php echo $ticket['status']; ?></p>
                                                <p class="text-danger">This action cannot be undone!</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Yes, Delete</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <div class="alert alert-info mb-0">
                                        <i class="bx bx-info-circle"></i> No tickets found in the system.
                                        <br>
                                        <?php if($userRole == 'student'): ?>
                                        <a href="add_ticket.php" class="btn btn-primary mt-2">Raise Your First Ticket</a>
                                        <?php endif; ?>
                                    </div>
                                 </td
                             </tr
                        <?php endif; ?>
                    </tbody>
                 </table
            </div>
        </div>
    </div>
</div>

<?php
include("components/footer.php");
?>