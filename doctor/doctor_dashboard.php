<?php
session_start();
if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit();
}

include '../db.php';
$doctor_id = $_SESSION['doctor_id'];

// Get doctor info
$query = $conn->prepare("SELECT name, specialization FROM doctors WHERE id = ?");
$query->bind_param("i", $doctor_id);
$query->execute();
$query->bind_result($doctor_name, $specialization);
$query->fetch();
$query->close();

// Get statistics
$total_appointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = $doctor_id"))['total'];
$pending_appointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = $doctor_id AND status = 'pending'"))['total'];
$today_appointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE doctor_id = $doctor_id AND appointment_date = CURDATE()"))['total'];
$total_availability = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM doctor_availability WHERE doctor_id = $doctor_id"))['total'];

// Get recent appointments
$recent_appointments = $conn->query("SELECT a.*, p.full_name AS patient_name 
                                    FROM appointments a 
                                    JOIN patients p ON a.patient_id = p.id 
                                    WHERE a.doctor_id = $doctor_id 
                                    ORDER BY a.created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Doctor Dashboard</title>
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

    .doctor-info {
      text-align: right;
    }

    .doctor-name {
      color: #0077b6;
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 5px;
    }

    .specialization {
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

    .recent-activity {
      background: white;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }

    .recent-activity h3 {
      color: #0077b6;
      font-size: 20px;
      margin-bottom: 20px;
      font-weight: 600;
    }

    .activity-item {
      display: flex;
      align-items: center;
      padding: 15px 0;
      border-bottom: 1px solid #f0f0f0;
    }

    .activity-item:last-child {
      border-bottom: none;
    }

    .activity-icon {
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

    .activity-content {
      flex: 1;
    }

    .activity-title {
      font-weight: 500;
      color: #333;
      margin-bottom: 4px;
    }

    .activity-time {
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
    <!-- Dashboard Header -->
    <div class="dashboard-header">
      <div class="welcome-section">
        <h1>Welcome back, Doctor!</h1>
        <p>Here's what's happening with your appointments today</p>
      </div>
      <div class="doctor-info">
        <div class="doctor-name">Dr. <?= htmlspecialchars($doctor_name) ?></div>
        <div class="specialization"><?= htmlspecialchars($specialization) ?></div>
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
        <div class="stat-number"><?= $today_appointments ?></div>
        <div class="stat-label">Today's Appointments</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🗓️</div>
        <div class="stat-number"><?= $total_availability ?></div>
        <div class="stat-label">Available Slots</div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
      <h3>Quick Actions</h3>
      <div class="action-buttons">
        <a href="view_appointments.php" class="action-btn">
          📅 View All Appointments
        </a>
        <a href="manage_availability.php" class="action-btn">
          🗓️ Set Availability
        </a>
        <a href="manage_appointment.php" class="action-btn">
          🛠️ Manage Appointments
        </a>
        <a href="view_medical_records.php" class="action-btn">
          📋 My Medical Records
        </a>
        <a href="manage_account.php" class="action-btn">
          ⚙️ Update Profile
        </a>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
      <h3>Recent Appointments</h3>
      <?php if ($recent_appointments && $recent_appointments->num_rows > 0): ?>
        <?php while ($appointment = $recent_appointments->fetch_assoc()): ?>
          <div class="activity-item">
            <div class="activity-icon">
              <?php 
                $status = strtolower($appointment['status']);
                if ($status == 'pending') echo '⏳';
                elseif ($status == 'approved') echo '✅';
                elseif ($status == 'cancelled') echo '❌';
                else echo '📅';
              ?>
            </div>
            <div class="activity-content">
              <div class="activity-title">
                <?= htmlspecialchars($appointment['patient_name']) ?> - 
                <?= date('M d, Y', strtotime($appointment['appointment_date'])) ?> at 
                <?= date('h:i A', strtotime($appointment['appointment_time'])) ?>
              </div>
              <div class="activity-time">
                Status: <?= ucfirst(htmlspecialchars($appointment['status'])) ?>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="activity-item">
          <div class="activity-icon">📅</div>
          <div class="activity-content">
            <div class="activity-title">No recent appointments</div>
            <div class="activity-time">Start by setting your availability</div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

</body>
</html>
