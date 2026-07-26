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
    echo "Invalid Appointment ID.";
    exit();
}

// Fetch appointment
$stmt = $conn->prepare("SELECT * FROM appointments WHERE id = ? AND patient_id = ?");
$stmt->bind_param("ii", $appointment_id, $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result->fetch_assoc();
if (!$appointment) {
    echo "Appointment not found or access denied.";
    exit();
}

// Get patient name
$query = $conn->prepare("SELECT full_name FROM patients WHERE id = ?");
$query->bind_param("i", $patient_id);
$query->execute();
$query->bind_result($full_name);
$query->fetch();
$query->close();

// Get unique departments
$departments = [];
$dept_result = $conn->query("SELECT DISTINCT specialization FROM doctors WHERE specialization IS NOT NULL AND specialization != ''");
while ($row = $dept_result->fetch_assoc()) {
    $departments[] = $row['specialization'];
}

// Get all doctors (for JS filtering)
$doctors = $conn->query("SELECT id, name, specialization FROM doctors");
$doctor_list = [];
while ($doc = $doctors->fetch_assoc()) {
    $doctor_list[] = $doc;
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

// AJAX: Return available 20-min slots for a doctor/date
if (isset($_GET['fetch_slots']) && isset($_GET['doctor_id']) && isset($_GET['date'])) {
    $doctor_id = intval($_GET['doctor_id']);
    $date = $_GET['date'];
    $slots = [];
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
    $stmt2 = $conn->prepare("SELECT appointment_time FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND id != ?");
    $stmt2->bind_param("isi", $doctor_id, $date, $appointment_id);
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
        if ($end <= $start) {
            $end->modify('+1 day');
        }
        while ($start < $end) {
            $slot_start = $start->format('H:i');
            $slot_end = $start->modify('+20 minutes');
            if ($slot_end > $end) break;
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

$message = "";

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $doctor_id = $_POST['doctor_id'];
    $date = $_POST['appointment_date'];
    $time = $_POST['appointment_time'];
    // Check slot availability (exclude this appointment)
    $check = $conn->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND id != ?");
    $check->bind_param("issi", $doctor_id, $date, $time, $appointment_id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $message = "⚠️ Slot already booked. Please choose a different time.";
    } else {
        $update = $conn->prepare("UPDATE appointments SET doctor_id=?, appointment_date=?, appointment_time=?, status='Pending' WHERE id=? AND patient_id=?");
        $update->bind_param("sssii", $doctor_id, $date, $time, $appointment_id, $patient_id);
        if ($update->execute()) {
            echo "<script>alert('Appointment updated successfully.'); window.location.href='appointment_history.php';</script>";
            exit();
        } else {
            $message = "❌ Failed to update appointment.";
        }
        $update->close();
    }
    $check->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Appointment</title>
  <link rel="stylesheet" href="patient.css">
  <style>
    @media (max-width: 768px) {
      .main { margin-left: 0; padding: 10px; }
      .sidebar { position: static; width: 100%; height: auto; }
      .card { max-width: 100%; padding: 10px; }
    }
    .msg { margin-top: 10px; padding: 10px; background-color: #e1f5e1; border-left: 5px solid green; color: #2e7d32; border-radius: 5px; }
    .msg.error { background: #ffeaea; color: #b71c1c; border-left: 5px solid #b71c1c; }
  </style>
</head>
<body>
<div class="sidebar">
    <h2>Patient Dashboard</h2>
    <p>👤 <strong><?= htmlspecialchars($full_name) ?></strong></p>
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
      <h3>✏️ Edit Appointment</h3>
      <?php if (!empty($message)): ?>
        <div class="msg error"><?= $message ?></div>
      <?php endif; ?>
      <form method="POST" id="editForm">
        <label for="department">Select Department:</label>
        <select name="department" id="department" required>
          <option value="">-- Select Department --</option>
          <?php foreach ($departments as $dept): ?>
            <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
          <?php endforeach; ?>
        </select>
        <label for="doctor_id">Select Doctor:</label>
        <select name="doctor_id" id="doctor_id" required>
          <option value="">-- Select Doctor --</option>
        </select>
        <label for="appointment_date">Select Date Of Appointment</label>
        <select name="appointment_date" id="appointment_date" required>
          <option value="">-- Select Date --</option>
        </select>
        <label for="appointment_time">Time Slot (20 min):</label>
        <select name="appointment_time" id="appointment_time" required>
          <option value="">-- Select Time Slot --</option>
        </select>
        <button type="submit">Update Appointment</button>
      </form>
      <br>
      <a href="appointment_history.php">⬅️ Back to History</a>
    </div>
  </div>
  <script>
    // JS for dynamic dropdowns
    const doctorList = <?php echo json_encode($doctor_list); ?>;
    const departmentSelect = document.getElementById('department');
    const doctorSelect = document.getElementById('doctor_id');
    const dateSelect = document.getElementById('appointment_date');
    const timeSelect = document.getElementById('appointment_time');
    // Pre-select department, doctor, date, time
    const currentDoctorId = "<?= $appointment['doctor_id'] ?>";
    const currentDate = "<?= $appointment['appointment_date'] ?>";
    const currentTime = "<?= $appointment['appointment_time'] ?>";
    let currentDept = '';
    doctorList.forEach(doc => { if (doc.id == currentDoctorId) currentDept = doc.specialization; });
    // Set department
    departmentSelect.value = currentDept;
    // Populate doctor dropdown
    function populateDoctors() {
      const dept = departmentSelect.value;
      doctorSelect.innerHTML = '<option value="">-- Select Doctor --</option>';
      dateSelect.innerHTML = '<option value="">-- Select Date --</option>';
      timeSelect.innerHTML = '<option value="">-- Select Time Slot --</option>';
      doctorList.forEach(doc => {
        if (doc.specialization === dept) {
          const opt = document.createElement('option');
          opt.value = doc.id;
          opt.textContent = doc.name + ' (' + doc.specialization + ')';
          if (doc.id == currentDoctorId) opt.selected = true;
          doctorSelect.appendChild(opt);
        }
      });
    }
    populateDoctors();
    // Populate date dropdown
    function fetchDates() {
      const doctorId = doctorSelect.value;
      dateSelect.innerHTML = '<option value="">-- Select Date --</option>';
      timeSelect.innerHTML = '<option value="">-- Select Time Slot --</option>';
      if (doctorId) {
        fetch(`edit_appointment.php?fetch_dates=1&doctor_id=${doctorId}&id=<?= $appointment_id ?>`)
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
                if (date === currentDate) opt.selected = true;
                dateSelect.appendChild(opt);
              });
            }
            fetchSlots();
          });
      }
    }
    // Populate slot dropdown
    function fetchSlots() {
      const doctorId = doctorSelect.value;
      const date = dateSelect.value;
      timeSelect.innerHTML = '<option value="">-- Select Time Slot --</option>';
      if (doctorId && date) {
        fetch(`edit_appointment.php?fetch_slots=1&doctor_id=${doctorId}&date=${date}&id=<?= $appointment_id ?>`)
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
                if (`${slot.start}:00` === currentTime) opt.selected = true;
                timeSelect.appendChild(opt);
              });
            }
          });
      }
    }
    departmentSelect.addEventListener('change', function() {
      populateDoctors();
      fetchDates();
    });
    doctorSelect.addEventListener('change', function() {
      fetchDates();
    });
    dateSelect.addEventListener('change', function() {
      fetchSlots();
    });
    // On page load, fetch dates and slots for current doctor/date/time
    fetchDates();
  </script>
</body>
</html>
