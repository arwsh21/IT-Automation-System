<?php
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'technician') {
    header("Location: login.php");
    exit();
}
include("components/header.php");
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4>Technician Dashboard</h4>
        <p>Welcome, <?php echo $_SESSION['user_name']; ?>!</p>
    </div>
</div>

<?php include("components/footer.php"); ?>