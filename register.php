<?php
session_start();
include 'db.php';

function validate_name($name) {
    return preg_match('/^[a-zA-Z ]{2,50}$/', $name);
}
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
function validate_password($password) {
    // At least 8 chars, 1 uppercase, 1 lowercase, 1 number
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
}
function validate_phone($phone) {
    return preg_match('/^[0-9]{10,15}$/', $phone);
}
function validate_age($age) {
    return $age >= 1 && $age <= 120;
}

// Always define these before any HTML
$field_errors = [
    'full_name' => '',
    'email' => '',
    'password' => '',
    'phone' => '',
    'gender' => '',
    'age' => ''
];
$form_values = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'gender' => '',
    'age' => ''
];
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $age = intval($_POST['age']);

    if (!validate_name($full_name)) {
        $field_errors['full_name'] = "Full Name must be 2-50 letters and spaces only.";
    } else {
        $form_values['full_name'] = htmlspecialchars($full_name);
    }
    if (!validate_email($email)) {
        $field_errors['email'] = "Invalid email format.";
    } else {
        $form_values['email'] = htmlspecialchars($email);
    }
    if (!validate_password($password)) {
        $field_errors['password'] = "Password must be at least 8 characters, include 1 uppercase, 1 lowercase, and 1 number.";
    }
    if (!validate_phone($phone)) {
        $field_errors['phone'] = "Phone must be 10-15 digits.";
    } else {
        $form_values['phone'] = htmlspecialchars($phone);
    }
    if (!in_array($gender, ['Male', 'Female', 'Other'])) {
        $field_errors['gender'] = "Please select a valid gender.";
    } else {
        $form_values['gender'] = $gender;
    }
    if (!validate_age($age)) {
        $field_errors['age'] = "Age must be between 1 and 120.";
    } else {
        $form_values['age'] = $age;
    }

    $has_field_error = false;
    foreach ($field_errors as $err) {
        if (!empty($err)) {
            $has_field_error = true;
            break;
        }
    }

    if (!$has_field_error) {
        $check = $conn->prepare("SELECT id FROM patients WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $field_errors['email'] = "Email already registered!";
            $form_values['email'] = '';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO patients (full_name, email, password, phone, gender, age) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $full_name, $email, $hashed_password, $phone, $gender, $age);

            if ($stmt->execute()) {
                $_SESSION['patient_id'] = $stmt->insert_id;
                $_SESSION['patient_name'] = $full_name;
                header("Location: patient_dashboard.php");
                exit();
            } else {
                $error = "Error: " . $stmt->error;
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
    <meta charset="UTF-8">
    <title>Register Patient</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(120deg, #f0f8ff 0%, #dff3ec 100%);
            margin: 0;
            min-height: 100vh;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #0077b6;
            padding: 10px 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            position: relative;
        }
        .logo img {
            height: 48px;
        }
        .menu {
            display: flex;
            align-items: center;
        }
        .menu a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-size: 16px;
            transition: 0.3s;
        }
        .menu a:hover,
        .menu a.active {
            text-decoration: underline;
            font-weight: bold;
        }
        
        .main-content {
            display: flex;
            height: calc(100vh - 80px);
            justify-content: center;
            align-items: center;
        }

        .register-box {
            background: white;
            padding: 30px;
            width: 400px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        h2 {
            margin-bottom: 20px;
        }

        input, select {
            width: 100%;
            padding: 12px;
            margin: 8px 0 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
        }

        .message {
            margin-bottom: 10px;
            color: red;
        }

        .success {
            color: green;
        }

        a {
            display: block;
            text-align: center;
            margin-top: 10px;
        }

        .field-error {
            color: red;
            font-size: 13px;
            margin-top: -10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <img src="images/hospital_logo.svg" alt="Hospital Appointment System Logo">
        </div>
        <div class="menu">
            <a href="index.php">Home</a>
            <a href="about.html">About</a>
        </div>
    </nav>
    
    <div class="main-content">
        <div class="register-box">
    <h2>Patient Registration</h2>
    <?php if (!empty($error)) echo "<div class='message'>$error</div>"; ?>
    <?php if (!empty($success)) echo "<div class='message success'>$success</div>"; ?>

    <form method="POST" autocomplete="off">
        <input type="text" name="full_name" placeholder="Full Name" value="<?php echo $form_values['full_name'] ?? '' ?>" <?php if($field_errors['full_name']) echo 'style="border-color:red;"'; ?> required>
        <?php if($field_errors['full_name']) echo "<div class='field-error'>{$field_errors['full_name']}</div>"; ?>

        <input type="email" name="email" placeholder="Email Address" value="<?php echo $form_values['email'] ?? '' ?>" <?php if($field_errors['email']) echo 'style="border-color:red;"'; ?> required>
        <?php if($field_errors['email']) echo "<div class='field-error'>{$field_errors['email']}</div>"; ?>

        <input type="password" name="password" placeholder="Password" value="" <?php if($field_errors['password']) echo 'style="border-color:red;"'; ?> required>
        <?php if($field_errors['password']) echo "<div class='field-error'>{$field_errors['password']}</div>"; ?>

        <input type="text" name="phone" placeholder="Phone Number" value="<?php echo $form_values['phone'] ?? '' ?>" <?php if($field_errors['phone']) echo 'style="border-color:red;"'; ?> required>
        <?php if($field_errors['phone']) echo "<div class='field-error'>{$field_errors['phone']}</div>"; ?>

        <select name="gender" <?php if($field_errors['gender']) echo 'style="border-color:red;"'; ?> required>
            <option value="">Select Gender</option>
            <option <?php if(($form_values['gender'] ?? '')=='Male') echo 'selected'; ?>>Male</option>
            <option <?php if(($form_values['gender'] ?? '')=='Female') echo 'selected'; ?>>Female</option>
            <option <?php if(($form_values['gender'] ?? '')=='Other') echo 'selected'; ?>>Other</option>
        </select>
        <?php if($field_errors['gender']) echo "<div class='field-error'>{$field_errors['gender']}</div>"; ?>

        <input type="number" name="age" placeholder="Age" value="<?php echo $form_values['age'] ?? '' ?>" <?php if($field_errors['age']) echo 'style="border-color:red;"'; ?> required>
        <?php if($field_errors['age']) echo "<div class='field-error'>{$field_errors['age']}</div>"; ?>

        <button type="submit">Register</button>
        <a href="patient_login.php">Already have an account? Login</a>
    </form>
</div>
    </div>
</body>
</html>
