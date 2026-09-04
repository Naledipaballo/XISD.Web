<?php
session_start();
include("dbconnect.php");

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>
            alert('Please login first.');
            window.location.href = 'login.html';
          </script>";
    exit();
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Customer';

// 2. Fetch dynamic dashboard metrics for this user
// Total items in cart/orders
$cart_count = 0;
$cart_total = 0.00;

$cart_query = "SELECT SUM(quantity) as total_items, SUM(price * quantity) as total_spent FROM cart WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $cart_query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $cart_count = $row['total_items'] ?? 0;
        $cart_total = $row['total_spent'] ?? 0.00;
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Pill Point Pharmacy</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Sidebar -->
<div id="sidebar" class="sidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="aboutus.html">About Us</a>
    <a href="medicines.php">Shop Medication</a>
    <a href="cart.php">My Cart</a>
    <a href="prescription.php">Prescription</a>
    <a href="reminders.php">Reminders</a>
    <a href="storelocator.php">Store Locator</a>
    <a href="trackorder.php">Track Order</a>
    <a href="myaccount.php">My Account</a>
    <a href="logout.php">Logout</a>
</div>

<!-- Header -->
<div class="header">
    <span class="menu-btn" onclick="openNav()">&#9776;</span>
    <a href="myaccount.php" class="profile"> 
        <img src="photo.jpeg" alt="profile">
    </a>
</div>

<!-- Main Content -->
<div id="main">

    <h1>Dashboard</h1>

    <div class="top-cards">
        <div class="top-card">
            <h2><?php echo $cart_count; ?></h2>
            <p>Cart Items</p>
        </div>

        <div class="top-card">
            <h2>0</h2>
            <p>Prescriptions</p>
        </div>

        <div class="top-card">
            <h2>0</h2>
            <p>Reminders</p>
        </div>

        <div class="top-card">
            <h2>R<?php echo number_format($cart_total, 2); ?></h2>
            <p>Total Cart Value</p>
        </div>
    </div>

    <div class="welcome-card">
        <h2>Welcome back, <?php echo htmlspecialchars($username); ?>!</h2>
        <p>Your trusted online pharmacy — Pill Point Delivery.</p>
    </div>

    <div class="cards">
        <div class="card">
            <h3>Shop Medication</h3>
            <a href="medicines.php">Open</a>
        </div>

        <div class="card">
            <h3>View Shopping Cart</h3>
            <a href="cart.php">View Cart</a>
        </div>

        <div class="card">
            <h3>Upload Prescription</h3>
            <a href="prescription.html">Upload</a>
        </div>

        <div class="card">
            <h3>Store Locator</h3>
            <a href="storelocator.html">Locate</a>
        </div>
    </div>

</div>

<script src="script.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>