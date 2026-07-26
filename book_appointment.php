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

// Get unique departments (specializations)
$departments = [];
$dept_result = $conn->query("SELECT DISTINCT specialization FROM doctors WHERE specialization IS NOT NULL AND specialization != ''");
while ($row = $dept_result->fetch_assoc()) {
    $departments[] = $row['specialization'];
}

// Get doctors (all, for JS filtering)
$doctors = $conn->query("SELECT id, name, specialization FROM doctors");
$doctor_list = [];
while ($doc = $doctors->fetch_assoc()) {
    $doctor_list[] = $doc;
}

// AJAX: Return 20-min slots for a given doctor and date
if (isset($_GET['fetch_slots']) && isset($_GET['doctor_id']) && isset($_GET['date'])) {
    $doctor_id = intval($_GET['doctor_id']);
    $date = $_GET['date'];
    $slots = [];
    // Get all availability periods for this doctor/date
    $stmt = $conn->prepare("SELECT start_time, end_time FROM doctor_availability WHERE doctor_id = ? AND date = ?");
    $stmt->bind_param("is", $doctor_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $periods = [];
    while ($row = $result->fetch_assoc()) {
        $periods[] = [
            'start' => $row['start_time'],
            'end' => $row['end_time']
        ];
    }
    // Get already booked slots for this doctor/date
    $booked = [];
    $stmt2 = $conn->prepare("SELECT appointment_time FROM appointments WHERE doctor_id = ? AND appointment_date = ?");
    $stmt2->bind_param("is", $doctor_id, $date);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($row = $res2->fetch_assoc()) {
        $booked[] = $row['appointment_time'];
    }
    $stmt2->close();
    // For each period, generate 20-min slots
    foreach ($periods as $p) {
        $start = new DateTime($p['start']);
        $end = new DateTime($p['end']);
        // Handle overnight slots (end < start)
        if ($end <= $start) {
            $end->modify('+1 day');
        }
        while ($start < $end) {
            $slot_start = $start->format('H:i');
            $slot_end = $start->modify('+20 minutes');
            if ($slot_end > $end) break;
            $slot_label = $slot_start . ' - ' . $start->format('H:i');
            if (!in_array($slot_start . ':00', $booked)) {
                $slots[] = [
                    'start' => $slot_start,
                    'end' => $start->format('H:i')
                ];
            }
        }
    }
    header('Content-Type: application/json');
    echo json_encode($slots);
    exit();
}

// AJAX: Return available dates for a given doctor
if (isset($_GET['fetch_dates']) && isset($_GET['doctor_id'])) {
    $doctor_id = intval($_GET['doctor_id']);
    $dates = [];
    $stmt = $conn->prepare("SELECT DISTINCT date FROM doctor_availability WHERE doctor_id = ? AND date >= CURDATE() ORDER BY date ASC");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $dates[] = $row['date'];
    }
    header('Content-Type: application/json');
    echo json_encode($dates);
    exit();
}

$message = "";

