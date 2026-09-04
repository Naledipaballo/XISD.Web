<?php
session_start();
include("dbconnect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Fetch order details
$sql = "SELECT o.id, o.total_amount, o.order_date, o.delivery_address, p.payment_method 
        FROM orders o 
        LEFT JOIN payments p ON o.id = p.order_id 
        WHERE o.id = ? AND o.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$order) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Successful - Pill Point</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="header">
    <span class="menu-btn" onclick="openNav()">&#9776;</span>
    <h1 class="header-title">Pill Point Delivery</h1>
</div>

<div class="container" style="padding: 30px; text-align: center; max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h2 style="color: #28a745;">✔ Order Placed Successfully!</h2>
    <p>Thank you for your purchase. Your order number is <strong>#<?php echo $order['id']; ?></strong>.</p>
    
    <div style="text-align: left; background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <p><strong>Total Paid:</strong> R<?php echo number_format($order['total_amount'], 2); ?></p>
        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></p>
        <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
        <p><strong>Date:</strong> <?php echo $order['order_date']; ?></p>
    </div>

    <a href="trackorder.php" class="btn" style="text-decoration:none; display:inline-block; margin-right:10px;">Track Delivery</a>
    <a href="medicines.php" class="btn" style="background:#6c757d; text-decoration:none; display:inline-block;">Continue Shopping</a>
</div>

</body>
</html>
<?php mysqli_close($conn); ?>