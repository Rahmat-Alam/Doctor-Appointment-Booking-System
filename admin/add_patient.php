<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

include '../db.php';

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];

    $sql = "INSERT INTO patients (full_name, gender, age, email, phone, password)
            VALUES ('$full_name', '$gender', $age, '$email', '$phone', '$password')";

    if (mysqli_query($conn, $sql)) {
        $success = "Patient added successfully!";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Patient</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <div class="header">
        <h1>Add Patient</h1>
        <div class="logout">
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="nav">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="add_admin.php">Add Admin</a>
        <a href="add_doctor.php">Add Doctor</a>
        <a href="add_patient.php" class="active">Add Patient</a>
        <a href="manage_doctors.php">Manage Doctors</a>
        <a href="manage_patients.php">Manage Patients</a>
        <a href="manage_appointments.php">Manage Appointments</a>
        <a href="manage_departments.php">Manage Departments</a>
        <a href="view_medical_records.php">View Medical Records</a>
    </div>

    <div class="content">
        <h2>Add New Patient</h2>
        
        <div class="form-container">
            <?php if ($success): ?>
                <div class="message success"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="message error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required>
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="age" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-success">Add Patient</button>
            </form>

            <div class="text-center mt-20">
                <a href="manage_patients.php" class="btn btn-primary">← Back to Manage Patients</a>
            </div>
        </div>
    </div>

</body>
</html>
