<?php
session_start();
include 'db.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: index.php");
    exit();
}

$appointment_id = $_GET['id'] ?? null;
$patient_id = $_SESSION['patient_id'];

if (!$appointment_id) {
    echo "❌ Invalid Appointment ID.";
    exit();
}

// Appointment की पुष्टि करें कि वो इसी patient की है
$stmt = $conn->prepare("SELECT id FROM appointments WHERE id = ? AND patient_id = ?");
$stmt->bind_param("ii", $appointment_id, $patient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "❌ Appointment not found or access denied.";
    exit();
}

// Appointment cancel करें
$cancel = $conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE id = ?");
$cancel->bind_param("i", $appointment_id);

if ($cancel->execute()) {
    echo "<script>alert('Appointment cancelled successfully.'); window.location.href='appointment_history.php';</script>";
    exit();
} else {
    echo "Error cancelling appointment: " . $cancel->error;
}
?>
