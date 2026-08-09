<?php

include("components/header.php");
require_once 'config/query.php';

$database = new Database();
$conn = $database->getConnection();

$message = '';
$error = '';

$labQuery = "SELECT lab_id, lab_name, location FROM LAB ORDER BY lab_name";
$labStmt = $conn->prepare($labQuery);
$labStmt->execute();
$labs = $labStmt->fetchAll(PDO::FETCH_ASSOC);

$techQuery = "SELECT tech_id, name, shift FROM IT_TECHNICIAN ORDER BY name";
$techStmt = $conn->prepare($techQuery);
$techStmt->execute();
$technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pc_number = strtoupper(trim($_POST['pc_number']));
    $lab_id = $_POST['lab_id'];
    $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
    $assigned_by = !empty($_POST['assigned_by']) ? $_POST['assigned_by'] : null;
    $assignment_start = !empty($_POST['assignment_start']) ? $_POST['assignment_start'] : null;
    $assignment_end = !empty($_POST['assignment_end']) ? $_POST['assignment_end'] : null;
    $status = $_POST['status'];
    $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $last_serviced = !empty($_POST['last_serviced']) ? $_POST['last_serviced'] : null;
    
    try {
    // START TRANSACTION
    $conn->beginTransaction();
    
    $query = "INSERT INTO PC (pc_number, lab_id, assigned_to, assigned_by, 
              assignment_start, assignment_end, status, purchase_date, last_serviced) 
              VALUES (:pc_number, :lab_id, :assigned_to, :assigned_by, 
              :assignment_start, :assignment_end, :status, :purchase_date, :last_serviced)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':pc_number', $pc_number);
    $stmt->bindParam(':lab_id', $lab_id);
    $stmt->bindParam(':assigned_to', $assigned_to);
    $stmt->bindParam(':assigned_by', $assigned_by);
    $stmt->bindParam(':assignment_start', $assignment_start);
    $stmt->bindParam(':assignment_end', $assignment_end);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':purchase_date', $purchase_date);
    $stmt->bindParam(':last_serviced', $last_serviced);
    
    $stmt->execute();
    
    // COMMIT TRANSACTION (saves everything)
    $conn->commit();
    
    $_SESSION['success_message'] = "PC added successfully!";
    echo "<script>location.assign('view_all_pcs.php')</script>";
    exit();
    
} catch(PDOException $e) {
    // ROLLBACK TRANSACTION 
    $conn->rollBack();
    
    if ($e->errorInfo[1] == 1062 || strpos($e->getMessage(), 'duplicate') !== false) {
        $error = "PC number already exists! Please use a unique PC number.";
    } else {
        $error = "Error: " . $e->getMessage();
    }
}
}
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">PC Management /</span> Add New PC
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
                        <h5 class="mb-0">PC Registration Form</h5>
                        <small class="text-muted float-end">Required fields are marked with *</small>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="pcForm">
                            <!-- PC Number -->
                            <div class="mb-3">
                                <label class="form-label" for="pc_number">PC Number *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="pc_number" 
                                       name="pc_number" 
                                       placeholder="e.g., PC-A01, LAB1-PC01, CS-101" 
                                       required />
                                <div class="form-text">Enter a unique identifier for this PC</div>
                            </div>
                            
                            <!-- Lab Selection -->
                            <div class="mb-3">
                                <label class="form-label" for="lab_id">Lab *</label>
                                <select class="form-select" id="lab_id" name="lab_id" required>
                                    <option value="">Select Lab</option>
                                    <?php foreach($labs as $lab): ?>
                                        <option value="<?php echo $lab['lab_id']; ?>">
                                            <?php echo htmlspecialchars($lab['lab_name']); ?> - <?php echo $lab['location']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- PC Status -->
                            <div class="mb-3">
                                <label class="form-label" for="status">PC Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Available">Available</option>
                                    <option value="Assigned">Assigned</option>
                                    <option value="Under Maintenance">Under Maintenance</option>
                                    <option value="Retired">Retired</option>
                                </select>
                            </div>
                            
                            <hr>
                            <h6 class="mb-3">Assignment Details (Optional)</h6>
                            
                            <!-- Assigned To (Student) -->
                            <div class="mb-3">
                                <label class="form-label" for="assigned_to">Assigned To (Student ID)</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="assigned_to" 
                                       name="assigned_to" 
                                       placeholder="Enter Student ID" />
                                <div class="form-text">Leave blank if not assigned</div>
                            </div>
                            
                            <!-- Assigned By (Technician) -->
                            <div class="mb-3">
                                <label class="form-label" for="assigned_by">Assigned By (Technician)</label>
                                <select class="form-select" id="assigned_by" name="assigned_by">
                                    <option value="">Select Technician</option>
                                    <?php foreach($technicians as $tech): ?>
                                        <option value="<?php echo $tech['tech_id']; ?>">
                                            <?php echo htmlspecialchars($tech['name']); ?> (<?php echo $tech['shift']; ?> Shift)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Assignment Start Date -->
                            <div class="mb-3">
                                <label class="form-label" for="assignment_start">Assignment Start Date</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="assignment_start" 
                                       name="assignment_start" />
                            </div>
                            
                            <!-- Assignment End Date -->
                            <div class="mb-3">
                                <label class="form-label" for="assignment_end">Assignment End Date</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="assignment_end" 
                                       name="assignment_end" />
                            </div>
                            
                            <hr>
                            <h6 class="mb-3">Maintenance Details (Optional)</h6>
                            
                            <!-- Purchase Date -->
                            <div class="mb-3">
                                <label class="form-label" for="purchase_date">Purchase Date</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="purchase_date" 
                                       name="purchase_date" />
                            </div>
                            
                            <!-- Last Serviced Date -->
                            <div class="mb-3">
                                <label class="form-label" for="last_serviced">Last Serviced Date</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="last_serviced" 
                                       name="last_serviced" />
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-plus-circle"></i> Add PC
                            </button>
                            <a href="view_all_pcs.php" class="btn btn-secondary">
                                <i class="bx bx-arrow-back"></i> Back to List
                            </a>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Info Card -->
            <div class="col-xl">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Important Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="bx bx-info-circle"></i> PC Naming Convention:</h6>
                            <ul class="mb-0">
                                <li>Use format: <strong>LAB-PC-NUMBER</strong> (e.g., A-01, B-15)</li>
                                <li>Or: <strong>DEPARTMENT-PC-NUMBER</strong> (e.g., CS-101)</li>
                                <li>Keep it unique and consistent</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <h6><i class="bx bx-shield-alt"></i> Status Guidelines:</h6>
                            <ul class="mb-0">
                                <li><strong>Available</strong> - Ready for assignment</li>
                                <li><strong>Assigned</strong> - Currently in use by student</li>
                                <li><strong>Under Maintenance</strong> - Being repaired</li>
                                <li><strong>Retired</strong> - Permanently decommissioned</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-success mt-3">
                            <h6><i class="bx bx-calendar"></i> Date Information:</h6>
                            <ul class="mb-0">
                                <li>Purchase date helps track warranty</li>
                                <li>Last serviced for maintenance schedule</li>
                                <li>Assignment dates for student allocation</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
