<?php
/**
 * Database Connection
 * Project: College Tech Fest Management System
 */

$host = "localhost";
$user = "festuser";
$password = "fest123";
$database = "CollegeTechFestDB";
$port = 3306;

// Create connection
$conn = mysqli_connect(
    $host,
    $user,
    $password,
    $database,
    $port
);

// Check connection
if (!$conn) {
    die("
        <div style='
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 25px;
            background: #fee2e2;
            border: 1px solid #ef4444;
            border-radius: 10px;
            color: #991b1b;
        '>
            <h2>Database Connection Failed</h2>
            <p>
                <strong>Error:</strong>
                " . mysqli_connect_error() . "
            </p>

            <p>
                Make sure MySQL is running and the database
                <strong>CollegeTechFestDB</strong> exists.
            </p>
        </div>
    ");
}

// Set UTF-8 character encoding
mysqli_set_charset($conn, "utf8mb4");

?>