<?php
session_start();
include("dbconnect.php");

if (!isset($_SESSION['user_id'])) {
    echo "<script>
            alert('Please login to view your cart.');
            window.location.href = 'login.html';
          </script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle item quantity updates or deletion if requested
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['qty'] as $cart_id => $quantity) {
            $quantity = (int)$quantity;
            $cart_id = (int)$cart_id;
            if ($quantity > 0) {
                $update_sql = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
                $stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($stmt, "iii", $quantity, $cart_id, $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
    } elseif (isset($_POST['remove_item'])) {
        $cart_id = (int)$_POST['cart_id'];
        $del_sql = "DELETE FROM cart WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $del_sql);
        mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Fetch active cart items for the user
$sql = "SELECT c.id AS cart_id, c.medicine_name, c.price, c.quantity 
        FROM cart c 
        WHERE c.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$total_amount = 0.00;
$cart_items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $total_amount += ($row['price'] * $row['quantity']);
    $cart_items[] = $row;
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Pill Point Delivery</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .cart-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
        .cart-table th, .cart-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .cart-table th { background-color: #007bff; color: white; }
        .btn-remove { background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
        .cart-actions { margin-top: 20px; display: flex; justify-content: space-between; align-items: center; }
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
    <a href="myaccount.php">My Account</a>
    <a href="aboutus.html">Contact Us</a>
    <a href="logout.php">Logout</a>
</div>

<div class="container" style="padding: 20px;">
    <h2>Your Shopping Cart</h2>

    <?php if (!empty($cart_items)): ?>
        <form action="cart.php" method="POST">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Medication</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['medicine_name']); ?></td>
                            <td>R<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <input type="number" name="qty[<?php echo $item['cart_id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" style="width: 60px; padding: 5px;">
                            </td>
                            <td>R<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            <td>
                                <button type="submit" name="remove_item" value="1" formaction="cart.php" onclick="this.form.elements['cart_id'].value = '<?php echo $item['cart_id']; ?>'" class="btn-remove">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <input type="hidden" name="cart_id" value="">

            <div class="cart-actions">
                <button type="submit" name="update_cart" class="btn" style="background: #6c757d;">Update Quantities</button>
                <h3>Total: <span style="color: #28a745;">R<?php echo number_format($total_amount, 2); ?></span></h3>
                <a href="checkout.php" class="btn" style="text-decoration: none;">Proceed to Checkout</a>
            </div>
        </form>
    <?php else: ?>
        <p style="margin-top: 20px;">Your cart is currently empty. <a href="medicines.php">Browse medications</a>.</p>
    <?php endif; ?>
</div>

<script src="script.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>