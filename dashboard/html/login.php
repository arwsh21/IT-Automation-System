<?php
session_start();  
require_once 'config/query.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $adminQuery = "SELECT admin_id as id, name, email, 'admin' as role, password FROM admin WHERE email = :email";
        $adminStmt = $conn->prepare($adminQuery);
        $adminStmt->bindParam(':email', $email);
        $adminStmt->execute();
        $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && $password === $admin['password']) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_name'] = $admin['name'];
            $_SESSION['user_email'] = $admin['email'];
            $_SESSION['role'] = 'admin';
            header("Location: dashboard.php");
            exit();
        }
        
        $techQuery = "SELECT tech_id as id, name, email, 'technician' as role, password FROM it_technician WHERE email = :email";
        $techStmt = $conn->prepare($techQuery);
        $techStmt->bindParam(':email', $email);
        $techStmt->execute();
        $tech = $techStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tech && $password === $tech['password']) {
            $_SESSION['user_id'] = $tech['id'];
            $_SESSION['user_name'] = $tech['name'];
            $_SESSION['user_email'] = $tech['email'];
            $_SESSION['role'] = 'technician';
            header("Location: dashboard.php");
            exit();
        }
        
        $studentQuery = "SELECT student_id as id, name, email, 'student' as role, password FROM student WHERE email = :email";
        $studentStmt = $conn->prepare($studentQuery);
        $studentStmt->bindParam(':email', $email);
        $studentStmt->execute();
        $student = $studentStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($student && $password === $student['password']) {  
            $_SESSION['user_id'] = $student['id'];
            $_SESSION['user_name'] = $student['name'];
            $_SESSION['user_email'] = $student['email'];
            $_SESSION['role'] = 'student';
            header("Location: student_dashboard.php");
            exit();
        }
        
        $error = "Invalid email or password!";
        
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IT Automation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header i {
            font-size: 60px;
            color: #667eea;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-login:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-server"></i>
            <h3 class="mt-3">IT Automation System</h3>
            <p class="text-muted">Login to your account</p>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" name="email" required placeholder="Enter your email">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" required placeholder="Enter your password">
            </div>
            
            <button type="submit" class="btn btn-primary btn-login w-100">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <hr class="my-4">
        
        <div class="text-center">
            <small class="text-muted">Demo Credentials:</small><br>
            <small>Admin: admin@lab.com / admin123</small><br>
            <small>Technician: tech@lab.com / tech123</small><br>
            <small>Student: student@lab.com / student123</small>
        </div>
    </div>
</body>
</html>