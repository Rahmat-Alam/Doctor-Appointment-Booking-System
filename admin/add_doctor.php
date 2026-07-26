<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

include '../db.php';
include '../departments_config.php';

$success = "";
$error = "";

// Get departments from centralized config
$departments = getDepartments();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate Name
    $name = trim($_POST['name']);
    if (!preg_match('/^[a-zA-Z .]{3,50}$/', $name)) {
        $error = "Name must be 3-50 letters, spaces, or dots.";
    }
    // Validate Email
    elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $email = $_POST['email'];
        $check_email = $conn->prepare("SELECT id FROM doctors WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $check_email->store_result();
        if ($check_email->num_rows > 0) {
            $error = "Email already exists.";
        }
        $check_email->close();
    }
    // Validate Password
    if (empty($error)) {
        $password = $_POST['password'];
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}$/', $password)) {
            $error = "Password must be at least 8 characters, include uppercase, lowercase, number, and special character.";
        }
    }
    // Validate Contact
    if (empty($error)) {
        $contact = trim($_POST['contact']);
        if (!preg_match('/^\+?\d{10,15}$/', $contact)) {
            $error = "Contact must be 10-15 digits, can start with +.";
        }
    }
    // Validate Address
    if (empty($error)) {
        $address = trim($_POST['address']);
        if (!preg_match('/^.{5,100}$/', $address)) {
            $error = "Address must be 5-100 characters.";
        }
    }
    // Validate Department
    if (empty($error)) {
        $department = $_POST['department'];
        if (!isValidDepartment($department)) {
            $error = "Invalid department selected.";
        }
    }
    // Validate Qualification
    if (empty($error)) {
        $qualification = trim($_POST['qualification']);
        if (!preg_match('/^[a-zA-Z .]{2,50}$/', $qualification)) {
            $error = "Qualification must be 2-50 letters, spaces, or dots.";
        }
    }
    // Validate Experience
    if (empty($error)) {
        $experience = intval($_POST['experience']);
        if ($experience < 0 || $experience > 60) {
            $error = "Experience must be between 0 and 60 years.";
        }
    }
    // Profile Picture Upload
    $profile_pic = "";
    if (empty($error) && $_FILES['profile_pic']['name']) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $max_size = 2 * 1024 * 1024; // 2MB
        $file_type = mime_content_type($_FILES['profile_pic']['tmp_name']);
        $file_size = $_FILES['profile_pic']['size'];
        if (!in_array($file_type, $allowed_types)) {
            $error = "Profile picture must be a JPG, JPEG, PNG, or GIF image.";
        } elseif ($file_size > $max_size) {
            $error = "Profile picture must be less than 2MB.";
        } else {
            $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $profile_pic = uniqid('doctor_', true) . '.' . $ext;
            $target_dir = "../images/";
            $target_file = $target_dir . $profile_pic;
            move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file);
        }
    }
    // Insert if no error
    if (empty($error)) {
        $stmt = $conn->prepare("INSERT INTO doctors (name, email, password, contact, address, specialization, qualification, experience, profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssis", $name, $email, $password, $contact, $address, $department, $qualification, $experience, $profile_pic);
        if ($stmt->execute()) {
            $success = "Doctor added successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Doctor</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
    <div class="header">
        <h1>Add Doctor</h1>
        <div class="logout">
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="nav">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="add_admin.php">Add Admin</a>
        <a href="add_doctor.php" class="active">Add Doctor</a>
        <a href="add_patient.php">Add Patient</a>
        <a href="manage_doctors.php">Manage Doctors</a>
        <a href="manage_patients.php">Manage Patients</a>
        <a href="manage_appointments.php">Manage Appointments</a>
        <a href="manage_departments.php">Manage Departments</a>
        <a href="view_medical_records.php">View Medical Records</a>
    </div>

    <div class="content">
        <h2>Add New Doctor</h2>
        
        <div class="form-container">
            <?php if ($success): ?>
                <div class="message success"><?= $success ?></div>
            <?php elseif ($error): ?>
                <div class="message error"><?= $error ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required pattern="[a-zA-Z .]{3,50}" title="3-50 letters, spaces, or dots">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required pattern="^[^@\s]+@[^@\s]+\.[^@\s]+$" title="Enter a valid email address">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}$" title="Min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char">
                </div>

                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact" required pattern="^\+?\d{10,15}$" title="10-15 digits, can start with +">
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" required pattern=".{5,100}" title="5-100 characters">
                </div>

                <div class="form-group">
                    <label>Department</label>
                    <select name="department" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Qualification</label>
                    <input type="text" name="qualification" required pattern="[a-zA-Z .]{2,50}" title="2-50 letters, spaces, or dots">
                </div>

                <div class="form-group">
                    <label>Experience (In Years)</label>
                    <input type="number" name="experience" required min="0" max="60">
                </div>

                <div class="form-group">
                    <label>Profile Picture</label>
                    <input type="file" name="profile_pic" accept="image/jpeg,image/png,image/gif,image/jpg">
                </div>

                <button type="submit" class="btn btn-success">Add Doctor</button>
            </form>

            <div class="text-center mt-20">
                <a href="manage_doctors.php" class="btn btn-primary">← Back to Manage Doctors</a>
            </div>
        </div>
    </div>

</body>
</html>
