<?php
session_start();
include("components/header.php");
require_once 'config/query.php';

$database = new Database();
$conn = $database->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

$pcs = [];
$searchLab = '';
$error = '';

// ==================== CALL STORED PROCEDURE ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_lab'])) {
    $searchLab = trim($_POST['search_lab']);
    
    try {
        $stmt = $conn->prepare("CALL CheckPCAvailability(:lab_name)");
        $stmt->bindParam(':lab_name', $searchLab);
        $stmt->execute();
        $pcs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
} else {
    // Default: show all available PCs
    try {
        $stmt = $conn->prepare("CALL CheckPCAvailability('')");
        $stmt->execute();
        $pcs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$labs = array_unique(array_column($pcs, 'lab_name'));
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">PCs /</span> Available PCs
        </h4>

        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" action="" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search by Lab Name</label>
                        <input type="text" name="search_lab" class="form-control" 
                               placeholder="e.g., Lab A, Computer Lab, etc."
                               value="<?php echo htmlspecialchars($searchLab); ?>">
                        <small class="text-muted">Leave empty to show all available PCs</small>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-search"></i> Search
                        </button>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="available_pc.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">✅ Available PCs</h5>
                        <h2><?php echo count($pcs); ?></h2>
                        <small>Ready for use</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">🏢 Labs with Availability</h5>
                        <h2><?php echo count($labs); ?></h2>
                        <small><?php echo !empty($searchLab) ? "Filtered by: $searchLab" : "All labs"; ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available PCs Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 text-success">
                    <i class="bx bx-check-circle"></i> 
                    <?php if(!empty($searchLab)): ?>
                        Available PCs in labs matching "<?php echo htmlspecialchars($searchLab); ?>"
                    <?php else: ?>
                        All Available PCs (Status: Available)
                    <?php endif; ?>
                </h5>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>PC Number</th>
                            <th>Lab Name</th>
                            <th>Location</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($pcs) > 0): ?>
                            <?php foreach($pcs as $pc): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($pc['pc_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($pc['lab_name']); ?></td>
                                <td><?php echo htmlspecialchars($pc['location']); ?></td>
                                <td>
                                    <?php if($userRole == 'student'): ?>
                                        <a href="add_ticket_std.php?pc_id=<?php echo $pc['pc_id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="bx bx-plus-circle"></i> Raise Ticket
                                        </a>
                                    <?php else: ?>
                                        <a href="view_all_pcs.php?pc_id=<?php echo $pc['pc_id']; ?>" 
                                           class="btn btn-sm btn-info">
                                            <i class="bx bx-show-alt"></i> View Details
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <div class="alert alert-info mb-0">
                                        <i class="bx bx-info-circle"></i> 
                                        <?php if(!empty($searchLab)): ?>
                                            No available PCs found in labs matching "<?php echo htmlspecialchars($searchLab); ?>"
                                        <?php else: ?>
                                            No available PCs found in any lab.
                                        <?php endif; ?>
                                    </div>
                                 </td>
                             </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Quick Links -->
        <div class="mt-3">
            <a href="lab_guide.php" class="btn btn-secondary">
                <i class="bx bx-building"></i> View Lab Guide
            </a>
            <a href="view_all_pcs.php" class="btn btn-info">
                <i class="bx bx-desktop"></i> View All PCs
            </a>
            <?php if($userRole == 'student'): ?>
                <a href="add_ticket_std.php" class="btn btn-primary">
                    <i class="bx bx-plus-circle"></i> Raise a Ticket
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include("components/footer.php"); ?>