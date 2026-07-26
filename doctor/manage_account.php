<?php
include '../db.php';
include '../departments_config.php';
session_start();

if (!isset($_SESSION['doctor_id'])) {
    header("Location: doctor_login.php");
    exit();
}

$doctor_id = $_SESSION['doctor_id'];

// Get departments from centralized config
$departments = getDepartments();

// Fetch doctor details
$sql = "SELECT name, email, specialization FROM doctors WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$stmt->bind_result($name, $email, $specialization);
$stmt->fetch();
$stmt->close();

// Update account details
$success = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_name = $_POST['full_name'];
    $new_email = $_POST['email'];
    $new_specialization = $_POST['specialization'] === 'Other' ? trim($_POST['custom_specialization']) : $_POST['specialization'];

    $update_sql = "UPDATE doctors SET name = ?, email = ?, specialization = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sssi", $new_name, $new_email, $new_specialization, $doctor_id);

    if ($update_stmt->execute()) {
        $success = "Account updated successfully.";
        $name = $new_name;
        $email = $new_email;
        $specialization = $new_specialization;
    }

    $update_stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Account</title>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
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
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 600px;
        }

        h3 {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        button {
            margin-top: 20px;
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background: #2563eb;
        }

        .logout {
            margin-top: 30px;
            display: inline-block;
            color: red;
            font-weight: bold;
        }

        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
        }

        #custom_specialization {
            display: none;
            margin-top: 5px;
        }
    </style>
    <script>
    function toggleCustomSpecialization() {
        var specSelect = document.getElementById('specialization');
        var customInput = document.getElementById('custom_specialization');
        if (specSelect.value === 'Other') {
            customInput.style.display = 'block';
            customInput.required = true;
        } else {
            customInput.style.display = 'none';
            customInput.required = false;
        }
    }
    </script>
</head>
<body>

<div class="sidebar">
    <h2>Doctor Dashboard</h2>
    <p>👨‍⚕️ <strong><?= htmlspecialchars($name) ?></strong></p>
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
        <h3>Manage Account</h3>

        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <form method="post">
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($name) ?>" required>

            <label>Email Address</label>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

            <label>Specialization</label>
            <select name="specialization" id="specialization" onchange="toggleCustomSpecialization()" required>
                <option value="">Select Specialization</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= htmlspecialchars($dept) ?>" <?= ($specialization == $dept) ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option>
                <?php endforeach; ?>
                <option value="Other" <?= (!in_array($specialization, $departments)) ? 'selected' : '' ?>>Other</option>
            </select>
            <input type="text" name="custom_specialization" id="custom_specialization" placeholder="Enter specialization" value="<?= (!in_array($specialization, $departments)) ? htmlspecialchars($specialization) : '' ?>">

            <button type="submit">Update Account</button>
        </form>
    </div>
</div>

<script>
window.onload = function() {
    toggleCustomSpecialization();
};
</script>

</body>
</html>