// Appointment booking
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_id = isset($_POST['doctor']) ? $_POST['doctor'] : null;
    $date = isset($_POST['date']) ? $_POST['date'] : null;
    $time = isset($_POST['appointment_time']) ? $_POST['appointment_time'] : null;
    if (!$doctor_id || !$date || !$time) {
        $message = "❌ Please select doctor, date, and time slot.";
    } else {
        // Check slot availability
        $check = $conn->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?");
        $check->bind_param("iss", $doctor_id, $date, $time);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $message = "⚠️ Slot already booked. Please choose a different time.";
        } else {
            $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, 'approved')");
            $stmt->bind_param("iiss", $patient_id, $doctor_id, $date, $time);
            if ($stmt->execute()) {
                $message = "✅ Appointment booked and approved successfully!";
            } else {
                $message = "❌ Failed to book appointment.";
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Book Appointment</title>
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
      max-width: 600px;
    }

    .card h3 {
      margin-bottom: 15px;
    }

    form label {
      display: block;
      margin-top: 10px;
    }

    form select, form input[type="date"] {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 16px;
    }

    form button {
      margin-top: 20px;
      padding: 12px;
      width: 100%;
      background-color: #3498db;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
    }

    form button:hover {
      background-color: #2980b9;
    }

    .msg {
      margin-top: 10px;
      padding: 10px;
      background-color: #e1f5e1;
      border-left: 5px solid green;
      color: #2e7d32;
      border-radius: 5px;
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
      <h3>📅 Book an Appointment</h3>

      <?php if (!empty($message)): ?>
        <div class="msg"><?= $message ?></div>
      <?php endif; ?>

      <form method="POST">
        <label for="department">Select Department:</label>
        <select name="department" id="department" required>
          <option value="">-- Select Department --</option>
          <?php foreach ($departments as $dept): ?>
            <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
          <?php endforeach; ?>
        </select>
        <label for="doctor">Select Doctor</label>
        <select name="doctor" id="doctor" required>
          <option value="">Select Doctor</option>
          <!-- Options will be populated by JS -->
        </select>
        <label for="date">Appointment Date</label>
        <select name="date" id="date" required>
          <option value="">-- Select Date --</option>
        </select>
        <label for="appointment_time">Time Slot (20 min):</label>
        <select name="appointment_time" id="appointment_time" required>
          <option value="">-- Select Time Slot --</option>
        </select>
        <button type="submit">Book Appointment</button>
      </form>
    </div>
  </div>

  <script>
    // Department and doctor filtering
    const departmentSelect = document.getElementById('department');
    const doctorSelect = document.getElementById('doctor');
    const dateSelect = document.getElementById('date');
    const timeSelect = document.getElementById('appointment_time');
    const doctorList = <?php echo json_encode($doctor_list); ?>;
    departmentSelect.addEventListener('change', function() {
      const dept = this.value;
      doctorSelect.innerHTML = '<option value="">-- Select Doctor --</option>';
      dateSelect.innerHTML = '<option value="">-- Select Date --</option>';
      timeSelect.innerHTML = '<option value="">-- Select Time Slot --</option>';
      doctorList.forEach(doc => {
        if (doc.specialization === dept) {
          const opt = document.createElement('option');
          opt.value = doc.id;
          opt.textContent = doc.name + ' (' + doc.specialization + ')';
          doctorSelect.appendChild(opt);
        }
      });
    });
    doctorSelect.addEventListener('change', function() {
      const doctorId = this.value;
      dateSelect.innerHTML = '<option value="">-- Select Date --</option>';
      timeSelect.innerHTML = '<option value="">-- Select Time Slot --</option>';
      if (doctorId) {
        fetch(`book_appointment.php?fetch_dates=1&doctor_id=${doctorId}`)
          .then(res => res.json())
          .then(dates => {
            if (dates.length === 0) {
              const opt = document.createElement('option');
              opt.value = '';
              opt.textContent = 'No dates available';
              dateSelect.appendChild(opt);
            } else {
              dates.forEach(date => {
                const opt = document.createElement('option');
                opt.value = date;
                opt.textContent = date;
                dateSelect.appendChild(opt);
              });
            }
          });
      }
    });
    // Dynamic 20-min time slots
    function fetchSlots() {
      const doctorId = doctorSelect.value;
      const date = dateSelect.value;
      timeSelect.innerHTML = '<option value="">-- Select Time Slot --</option>';
      if (doctorId && date) {
        fetch(`book_appointment.php?fetch_slots=1&doctor_id=${doctorId}&date=${date}`)
          .then(res => res.json())
          .then(slots => {
            if (slots.length === 0) {
              const opt = document.createElement('option');
              opt.value = '';
              opt.textContent = 'No slots available';
              timeSelect.appendChild(opt);
            } else {
              slots.forEach(slot => {
                const opt = document.createElement('option');
                opt.value = slot.start + ':00';
                opt.textContent = `${slot.start} - ${slot.end}`;
                timeSelect.appendChild(opt);
              });
            }
          });
      }
    }
    dateSelect.addEventListener('change', fetchSlots);
  </script>

</body>
</html>

<?php $conn->close(); ?>
