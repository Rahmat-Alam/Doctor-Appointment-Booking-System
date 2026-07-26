<?php
session_start();
include 'db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, full_name, password FROM patients WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $full_name, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['patient_id'] = $id;
            $_SESSION['full_name'] = $full_name;
            header("Location: patient_dashboard.php");
            exit();
        } else {
            $error = "❌ Invalid password.";
        }
    } else {
        $error = "❌ No account found with that email.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Login - KMCH</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #f0f8ff;
            color: #333;
            line-height: 1.6;
        }

        /* Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #0077b6;
            padding: 10px 30px;
        }

        .logo img {
            height: 50px;
        }

        .menu a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-weight: 500;
        }

        .menu a:hover {
            text-decoration: underline;
        }

        /* Container */
        .container {
            max-width: 400px;
            margin: 60px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #0077b6;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #0077b6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #005f8a;
        }

        .error {
            color: red;
            margin-bottom: 15px;
            text-align: center;
        }

        .register-link {
            text-align: center;
            margin-top: 10px;
        }

        .section {
            padding: 60px 15%;
            background-color: #f8f9fa;
            border-top: 1px solid #ccc;
        }

        .section h3 {
            color: #0077b6;
        }

        .footer {
            background-color: #023e8a;
            color: white;
            padding: 30px;
            text-align: center;
        }

        @media screen and (max-width: 600px) {
            .section {
                padding: 40px 20px;
            }

            .container {
                margin: 30px 20px;
            }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
            <div class="logo">
            <img src="images/hospital_logo.svg" alt="Hospital Appointment System Logo">
        </div>
    <div class="menu">
        <a href="index.php">Home</a>
        <a href="#">About</a>
        <a href="#" class="active">Login</a>
    </div>
</nav>

<!-- Login Form -->
<div class="container">
    <h2>Patient Login</h2>

    <?php if (!empty($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Enter your email" required />
        <input type="password" name="password" placeholder="Enter your password" required />
        <button type="submit">Login</button>
    </form>

    <div class="register-link">
        Don't have an account? <a href="register.php">Register here</a>
    </div>
</div>

<!-- Section 1 -->
<div class="section">
    <h3>Why Login is Important?</h3>
    <p>Login ensures that your health records, appointment history, and prescriptions remain secure and accessible only to you.</p>
</div>

<!-- Section 2 -->
<div class="section">
    <h3>Features After Login</h3>
    <ul>
        <li>✔ Book Appointments Instantly</li>
        <li>✔ Track Your Medical History</li>
        <li>✔ Give Feedback to Doctors</li>
        <li>✔ Update Your Profile Anytime</li>
    </ul>
</div>

<!-- Section 3 -->
<div class="section">
    <h3>How to Login?</h3>
    <ol>
        <li>Enter your registered Email</li>
        <li>Type your secure Password</li>
        <li>Click on Login</li>
        <li>Access your personalized dashboard</li>
    </ol>
</div>

<!-- Section 4 -->
<div class="section">
    <h3>Benefits of KMCH System</h3>
    <p>KMCH offers a user-friendly, secure and efficient platform for managing your healthcare needs online. No more long queues, missed appointments or data confusion.</p>
</div>

<!-- Section 5 -->
<div class="section">
    <h3>Data Protection & Security</h3>
    <p>All your information is encrypted and protected under HIPAA-compliant systems. Your privacy is our responsibility.</p>
</div>

<!-- Section 6 -->
<div class="section">
    <h3>What Our Patients Say</h3>
    <p><strong>Ravi Kumar:</strong> "बहुत आसान और तेज़ सिस्टम है। घर बैठे appointment बुक किया!"</p>
    <p><strong>Neha Verma:</strong> "Doctors की जानकारी और रिपोर्ट एक जगह मिल जाती है। Highly recommended!"</p>
</div>

<!-- Section 7 -->
<div class="section">
    <h3>Frequently Asked Questions</h3>
    <p><strong>Q:</strong> मैं अपना पासवर्ड भूल गया हूँ, क्या करूँ?<br><strong>A:</strong> Reset link जल्दी ही जोड़ दी जाएगी। अभी admin से संपर्क करें।</p>
    <p><strong>Q:</strong> Login करते वक्त Error आ रहा है?<br><strong>A:</strong> सही email और password डालें या registration करें।</p>
</div>

<!-- Footer -->
<div class="footer">
    <p>&copy; 2025 KMCH | All Rights Reserved</p>
    <p>Contact: support@kmch.in | +91-9876543210</p>
</div>

</body>
</html>
