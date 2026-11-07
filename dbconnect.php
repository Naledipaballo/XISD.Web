<?php
$servername = "sql111.infinityfree.com";
$username   = "if0_43367776";
$password   = "5zUYFvu55PgT8H";
$dbname     = "if0_43367776_xisd_db";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}
?>