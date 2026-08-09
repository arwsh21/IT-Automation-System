<?php
session_start();
include("components/header.php");
require_once 'config/query.php';

$database = new Database();
$conn = $database->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
$studentId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$studentName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';

// Redirect if not student
if ($userRole != 'student') {
    header("Location: dashboard.php");
    exit();
}

$error = '';

//  available PCs 
$pcQuery = "SELECT pc_id, pc_number, lab_id, status FROM PC WHERE status IN ('Available', 'Assigned') ORDER BY pc_number";
$pcStmt = $conn->prepare($pcQuery);
$pcStmt->execute();
$pcs = $pcStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $raised_by = $studentId;  
    $assigned_to = null;       // (admin will assign)
    $pc_id = !empty($_POST['pc_id']) ? $_POST['pc_id'] : null;
    $category = $_POST['category'];
    $description = trim($_POST['description']);
    $status = 'Open';
    
    try {
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
                     VALUES (:ticket_id, NULL, NULL, :status, 'Ticket raised by student', NOW())";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bindParam(':ticket_id', $ticket_id);
        $logStmt->bindParam(':status', $status);
        $logStmt->execute();
        
        $conn->commit();
        
        $_SESSION['success_message'] = "Ticket #$ticket_id raised successfully!";
        echo "<script>location.assign('my_open_tickets.php')</script>";
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
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-xl">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Raise a Support Ticket</h5>
                        <small class="text-muted">Student: <?php echo htmlspecialchars($studentName); ?></small>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="ticketForm">
                            
                            <!-- PC Selection (Optional) -->
                            <div class="mb-3">
                                <label class="form-label" for="pc_id">PC Number (Optional)</label>
                                <select class="form-select" id="pc_id" name="pc_id">
                                    <option value="">Select PC (if related to a specific computer)</option>
                                    <?php foreach($pcs as $pc): ?>
                                        <option value="<?php echo $pc['pc_id']; ?>">
                                            <?php echo $pc['pc_number']; ?> - Status: <?php echo $pc['status']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Only select if the issue is related to a specific PC</div>
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
                            
                            <!-- Description -->
                            <div class="mb-3">
                                <label class="form-label" for="description">Issue Description *</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="5" 
                                          placeholder="Please describe your issue in detail..."
                                          required></textarea>
                                <div class="form-text">Provide as much detail as possible</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-plus-circle"></i> Raise Ticket
                            </button>
                            <a href="my_open_tickets.php" class="btn btn-secondary">
                                <i class="bx bx-arrow-back"></i> Back to My Tickets
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
                                <li>Restart your PC first</li>
                                <li>Note down any error messages</li>
                                <li>Identify the PC number if applicable</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <h6><i class="bx bx-time"></i> What happens next?</h6>
                            <ul class="mb-0">
                                <li>Technician will be assigned to your ticket</li>
                                <li>You can track status in "My Open Tickets"</li>
                                <li>You'll be notified when resolved</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('ticketForm').addEventListener('submit', function(e) {
    var category = document.getElementById('category').value;
    var description = document.getElementById('description').value;
    
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
</script>

<?php include("components/footer.php"); ?>