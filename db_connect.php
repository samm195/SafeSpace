<?php
// Set the default timezone for the entire application
date_default_timezone_set('Africa/Tunis');

// Database credentials for local XAMPP
$servername = "localhost";
$username = "root";       // Default XAMPP username
$password = "";           // Default XAMPP password is blank
$database = "safespace";  // The name of the database we just created

// Create the connection
$conn = new mysqli($servername, $username, $password, $database);

// Check if the connection worked
if ($conn->connect_error) {
    // If it fails, stop everything and print the error
    die("Connection failed: " . $conn->connect_error);
}


?>