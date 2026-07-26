<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

include '../db.php';

$doctor_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM doctors"))['total'];
$patient_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM patients"))['total'];
$appointment_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments"))['total'];
$pending_appointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'"))['total'];
$approved_appointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE status = 'approved'"))['total'];
$cancelled_appointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE status = 'cancelled'"))['total'];

// Fetch analytics data for charts
// Appointments per month (last 12 months)
$appts_per_month = [];
$months = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $months[] = date('M Y', strtotime($month.'-01'));
    $res = mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE DATE_FORMAT(appointment_date, '%Y-%m') = '$month'");
    $row = mysqli_fetch_assoc($res);
    $appts_per_month[] = (int)$row['total'];
}
// New users per month (last 12 months)
$patients_per_month = [];
$doctors_per_month = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $res1 = mysqli_query($conn, "SELECT COUNT(*) as total FROM patients WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'");
    $row1 = mysqli_fetch_assoc($res1);
    $patients_per_month[] = (int)$row1['total'];
    $res2 = mysqli_query($conn, "SELECT COUNT(*) as total FROM doctors WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'");
    $row2 = mysqli_fetch_assoc($res2);
    $doctors_per_month[] = (int)$row2['total'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>

    <div class="header">
        <h1>Admin Dashboard</h1>
        <div class="logout">
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="nav">
        <a href="admin_dashboard.php" class="active">Dashboard</a>
        <a href="add_admin.php">Add Admin</a>
        <a href="add_doctor.php">Add Doctor</a>
        <a href="add_patient.php">Add Patient</a>
        <a href="manage_doctors.php">Manage Doctors</a>
        <a href="manage_patients.php">Manage Patients</a>
        <a href="manage_appointments.php">Manage Appointments</a>
        <a href="manage_departments.php">Manage Departments</a>
        <a href="view_medical_records.php">View Medical Records</a>
    </div>

    <div class="content">
        <h2>Admin Dashboard</h2>

        <div class="card-container">
            <div class="card">
                <h3>Doctors</h3>
                <p class="count"><?= $doctor_count ?></p>
                <p>Manage doctor records and schedules.</p>
            </div>
            <div class="card">
                <h3>Patients</h3>
                <p class="count"><?= $patient_count ?></p>
                <p>View and manage patient accounts.</p>
            </div>
            <div class="card">
                <h3>Total Appointments</h3>
                <p class="count"><?= $appointment_count ?></p>
                <p>All bookings in the system.</p>
            </div>
            <div class="card">
                <h3>Approved Appointments</h3>
                <p class="count approved"><?= $approved_appointments ?></p>
                <p>Confirmed by doctors/admin.</p>
            </div>
            <div class="card">
                <h3>Pending Appointments</h3>
                <p class="count pending"><?= $pending_appointments ?></p>
                <p>Waiting for approval.</p>
            </div>
            <div class="card">
                <h3>Cancelled Appointments</h3>
                <p class="count cancelled"><?= $cancelled_appointments ?></p>
                <p>Cancelled by patients or admin.</p>
            </div>
        </div>
        <div class="card" style="margin-top:40px;">
            <h3>📈 Appointment Trends (Last 12 Months)</h3>
            <canvas id="apptsChart" height="80"></canvas>
        </div>
        <div class="card" style="margin-top:30px;">
            <h3>👥 New Users Per Month</h3>
            <canvas id="usersChart" height="80"></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      // Appointments per month
      const apptLabels = <?= json_encode($months) ?>;
      const apptData = <?= json_encode($appts_per_month) ?>;
      const ctx1 = document.getElementById('apptsChart').getContext('2d');
      new Chart(ctx1, {
        type: 'line',
        data: {
          labels: apptLabels,
          datasets: [{
            label: 'Appointments',
            data: apptData,
            borderColor: '#3498db',
            backgroundColor: 'rgba(52,152,219,0.1)',
            fill: true,
            tension: 0.3
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true } }
        }
      });
      // New users per month
      const userLabels = <?= json_encode($months) ?>;
      const patientData = <?= json_encode($patients_per_month) ?>;
      const doctorData = <?= json_encode($doctors_per_month) ?>;
      const ctx2 = document.getElementById('usersChart').getContext('2d');
      new Chart(ctx2, {
        type: 'bar',
        data: {
          labels: userLabels,
          datasets: [
            {
              label: 'Patients',
              data: patientData,
              backgroundColor: '#10b981'
            },
            {
              label: 'Doctors',
              data: doctorData,
              backgroundColor: '#3b82f6'
            }
          ]
        },
        options: {
          responsive: true,
          scales: { y: { beginAtZero: true } }
        }
      });
    </script>

</body>
</html>
