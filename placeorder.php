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

    header("Location: cart.html");
    exit();

}

$user_id = $_SESSION['user_id'];
$cart_id = intval($_POST['cart_id']);


/* Delete only the logged-in customer's cart item */

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM cart
     WHERE id = ?
     AND user_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $cart_id,
    $user_id
);

if (mysqli_stmt_execute($stmt)) {

    echo "<script>
            alert('Medicine removed from cart.');
            window.location.href='cart.html';
          </script>";

} else {

    echo "<script>
            alert('Unable to remove medicine.');
            window.location.href='cart.html';
          </script>";
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

?>