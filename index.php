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

        if ($password === $hashed_password) {
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
    <title>Hospital Appointment System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style1.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(120deg, #f0f8ff 0%, #dff3ec 100%);
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
        .hero {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 60vh;
            text-align: center;
            padding: 60px 20px 30px 20px;
            position: relative;
            overflow: hidden;
            width: 100vw;
            height: 100vh;
            min-height: 100vh;
        }
        .hero-bg {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            width: 100vw;
            height: 100vh;
            z-index: 0;
            background-size: cover;
            background-position: center center;
            transition: opacity 1s;
            opacity: 0;
        }
        .hero-bg.active {
            opacity: 1;
        }
        .hero-content {
            position: relative;
            z-index: 1;
        }
        .hero h1 {
            font-size: 2.8rem;
            color: #023e8a;
            margin-bottom: 18px;
        }
        .hero p {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 32px;
        }
        .login-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-bottom: 40px;
        }
        .login-btn {
            background: #0077b6;
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .login-btn:hover {
            background: #023e8a;
        }
        .features {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 32px;
            margin: 0 auto 60px auto;
            max-width: 1100px;
        }
        .feature-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 32px 24px;
            flex: 1 1 300px;
            min-width: 260px;
            max-width: 340px;
            text-align: center;
        }
        .feature-card h3 {
            color: #0077b6;
            margin-bottom: 12px;
        }
        .feature-card p {
            color: #333;
            font-size: 1rem;
        }
        @media (max-width: 900px) {
            .features {
                flex-direction: column;
                align-items: center;
            }
        }
        footer {
            background: #023e8a;
            color: white;
            text-align: center;
            padding: 30px 10px 18px 10px;
            margin-top: 40px;
        }
        
        /* Featured Doctors Section */
        .featured-doctors {
            padding: 60px 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .featured-doctors h2 {
            text-align: center;
            font-size: 2.5rem;
            color: #023e8a;
            margin-bottom: 10px;
        }
        
        .section-subtitle {
            text-align: center;
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 50px;
        }
        
        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .doctor-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .doctor-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            align-items: center;
            gap: 20px;
            cursor: pointer;
        }
        
        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .doctor-avatar {
            flex-shrink: 0;
        }
        
        .doctor-avatar img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #0077b6;
        }
        
        .avatar-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0077b6, #023e8a);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            border: 3px solid #0077b6;
        }
        
        .doctor-info {
            flex: 1;
        }
        
        .doctor-info h3 {
            color: #023e8a;
            font-size: 1.3rem;
            margin: 0 0 8px 0;
            font-weight: 600;
        }
        
        .doctor-info p {
            margin: 5px 0;
            color: #555;
            font-size: 0.95rem;
        }
        
        .specialization {
            color: #0077b6 !important;
            font-weight: 500;
        }
        
        .qualification {
            color: #28a745 !important;
            font-weight: 500;
        }
        
        .experience {
            color: #ffc107 !important;
            font-weight: 500;
        }
        
        .rating-section {
            margin-top: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stars {
            display: flex;
            gap: 2px;
        }
        
        .star {
            color: #ddd;
            font-size: 16px;
            transition: color 0.2s ease;
        }
        
        .star.filled {
            color: #ffc107;
        }
        
        .rating-text {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }
        
        .no-doctors {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .doctors-grid {
                grid-template-columns: 1fr;
            }
            
            .doctor-card {
                flex-direction: column;
                text-align: center;
            }
            
            .featured-doctors h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <img src="images/hospital_logo.svg" alt="Hospital Appointment System Logo">
        </div>
        <div class="menu">
            <a href="index.php" class="active">Home</a>
            <a href="about.html">About</a>
        </div>
    </nav>
    <section class="hero">
        <div class="hero-bg" id="hero-bg-0" style="background-image:url('images/1st.png'); opacity:1;"></div>
        <div class="hero-bg" id="hero-bg-1" style="background-image:url('images/2nd.png');"></div>
        <div class="hero-bg" id="hero-bg-2" style="background-image:url('images/3rd.png');"></div>
        <div class="hero-bg" id="hero-bg-3" style="background-image:url('images/4th.png');"></div>
        <div class="hero-bg" id="hero-bg-4" style="background-image:url('images/5th.png');"></div>
        <div class="hero-bg" id="hero-bg-5" style="background-image:url('images/6th.png');"></div>
        <div class="hero-content">
            <h1>Welcome to the Online Doctor Appointment Booking System</h1>
            <p>Book appointments, manage your health records, and connect with doctors easily and securely.</p>
            <div class="login-buttons">
                <a href="patient_login.php" class="login-btn">Patient Login</a>
                <a href="register.php" class="login-btn">Patient Register</a>
                <a href="doctor/doctor_login.php" class="login-btn">Doctor Login</a>
                <a href="admin/admin_login.php" class="login-btn">Admin Login</a>
            </div>
        </div>
    </section>
    <section class="features">
        <div class="feature-card">
            <h3>For Patients</h3>
            <p>Register, search doctors, book or cancel appointments, view medical records, rate doctors, and manage your profile.</p>
        </div>
        <div class="feature-card">
            <h3>For Doctors</h3>
            <p>Login, set availability, view appointments, manage your profile, and confirm appointments.</p>
        </div>
        <div class="feature-card">
            <h3>For Admins</h3>
            <p>Manage users, monitor appointments, and access analytics dashboard for trends and user activity.</p>
        </div>
    </section>
    
    <!-- Featured Doctors Section -->
    <section class="featured-doctors">
        <div class="container">
            <h2>👨‍⚕️ Our Featured Doctors</h2>
            <p class="section-subtitle">Meet our experienced healthcare professionals</p>
            <div class="doctors-grid">
                <?php
                // Fetch doctors with their ratings and reviews
                $doctors_query = "SELECT d.*, 
                                       AVG(dr.rating) as avg_rating,
                                       COUNT(dr.id) as review_count
                                FROM doctors d 
                                LEFT JOIN doctor_reviews dr ON d.id = dr.doctor_id 
                                GROUP BY d.id 
                                ORDER BY avg_rating DESC, review_count DESC 
                                LIMIT 6";
                $doctors_result = $conn->query($doctors_query);
                
                if ($doctors_result && $doctors_result->num_rows > 0):
                    while ($doctor = $doctors_result->fetch_assoc()):
                        $avg_rating = $doctor['avg_rating'] ? round($doctor['avg_rating'], 1) : 0;
                        $review_count = $doctor['review_count'] ?: 0;
                ?>
                    <a href="doctor_profile.php?id=<?= $doctor['id'] ?>" class="doctor-card-link">
                        <div class="doctor-card">
                            <div class="doctor-avatar">
                                <?php if (!empty($doctor['profile_pic'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($doctor['profile_pic']) ?>" alt="Dr. <?= htmlspecialchars($doctor['name']) ?>">
                                <?php else: ?>
                                    <div class="avatar-placeholder">👨‍⚕️</div>
                                <?php endif; ?>
                            </div>
                            <div class="doctor-info">
                                <h3>Dr. <?= htmlspecialchars($doctor['name']) ?></h3>
                                <p class="specialization">🏥 <?= htmlspecialchars($doctor['specialization']) ?></p>
                                <p class="qualification">🎓 <?= htmlspecialchars($doctor['qualification']) ?></p>
                                <p class="experience">⏰ <?= htmlspecialchars($doctor['experience']) ?> years experience</p>
                                <div class="rating-section">
                                    <div class="stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?= $i <= $avg_rating ? 'filled' : '' ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="rating-text"><?= $avg_rating ?>/5 (<?= $review_count ?> reviews)</span>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php 
                    endwhile;
                else:
                ?>
                    <div class="no-doctors">
                        <p>No doctors available at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <footer>
        &copy; <?= date('Y') ?> Hospital Appointment System. All rights reserved.
    </footer>
    <script>
    // Hero background slideshow (only 1st.png to 6th.png, 3s interval)
    const bgCount = 6;
    let current = 0;
    setInterval(() => {
        document.getElementById('hero-bg-' + current).classList.remove('active');
        current = (current + 1) % bgCount;
        document.getElementById('hero-bg-' + current).classList.add('active');
    }, 3000);
    </script>
</body>
</html>
