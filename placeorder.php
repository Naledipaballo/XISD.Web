<?php
session_start();
include("dbconnect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cart.php");
    exit();
}

$user_id          = $_SESSION['user_id'];
$delivery_address = trim($_POST['delivery_address'] ?? '');
$payment_method   = trim($_POST['payment_method'] ?? '');

if (empty($delivery_address) || empty($payment_method)) {
    echo "<script>alert('Please complete all shipping and payment details.'); window.location.href = 'checkout.php';</script>";
    exit();
}

// Fetch active cart items
$cart_sql = "SELECT c.medicine_name, c.price, c.quantity, m.id AS medicine_id 
            FROM cart c 
            LEFT JOIN medicines m ON c.medicine_name = m.medicine_name 
            WHERE c.user_id = ?";
$cart_stmt = mysqli_prepare($conn, $cart_sql);
mysqli_stmt_bind_param($cart_stmt, "i", $user_id);
mysqli_stmt_execute($cart_stmt);
$cart_result = mysqli_stmt_get_result($cart_stmt);

$total_amount = 0;
$cart_items = [];

while ($row = mysqli_fetch_assoc($cart_result)) {
    $total_amount += ($row['price'] * $row['quantity']);
    $cart_items[] = $row;
}
mysqli_stmt_close($cart_stmt);

if (empty($cart_items)) {
    echo "<script>alert('Your cart is empty.'); window.location.href = 'medicines.php';</script>";
    exit();
}

// Database Transaction
mysqli_begin_transaction($conn);

try {
    // 1. Insert into orders
    $order_sql = "INSERT INTO orders (user_id, total_amount, status, delivery_address) VALUES (?, ?, 'Paid', ?)";
    $o_stmt = mysqli_prepare($conn, $order_sql);
    mysqli_stmt_bind_param($o_stmt, "ids", $user_id, $total_amount, $delivery_address);
    mysqli_stmt_execute($o_stmt);
    $order_id = mysqli_insert_id($conn);
    mysqli_stmt_close($o_stmt);

    // 2. Insert into order_items
    $item_sql = "INSERT INTO order_items (order_id, medicine_id, quantity, price) VALUES (?, ?, ?, ?)";
    $i_stmt = mysqli_prepare($conn, $item_sql);
    foreach ($cart_items as $item) {
        $med_id = $item['medicine_id'] ?? 0;
        mysqli_stmt_bind_param($i_stmt, "iiid", $order_id, $med_id, $item['quantity'], $item['price']);
        mysqli_stmt_execute($i_stmt);
    }
    mysqli_stmt_close($i_stmt);

    // 3. Insert into payments
    $pay_sql = "INSERT INTO payments (order_id, payment_method, amount, payment_status) VALUES (?, ?, ?, 'Completed')";
    $p_stmt = mysqli_prepare($conn, $pay_sql);
    mysqli_stmt_bind_param($p_stmt, "isd", $order_id, $payment_method, $total_amount);
    mysqli_stmt_execute($p_stmt);
    mysqli_stmt_close($p_stmt);

    // 4. Insert into deliveries
    $del_sql = "INSERT INTO deliveries (order_id, delivery_address, delivery_status, estimated_delivery) VALUES (?, ?, 'Preparing', DATE_ADD(NOW(), INTERVAL 2 DAY))";
    $d_stmt = mysqli_prepare($conn, $del_sql);
    mysqli_stmt_bind_param($d_stmt, "is", $order_id, $delivery_address);
    mysqli_stmt_execute($d_stmt);
    mysqli_stmt_close($d_stmt);

    // 5. Clear cart
    $clear_sql = "DELETE FROM cart WHERE user_id = ?";
    $c_stmt = mysqli_prepare($conn, $clear_sql);
    mysqli_stmt_bind_param($c_stmt, "i", $user_id);
    mysqli_stmt_execute($c_stmt);
    mysqli_stmt_close($c_stmt);

    mysqli_commit($conn);
    header("Location: ordersuccess.php?order_id=" . $order_id);
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "<script>alert('Failed to process order: " . addslashes($e->getMessage()) . "'); window.location.href = 'checkout.php';</script>";
    exit();
}
?>