<?php
session_start();

include("dbconnect.php");

if(isset($_POST['username']) && isset($_POST['password']))
{
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $sql = "SELECT * FROM users
            WHERE username='$username'
            AND password='$password'";

    $result = mysqli_query($conn, $sql);

    if(mysqli_num_rows($result) == 1)
    {
        $user = mysqli_fetch_assoc($result);

        $_SESSION['userid'] = $user['id'];
        $_SESSION['firstname'] = $user['firstname'];
        $_SESSION['lastname'] = $user['lastname'];
        $_SESSION['username'] = $user['username'];

        header("Location: dashboard.html");
        exit();
    }
    else
    {
        echo "<script>
                alert('Invalid Username or Password');
                window.location='login.html';
              </script>";
    }
}
else
{
    header("Location: login.html");
    exit();
}
?>