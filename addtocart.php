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

    header("Location: shop.html");
    exit();

}

$user_id = $_SESSION['user_id'];

$medicine = trim($_POST['medicine_name']);
$price = floatval($_POST['price']);
$quantity = intval($_POST['quantity']);

if ($quantity < 1) {
    $quantity = 1;
}


/* Check whether medicine is already in the cart */

$check = mysqli_prepare(
    $conn,
    "SELECT id, quantity
     FROM cart
     WHERE user_id = ?
     AND medicine_name = ?"
);

mysqli_stmt_bind_param(
    $check,
    "is",
    $user_id,
    $medicine
);

mysqli_stmt_execute($check);

$result = mysqli_stmt_get_result($check);


if (mysqli_num_rows($result) > 0) {

    $cart_item = mysqli_fetch_assoc($result);

    $new_quantity =
        $cart_item['quantity'] + $quantity;

    $update = mysqli_prepare(
        $conn,
        "UPDATE cart
         SET quantity = ?, price = ?
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $update,
        "idi",
        $new_quantity,
        $price,
        $cart_item['id']
    );

    mysqli_stmt_execute($update);

    mysqli_stmt_close($update);

} else {

    $insert = mysqli_prepare(
        $conn,
        "INSERT INTO cart
        (user_id, medicine_name, price, quantity)
        VALUES (?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $insert,
        "isdi",
        $user_id,
        $medicine,
        $price,
        $quantity
    );

    mysqli_stmt_execute($insert);

    mysqli_stmt_close($insert);
}

mysqli_stmt_close($check);
mysqli_close($conn);


/* Send customer to cart */

header("Location: cart.html");
exit();

?>