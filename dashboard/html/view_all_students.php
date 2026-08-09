<?php

include("config/query.php");
include("components/header.php");

$database = new Database();
$conn = $database->getConnection();

// ==================== HANDLE DELETE OPERATION ====================
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    try {
        $checkQuery = "SELECT COUNT(*) as count FROM TICKET WHERE raised_by = :id";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(':id', $delete_id);
        $checkStmt->execute();
        $hasTickets = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($hasTickets['count'] > 0) {
            $_SESSION['error_message'] = "Cannot delete - Student has existing tickets.";
        } else {
            $deleteQuery = "DELETE FROM STUDENT WHERE student_id = :id";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->bindParam(':id', $delete_id);
            if ($deleteStmt->execute()) {
                $_SESSION['success_message'] = "Student deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to delete student.";
            }
        }
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Cannot delete - " . $e->getMessage();
    }
    echo "<script>location.assign('view_all_students.php')</script>";
    exit();
}

//  No transaction needed since 2+ tables update nahi horahe 
if (isset($_POST['update_id'])) {
    $update_id = $_POST['update_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $batch = $_POST['batch'];
    $department = $_POST['department'];
    $old_password = $_POST['old_password'] ?? null;
    $new_password = $_POST['new_password'] ?? null;
    $confirm_password = $_POST['confirm_password'] ?? null;
    
    try {
        if (!empty($new_password) || !empty($confirm_password)) {
            if ($new_password !== $confirm_password) {
                $_SESSION['error_message'] = "New password and confirmation do not match!";
                echo "<script>location.assign('view_all_students.php')</script>";
                exit();
            }
            
            if (strlen($new_password) < 6) {
                $_SESSION['error_message'] = "New password must be at least 6 characters!";
                echo "<script>location.assign('view_all_students.php')</script>";
                exit();
            }
            
            $checkQuery = "SELECT password FROM STUDENT WHERE student_id = :id";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bindParam(':id', $update_id);
            $checkStmt->execute();
            $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || $old_password !== $user['password']) {
                $_SESSION['error_message'] = "Old password is incorrect!";
                echo "<script>location.assign('view_all_students.php')</script>";
                exit();
            }
            
            $updateQuery = "UPDATE STUDENT 
                            SET name = :name, email = :email, batch = :batch, 
                                department = :department, password = :password
                            WHERE student_id = :id";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bindParam(':password', $new_password);  
        } else {
            $updateQuery = "UPDATE STUDENT 
                            SET name = :name, email = :email, batch = :batch, department = :department
                            WHERE student_id = :id";
            $updateStmt = $conn->prepare($updateQuery);
        }
        
        $updateStmt->bindParam(':name', $name);
        $updateStmt->bindParam(':email', $email);
        $updateStmt->bindParam(':batch', $batch);
        $updateStmt->bindParam(':department', $department);
        $updateStmt->bindParam(':id', $update_id);
        
        if ($updateStmt->execute()) {
            $_SESSION['success_message'] = "Student updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update student.";
        }
    } catch(PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate') !== false) {
            $_SESSION['error_message'] = "Email already exists!";
        } else {
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
        }
    }
    echo "<script>location.assign('view_all_students.php')</script>";
    exit();
}

//   ALL STUDENTS 
$sql = "SELECT student_id, name, email, batch, department FROM STUDENT ORDER BY student_id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

