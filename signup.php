<?php

include("dbconnect.php");

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: signup.html");
    exit();
}

$first_name = $_POST['first_name'];
$last_name  = $_POST['last_name'];
$age        = $_POST['age'];
$phone      = $_POST['phone'];
$email      = $_POST['email'];
$address    = $_POST['address'];
$province   = $_POST['province'];
$username   = $_POST['username'];
$password   = password_hash($_POST['password'], PASSWORD_DEFAULT);

$check = mysqli_prepare(
    $conn,
    "SELECT id FROM customers WHERE username = ? OR email = ?"
);

mysqli_stmt_bind_param(
    $check,
    "ss",
    $username,
    $email
);

mysqli_stmt_execute($check);

$result = mysqli_stmt_get_result($check);

if (mysqli_num_rows($result) > 0) {
    echo "<script>
            alert('Username or email already exists.');
            window.location.href='signup.html';
          </script>";
    exit();
}

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO customers
    (first_name, last_name, age, phone, email, address, province, username, password)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

mysqli_stmt_bind_param(
    $stmt,
    "ssissssss",
    $first_name,
    $last_name,
    $age,
    $phone,
    $email,
    $address,
    $province,
    $username,
    $password
);

if (mysqli_stmt_execute($stmt)) {

    echo "<script>
            alert('Account created successfully!');
            window.location.href='login.html';
          </script>";

} else {

    echo "<script>
            alert('Registration failed.');
            window.location.href='signup.html';
          </script>";
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

?>

