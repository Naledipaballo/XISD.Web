<?php

session_start();

include("dbconnect.php");


/* Check if customer is logged in */

if (!isset($_SESSION['user_id'])) {

    echo "<script>
            alert('Please login first.');
            window.location.href='login.html';
          </script>";

    exit();
}


/* Make sure the request came from the form */

if ($_SERVER["REQUEST_METHOD"] != "POST") {

    header("Location: medication_reminders.html");
    exit();

}


$user_id = $_SESSION['user_id'];

$medicine_name = trim($_POST['medicine_name']);
$dosage = trim($_POST['dosage']);
$reminder_time = $_POST['reminder_time'];
$reminder_date = $_POST['reminder_date'];


/* Insert reminder */

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO medication_reminders
    (user_id, medicine_name, dosage, reminder_time, reminder_date)
    VALUES (?, ?, ?, ?, ?)"
);


mysqli_stmt_bind_param(
    $stmt,
    "issss",
    $user_id,
    $medicine_name,
    $dosage,
    $reminder_time,
    $reminder_date
);


if (mysqli_stmt_execute($stmt)) {

    echo "<script>
            alert('Medication reminder added successfully!');
            window.location.href='medication_reminders.html';
          </script>";

} else {

    echo "<script>
            alert('Unable to add medication reminder.');
            window.location.href='medication_reminders.html';
          </script>";
}


mysqli_stmt_close($stmt);
mysqli_close($conn);

?>