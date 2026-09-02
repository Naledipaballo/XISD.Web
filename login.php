<?php

session_start();

include("dbconnect.php");

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: login.html");
    exit();
}

$username = trim($_POST['username']);
$password = $_POST['password'];

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, first_name, last_name, username, password
     FROM customers
     WHERE username = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $username
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 1) {

    $user = mysqli_fetch_assoc($result);

    if (password_verify($password, $user['password'])) {

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['username'] = $user['username'];

        header("Location: home.html");
        exit();

    } else {

        echo "<script>
                alert('Incorrect password.');
                window.location.href='login.html';
              </script>";
        exit();
    }

} else {

    echo "<script>
            alert('Username not found.');
            window.location.href='login.html';
          </script>";
    exit();
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

?>