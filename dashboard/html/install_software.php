<?php
session_start();
include("components/header.php");
require_once 'config/query.php';

$database = new Database();
$conn = $database->getConnection();

$error = '';
$success = '';


$pcQuery = "SELECT pc_id, pc_number, lab_id, status FROM PC WHERE status IN ('Available', 'Assigned') ORDER BY pc_number";
$pcStmt = $conn->prepare($pcQuery);
$pcStmt->execute();
$pcs = $pcStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all software
$softwareQuery = "SELECT software_id, name, version, license_type FROM SOFTWARE ORDER BY name";
$softwareStmt = $conn->prepare($softwareQuery);
$softwareStmt->execute();
$softwareList = $softwareStmt->fetchAll(PDO::FETCH_ASSOC);


$techQuery = "SELECT tech_id, name FROM IT_TECHNICIAN ORDER BY name";
$techStmt = $conn->prepare($techQuery);
$techStmt->execute();
$technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pc_id = $_POST['pc_id'];
    $software_id = $_POST['software_id'];
    $installed_by = !empty($_POST['installed_by']) ? $_POST['installed_by'] : null;
    $install_status = 'Active';
    
    // Check if already installed
    $checkQuery = "SELECT COUNT(*) FROM INSTALLATION WHERE pc_id = :pc_id AND software_id = :software_id AND install_status = 'Active'";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':pc_id', $pc_id);
    $checkStmt->bindParam(':software_id', $software_id);
    $checkStmt->execute();
    $alreadyInstalled = $checkStmt->fetchColumn();
    
    if ($alreadyInstalled > 0) {
        $error = "This software is already installed on this PC!";
    } else {
        try {
            // TRANSACTION
            $conn->beginTransaction();
            
            $query = "INSERT INTO INSTALLATION (pc_id, software_id, installed_by, install_date, install_status) 
                      VALUES (:pc_id, :software_id, :installed_by, CURDATE(), :install_status)";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':pc_id', $pc_id);
            $stmt->bindParam(':software_id', $software_id);
            $stmt->bindParam(':installed_by', $installed_by);
            $stmt->bindParam(':install_status', $install_status);
            $stmt->execute();
            
            //T TRANSACTION
            $conn->commit();
            
            $_SESSION['success_message'] = "Software installed successfully on PC!";
            echo "<script>location.assign('view_all_software.php')</script>";
            exit();
            
        } catch(PDOException $e) {
            $conn->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Software Management /</span> Install Software on PC
        </h4>

        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-xl">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Install Software on PC</h5>
                        <small class="text-muted float-end">Select PC and Software</small>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="installForm">
                            <!-- Select PC -->
                            <div class="mb-3">
                                <label class="form-label" for="pc_id">Select PC *</label>
                                <select class="form-select" id="pc_id" name="pc_id" required>
                                    <option value="">Choose PC</option>
                                    <?php foreach($pcs as $pc): ?>
                                        <option value="<?php echo $pc['pc_id']; ?>">
                                            <?php echo $pc['pc_number']; ?> (Status: <?php echo $pc['status']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Select Software -->
                            <div class="mb-3">
                                <label class="form-label" for="software_id">Select Software *</label>
                                <select class="form-select" id="software_id" name="software_id" required>
                                    <option value="">Choose Software</option>
                                    <?php foreach($softwareList as $software): ?>
                                        <option value="<?php echo $software['software_id']; ?>">
                                            <?php echo $software['name']; ?> (v<?php echo $software['version']; ?>) - <?php echo $software['license_type']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            
                            <div class="mb-3">
                                <label class="form-label" for="installed_by">Installed By (Technician) *</label>
                                <select class="form-select" id="installed_by" name="installed_by" required>
                                    <option value="">Select Technician</option>
                                    <?php foreach($technicians as $tech): ?>
                                        <option value="<?php echo $tech['tech_id']; ?>">
                                            <?php echo htmlspecialchars($tech['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-download"></i> Install Software
                            </button>
                            <a href="view_all_software.php" class="btn btn-secondary">
                                <i class="bx bx-arrow-back"></i> Back
                            </a>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Info Card -->
            <div class="col-xl">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Installation Guidelines</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="bx bx-info-circle"></i> Before Installing:</h6>
                            <ul class="mb-0">
                                <li>Ensure PC is in Available or Assigned status</li>
                                <li>Check if software is already installed</li>
                                <li>Verify license availability</li>
                                <li>Confirm technician is assigned</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <h6><i class="bx bx-shield-alt"></i> Note:</h6>
                            <p class="mb-0">Installation will be logged in Software Log automatically.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('installForm').addEventListener('submit', function(e) {
    var pc = document.getElementById('pc_id').value;
    var software = document.getElementById('software_id').value;
    var technician = document.getElementById('installed_by').value;
    
    if (!pc) {
        e.preventDefault();
        alert('Please select a PC!');
        return false;
    }
    if (!software) {
        e.preventDefault();
        alert('Please select software to install!');
        return false;
    }
    if (!technician) {
        e.preventDefault();
        alert('Please select the technician performing installation!');
        return false;
    }
    return true;
});
</script>

<?php
include("components/footer.php");
?>