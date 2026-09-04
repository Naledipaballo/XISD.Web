<?php
session_start();
include("dbconnect.php");

// Restrict access: Require user to be logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>
            alert('Please login first.');
            window.location.href='login.html';
          </script>";
    exit();
}

// Restrict access: POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: shop.html");
    exit();
}

// Capture and sanitize input values
$user_id  = $_SESSION['user_id'];
$medicine = trim($_POST['medicine_name'] ?? '');
$price    = floatval($_POST['price'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);

// Ensure a valid minimum quantity
if ($quantity < 1) {
    $quantity = 1;
}

// 1. Check if the medicine is already in the user's cart
$check_query = "SELECT id, quantity FROM cart WHERE user_id = ? AND medicine_name = ?";
$check_stmt  = mysqli_prepare($conn, $check_query);

if (!$check_stmt) {
    die("Database Error (Check Query Failed): " . mysqli_error($conn));
}

mysqli_stmt_bind_param($check_stmt, "is", $user_id, $medicine);
mysqli_stmt_execute($check_stmt);
$result = mysqli_stmt_get_result($check_stmt);

// 2. If item exists, update its quantity; otherwise, insert a new record
if ($result && mysqli_num_rows($result) > 0) {
    $cart_item    = mysqli_fetch_assoc($result);
    $new_quantity = $cart_item['quantity'] + $quantity;

    $update_query = "UPDATE cart SET quantity = ?, price = ? WHERE id = ?";
    $update_stmt  = mysqli_prepare($conn, $update_query);

    if (!$update_stmt) {
        die("Database Error (Update Query Failed): " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($update_stmt, "idi", $new_quantity, $price, $cart_item['id']);
    mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);
} else {
    $insert_query = "INSERT INTO cart (user_id, medicine_name, price, quantity) VALUES (?, ?, ?, ?)";
    $insert_stmt  = mysqli_prepare($conn, $insert_query);

    if (!$insert_stmt) {
        die("Database Error (Insert Query Failed): " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($insert_stmt, "isdi", $user_id, $medicine, $price, $quantity);
    mysqli_stmt_execute($insert_stmt);
    mysqli_stmt_close($insert_stmt);
}

mysqli_stmt_close($check_stmt);
mysqli_close($conn);

// Redirect to dynamic cart view
header("Location: cart.php");
exit();
?>