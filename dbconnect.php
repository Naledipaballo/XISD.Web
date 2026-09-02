<?php

$serverName = ".\\SQLEXPRESS";

$connectionOptions = array(
    "Database" => "PillPointDelivery",
    "TrustServerCertificate" => true
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die("Database Connection Failed:<br><pre>" . print_r(sqlsrv_errors(), true) . "</pre>");
}

?>