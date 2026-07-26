<?php
// Simple script to insert admin user with plain text password
$conn = new mysqli('localhost', 'root', '', 'appointment_system');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$username = 'rahmat';
$password = 'rahmat@123';
// Check if user already exists
$stmt = $conn->prepare("SELECT id FROM admin WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo "Admin user already exists.";
} else {
    $stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
    $stmt->bind_param('ss', $username, $password);
    if ($stmt->execute()) {
        echo "Admin user inserted successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }
}
$stmt->close();
$conn->close(); 