<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';

$patient_id = $_SESSION['patient_id'];

// Get full name
$query = $conn->prepare("SELECT full_name FROM patients WHERE id = ?");
$query->bind_param("i", $patient_id);
$query->execute();
$query->bind_result($full_name);
$query->fetch();
$query->close();

// Get appointments
$appointments = $conn->prepare("SELECT 
    a.id, a.appointment_date, a.appointment_time, a.status, 
    d.name AS doctor_name, d.specialization 
    FROM appointments a 
    JOIN doctors d ON a.doctor_id = d.id 
    WHERE a.patient_id = ? 
    ORDER BY a.appointment_date DESC");
$appointments->bind_param("i", $patient_id);
$appointments->execute();
$result = $appointments->get_result();

// Handle delete
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $del_id, $patient_id);
    $stmt->execute();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Appointment History</title>
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

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    th, td {
      padding: 12px;
      text-align: center;
      border-bottom: 1px solid #ddd;
    }

    th {
      background-color: #3498db;
      color: white;
    }

    td {
      background-color: #fafafa;
    }

    a.action-btn {
      text-decoration: none;
      padding: 6px 10px;
      border-radius: 6px;
      color: white;
      font-size: 14px;
    }

    .edit-btn {
      background-color: #0288d1;
    }

    .cancel-btn {
      background-color: #e74c3c;
    }

    .edit-btn:hover {
      background-color: #0277bd;
    }

    .cancel-btn:hover {
      background-color: #c0392b;
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
      <h3>📖 Your Appointment History</h3>
      <p>Below is a list of all your booked appointments:</p>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Doctor Name</th>
            <th>Specialization</th>
            <th>Appointment Date</th>
            <th>Time</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['doctor_name']) ?></td>
              <td><?= htmlspecialchars($row['specialization']) ?></td>
              <td><?= $row['appointment_date'] ?></td>
              <td><?= $row['appointment_time'] ?></td>
              <td><?= $row['status'] ?></td>
              <td>
                <a class="action-btn edit-btn" href="edit_appointment.php?id=<?= $row['id'] ?>">Edit</a>
                <a class="action-btn cancel-btn" href="cancel_appointment.php?id=<?= $row['id'] ?>" onclick="return confirm('Cancel this appointment?');">Cancel</a>
                <a class="action-btn cancel-btn" style="background:#ef4444;" href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to permanently delete this appointment?');">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>
</html>

<?php
$appointments->close();
$conn->close();
?>
