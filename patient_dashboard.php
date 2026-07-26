<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
    header("Location: index.php");
    exit();
}
include 'db.php';

$patient_id = $_SESSION['patient_id'];

$query = $conn->prepare("SELECT full_name FROM patients WHERE id = ?");
$query->bind_param("i", $patient_id);
$query->execute();
$query->bind_result($full_name);
$query->fetch();
$query->close();

// Get statistics
$total_appointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE patient_id = $patient_id"))['total'];
$upcoming_appointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE patient_id = $patient_id AND appointment_date >= CURDATE()"))['total'];
$completed_appointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE patient_id = $patient_id AND appointment_date < CURDATE()"))['total'];
$pending_appointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE patient_id = $patient_id AND status = 'pending'"))['total'];

// Get upcoming appointments
$recent_appointments = $conn->query("SELECT a.*, d.name AS doctor_name, d.specialization 
                                    FROM appointments a 
                                    JOIN doctors d ON a.doctor_id = d.id 
                                    WHERE a.patient_id = $patient_id 
                                    AND a.appointment_date >= CURDATE()
                                    ORDER BY a.appointment_date ASC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Patient Dashboard</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(120deg, #f0f8ff 0%, #dff3ec 100%);
      margin: 0;
      padding: 0;
      min-height: 100vh;
    }

    .sidebar {
      width: 220px;
      height: 100vh;
      background: #2c3e50;
      padding: 20px;
      color: white;
      position: fixed;
      box-shadow: 2px 0 10px rgba(0,0,0,0.1);
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

    .dashboard-header {
      background: white;
      padding: 25px 30px;
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      margin-bottom: 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .welcome-section h1 {
      color: #0077b6;
      font-size: 28px;
      margin: 0 0 8px 0;
      font-weight: 600;
    }

    .welcome-section p {
      color: #666;
      font-size: 16px;
      margin: 0;
    }

    .patient-info {
      text-align: right;
    }

    .patient-name {
      color: #0077b6;
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 5px;
    }

    .patient-status {
      color: #023e8a;
      font-size: 14px;
      background: #e3f2fd;
      padding: 5px 12px;
      border-radius: 20px;
      display: inline-block;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 25px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      padding: 25px;
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      text-align: center;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border-left: 4px solid #0077b6;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }

    .stat-icon {
      font-size: 40px;
      margin-bottom: 15px;
    }

    .stat-number {
      font-size: 32px;
      font-weight: bold;
      color: #0077b6;
      margin-bottom: 8px;
    }

    .stat-label {
      color: #666;
      font-size: 14px;
      font-weight: 500;
    }

    .quick-actions {
      background: white;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      margin-bottom: 30px;
    }

    .quick-actions h3 {
      color: #0077b6;
      font-size: 20px;
      margin-bottom: 20px;
      font-weight: 600;
    }

    .action-buttons {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
    }

    .action-btn {
      background: linear-gradient(135deg, #0077b6, #023e8a);
      color: white;
      padding: 15px 20px;
      border: none;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .action-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0,119,182,0.3);
      text-decoration: none;
      color: white;
    }

    .upcoming-appointments {
      background: white;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }

    .upcoming-appointments h3 {
      color: #0077b6;
      font-size: 20px;
      margin-bottom: 20px;
      font-weight: 600;
    }

    .appointment-item {
      display: flex;
      align-items: center;
      padding: 15px 0;
      border-bottom: 1px solid #f0f0f0;
    }

    .appointment-item:last-child {
      border-bottom: none;
    }

    .appointment-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #e3f2fd;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      font-size: 18px;
    }

    .appointment-content {
      flex: 1;
    }

    .appointment-title {
      font-weight: 500;
      color: #333;
      margin-bottom: 4px;
    }

    .appointment-time {
      font-size: 12px;
      color: #666;
    }

    .logout {
      margin-top: 30px;
      display: inline-block;
      color: red;
      font-weight: bold;
    }

    @media (max-width: 768px) {
      .main {
        margin-left: 0;
        padding: 20px;
      }
      
      .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
      }
      
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .action-buttons {
        grid-template-columns: 1fr;
      }
      
      .dashboard-header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
      }
      
      .patient-info {
        text-align: center;
      }
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
    <!-- Dashboard Header -->
    <div class="dashboard-header">
      <div class="welcome-section">
        <h1>Welcome Back, Patient!</h1>
        <p>Here's your health journey overview and upcoming appointments.</p>
      </div>
      <div class="patient-info">
        <div class="patient-name"><?= htmlspecialchars($full_name) ?></div>
        <div class="patient-status">Active Patient</div>
      </div>
    </div>

    <!-- Statistics Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">📅</div>
        <div class="stat-number"><?= $total_appointments ?></div>
        <div class="stat-label">Total Appointments</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">⏳</div>
        <div class="stat-number"><?= $pending_appointments ?></div>
        <div class="stat-label">Pending Appointments</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-number"><?= $upcoming_appointments ?></div>
        <div class="stat-label">Upcoming Appointments</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-number"><?= $completed_appointments ?></div>
        <div class="stat-label">Completed Appointments</div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
      <h3>Quick Actions</h3>
      <div class="action-buttons">
        <a href="book_appointment.php" class="action-btn">
          📅 Book New Appointment
        </a>
        <a href="appointment_history.php" class="action-btn">
          📖 View History
        </a>
        <a href="view_medical_records.php" class="action-btn">
          🩺 Medical Records
        </a>
        <a href="rate_doctor.php" class="action-btn">
          ⭐ Rate Doctor
        </a>
        <a href="manage_account.php" class="action-btn">
          ⚙️ Manage Account
        </a>
        <a href="feedback.php" class="action-btn">
          💬 Give Feedback
        </a>
      </div>
    </div>

    <!-- Upcoming Appointments -->
    <div class="upcoming-appointments">
      <h3>Upcoming Appointments</h3>
      <?php if ($recent_appointments && $recent_appointments->num_rows > 0): ?>
        <?php while ($appointment = $recent_appointments->fetch_assoc()): ?>
          <div class="appointment-item">
            <div class="appointment-icon">
              <?php 
                $status = strtolower($appointment['status']);
                if ($status == 'pending') echo '⏳';
                elseif ($status == 'approved') echo '✅';
                elseif ($status == 'cancelled') echo '❌';
                else echo '📅';
              ?>
            </div>
            <div class="appointment-content">
              <div class="appointment-title">
                Dr. <?= htmlspecialchars($appointment['doctor_name']) ?> - 
                <?= htmlspecialchars($appointment['specialization']) ?>
              </div>
              <div class="appointment-time">
                <?= date('M d, Y', strtotime($appointment['appointment_date'])) ?> at 
                <?= date('h:i A', strtotime($appointment['appointment_time'])) ?> | 
                Status: <?= ucfirst(htmlspecialchars($appointment['status'])) ?>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="appointment-item">
          <div class="appointment-icon">📅</div>
          <div class="appointment-content">
            <div class="appointment-title">No upcoming appointments</div>
            <div class="appointment-time">Book your first appointment to get started</div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

</body>
</html>
