<?php
session_start();
include("config/query.php");
include("components/header.php");

$database = new Database();
$conn = $database->getConnection();

// =TRANSACTION 
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    try {
        $conn->beginTransaction();
        
        $checkQuery = "SELECT COUNT(*) FROM INSTALLATION WHERE software_id = :id AND install_status = 'Active'";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(':id', $delete_id);
        $checkStmt->execute();
        $isInstalled = $checkStmt->fetchColumn();
        
        if ($isInstalled > 0) {
            throw new Exception("Cannot delete - Software is currently installed on PCs. Uninstall first.");
        }
        // delete software
        $deleteLogQuery = "DELETE FROM software_log WHERE software_id = :id";
        $deleteLogStmt = $conn->prepare($deleteLogQuery);
        $deleteLogStmt->bindParam(':id', $delete_id);
        $deleteLogStmt->execute();
        
        $deleteQuery = "DELETE FROM SOFTWARE WHERE software_id = :id";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bindParam(':id', $delete_id);
        $deleteStmt->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "Software deleted successfully!";
        
    } catch(Exception $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = $e->getMessage();
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "Cannot delete - Software may be referenced elsewhere: " . $e->getMessage();
    }
    echo "<script>location.assign('view_all_software.php')</script>";
    exit();
}

if (isset($_POST['update_id'])) {
    $update_id = $_POST['update_id'];
    $name = $_POST['name'];
    $version = $_POST['version'];
    $license_type = $_POST['license_type'];
    $license_expiry = !empty($_POST['license_expiry']) ? $_POST['license_expiry'] : null;
    
    try {
        $conn->beginTransaction();
        // update software
        $updateQuery = "UPDATE SOFTWARE 
                        SET name = :name, 
                            version = :version, 
                            license_type = :license_type, 
                            license_expiry = :license_expiry 
                        WHERE software_id = :id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindParam(':name', $name);
        $updateStmt->bindParam(':version', $version);
        $updateStmt->bindParam(':license_type', $license_type);
        $updateStmt->bindParam(':license_expiry', $license_expiry);
        $updateStmt->bindParam(':id', $update_id);
        $updateStmt->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "Software updated successfully!";
        
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
    echo "<script>location.assign('view_all_software.php')</script>";
    exit();
}
// view : software_details
$sql = "SELECT * FROM software_details ORDER BY software_id";
$stmt = $conn->prepare($sql);
$stmt->execute();
$software = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <span class="text-muted fw-light">Software Management /</span> View All Software
        </h4>

        <div class="mb-3">
            <a href="add_software.php" class="btn btn-primary">
                <i class="bx bx-plus-circle"></i> Add New Software
            </a>
        </div>

        <div class="card">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Software ID</th>
                            <th>Name</th>
                            <th>Version</th>
                            <th>License Type</th>
                            <th>License Expiry</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <?php if(count($software) > 0): ?>
                            <?php foreach($software as $sw): ?>
                            <tr>
                                <td><?php echo $sw['software_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($sw['name']); ?></strong></td>
                                <td><?php echo $sw['version']; ?></td>
                                <td>
                                    <?php
                                    $licenseClass = '';
                                    switch($sw['license_type']) {
                                        case 'Free':       $licenseClass = 'bg-label-success'; break;
                                        case 'Commercial': $licenseClass = 'bg-label-danger';  break;
                                        case 'Educational':$licenseClass = 'bg-label-primary'; break;
                                        default:           $licenseClass = 'bg-label-info';
                                    }
                                    ?>
                                    <span class="badge <?php echo $licenseClass; ?>">
                                        <?php echo $sw['license_type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if($sw['license_expiry']) {
                                        echo date('M d, Y', strtotime($sw['license_expiry']));
                                        if(strtotime($sw['license_expiry']) < time()) {
                                            echo ' <span class="badge bg-danger">Expired</span>';
                                        }
                                    } else {
                                        echo '<span class="text-muted">Perpetual</span>';
                                    }
                                    ?>
                                </td>

                            
                                <td>
                                    <div class="d-flex flex-column gap-1" style="width: fit-content;">

                                        <!-- View Button -->
                                        <button type="button"
                                                class="btn btn-sm btn-info"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewModal<?php echo $sw['software_id']; ?>">
                                            <i class="bx bx-show-alt"></i> View
                                        </button>

                                        <!-- Edit Button -->
                                        <button type="button"
                                                class="btn btn-sm btn-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editModal<?php echo $sw['software_id']; ?>">
                                            <i class="bx bx-edit"></i> Edit
                                        </button>

                                        <!-- Delete Button -->
                                        <button type="button"
                                                class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal<?php echo $sw['software_id']; ?>">
                                            <i class="bx bx-trash"></i> Delete
                                        </button>

                                    </div>
                                </td>
                            </tr>

                            <!-- View Modal -->
                            <div class="modal fade" id="viewModal<?php echo $sw['software_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Software Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Software ID:</strong> <?php echo $sw['software_id']; ?></p>
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($sw['name']); ?></p>
                                            <p><strong>Version:</strong> <?php echo $sw['version']; ?></p>
                                            <p><strong>License Type:</strong> <?php echo $sw['license_type']; ?></p>
                                            <p><strong>License Expiry:</strong> <?php echo $sw['license_expiry'] ?: 'Perpetual (No Expiry)'; ?></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $sw['software_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Software</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="update_id" value="<?php echo $sw['software_id']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Software Name</label>
                                                    <input type="text" class="form-control" name="name"
                                                           value="<?php echo htmlspecialchars($sw['name']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Version</label>
                                                    <input type="text" class="form-control" name="version"
                                                           value="<?php echo $sw['version']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">License Type</label>
                                                    <select class="form-select" name="license_type" required>
                                                        <option value="Free"        <?php echo $sw['license_type'] == 'Free'        ? 'selected' : ''; ?>>Free</option>
                                                        <option value="Commercial"  <?php echo $sw['license_type'] == 'Commercial'  ? 'selected' : ''; ?>>Commercial</option>
                                                        <option value="Educational" <?php echo $sw['license_type'] == 'Educational' ? 'selected' : ''; ?>>Educational</option>
                                                        <option value="Open Source" <?php echo $sw['license_type'] == 'Open Source' ? 'selected' : ''; ?>>Open Source</option>
                                                        <option value="Trial"       <?php echo $sw['license_type'] == 'Trial'       ? 'selected' : ''; ?>>Trial</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">License Expiry</label>
                                                    <input type="date" class="form-control" name="license_expiry"
                                                           value="<?php echo $sw['license_expiry']; ?>">
                                                    <small class="text-muted">Leave blank for perpetual licenses</small>
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

                            <!-- Delete Modal -->
                            <div class="modal fade" id="deleteModal<?php echo $sw['software_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirm Delete</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="delete_id" value="<?php echo $sw['software_id']; ?>">
                                                <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($sw['name']); ?></strong>?</p>
                                                <p class="text-danger">This action cannot be undone!</p>
                                                <?php if($sw['license_type'] == 'Commercial'): ?>
                                                    <p class="text-warning">Warning: This is a commercial license software.</p>
                                                <?php endif; ?>
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
                                        <i class="bx bx-info-circle"></i> No software found.
                                        <br>
                                        <a href="add_software.php" class="btn btn-primary mt-2">Add Your First Software</a>
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

<?php include("components/footer.php"); ?>