document.getElementById('pcForm').addEventListener('submit', function(e) {
    var pcNumber = document.getElementById('pc_number').value;
    var labId = document.getElementById('lab_id').value;
    var status = document.getElementById('status').value;
    
    // Validate PC number format (optional - customize as needed)
    if (pcNumber.trim() === '') {
        e.preventDefault();
        alert('PC number is required!');
        return false;
    }
    
    // Validate lab selection
    if (labId === '') {
        e.preventDefault();
        alert('Please select a lab!');
        return false;
    }
    
    // Validate status
    if (status === '') {
        e.preventDefault();
        alert('Please select a status!');
        return false;
    }
    
    // Validate assignment dates if assigned
    var assignedTo = document.getElementById('assigned_to').value;
    var assignedBy = document.getElementById('assigned_by').value;
    var startDate = document.getElementById('assignment_start').value;
    var endDate = document.getElementById('assignment_end').value;
    
    if (assignedTo && !startDate) {
        e.preventDefault();
        alert('Please set assignment start date when assigning to a student!');
        return false;
    }
    
    if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
        e.preventDefault();
        alert('Assignment end date must be after start date!');
        return false;
    }
    
    // Validate dates
    var purchaseDate = document.getElementById('purchase_date').value;
    var lastServiced = document.getElementById('last_serviced').value;
    
    if (purchaseDate && lastServiced && new Date(lastServiced) < new Date(purchaseDate)) {
        e.preventDefault();
        alert('Last serviced date cannot be before purchase date!');
        return false;
    }
});

// Auto-generate PC number suggestion based on lab selection
document.getElementById('lab_id').addEventListener('change', function() {
    var labSelect = this;
    var labText = labSelect.options[labSelect.selectedIndex]?.text;
    var pcNumberField = document.getElementById('pc_number');
    
    if (labText && !pcNumberField.value) {
        // Extract lab name from the text (e.g., "Lab A - Block 1" -> "LabA")
        var labName = labText.split(' - ')[0].replace(/\s/g, '');
        // Suggest a PC number (user can modify)
        pcNumberField.placeholder = `e.g., ${labName}-PC01`;
    }
});

// Show/hide assignment fields based on status
document.getElementById('status').addEventListener('change', function() {
    var status = this.value;
    var assignedTo = document.getElementById('assigned_to');
    var assignedBy = document.getElementById('assigned_by');
    
    if (status === 'Assigned') {
        assignedTo.required = false;
        assignedBy.required = false;
    } else {
        assignedTo.required = false;
        assignedBy.required = false;
    }
});
</script>

<style>
    .form-label {
        font-weight: 600;
        color: #566a7f;
    }
    hr {
        margin: 20px 0;
        border-top: 2px dashed #e9ecef;
    }
    h6 {
        color: #696cff;
    }
</style>

<?php
include("components/footer.php");
?>