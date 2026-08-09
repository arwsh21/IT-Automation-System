<?php
session_start();
include("config/query.php");
include("components/header.php");

$database = new Database();
$conn = $database->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

$pcData = null;
$pcId = '';
$error = '';
$searched = false;

// ====================  stored procsesedecd: GetPCStatusReport ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pc_id'])) {
    $pcId = trim($_POST['pc_id']);
    $searched = true;
    
    if (!is_numeric($pcId)) {
        $error = "Please enter a valid numeric PC ID.";
    } else {
        try {
            $stmt = $conn->prepare("CALL GetPCStatusReport(:pc_id)");
            $stmt->bindParam(':pc_id', $pcId);
            $stmt->execute();
            $pcData = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            if (!$pcData) {
                $error = "No PC found with ID: " . $pcId;
                $pcData = null;
            }
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Reports /</span> PC Status Report
        </h4>

        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" action="" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Enter PC ID</label>
                        <input type="number" name="pc_id" class="form-control" 
                               placeholder="Enter PC ID (e.g., 1, 2, 3...)" 
                               value="<?php echo htmlspecialchars($pcId); ?>" required>
                        <small class="text-muted">Enter the numeric PC ID to view details</small>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-search"></i> Get Report
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="bx bx-error-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if($searched && $pcData): ?>
        
        <!-- PC Status Card -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">PC Status Report - ID: <?php echo $pcData['pc_id']; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- PC Information -->
                            <div class="col-md-6">
                                <h6 class="text-primary">🖥️ PC Information</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>PC Number</th>
                                        <td><strong><?php echo $pcData['pc_number']; ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            switch($pcData['status']) {
                                                case 'Available': $statusClass = 'success'; break;
                                                case 'Assigned': $statusClass = 'info'; break;
                                                case 'Under Maintenance': $statusClass = 'warning'; break;
                                                case 'Retired': $statusClass = 'danger'; break;
                                                default: $statusClass = 'secondary';
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $pcData['status']; ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Purchase Date</th>
                                        <td><?php echo $pcData['purchase_date'] ?? 'N/A'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Last Serviced</th>
                                        <td><?php echo $pcData['last_serviced'] ?? 'N/A'; ?></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Lab Information -->
                            <div class="col-md-6">
                                <h6 class="text-success">🏢 Lab Information</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Lab Name</th>
                                        <td><?php echo $pcData['lab_name'] ?? 'N/A'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Location</th>
                                        <td><?php echo $pcData['location'] ?? 'N/A'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Capacity</th>
                                        <td><?php echo $pcData['capacity'] ?? 'N/A'; ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Assignment Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-warning">📋 Assignment Details</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Assigned To (Student)</th>
                                            <td>
                                                <?php if($pcData['assigned_to_student']): ?>
                                                    <strong><?php echo $pcData['assigned_to_student']; ?></strong>
                                                <?php else: ?>
                                                    <span class="text-muted">Not Assigned</span>
                                                <?php endif; ?>
                                            </td>
                                    </tr>
                                    <tr>
                                        <th>Assigned By (Technician)</th>
                                        <td><?php echo $pcData['assigned_by_technician'] ?? 'N/A'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Assignment Start</th>
                                        <td><?php echo $pcData['assignment_start'] ?? 'N/A'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Assignment End</th>
                                        <td><?php echo $pcData['assignment_end'] ?? 'N/A'; ?></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Statistics -->
                            <div class="col-md-6">
                                <h6 class="text-info">📊 Statistics</h6>
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Total Tickets</th>
                                        <td><?php echo $pcData['total_tickets'] ?? '0'; ?> tickets</td>
                                            </tr>
                                            <tr>
                                            <th>Installed Software</th>
                                        <td><?php echo $pcData['installed_software'] ?? '0'; ?> applications</td>
                                        </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Age Calculation -->
                        <?php if($pcData['purchase_date']): ?>
                        <hr>
                        <div class="alert alert-info">
                            <i class="bx bx-calendar"></i> 
                            PC Age: 
                            <?php 
                            $purchase = new DateTime($pcData['purchase_date']);
                            $now = new DateTime();
                            $age = $now->diff($purchase);
                            echo $age->y . ' years, ' . $age->m . ' months';
                            ?>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>
        </div>
        
        <?php elseif($searched && !$pcData && !$error): ?>
            <div class="alert alert-info">
                <i class="bx bx-info-circle"></i> No PC found. Please enter a valid PC ID.
            </div>
        <?php endif; ?>
        
        <!-- Quick Links -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3">Quick Navigation</h6>
                        <a href="view_all_pcs.php" class="btn btn-secondary me-2">
                            <i class="bx bx-desktop"></i> View All PCs
                        </a>
                        <a href="assigned_pc.php" class="btn btn-info">
                            <i class="bx bx-user-check"></i> View Assigned PCs
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php
include("components/footer.php");
?>