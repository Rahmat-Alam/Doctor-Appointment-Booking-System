<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';

$patient_id = $_SESSION['patient_id'];

// Get patient details
$query = $conn->prepare("SELECT full_name, email, phone FROM patients WHERE id = ?");
$query->bind_param("i", $patient_id);
$query->execute();
$query->bind_result($full_name, $email, $phone);
$query->fetch();
$query->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Account</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f1f1f1;
      margin: 0;
      padding: 0;
    }

    .sidebar {
      width: 220px;
      height: 100vh;
      background: #2c3e50;
      padding: 20px;
      color: white;
      position: fixed;
    }

    .sidebar h2 {
      font-size: 22px;
      margin-bottom: 30px;
    }

    .sidebar a {
      color: white;
      display: block;
      margin: 10px 0;
      text-decoration: none;
      padding: 10px;
      border-radius: 6px;
    }

    .sidebar a:hover {
      background: #34495e;
    }

    .main {
      margin-left: 240px;
      padding: 30px;
    }

    .card {
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .card h3 {
      margin-bottom: 15px;
    }

    form {
      display: flex;
      flex-direction: column;
    }

    label {
      margin-top: 10px;
      font-weight: bold;
    }

    input {
      padding: 10px;
      margin-top: 5px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }

    button {
      margin-top: 20px;
      padding: 10px;
      background-color: #3498db;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }

    button:hover {
      background-color: #2980b9;
    }

    .logout {
      margin-top: 30px;
      display: inline-block;
      color: red;
      font-weight: bold;
    }
  </style>
</head>
<body>

<div class="sidebar">
    <h2>Patient Dashboard</h2>
    <p>👤 <strong><?= htmlspecialchars($full_name) ?></strong></p>
    <a href="patient_dashboard.php">🏠 Dashboard Home</a>
    <a href="book_appointment.php">📅 Book Appointment</a>
    <a href="appointment_history.php">📖 Appointment History</a>
    <a href="view_medical_records.php">🩺 Medical Records</a>
    <a href="rate_doctor.php">⭐ Rate Doctor</a>
    <a href="manage_account.php">⚙️ Manage Account</a>
    <a href="feedback.php">💬 Feedback</a>
    <a class="logout" href="logout.php">🚪 Logout</a>
  </div>

  <div class="main">
    <div class="card">
    <?php
if (isset($_GET['success'])) {
    echo "<p style='color: green;'>✅ Account updated successfully!</p>";
} elseif (isset($_GET['error'])) {
    echo "<p style='color: red;'>❌ Failed to update account. Please try again.</p>";
}
?>

      <h3>⚙️ Manage Your Account</h3>
      <form action="update_account.php" method="POST">
        <label for="full_name">Full Name</label>
        <input type="text" name="full_name" id="full_name" value="<?= htmlspecialchars($full_name) ?>" required>

        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($email) ?>" required>

        <label for="phone">Phone</label>
        <input type="text" name="phone" id="phone" value="<?= htmlspecialchars($phone) ?>" required>

        <button type="submit">Update Account</button>
      </form>
    </div>
  </div>

</body>
</html>
