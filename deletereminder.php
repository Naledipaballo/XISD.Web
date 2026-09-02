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

    header("Location: medication_reminders.html");
    exit();

}


$user_id = $_SESSION['user_id'];

$reminder_id = intval($_POST['reminder_id']);


/* Delete only the logged-in user's reminder */

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM medication_reminders
     WHERE id = ?
     AND user_id = ?"
);


mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $reminder_id,
    $user_id
);


if (mysqli_stmt_execute($stmt)) {

    echo "<script>
            alert('Reminder deleted successfully.');
            window.location.href='view_reminders.php';
          </script>";

} else {

    echo "<script>
            alert('Unable to delete reminder.');
            window.location.href='view_reminders.php';
          </script>";

}


mysqli_stmt_close($stmt);
mysqli_close($conn);

?>