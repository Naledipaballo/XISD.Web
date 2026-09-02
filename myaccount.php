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

$user_id = $_SESSION['user_id'];


/* Get customer information */

$stmt = mysqli_prepare(
    $conn,
    "SELECT first_name,
            last_name,
            age,
            phone,
            email,
            address,
            province,
            username
     FROM customers
     WHERE id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


if (mysqli_num_rows($result) != 1) {

    echo "<script>
            alert('Account information could not be found.');
            window.location.href='home.html';
          </script>";

    exit();
}

$customer = mysqli_fetch_assoc($result);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Pill Point Delivery - My Account</title>

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

        <h2>My Account</h2>


        <div class="account-details">

            <h3>Personal Information</h3>

            <p>
                <strong>First Name:</strong>

                <?php
                echo htmlspecialchars(
                    $customer['first_name']
                );
                ?>
            </p>


            <p>
                <strong>Last Name:</strong>

                <?php
                echo htmlspecialchars(
                    $customer['last_name']
                );
                ?>
            </p>


            <p>
                <strong>Age:</strong>

                <?php
                echo htmlspecialchars(
                    $customer['age']
                );
                ?>
            </p>


            <p>
                <strong>Phone:</strong>

                <?php
                echo htmlspecialchars(
                    $customer['phone']
                );
                ?>
            </p>


            <p>
                <strong>Email:</strong>

                <?php
                echo htmlspecialchars(
                    $customer['email']
                );
                ?>
            </p>


            <p>
                <strong>Username:</strong>

                <?php
                echo htmlspecialchars(
                    $customer['username']
                );
                ?>
            </p>


            <h3>Delivery Information</h3>


            <p>
                <strong>Address:</strong><br>

                <?php
                echo htmlspecialchars(
                    $customer['address']
                );
                ?>
            </p>


            <p>
                <strong>Province:</strong>

                <?php
                echo htmlspecialchars(
                    $customer['province']
                );
                ?>
            </p>

        </div>


        <br>

        <a href="home.html">
            <button type="button">
                Back to Home
            </button>
        </a>


        <a href="logout.php">
            <button type="button">
                Logout
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