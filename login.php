<?php
session_start();
include("dbconnect.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.html");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    echo "<script>
            alert('Please fill in both username and password.');
            window.location.href = 'login.html';
          </script>";
    exit();
}

// Query customer record by username or email
$query = "SELECT id, username, password FROM customers WHERE username = ? OR email = ?";
$stmt  = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $username, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        // Verify hashed password
        if (password_verify($password, $row['password'])) {
            // Set user session variables
            $_SESSION['user_id']  = $row['id'];
            $_SESSION['username'] = $row['username'];

            // Redirect directly to dashboard
            header("Location: dashboard.php");
            exit();
        }
    }
    
    mysqli_stmt_close($stmt);
}

// If credentials don't match
echo "<script>
        alert('Invalid username or password.');
        window.location.href = 'login.html';
      </script>";
mysqli_close($conn);
exit();
?>