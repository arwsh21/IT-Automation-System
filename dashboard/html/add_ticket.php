<?php
include("components/header.php");
require_once 'config/query.php';

$database = new Database();
$conn = $database->getConnection();

$message = '';
$error = '';

$studentQuery = "SELECT student_id, name, email, department FROM STUDENT ORDER BY name";
$studentStmt = $conn->prepare($studentQuery);
$studentStmt->execute();
$students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

$pcQuery = "SELECT pc_id, pc_number, lab_id, status FROM PC WHERE status IN ('Available', 'Assigned') ORDER BY pc_number";
$pcStmt = $conn->prepare($pcQuery);
$pcStmt->execute();
$pcs = $pcStmt->fetchAll(PDO::FETCH_ASSOC);

$techQuery = "SELECT tech_id, name FROM IT_TECHNICIAN ORDER BY name";
$techStmt = $conn->prepare($techQuery);
$techStmt->execute();
$technicians = $techStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $raised_by = $_POST['raised_by'];
    $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
    $pc_id = !empty($_POST['pc_id']) ? $_POST['pc_id'] : null;
    $category = $_POST['category'];
    $description = trim($_POST['description']);
    $status = 'Open';
    
    try {
    // START TRANSACTION
    $conn->beginTransaction();
    
    $query = "INSERT INTO TICKET (raised_by, assigned_to, pc_id, category, description, status, created_at) 
              VALUES (:raised_by, :assigned_to, :pc_id, :category, :description, :status, NOW())";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':raised_by', $raised_by);
    $stmt->bindParam(':assigned_to', $assigned_to);
    $stmt->bindParam(':pc_id', $pc_id);
    $stmt->bindParam(':category', $category);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':status', $status);
    
    $stmt->execute();
    $ticket_id = $conn->lastInsertId();
    
    $logQuery = "INSERT INTO TICKET_LOG (ticket_id, updated_by, old_status, new_status, note, updated_at) 
                 VALUES (:ticket_id, :assigned_to, NULL, :status, 'Ticket raised by student', NOW())";
    $logStmt = $conn->prepare($logQuery);
    $logStmt->bindParam(':ticket_id', $ticket_id);
    $logStmt->bindParam(':assigned_to', $assigned_to);
    $logStmt->bindParam(':status', $status);
    $logStmt->execute();
    
    // COMMIT TRANSACTION (saves both ticket AND log hehe)
    $conn->commit();
    
    $_SESSION['success_message'] = "Ticket #$ticket_id raised successfully!";
    echo "<script>location.assign('view_all_tickets.php')</script>";
    exit();
    
} catch(PDOException $e) {
    
    $conn->rollBack();
    $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Tickets /</span> Raise a New Ticket
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
                        <h5 class="mb-0">Raise a Support Ticket</h5>
                        <small class="text-muted float-end">Fill all required fields *</small>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="ticketForm">
                            <!-- Student (Raised By) -->
                            <div class="mb-3">
                                <label class="form-label" for="raised_by">Student Name *</label>
                                <select class="form-select" id="raised_by" name="raised_by" required>
                                    <option value="">Select Student</option>
                                    <?php foreach($students as $student): ?>
                                        <option value="<?php echo $student['student_id']; ?>">
                                            <?php echo htmlspecialchars($student['name']); ?> - <?php echo $student['email']; ?> (<?php echo $student['department']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- PC Selection -->
                            <div class="mb-3">
                                <label class="form-label" for="pc_id">PC Number (Optional)</label>
                                <select class="form-select" id="pc_id" name="pc_id">
                                    <option value="">Select PC (if applicable)</option>
                                    <?php foreach($pcs as $pc): ?>
                                        <option value="<?php echo $pc['pc_id']; ?>">
                                            <?php echo $pc['pc_number']; ?> - Status: <?php echo $pc['status']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Select the PC if the issue is related to a specific computer</div>
                            </div>
                            
                            <!-- Category -->
                            <div class="mb-3">
                                <label class="form-label" for="category">Issue Category *</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Hardware">Hardware Issue</option>
                                    <option value="Software">Software Issue</option>
                                    <option value="Network">Network Problem</option>
                                    <option value="Printer">Printer Issue</option>
                                    <option value="Account">Account Access</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <!-- Assign to Technician (Optional - Admin only) -->
                            <div class="mb-3">
                                <label class="form-label" for="assigned_to">Assign to Technician (Optional)</label>
                                <select class="form-select" id="assigned_to" name="assigned_to">
                                    <option value="">Unassigned (Admin will assign)</option>
                                    <?php foreach($technicians as $tech): ?>
                                        <option value="<?php echo $tech['tech_id']; ?>">
                                            <?php echo htmlspecialchars($tech['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Description -->
                            <div class="mb-3">
                                <label class="form-label" for="description">Issue Description *</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="5" 
                                          placeholder="Please describe your issue in detail..."
                                          required></textarea>
                                <div class="form-text">Provide as much detail as possible to help technicians resolve your issue quickly</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-plus-circle"></i> Raise Ticket
                            </button>
                            <a href="view_all_tickets.php" class="btn btn-secondary">
                                <i class="bx bx-arrow-back"></i> Back to Tickets
                            </a>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Info Card -->
            <div class="col-xl">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Ticket Guidelines</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="bx bx-info-circle"></i> Before Raising a Ticket:</h6>
                            <ul class="mb-0">
                                <li>Check if the issue persists after restart</li>
                                <li>Note down any error messages</li>
                                <li>Identify the specific PC number if applicable</li>
                                <li>Try basic troubleshooting first</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <h6><i class="bx bx-time"></i> Ticket Status Workflow:</h6>
                            <ul class="mb-0">
                                <li><strong>Open</strong> - Ticket created, waiting for assignment</li>
                                <li><strong>In Progress</strong> - Technician working on issue</li>
                                <li><strong>Resolved</strong> - Issue fixed, pending confirmation</li>
                                <li><strong>Closed</strong> - Ticket completed</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-success mt-3">
                            <h6><i class="bx bx-check-circle"></i> Response Time:</h6>
                            <p class="mb-0">Tickets are typically assigned within 24 hours. Urgent issues will be prioritized.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
document.getElementById('ticketForm').addEventListener('submit', function(e) {
    var raised_by = document.getElementById('raised_by').value;
    var category = document.getElementById('category').value;
    var description = document.getElementById('description').value;
    
    if (raised_by === '') {
        e.preventDefault();
        alert('Please select a student!');
        return false;
    }
    
    if (category === '') {
        e.preventDefault();
        alert('Please select an issue category!');
        return false;
    }
    
    if (description.trim() === '') {
        e.preventDefault();
        alert('Please describe your issue!');
        return false;
    }
    
    if (description.length < 10) {
        e.preventDefault();
        alert('Please provide more details (at least 10 characters)!');
        return false;
    }
    
    return true;
});

// Dynamic PC filtering based on category (optional)
document.getElementById('category').addEventListener('change', function() {
    var category = this.value;
    var pcSelect = document.getElementById('pc_id');
    var descriptionField = document.getElementById('description');
    
    if (category === 'Hardware') {
        pcSelect.required = false;
        descriptionField.placeholder = "Describe the hardware issue (e.g., PC not turning on, keyboard not working, screen flickering...)";
    } else if (category === 'Software') {
        pcSelect.required = false;
        descriptionField.placeholder = "Describe the software issue (e.g., application not opening, error message, license problem...)";
    } else if (category === 'Network') {
        pcSelect.required = false;
        descriptionField.placeholder = "Describe the network issue (e.g., no internet, slow connection, cannot access specific website...)";
    } else {
        pcSelect.required = false;
        descriptionField.placeholder = "Please describe your issue in detail...";
    }
});
</script>

<style>
    .form-label {
        font-weight: 600;
        color: #566a7f;
    }
    textarea {
        resize: vertical;
    }
</style>

<?php
include("components/footer.php");
?>