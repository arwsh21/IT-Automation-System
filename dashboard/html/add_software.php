<?php
session_start();
include("components/header.php");
require_once 'config/query.php';

$database = new Database();
$conn = $database->getConnection();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $version = trim($_POST['version']);
    $license_type = $_POST['license_type'];
    $license_expiry = !empty($_POST['license_expiry']) ? $_POST['license_expiry'] : null;
    
    try {
        //  TRANSACTION aaaaaaaaaaaaaaaaaaaaaaaaa
        $conn->beginTransaction();
        
        $query = "INSERT INTO SOFTWARE (name, version, license_type, license_expiry) 
                  VALUES (:name, :version, :license_type, :license_expiry)";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':version', $version);
        $stmt->bindParam(':license_type', $license_type);
        $stmt->bindParam(':license_expiry', $license_expiry);
        
        $stmt->execute();
        
        // committt
        $conn->commit();
        
        $_SESSION['success_message'] = "Software added successfully!";
        echo "<script>location.assign('view_all_software.php')</script>";
        exit();
        
    } catch(PDOException $e) {
        // ROLLBACKkkkk
        $conn->rollBack();
        
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            $error = "Software name already exists!";
        } else {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Software Management /</span> Add New Software
        </h4>

        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-xl">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Software Registration Form</h5>
                        <small class="text-muted float-end">Required fields are marked with *</small>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="softwareForm">
                            <!-- Software Name -->
                            <div class="mb-3">
                                <label class="form-label" for="name">Software Name *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       placeholder="e.g., Visual Studio Code, MATLAB, Photoshop" 
                                       required />
                            </div>
                            
                            <!-- Version -->
                            <div class="mb-3">
                                <label class="form-label" for="version">Version *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="version" 
                                       name="version" 
                                       placeholder="e.g., 1.88, R2024a, 2024.1" 
                                       required />
                                <div class="form-text">Enter the complete version number</div>
                            </div>
                            
                            <!-- License Type -->
                            <div class="mb-3">
                                <label class="form-label" for="license_type">License Type *</label>
                                <select class="form-select" id="license_type" name="license_type" required>
                                    <option value="">Select License Type</option>
                                    <option value="Free">Free</option>
                                    <option value="Commercial">Commercial</option>
                                    <option value="Educational">Educational</option>
                                    <option value="Open Source">Open Source</option>
                                    <option value="Trial">Trial</option>
                                    <option value="Subscription">Subscription</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="license_expiry">License Expiry Date</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="license_expiry" 
                                       name="license_expiry" />
                                <div class="form-text">Leave blank for perpetual/Free licenses</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-plus-circle"></i> Add Software
                            </button>
                            <a href="view_all_software.php" class="btn btn-secondary">
                                <i class="bx bx-arrow-back"></i> Back to List
                            </a>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-xl">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Software Examples</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="bx bx-info-circle"></i> Existing Software:</h6>
                            <ul class="mb-0">
                                <li><strong>Visual Studio Code</strong> - Version 1.88 (Free)</li>
                                <li><strong>MATLAB</strong> - Version R2024a (Commercial - Expires 2025-12-31)</li>
                                <li><strong>IntelliJ IDEA</strong> - Version 2024.1 (Educational - Expires 2025-06-30)</li>
                                <li><strong>MySQL Workbench</strong> - Version 8.0.36 (Free)</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <h6><i class="bx bx-shield-alt"></i> License Guidelines:</h6>
                            <ul class="mb-0">
                                <li><strong>Free</strong> - No expiry needed</li>
                                <li><strong>Commercial</strong> - Set expiry date</li>
                                <li><strong>Educational</strong> - Academic licenses</li>
                                <li><strong>Open Source</strong> - Free with source code</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('softwareForm').addEventListener('submit', function(e) {
    var name = document.getElementById('name').value;
    var version = document.getElementById('version').value;
    var licenseType = document.getElementById('license_type').value;
    
    if (name.trim() === '') {
        e.preventDefault();
        alert('Software name is required!');
        return false;
    }
    
    if (version.trim() === '') {
        e.preventDefault();
        alert('Version is required!');
        return false;
    }
    
    if (licenseType === '') {
        e.preventDefault();
        alert('Please select a license type!');
        return false;
    }
    
    var expiryDate = document.getElementById('license_expiry').value;
    if (licenseType === 'Commercial' && !expiryDate) {
        if (!confirm('Commercial licenses usually have an expiry date. Continue without expiry date?')) {
            e.preventDefault();
            return false;
        }
    }
});
</script>

<style>
    .form-label {
        font-weight: 600;
        color: #566a7f;
    }
</style>

<?php
include("components/footer.php");
?>