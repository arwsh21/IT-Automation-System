<?php
session_start();
include("config/query.php");
include("components/header.php");

$db = new Database();
$conn = $db->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

// del - WITH TRANSACTION
/* if (isset($_POST['delete_id']) && $userRole == 'admin') {
    $delete_id = $_POST['delete_id'];
    try {
        $conn->beginTransaction();
        
        $deleteLogQuery = "DELETE FROM pc_log WHERE pc_id = :id";
        $deleteLogStmt = $conn->prepare($deleteLogQuery);
        $deleteLogStmt->bindParam(':id', $delete_id);
        $deleteLogStmt->execute();
        
        $deleteQuery = "DELETE FROM PC WHERE pc_id = :id";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bindParam(':id', $delete_id);
        $deleteStmt->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "PC deleted successfully!";
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "Cannot delete - PC may have tickets or installations: " . $e->getMessage();
    }
    echo "<script>location.assign('view_all_pcs.php')</script>";
    exit();
}
    */

//<!-- UPDATE WITH TRANSACTION
if (isset($_POST['update_id']) && $userRole == 'admin') {
    $update_id = $_POST['update_id'];
    $pc_number = $_POST['pc_number'];
    $lab_id = $_POST['lab_id'];
    $status = $_POST['status'];
    $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
    $assigned_by = !empty($_POST['assigned_by']) ? $_POST['assigned_by'] : null;
    $assignment_start = !empty($_POST['assignment_start']) ? $_POST['assignment_start'] : null;
    $assignment_end = !empty($_POST['assignment_end']) ? $_POST['assignment_end'] : null;
    $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $last_serviced = !empty($_POST['last_serviced']) ? $_POST['last_serviced'] : null;
    
    try {
        $conn->beginTransaction();
        
        $updateQuery = "UPDATE PC 
                        SET pc_number = :pc_number,
                            lab_id = :lab_id,
                            status = :status,
                            assigned_to = :assigned_to,
                            assigned_by = :assigned_by,
                            assignment_start = :assignment_start,
                            assignment_end = :assignment_end,
                            purchase_date = :purchase_date,
                            last_serviced = :last_serviced
                        WHERE pc_id = :id";
        
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindParam(':pc_number', $pc_number);
        $updateStmt->bindParam(':lab_id', $lab_id);
        $updateStmt->bindParam(':status', $status);
        $updateStmt->bindParam(':assigned_to', $assigned_to);
        $updateStmt->bindParam(':assigned_by', $assigned_by);
        $updateStmt->bindParam(':assignment_start', $assignment_start);
        $updateStmt->bindParam(':assignment_end', $assignment_end);
        $updateStmt->bindParam(':purchase_date', $purchase_date);
        $updateStmt->bindParam(':last_serviced', $last_serviced);
        $updateStmt->bindParam(':id', $update_id);
        $updateStmt->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "PC updated successfully!";
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
    echo "<script>location.assign('view_all_pcs.php')</script>";
    exit();
}

// USING VIEW pc_full_details
$sql = "SELECT * FROM pc_full_details ORDER BY pc_id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$pcs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 
$labs = [];
$students = [];
$technicians = [];
if ($userRole == 'admin') {
    $labSql = "SELECT lab_id, lab_name, location FROM LAB ORDER BY lab_name";
    $labStmt = $conn->prepare($labSql);
    $labStmt->execute();
    $labs = $labStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $studentSql = "SELECT student_id, name, email FROM STUDENT ORDER BY name";
    $studentStmt = $conn->prepare($studentSql);
    $studentStmt->execute();
    $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $techSql = "SELECT tech_id, name FROM IT_TECHNICIAN ORDER BY name";
    $techStmt = $conn->prepare($techSql);
    $techStmt->execute();
    $technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC);
}


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
            <span class="text-muted fw-light">PC Management /</span> View All PCs
        </h4>

        <!-- Add PC Button - Admin ONLY -->
        <?php if($userRole == 'admin'): ?>
        <div class="mb-3">
            <a href="add_pc.php" class="btn btn-primary">
                <i class="bx bx-plus-circle"></i> Add New PC
            </a>
        </div>
        <?php endif; ?>

        <!-- PCs Table -->
        <div class="card">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>PC ID</th>
                            <th>PC Number</th>
                            <th>Lab</th>
                            <th>Status</th>
                            <th>Assigned Student</th>
                            <th>Assigned By</th>
                            <th>Purchase Date</th>
                            <th>Last Serviced</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <?php if(count($pcs) > 0): ?>
                            <?php foreach($pcs as $pc): ?>
                            <tr>
                                <td><?php echo $pc['pc_id']; ?></td>
                                <td><strong><?php echo $pc['pc_number']; ?></strong></td>
                                <td>
                                    <?php echo $pc['lab_name']; ?>
                                    <br>
                                    <small class="text-muted"><?php echo $pc['location']; ?></small>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    switch($pc['pc_status']) {
                                        case 'Available':
                                            $statusClass = 'bg-label-success';
                                            break;
                                        case 'Assigned':
                                            $statusClass = 'bg-label-primary';
                                            break;
                                        case 'Under Maintenance':
                                            $statusClass = 'bg-label-warning';
                                            break;
                                        case 'Retired':
                                            $statusClass = 'bg-label-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo $pc['pc_status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($pc['assigned_student_name']): ?>
                                        <?php echo htmlspecialchars($pc['assigned_student_name']); ?>
                                        <br>
                                        <small><?php echo $pc['department']; ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $pc['assigned_by_technician'] ?: '<span class="text-muted">N/A</span>'; ?>
                                </td>
                                <td>
                                    <?php echo $pc['purchase_date'] ? date('M d, Y', strtotime($pc['purchase_date'])) : 'N/A'; ?>
                                </td>
                                <td>
                                    <?php echo $pc['last_serviced'] ? date('M d, Y', strtotime($pc['last_serviced'])) : 'N/A'; ?>
                                </td>
                                <td>
                                    <!-- View Button - Everyone -->
                                    <button type="button" 
                                            class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewModal<?php echo $pc['pc_id']; ?>">
                                        <i class="bx bx-show-alt"></i> View
                                    </button>
                                    
                                    <!-- Edit Button - Admin ONLY -->
                                    <?php if($userRole == 'admin'): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?php echo $pc['pc_id']; ?>">
                                        <i class="bx bx-edit"></i> Edit
                                    </button>
                                    <?php endif; ?>
                                    
                                    <!-- Delete Button - Admin ONLY 
                                    <?php if($userRole == 'admin'): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal<?php echo $pc['pc_id']; ?>">
                                        <i class="bx bx-trash"></i> Delete
                                    </button>  -->
                                    <?php endif; ?>
                                    </td>
                             </tr>

                            <!-- View Modal -->
                            <div class="modal fade" id="viewModal<?php echo $pc['pc_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">PC Details - <?php echo $pc['pc_number']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>PC ID:</strong> <?php echo $pc['pc_id']; ?></p>
                                                    <p><strong>PC Number:</strong> <?php echo $pc['pc_number']; ?></p>
                                                    <p><strong>Lab:</strong> <?php echo $pc['lab_name']; ?></p>
                                                    <p><strong>Location:</strong> <?php echo $pc['location']; ?></p>
                                                    <p><strong>Status:</strong> <?php echo $pc['pc_status']; ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Purchase Date:</strong> <?php echo $pc['purchase_date'] ?: 'N/A'; ?></p>
                                                    <p><strong>Last Serviced:</strong> <?php echo $pc['last_serviced'] ?: 'N/A'; ?></p>
                                                    <p><strong>Assignment Start:</strong> <?php echo $pc['assignment_start'] ?: 'N/A'; ?></p>
                                                    <p><strong>Assignment End:</strong> <?php echo $pc['assignment_end'] ?: 'N/A'; ?></p>
                                                </div>
                                            </div>
                                            <hr>
                                            <h6>Assignment Details:</h6>
                                            <p><strong>Assigned Student:</strong> <?php echo $pc['assigned_student_name'] ?: 'Not Assigned'; ?></p>
                                            <p><strong>Student Email:</strong> <?php echo $pc['student_email'] ?: 'N/A'; ?></p>
                                            <p><strong>Assigned By Technician:</strong> <?php echo $pc['assigned_by_technician'] ?: 'N/A'; ?></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Modal - Admin ONLY -->
                            <?php if($userRole == 'admin'): ?>
                            <div class="modal fade" id="editModal<?php echo $pc['pc_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form method="POST" action="">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit PC - <?php echo $pc['pc_number']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="update_id" value="<?php echo $pc['pc_id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">PC Number</label>
                                                    <input type="text" class="form-control" name="pc_number" 
                                                           value="<?php echo $pc['pc_number']; ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Lab</label>
                                                    <select class="form-select" name="lab_id" required>
                                                        <?php foreach($labs as $lab): ?>
                                                            <option value="<?php echo $lab['lab_id']; ?>" 
                                                                <?php echo $pc['lab_id'] == $lab['lab_id'] ? 'selected' : ''; ?>>
                                                                <?php echo $lab['lab_name']; ?> - <?php echo $lab['location']; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select class="form-select" name="status" required>
                                                        <option value="Available" <?php echo $pc['pc_status'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                                                        <option value="Assigned" <?php echo $pc['pc_status'] == 'Assigned' ? 'selected' : ''; ?>>Assigned</option>
                                                        <option value="Under Maintenance" <?php echo $pc['pc_status'] == 'Under Maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                                                        <option value="Retired" <?php echo $pc['pc_status'] == 'Retired' ? 'selected' : ''; ?>>Retired</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Assigned Student</label>
                                                    <select class="form-select" name="assigned_to">
                                                        <option value="">Not Assigned</option>
                                                        <?php foreach($students as $student): ?>
                                                            <option value="<?php echo $student['student_id']; ?>" 
                                                                <?php echo $pc['assigned_to'] == $student['student_id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($student['name']); ?> (<?php echo $student['email']; ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Assigned By (Technician)</label>
                                                    <select class="form-select" name="assigned_by">
                                                        <option value="">Select Technician</option>
                                                        <?php foreach($technicians as $tech): ?>
                                                            <option value="<?php echo $tech['tech_id']; ?>" 
                                                                <?php echo $pc['assigned_by'] == $tech['tech_id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($tech['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Assignment Start Date</label>
                                                    <input type="date" class="form-control" name="assignment_start" 
                                                           value="<?php echo $pc['assignment_start']; ?>">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Assignment End Date</label>
                                                    <input type="date" class="form-control" name="assignment_end" 
                                                           value="<?php echo $pc['assignment_end']; ?>">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Purchase Date</label>
                                                    <input type="date" class="form-control" name="purchase_date" 
                                                           value="<?php echo $pc['purchase_date']; ?>">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Last Serviced Date</label>
                                                    <input type="date" class="form-control" name="last_serviced" 
                                                           value="<?php echo $pc['last_serviced']; ?>">
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
                            <div class="modal fade" id="deleteModal<?php echo $pc['pc_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirm Delete</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="delete_id" value="<?php echo $pc['pc_id']; ?>">
                                                <p>Are you sure you want to delete <strong><?php echo $pc['pc_number']; ?></strong>?</p>
                                                <p><strong>Lab:</strong> <?php echo $pc['lab_name']; ?></p>
                                                <p><strong>Status:</strong> <?php echo $pc['pc_status']; ?></p>
                                                <?php if($pc['assigned_student_name']): ?>
                                                    <p><strong>Assigned to:</strong> <?php echo $pc['assigned_student_name']; ?></p>
                                                <?php endif; ?>
                                                <p class="text-danger">This action cannot be undone!</p>
                                                <p class="text-warning">Note: PCs with tickets or software installations cannot be deleted.</p>
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
                                <td colspan="9" class="text-center py-4">
                                    <div class="alert alert-info mb-0">
                                        <i class="bx bx-info-circle"></i> No PCs found in the system.
                                        <br>
                                        <?php if($userRole == 'admin'): ?>
                                        <a href="add_pc.php" class="btn btn-primary mt-2">Add Your First PC</a>
                                        <?php endif; ?>
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