<?php
session_start();
include("config/query.php");
include("components/header.php");

$database = new Database();
$conn = $database->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

if ($userRole != 'admin') {
    header("Location: dashboard.php");
    exit();
}

// aaaaaaaaa
if (isset($_POST['update_id'])) {
    $update_id = $_POST['update_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $employee_no = $_POST['employee_no'];
    $shift = $_POST['shift'];
    $old_password = $_POST['old_password'] ?? null;
    $new_password = $_POST['new_password'] ?? null;
    $confirm_password = $_POST['confirm_password'] ?? null;
    
    try {
        if (!empty($new_password) || !empty($confirm_password)) {
            if ($new_password !== $confirm_password) {
                $_SESSION['error_message'] = "New password and confirmation do not match!";
                echo "<script>location.assign('view_all_technicians.php')</script>";
                exit();
            }
            
            if (strlen($new_password) < 4) {
                $_SESSION['error_message'] = "New password must be at least 4 characters!";
                echo "<script>location.assign('view_all_technicians.php')</script>";
                exit();
            }
            
            $checkQuery = "SELECT password FROM it_technician WHERE tech_id = :id";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bindParam(':id', $update_id);
            $checkStmt->execute();
            $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || $old_password !== $user['password']) {
                $_SESSION['error_message'] = "Old password is incorrect!";
                echo "<script>location.assign('view_all_technicians.php')</script>";
                exit();
            }
            
            $updateQuery = "UPDATE it_technician 
                            SET name = :name, email = :email, employee_no = :employee_no, 
                                shift = :shift, password = :password
                            WHERE tech_id = :id";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bindParam(':password', $new_password);  
        } else {
            $updateQuery = "UPDATE it_technician 
                            SET name = :name, email = :email, employee_no = :employee_no, shift = :shift
                            WHERE tech_id = :id";
            $updateStmt = $conn->prepare($updateQuery);
        }
        
        $updateStmt->bindParam(':name', $name);
        $updateStmt->bindParam(':email', $email);
        $updateStmt->bindParam(':employee_no', $employee_no);
        $updateStmt->bindParam(':shift', $shift);
        $updateStmt->bindParam(':id', $update_id);
        
        if ($updateStmt->execute()) {
            $_SESSION['success_message'] = "Technician updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update technician.";
        }
    } catch(PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate') !== false) {
            $_SESSION['error_message'] = "Email or Employee Number already exists!";
        } else {
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
        }
    }
    echo "<script>location.assign('view_all_technicians.php')</script>";
    exit();
}

// all techc
$sql = "SELECT tech_id, name, email, employee_no, shift FROM it_technician ORDER BY tech_id";
$stmt = $conn->prepare($sql);
$stmt->execute();
$technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

// display
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
            <span class="text-muted fw-light">Admin /</span> View Technicians
        </h4>

        <div class="mb-3">
            <a href="add_technician.php" class="btn btn-primary">
                <i class="bx bx-user-plus"></i> Add New Technician
            </a>
        </div>

        <div class="card">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Employee No</th>
                            <th>Shift</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($technicians) > 0): ?>
                            <?php foreach($technicians as $tech): ?>
                            <tr>
                                <td><?php echo $tech['tech_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($tech['name']); ?></strong></td>
                                <td><?php echo $tech['email']; ?></td>
                                <td><?php echo $tech['employee_no']; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $tech['shift'] == 'Morning' ? 'success' : 
                                            ($tech['shift'] == 'Evening' ? 'warning' : 'info'); 
                                    ?>">
                                        <?php echo $tech['shift']; ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $tech['tech_id']; ?>">
                                        <i class="bx bx-show-alt"></i> View
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            data-bs-toggle="modal" data-bs-target="#editModal<?php echo $tech['tech_id']; ?>">
                                        <i class="bx bx-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>

                            <!-- VIEW MODAL -->
                            <div class="modal fade" id="viewModal<?php echo $tech['tech_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Technician Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Tech ID:</strong> <?php echo $tech['tech_id']; ?></p>
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($tech['name']); ?></p>
                                            <p><strong>Email:</strong> <?php echo $tech['email']; ?></p>
                                            <p><strong>Employee No:</strong> <?php echo $tech['employee_no']; ?></p>
                                            <p><strong>Shift:</strong> <?php echo $tech['shift']; ?></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- EDIT MODAL -->
                            <div class="modal fade" id="editModal<?php echo $tech['tech_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="" id="editForm<?php echo $tech['tech_id']; ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Technician</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="update_id" value="<?php echo $tech['tech_id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Full Name</label>
                                                    <input type="text" class="form-control" name="name" 
                                                           value="<?php echo htmlspecialchars($tech['name']); ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" class="form-control" name="email" 
                                                           value="<?php echo $tech['email']; ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Employee Number</label>
                                                    <input type="text" class="form-control" name="employee_no" 
                                                           value="<?php echo $tech['employee_no']; ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Shift</label>
                                                    <select class="form-select" name="shift" required>
                                                        <option value="Morning" <?php echo $tech['shift'] == 'Morning' ? 'selected' : ''; ?>>Morning (9 AM - 5 PM)</option>
                                                        <option value="Evening" <?php echo $tech['shift'] == 'Evening' ? 'selected' : ''; ?>>Evening (2 PM - 10 PM)</option>
                                                        <option value="Night" <?php echo $tech['shift'] == 'Night' ? 'selected' : ''; ?>>Night (9 PM - 5 AM)</option>
                                                    </select>
                                                </div>
                                                
                                                <hr>
                                                <h6 class="text-muted">Password Change (Optional)</h6>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Old Password</label>
                                                    <input type="password" class="form-control" name="old_password" 
                                                           id="old_password_<?php echo $tech['tech_id']; ?>"
                                                           placeholder="Enter current password to change">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">New Password</label>
                                                    <input type="password" class="form-control" name="new_password" 
                                                           id="new_password_<?php echo $tech['tech_id']; ?>"
                                                           placeholder="Enter new password (min 4 chars)">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Confirm New Password</label>
                                                    <input type="password" class="form-control" name="confirm_password" 
                                                           id="confirm_password_<?php echo $tech['tech_id']; ?>"
                                                           placeholder="Confirm new password">
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

                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="alert alert-info mb-0">
                                        <i class="bx bx-info-circle"></i> No technicians found.
                                        <br>
                                        <a href="add_technician.php" class="btn btn-primary mt-2">Add First Technician</a>
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

<script>
document.querySelectorAll('[id^="editForm"]').forEach(form => {
    form.addEventListener('submit', function(e) {
        const techId = this.id.replace('editForm', '');
        const oldPass = document.getElementById('old_password_' + techId).value;
        const newPass = document.getElementById('new_password_' + techId).value;
        const confirmPass = document.getElementById('confirm_password_' + techId).value;
        
        if (newPass || confirmPass) {
            if (!oldPass) {
                e.preventDefault();
                alert('Please enter your old password to change password');
                return false;
            }
            if (newPass !== confirmPass) {
                e.preventDefault();
                alert('New password and confirmation do not match!');
                return false;
            }
            if (newPass.length < 4 && newPass.length > 0) {
                e.preventDefault();
                alert('New password must be at least 4 characters long!');
                return false;
            }
        }
        return true;
    });
});
</script>

<?php include("components/footer.php"); ?>