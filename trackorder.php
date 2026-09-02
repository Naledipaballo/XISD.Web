<?php

session_start();

include("dbconnect.php");

if (!isset($_SESSION['user_id'])) {

    echo "<script>
            alert('Please login first.');
            window.location.href='login.html';
          </script>";

    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {

    header("Location: track_order.html");
    exit();

}

$user_id = $_SESSION['user_id'];
$order_id = intval($_POST['order_id']);


/* Find the customer's order */

$stmt = mysqli_prepare(
    $conn,
    "SELECT order_id,
            delivery_address,
            province,
            delivery_option,
            payment_method,
            subtotal,
            delivery_fee,
            total,
            status
     FROM orders
     WHERE order_id = ?
     AND user_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $order_id,
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


if (mysqli_num_rows($result) == 0) {

    echo "<script>
            alert('Order not found.');
            window.location.href='track_order.html';
          </script>";

    exit();
}

$order = mysqli_fetch_assoc($result);

?>

<!DOCTYPE html>
<html>
<head>

    <title>Pill Point Delivery - Order Status</title>

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

<header>

    <h1>Pill Point Delivery</h1>

    <nav>
        <a href="home.html">Home</a>
        <a href="shop.html">Shop</a>
        <a href="cart.html">Cart</a>
        <a href="track_order.html">Track Order</a>
        <a href="my_account.html">My Account</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>


<main>

    <div class="container">

        <h2>Order Tracking</h2>

        <h3>
            Order #<?php echo $order['order_id']; ?>
        </h3>

        <p>
            <strong>Status:</strong>

            <?php echo htmlspecialchars($order['status']); ?>

        </p>

        <hr>

        <h3>Delivery Information</h3>

        <p>
            <strong>Address:</strong><br>

            <?php
            echo htmlspecialchars(
                $order['delivery_address']
            );
            ?>
        </p>

        <p>
            <strong>Province:</strong>

            <?php
            echo htmlspecialchars(
                $order['province']
            );
            ?>
        </p>

        <p>
            <strong>Delivery:</strong>

            <?php
            echo htmlspecialchars(
                $order['delivery_option']
            );
            ?>
        </p>

        <h3>Payment</h3>

        <p>
            <strong>Payment Method:</strong>

            <?php
            echo htmlspecialchars(
                $order['payment_method']
            );
            ?>
        </p>

        <h3>Order Total</h3>

        <p>
            Subtotal:
            R<?php echo number_format(
                $order['subtotal'],
                2
            ); ?>
        </p>

        <p>
            Delivery:
            R<?php echo number_format(
                $order['delivery_fee'],
                2
            ); ?>
        </p>

        <p>
            <strong>
                Total:
                R<?php echo number_format(
                    $order['total'],
                    2
                ); ?>
            </strong>
        </p>

        <br>

        <a href="track_order.html">
            <button type="button">
                Track Another Order
            </button>
        </a>

        <a href="shop.html">
            <button type="button">
                Continue Shopping
            </button>
        </a>

    </div>

</main>

</body>
</html>

<?php

mysqli_stmt_close($stmt);
mysqli_close($conn);

?>