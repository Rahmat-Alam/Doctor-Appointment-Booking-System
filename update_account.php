<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

$patient_id = $_SESSION['patient_id'];

// Input values
$full_name = trim($_POST['full_name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);

// Validate inputs
if (!empty($full_name) && !empty($email) && !empty($phone)) {
    $stmt = $conn->prepare("UPDATE patients SET full_name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("sssi", $full_name, $email, $phone, $patient_id);

    if ($stmt->execute()) {
        // Redirect back with success message
        header("Location: manage_account.php?success=1");
    } else {
        // Redirect back with error
        header("Location: manage_account.php?error=1");
    }

    $stmt->close();
} else {
    // Missing field
    header("Location: manage_account.php?error=1");
}
exit();
?>
