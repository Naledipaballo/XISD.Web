<?php
session_start();
include("dbconnect.php");

// 1. Verify user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo "<script>
            alert('Please login to proceed to checkout.');
            window.location.href = 'login.html';
          </script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = "";

// 2. Fetch customer default details for pre-filling the address form
$cust_sql = "SELECT first_name, last_name, email, phone, address FROM customers WHERE id = ?";
$cust_stmt = mysqli_prepare($conn, $cust_sql);
mysqli_stmt_bind_param($cust_stmt, "i", $user_id);
mysqli_stmt_execute($cust_stmt);
$user_info = mysqli_fetch_assoc(mysqli_stmt_get_result($cust_stmt));
mysqli_stmt_close($cust_stmt);

// 3. Fetch active cart items
$cart_sql = "SELECT c.id AS cart_id, c.medicine_name, c.price, c.quantity, m.id AS medicine_id 
            FROM cart c 
            LEFT JOIN medicines m ON c.medicine_name = m.medicine_name 
            WHERE c.user_id = ?";
$cart_stmt = mysqli_prepare($conn, $cart_sql);
mysqli_stmt_bind_param($cart_stmt, "i", $user_id);
mysqli_stmt_execute($cart_stmt);
$cart_result = mysqli_stmt_get_result($cart_stmt);

$cart_items = [];
$total_amount = 0.00;

while ($item = mysqli_fetch_assoc($cart_result)) {
    $item_total = $item['price'] * $item['quantity'];
    $total_amount += $item_total;
    $cart_items[] = $item;
}
mysqli_stmt_close($cart_stmt);

// Redirect to cart if empty
if (empty($cart_items) && $_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "<script>
            alert('Your shopping cart is empty.');
            window.location.href = 'medicines.php';
          </script>";
    exit();
}

// 4. Handle Order Placement (POST Submission)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    $delivery_address = trim($_POST['delivery_address']);
    $payment_method   = trim($_POST['payment_method']);

    if (empty($delivery_address) || empty($payment_method)) {
        $error_message = "Please provide a valid delivery address and select a payment method.";
    } else {
        // Start Database Transaction for Data Consistency
        mysqli_begin_transaction($conn);

        try {
            // A. Insert into `orders`
            $order_sql = "INSERT INTO orders (user_id, total_amount, status, delivery_address) VALUES (?, ?, 'Paid', ?)";
            $o_stmt = mysqli_prepare($conn, $order_sql);
            mysqli_stmt_bind_param($o_stmt, "ids", $user_id, $total_amount, $delivery_address);
            mysqli_stmt_execute($o_stmt);
            $order_id = mysqli_insert_id($conn);
            mysqli_stmt_close($o_stmt);

            // B. Insert into `order_items`
            $item_sql = "INSERT INTO order_items (order_id, medicine_id, quantity, price) VALUES (?, ?, ?, ?)";
            $i_stmt = mysqli_prepare($conn, $item_sql);

            foreach ($cart_items as $item) {
                // Default to medicine_id 0 if unmatched
                $med_id = $item['medicine_id'] ?? 0;
                mysqli_stmt_bind_param($i_stmt, "iiid", $order_id, $med_id, $item['quantity'], $item['price']);
                mysqli_stmt_execute($i_stmt);
            }
            mysqli_stmt_close($i_stmt);

            // C. Insert into `payments`
            $pay_sql = "INSERT INTO payments (order_id, payment_method, amount, payment_status) VALUES (?, ?, ?, 'Completed')";
            $p_stmt = mysqli_prepare($conn, $pay_sql);
            mysqli_stmt_bind_param($p_stmt, "isd", $order_id, $payment_method, $total_amount);
            mysqli_stmt_execute($p_stmt);
            mysqli_stmt_close($p_stmt);

            // D. Insert into `deliveries`
            $del_sql = "INSERT INTO deliveries (order_id, delivery_address, delivery_status, estimated_delivery) VALUES (?, ?, 'Preparing', DATE_ADD(NOW(), INTERVAL 2 DAY))";
            $d_stmt = mysqli_prepare($conn, $del_sql);
            mysqli_stmt_bind_param($d_stmt, "is", $order_id, $delivery_address);
            mysqli_stmt_execute($d_stmt);
            mysqli_stmt_close($d_stmt);

            // E. Clear user's active cart
            $clear_sql = "DELETE FROM cart WHERE user_id = ?";
            $c_stmt = mysqli_prepare($conn, $clear_sql);
            mysqli_stmt_bind_param($c_stmt, "i", $user_id);
            mysqli_stmt_execute($c_stmt);
            mysqli_stmt_close($c_stmt);

            // Commit all queries
            mysqli_commit($conn);

            echo "<script>
                    alert('Order #$order_id placed successfully!');
                    window.location.href = 'tracking.php';
                  </script>";
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = "Order processing failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Pill Point Delivery</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .checkout-grid { grid-template-columns: 1fr; }
        }
        .order-summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .order-summary-table th, .order-summary-table td {
            border-bottom: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .error-msg {
            color: #721c24;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <span class="menu-btn" onclick="openNav()">&#9776;</span>
    <h1 class="header-title">Pill Point Delivery</h1>
</div>

<!-- Sidebar -->
<div id="sidebar" class="sidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="medicines.php">Shop Medication</a>
    <a href="prescription.php">Prescription</a>
    <a href="reminders.php">Reminders</a>
    <a href="storelocator.html">Store Locator</a>
    <a href="cart.php">Cart</a>
    <a href="myaccount.php">My Account</a>
    <a href="aboutus.html">About Us</a>
    <a href="logout.php">Logout</a>
</div>

<div class="container" style="padding: 20px;">
    <h2>Order Checkout</h2>

    <?php if (!empty($error_message)): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="checkout-grid">
        
        <!-- Column 1: Order Summary -->
        <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h3>Order Summary</h3>
            <table class="order-summary-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['medicine_name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>R<?php echo number_format($item['price'], 2); ?></td>
                            <td>R<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <h3 style="text-align: right;">Grand Total: <span style="color: #28a745;">R<?php echo number_format($total_amount, 2); ?></span></h3>
        </div>

        <!-- Column 2: Payment & Delivery Form -->
        <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h3>Shipping & Payment Details</h3>
            
            <form action="checkout.php" method="POST">
                <div class="input-box full" style="margin-bottom: 15px;">
                    <label>Full Name</label>
                    <input type="text" value="<?php echo htmlspecialchars(($user_info['first_name'] ?? '') . ' ' . ($user_info['last_name'] ?? '')); ?>" readonly style="background-color: #f0f0f0;">
                </div>

                <div class="input-box full" style="margin-bottom: 15px;">
                    <label>Delivery Address</label>
                    <textarea name="delivery_address" required rows="3"><?php echo htmlspecialchars($user_info['address'] ?? ''); ?></textarea>
                </div>

                <div class="input-box full" style="margin-bottom: 20px;">
                    <label>Payment Method</label>
                    <select name="payment_method" required>
                        <option value="">Select Payment Option</option>
                        <option value="Credit/Debit Card">Credit / Debit Card</option>
                        <option value="EFT/Bank Transfer">Instant EFT / Bank Transfer</option>
                        <option value="Cash on Delivery">Cash on Delivery</option>
                    </select>
                </div>

                <button type="submit" name="place_order" class="btn" style="width: 100%; padding: 12px; font-size: 1.1em;">
                    Confirm & Place Order (R<?php echo number_format($total_amount, 2); ?>)
                </button>
            </form>
        </div>

    </div>
</div>

<script src="script.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>