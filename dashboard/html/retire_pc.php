<?php
session_start();
include("config/query.php");
include("components/header.php");

$db = new Database();
$conn = $db->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

// Admin only
if ($userRole != 'admin') {
    header("Location: dashboard.php");
    exit();
}

// transaaction
if (isset($_POST['retire_id'])) {
    $retire_id = $_POST['retire_id'];
    
    try {
        $conn->beginTransaction();
        
        // status changing karo to 'Retired'
        $updateQuery = "UPDATE PC SET status = 'Retired' WHERE pc_id = :id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindParam(':id', $retire_id);
        $updateStmt->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "PC retired successfully!";
    } catch(PDOException $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
    echo "<script>location.assign('view_all_pcs.php')</script>";
    exit();
}

// jo nhi hein
$sql = "SELECT p.pc_id, p.pc_number, p.status, l.lab_name 
        FROM PC p
        LEFT JOIN LAB l ON p.lab_id = l.lab_id
        WHERE p.status != 'Retired'
        ORDER BY p.pc_number";
$stmt = $conn->prepare($sql);
$stmt->execute();
$pcs = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <span class="text-muted fw-light">PC Management /</span> Retire PC
        </h4>

        <div class="card">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>PC Number</th>
                            <th>Lab</th>
                            <th>Current Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <?php if(count($pcs) > 0): ?>
                            <?php foreach($pcs as $pc): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($pc['pc_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($pc['lab_name']); ?></td>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    switch($pc['status']) {
                                        case 'Available':
                                            $statusClass = 'bg-label-success';
                                            break;
                                        case 'Assigned':
                                            $statusClass = 'bg-label-primary';
                                            break;
                                        case 'Under Maintenance':
                                            $statusClass = 'bg-label-warning';
                                            break;
                                        default:
                                            $statusClass = 'bg-label-secondary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo $pc['status']; ?>
                                    </span>
                                        </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Retire <?php echo htmlspecialchars($pc['pc_number']); ?>? This can be undone by editing the PC status.')">
                                        <input type="hidden" name="retire_id" value="<?php echo $pc['pc_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-warning">
                                            <i class="bx bx-trash"></i> Retire PC
                                        </button>
                                    </form>
                                        </td>
                                        </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <div class="alert alert-info mb-0">
                                        <i class="bx bx-info-circle"></i> No active PCs found. All PCs are already retired.
                                    </div>
                        </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                        </table>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="view_all_pcs.php" class="btn btn-secondary">
                <i class="bx bx-arrow-back"></i> Back to PCs
            </a>
        </div>
    </div>
</div>

<?php include("components/footer.php"); ?>