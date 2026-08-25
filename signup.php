<?php

include("dbconnect.php");

if(isset($_POST['firstname']))
{

$firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
$lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
$age = mysqli_real_escape_string($conn, $_POST['age']);
$phone = mysqli_real_escape_string($conn, $_POST['phone']);
$email = mysqli_real_escape_string($conn, $_POST['email']);
$address = mysqli_real_escape_string($conn, $_POST['address']);
$post = mysqli_real_escape_string($conn, $_POST['post']);
$province = mysqli_real_escape_string($conn, $_POST['province']);
$username = mysqli_real_escape_string($conn, $_POST['username']);
$password = mysqli_real_escape_string($conn, $_POST['password']);

$sql = "INSERT INTO users
(firstname, lastname, age, phone, email, address, postalcode, province, username, password)
VALUES
('$firstname','$lastname','$age','$phone','$email','$address','$post','$province','$username','$password')";

if(mysqli_query($conn,$sql))
{
    echo "<script>
            alert('Account created successfully!');
            window.location='login.html';
          </script>";
}
else
{
    echo "Error: " . mysqli_error($conn);
}

}

?>