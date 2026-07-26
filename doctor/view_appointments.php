<?php
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit();
}

include '../db.php';
$doctor_id = $_SESSION['doctor_id'];

// Fetch doctor name
$query = $conn->prepare("SELECT name FROM doctors WHERE id = ?");
$query->bind_param("i", $doctor_id);
$query->execute();
$query->bind_result($doctor_name);
$query->fetch();
$query->close();

// Fetch appointments
$sql = "SELECT a.*, p.full_name AS patient_name 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        WHERE a.doctor_id = $doctor_id 
        ORDER BY a.appointment_date DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Doctor - View Appointments</title>
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
            background: white;
            box-shadow: 0 0 10px #ccc;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background: #f4f4f4;
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
    <h2>Doctor Dashboard</h2>
    <p>👨‍⚕️ <strong><?= htmlspecialchars($doctor_name) ?></strong></p>
    <a href="doctor_dashboard.php">🏠 Dashboard Home</a>
    <a href="view_appointments.php">📅 View Appointments</a>
    <a href="manage_availability.php">🗓️ Manage Availability</a>
    <a href="manage_appointment.php">🛠️ Manage Appointments</a>
    <a href="add_medical_record.php">🩺 Add Medical Record</a>
    <a href="generate_medical_record.php">📋 Generate Medical Record</a>
    <a href="view_medical_records.php">📋 My Medical Records</a>
    <a href="manage_account.php">⚙️ Manage Account</a>
    <a class="logout" href="../logout.php">🚪 Logout</a>
  </div>

<div class="main">
    <div class="card">
        <h3>All Appointments</h3>

        <h2>View Appointments</h2>

        <table>
            <tr>
                <th>Patient Name</th>
                <th>Appointment Date</th>
                <th>Time Slot</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?= htmlspecialchars($row['patient_name']) ?></td>
                    <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                    <td><?= htmlspecialchars($row['appointment_time']) ?></td>
                    <td><?= ucfirst(htmlspecialchars($row['status'])) ?></td>
                    <td>
                        <form action="update_appointment.php" method="post">
                            <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="status" value="<?= $row['status'] ?>">
                            <button type="submit">Update Status</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

</body>
</html>
