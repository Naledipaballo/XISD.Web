<?php
session_start();
include("dbconnect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch delivery status for user's orders
$query = "SELECT d.id AS delivery_id, d.order_id, d.delivery_address, d.delivery_status, d.estimated_delivery, o.total_amount, o.order_date 
          FROM deliveries d 
          JOIN orders o ON d.order_id = o.id 
          WHERE o.user_id = ? 
          ORDER BY o.order_date DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$deliveries = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Track Orders - Pill Point Delivery</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .track-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
        .track-table th, .track-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .track-table th { background-color: #007bff; color: white; }
        .status-pill { background: #e2e3e5; color: #383d41; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>

<div class="header">
    <span class="menu-btn" onclick="openNav()">&#9776;</span>
    <h1 class="header-title">Pill Point Delivery</h1>
</div>

<div id="sidebar" class="sidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="medicines.php">Shop Medication</a>
    <a href="prescription.php">Prescription</a>
    <a href="reminders.php">Reminders</a>
    <a href="trackorder.php">Track Orders</a>
    <a href="storelocator.php">Store Locator</a>
    <a href="cart.php">Cart</a>
    <a href="myaccount.php">My Account</a>
    <a href="contact.php">Contact Us</a>
    <a href="logout.php">Logout</a>
</div>

<div class="container" style="padding: 20px;">
    <h2>Track Your Order Deliveries</h2>

    <table class="track-table">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Order Date</th>
                <th>Delivery Address</th>
                <th>Status</th>
                <th>Estimated Delivery</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($deliveries) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($deliveries)): ?>
                    <tr>
                        <td>#<?php echo $row['order_id']; ?></td>
                        <td><?php echo $row['order_date']; ?></td>
                        <td><?php echo htmlspecialchars($row['delivery_address']); ?></td>
                        <td><span class="status-pill"><?php echo htmlspecialchars($row['delivery_status']); ?></span></td>
                        <td><?php echo $row['estimated_delivery'] ? $row['estimated_delivery'] : 'Pending'; ?></td>
                        <td>R<?php echo number_format($row['total_amount'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center;">No active order deliveries found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="script.js"></script>
</body>
</html>
<?php 
mysqli_stmt_close($stmt);
mysqli_close($conn); 
?>