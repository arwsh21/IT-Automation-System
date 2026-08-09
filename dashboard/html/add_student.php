<?php

include("components/header.php");
require_once 'config/query.php';

$database = new Database();
$conn = $database->getConnection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $batch = $_POST['batch'];
    $department = $_POST['department'];
    
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        try {
            // Store password as plain text - NO HASHING
            $query = "INSERT INTO student (name, email, password, batch, department) 
                      VALUES (:name, :email, :password, :batch, :department)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':name', $full_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);  // Plain text
            $stmt->bindParam(':batch', $batch);
            $stmt->bindParam(':department', $department);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Student added successfully!";
                header("Location: view_all_students.php");
                exit();
            }
        } catch(PDOException $e) {
            $error = "Email already exists!";
        }
    }
}
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Students /</span> Add New Student
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
                        <h5 class="mb-0">Student Registration Form</h5>
                        <small class="text-muted float-end">Required fields are marked with *</small>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="studentForm">
                            <div class="mb-3">
                                <label class="form-label" for="full_name">Full Name *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="full_name" 
                                       name="full_name" 
                                       placeholder="John Doe" 
                                       required />
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="email">Email *</label>
                                <div class="input-group input-group-merge">
                                    <input type="email"
                                           id="email"
                                           name="email"
                                           class="form-control"
                                           placeholder="john.doe"
                                           required />
                                    <span class="input-group-text">@example.com</span>
                                </div>
                                <div class="form-text">Use your university email address</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="password">Password *</label>
                                <input type="password"
                                       id="password"
                                       name="password"
                                       class="form-control"
                                       placeholder="••••••"
                                       required />
                                <div class="form-text">Minimum 6 characters</div>
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
                            
                            <div class="mb-3">
                                <label class="form-label" for="batch">Batch/Year *</label>
                                <select class="form-select" id="batch" name="batch" required>
                                    <option value="">Select Batch</option>
                                    <option value="2021">2021</option>
                                    <option value="2022">2022</option>
                                    <option value="2023">2023</option>
                                    <option value="2024">2024</option>
                                    <option value="2025">2025</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="department">Department *</label>
                                <select class="form-select" id="department" name="department" required>
                                    <option value="">Select Department</option>
                                    <option value="CS">Computer Science (CS)</option>
                                    <option value="SE">Software Engineering (SE)</option>
                                    <option value="AI">Artificial Intelligence (AI)</option>
                                    <option value="DS">Data Science (DS)</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Register Student
                            </button>
                            <a href="view_students.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to List
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
                            <h6><i class="fas fa-info-circle"></i> Registration Guidelines:</h6>
                            <ul class="mb-0">
                                <li>Use your official university email</li>
                                <li>Create a strong password</li>
                                <li>Select correct batch and department</li>
                                <li>You will receive a confirmation email</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <h6><i class="fas fa-shield-alt"></i> Privacy Note:</h6>
                            <p class="mb-0">Your information is protected and will only be used for lab management purposes.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.getElementById('studentForm').addEventListener('submit', function(e) {
    var password = document.getElementById('password').value;
    var confirm = document.getElementById('confirm_password').value;
    
    if (password != confirm) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return false;
    }
});
</script>


          
<?php
include("components/footer.php");
?>