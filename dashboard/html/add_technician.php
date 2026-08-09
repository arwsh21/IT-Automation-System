<?php
session_start();
include("components/header.php");
require_once 'config/query.php';

$database = new Database();
$conn = $database->getConnection();

$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $employee_no = trim($_POST['employee_no']);
    $shift = $_POST['shift'];
    
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 4) {
        $error = "Password must be at least 4 characters!";
    } else {
        try {
            $query = "INSERT INTO it_technician (name, email, password, employee_no, shift) 
                      VALUES (:name, :email, :password, :employee_no, :shift)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':employee_no', $employee_no);
            $stmt->bindParam(':shift', $shift);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Technician added successfully!";
                echo "<script>location.assign('view_all_technicians.php')</script>";
                exit();
            }
        } catch(PDOException $e) {
            if (strpos($e->getMessage(), 'duplicate') !== false) {
                $error = "Email or Employee Number already exists!";
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Admin /</span> Add New Technician
        </h4>

        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-xl">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Technician Registration Form</h5>
                        <small class="text-muted float-end">Required fields are marked with *</small>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="technicianForm">
                            
                            <div class="mb-3">
                                <label class="form-label" for="name">Full Name *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       placeholder="e.g., Ali Hassan" 
                                       required />
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="email">Email *</label>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       class="form-control"
                                       placeholder="technician@lab.com"
                                       required />
                                <div class="form-text">Must be a valid email address</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="employee_no">Employee Number *</label>
                                <input type="text"
                                       id="employee_no"
                                       name="employee_no"
                                       class="form-control"
                                       placeholder="e.g., TECH001"
                                       required />
                                <div class="form-text">Unique employee identifier</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="shift">Shift *</label>
                                <select class="form-select" id="shift" name="shift" required>
                                    <option value="">Select Shift</option>
                                    <option value="Morning">Morning (9 AM - 5 PM)</option>
                                    <option value="Evening">Evening (2 PM - 10 PM)</option>
                                    <option value="Night">Night (9 PM - 5 AM)</option>
                                </select>
                            </div>
                            
                            <hr>
                            <h6 class="text-muted">Password Setup</h6>
                            
                            <div class="mb-3">
                                <label class="form-label" for="password">Password *</label>
                                <input type="password"
                                       id="password"
                                       name="password"
                                       class="form-control"
                                       placeholder="••••••"
                                       required />
                                <div class="form-text">Minimum 4 characters</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="confirm_password">Confirm Password *</label>
                                <input type="password"
                                       id="confirm_password"
                                       name="confirm_password"
                                       class="form-control"
                                       placeholder="••••••"
                                       required />
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-user-plus"></i> Add Technician
                            </button>
                            <a href="view_all_technicians.php" class="btn btn-secondary">
                                <i class="bx bx-arrow-back"></i> Back to Technicians
                            </a>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Info Card -->
            <div class="col-xl">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Shift Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-primary">
                            <h6><i class="bx bx-sun"></i> Morning Shift</h6>
                            <p class="mb-0">9:00 AM - 5:00 PM<br>Standard working hours</p>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <h6><i class="bx bx-moon"></i> Evening Shift</h6>
                            <p class="mb-0">2:00 PM - 10:00 PM<br>For late afternoon support</p>
                        </div>
                        
                        <div class="alert alert-dark mt-3">
                            <h6><i class="bx bx-bed"></i> Night Shift</h6>
                            <p class="mb-0">9:00 PM - 5:00 AM<br>24/7 lab support coverage</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('technicianForm').addEventListener('submit', function(e) {
    var password = document.getElementById('password').value;
    var confirm = document.getElementById('confirm_password').value;
    var name = document.getElementById('name').value;
    var email = document.getElementById('email').value;
    var empNo = document.getElementById('employee_no').value;
    var shift = document.getElementById('shift').value;
    
    if (name.trim() === '') {
        e.preventDefault();
        alert('Please enter technician name!');
        return false;
    }
    
    if (email.trim() === '') {
        e.preventDefault();
        alert('Please enter email!');
        return false;
    }
    
    if (empNo.trim() === '') {
        e.preventDefault();
        alert('Please enter employee number!');
        return false;
    }
    
    if (shift === '') {
        e.preventDefault();
        alert('Please select shift!');
        return false;
    }
    
    if (password != confirm) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
    
    if (password.length < 4) {
        e.preventDefault();
        alert('Password must be at least 4 characters long!');
        return false;
    }
    
    return true;
});
</script>

<style>
    .form-label {
        font-weight: 600;
        color: #566a7f;
    }
</style>

<?php include("components/footer.php"); ?>