//  DISPLAY MESSAGES
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
            <span class="text-muted fw-light">Student Management /</span> View All Students
        </h4>

        <div class="mb-3">
            <a href="add_student.php" class="btn btn-primary">
                <i class="bx bx-plus-circle"></i> Add New Student
            </a>
        </div>

        <div class="card">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Batch</th>
                            <th>Department</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <?php if(count($students) > 0): ?>
                            <?php foreach($students as $student): ?>
                            <tr>
                                <td><?php echo $student['student_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                                <td><?php echo $student['email']; ?></td>
                                <td><?php echo $student['batch']; ?></td>
                                <td>
                                    <?php
                                    $deptClass = '';
                                    switch($student['department']) {
                                        case 'CS': $deptClass = 'bg-label-primary'; break;
                                        case 'SE': $deptClass = 'bg-label-success'; break;
                                        case 'AI': $deptClass = 'bg-label-info'; break;
                                        case 'DS': $deptClass = 'bg-label-warning'; break;
                                        default: $deptClass = 'bg-label-secondary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $deptClass; ?>">
                                        <?php echo $student['department']; ?>
                                    </span>
                            </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $student['student_id']; ?>">
                                        <i class="bx bx-show-alt"></i> View
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            data-bs-toggle="modal" data-bs-target="#editModal<?php echo $student['student_id']; ?>">
                                        <i class="bx bx-edit"></i> Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $student['student_id']; ?>">
                                        <i class="bx bx-trash"></i> Delete
                                    </button>
                            </td>
                             </tr>

                            <!-- VIEW MODAL -->
                            <div class="modal fade" id="viewModal<?php echo $student['student_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                         <div class="modal-header">
                                            <h5 class="modal-title">Student Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Student ID:</strong> <?php echo $student['student_id']; ?></p>
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
                                            <p><strong>Email:</strong> <?php echo $student['email']; ?></p>
                                            <p><strong>Batch:</strong> <?php echo $student['batch']; ?></p>
                                            <p><strong>Department:</strong> <?php echo $student['department']; ?></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- EDIT MODAL (Plain Text Passwords) -->
                            <div class="modal fade" id="editModal<?php echo $student['student_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="" id="editForm<?php echo $student['student_id']; ?>" autocomplete="off">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Student</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="update_id" value="<?php echo $student['student_id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Full Name</label>
                                                    <input type="text" class="form-control" name="name" 
                                                           value="<?php echo htmlspecialchars($student['name']); ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" class="form-control" name="email" 
                                                           value="<?php echo $student['email']; ?>" required>
                                                </div>
                                                
                                                <hr>
                                                <h6 class="text-muted">Password Change (Optional)</h6>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Old Password</label>
                                                    <input type="password" class="form-control" name="old_password" 
                                                           id="old_password_<?php echo $student['student_id']; ?>"
                                                           placeholder="Enter current password to change" autocomplete="new-password">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">New Password</label>
                                                    <input type="password" class="form-control" name="new_password" 
                                                           id="new_password_<?php echo $student['student_id']; ?>"
                                                           placeholder="Enter new password" autocomplete="new-password">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Confirm New Password</label>
                                                    <input type="password" class="form-control" name="confirm_password" 
                                                           id="confirm_password_<?php echo $student['student_id']; ?>"
                                                           placeholder="Confirm new password" autocomplete="new-password">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Batch/Year</label>
                                                    <select class="form-select" name="batch" required>
                                                        <option value="2021" <?php echo $student['batch'] == '2021' ? 'selected' : ''; ?>>2021</option>
                                                        <option value="2022" <?php echo $student['batch'] == '2022' ? 'selected' : ''; ?>>2022</option>
                                                        <option value="2023" <?php echo $student['batch'] == '2023' ? 'selected' : ''; ?>>2023</option>
                                                        <option value="2024" <?php echo $student['batch'] == '2024' ? 'selected' : ''; ?>>2024</option>
                                                        <option value="2025" <?php echo $student['batch'] == '2025' ? 'selected' : ''; ?>>2025</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Department</label>
                                                    <select class="form-select" name="department" required>
                                                        <option value="CS" <?php echo $student['department'] == 'CS' ? 'selected' : ''; ?>>Computer Science (CS)</option>
                                                        <option value="SE" <?php echo $student['department'] == 'SE' ? 'selected' : ''; ?>>Software Engineering (SE)</option>
                                                        <option value="AI" <?php echo $student['department'] == 'AI' ? 'selected' : ''; ?>>Artificial Intelligence (AI)</option>
                                                        <option value="DS" <?php echo $student['department'] == 'DS' ? 'selected' : ''; ?>>Data Science (DS)</option>
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

                            <!-- DELETE MODAL -->
                            <div class="modal fade" id="deleteModal<?php echo $student['student_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirm Delete</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="delete_id" value="<?php echo $student['student_id']; ?>">
                                                <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($student['name']); ?></strong>?</p>
                                                <p><strong>Email:</strong> <?php echo $student['email']; ?></p>
                                                <p><strong>Department:</strong> <?php echo $student['department']; ?></p>
                                                <p class="text-danger">This action cannot be undone!</p>
                                                <p class="text-warning">Note: Students with tickets or assigned PCs cannot be deleted.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Yes, Delete</button>
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
                                        <i class="bx bx-info-circle"></i> No students found in the system.
                                        <br>
                                        <a href="add_student.php" class="btn btn-primary mt-2">Add Your First Student</a>
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
        const studentId = this.id.replace('editForm', '');
        const oldPass = document.getElementById('old_password_' + studentId).value;
        const newPass = document.getElementById('new_password_' + studentId).value;
        const confirmPass = document.getElementById('confirm_password_' + studentId).value;
        
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
            if (newPass.length < 6 && newPass.length > 0) {
                e.preventDefault();
                alert('New password must be at least 6 characters long!');
                return false;
            }
        }
        return true;
    });
});
</script>

<?php
include("components/footer.php");
?>