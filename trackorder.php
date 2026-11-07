<?php
session_start();
include("dbconnect.php");

if (!isset($_SESSION['user_id'])) {
    echo "<script>
            alert('Please login first.');
            window.location.href = 'login.html';
          </script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$search_result = null;
$error_message = "";

// Handle order search
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['track_order'])) {
    $order_number = trim($_POST['order_number']);

    if (!empty($order_number)) {
        // Query orders table matching order id or tracking number
        $sql = "SELECT * FROM orders WHERE (id = ? OR order_number = ?) AND user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isi", $order_number, $order_number, $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $search_result = $row;
            } else {
                $error_message = "No order found with that number.";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error_message = "Please enter an order number.";
    }
}

// Fetch existing orders for the user
$existing_orders_sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC";
$eo_stmt = mysqli_prepare($conn, $existing_orders_sql);
mysqli_stmt_bind_param($eo_stmt, "i", $user_id);
mysqli_stmt_execute($eo_stmt);
$existing_orders_result = mysqli_stmt_get_result($eo_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - Pill Point Delivery</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            box-sizing: border-box;
        }

        .header-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            margin: 0;
        }

        .track-container {
            max-width: 700px;
            margin: 110px auto 40px auto;
            background: #fff;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .track-container h2 {
            color: #003049;
            text-align: center;
            margin-bottom: 25px;
        }

        /* Stacked layout: Input above the button */
        .track-form-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 25px;
        }

        .track-form-group label {
            font-weight: bold;
            color: #003049;
        }

        .track-form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            box-sizing: border-box;
            outline: none;
        }

        .btn {
            background: #669BBC; /* Matches website theme button color */
            color: white;
            border: none;
            padding: 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            width: 100%;
        }

        .btn:hover {
            background: #003049;
        }

        .orders-section {
            margin-top: 35px;
            border-top: 1px solid #e2e8f0;
            padding-top: 25px;
        }

        .orders-section h3 {
            color: #003049;
            margin-bottom: 15px;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            font-size: 14px;
        }

        th {
            background-color: #f8f9fa;
            color: #003049;
        }
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
    <a href="storelocator.php">Store Locator</a>
    <a href="cart.php">Cart</a>
    <a href="trackorder.php">Track Order</a>
    <a href="myaccount.php">My Account</a>
    <a href="logout.php">Logout</a>
</div>

<div class="track-container">
    <h2>Track Your Order</h2>

    <form action="trackorder.php" method="POST">
        <div class="track-form-group">
            <label>Enter Order Number</label>
            <input type="text" name="order_number" placeholder="Enter the order number of an existing order to view its delivery status" required>
        </div>
        <button type="submit" name="track_order" class="btn">Track Order</button>
    </form>

    <?php if (!empty($error_message)): ?>
        <p style="color: #721c24; background: #f8d7da; padding: 10px; border-radius: 6px; text-align: center; font-weight: bold; margin-top: 20px;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <?php if ($search_result): ?>
        <div style="background: #e2e8f0; padding: 15px; border-radius: 6px; margin-top: 20px;">
            <h4 style="color: #003049; margin-bottom: 8px;">Order Details (#<?php echo htmlspecialchars($search_result['id']); ?>)</h4>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($search_result['status'] ?? 'Processing'); ?></p>
            <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($search_result['delivery_address'] ?? 'N/A'); ?></p>
            <p><strong>Total:</strong> R<?php echo number_format($search_result['total_price'] ?? 0, 2); ?></p>
        </div>
    <?php endif; ?>

    <div class="orders-section">
        <h3>Your Existing Orders</h3>
        <?php if (mysqli_num_rows($existing_orders_result) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($order = mysqli_fetch_assoc($existing_orders_result)): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td>R<?php echo number_format($order['total_price'] ?? 0, 2); ?></td>
                        <td><?php echo htmlspecialchars($order['status'] ?? 'Processing'); ?></td>
                        <td><?php echo htmlspecialchars($order['order_date'] ?? ''); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #64748b;">You do not have any existing orders yet. <a href="medicines.php" style="color: #669BBC; font-weight: bold; text-decoration: none;">Shop Medication</a></p>
        <?php endif; ?>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>
<?php 
mysqli_stmt_close($eo_stmt);
mysqli_close($conn); 
?>