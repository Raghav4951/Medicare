<?php
/*
|--------------------------------------------------------------------------
| Database Configuration
|--------------------------------------------------------------------------
| Change these values according to your hosting/database details
|--------------------------------------------------------------------------
*/

$host = "localhost";
$username = "root";          // Change this after deployment
$password = "";              // Add your database password
$database = "doctor_appointment";


/*
|--------------------------------------------------------------------------
| Create Database Connection
|--------------------------------------------------------------------------
*/

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);


/*
|--------------------------------------------------------------------------
| Check Connection
|--------------------------------------------------------------------------
*/

if ($conn->connect_error) {

    // Save error in server logs
    error_log("Database Connection Error: " . $conn->connect_error);

    // Show safe message to users
    die("Database connection failed. Please try again later.");

}


/*
|--------------------------------------------------------------------------
| Set Character Encoding
|--------------------------------------------------------------------------
*/

$conn->set_charset("utf8mb4");


?>