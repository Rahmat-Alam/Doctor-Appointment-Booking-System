<?php
session_start();
include 'db.php';

// Get doctor ID from URL
$doctor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$doctor_id) {
    header("Location: index.php");
    exit();
}

// Fetch doctor details
$doctor_query = "SELECT * FROM doctors WHERE id = ?";
$stmt = $conn->prepare($doctor_query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$doctor_result = $stmt->get_result();

if ($doctor_result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$doctor = $doctor_result->fetch_assoc();

// Fetch doctor's average rating and review count
$rating_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                 FROM doctor_reviews 
                 WHERE doctor_id = ? AND rating IS NOT NULL";
$rating_stmt = $conn->prepare($rating_query);
$rating_stmt->bind_param("i", $doctor_id);
$rating_stmt->execute();
$rating_result = $rating_stmt->get_result();
$rating_data = $rating_result->fetch_assoc();

$avg_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : 0;
$review_count = $rating_data['review_count'] ?: 0;

// Fetch patient reviews with patient names
$reviews_query = "SELECT dr.*, p.full_name as patient_name, p.email as patient_email
                  FROM doctor_reviews dr 
                  JOIN patients p ON dr.patient_id = p.id 
                  WHERE dr.doctor_id = ? 
                  ORDER BY dr.created_at DESC";
$reviews_stmt = $conn->prepare($reviews_query);
$reviews_stmt->bind_param("i", $doctor_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();

// Fetch doctor's availability
$availability_query = "SELECT * FROM doctor_availability 
                      WHERE doctor_id = ? AND date >= CURDATE() 
                      ORDER BY date ASC, start_time ASC";
$availability_stmt = $conn->prepare($availability_query);
$availability_stmt->bind_param("i", $doctor_id);
$availability_stmt->execute();
$availability_result = $availability_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dr. <?= htmlspecialchars($doctor['name']) ?> - Profile</title>
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
        
        .menu a:hover {
            text-decoration: underline;
            font-weight: bold;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #0077b6;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 30px;
            padding: 10px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .profile-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .doctor-avatar {
            flex-shrink: 0;
        }
        
        .doctor-avatar img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #0077b6;
        }
        
        .avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0077b6, #023e8a);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            border: 4px solid #0077b6;
        }
        
        .doctor-info h1 {
            color: #023e8a;
            font-size: 2.5rem;
            margin: 0 0 10px 0;
        }
        
        .doctor-info .specialization {
            color: #0077b6;
            font-size: 1.3rem;
            font-weight: 500;
            margin: 0 0 15px 0;
        }
        
        .rating-section {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stars {
            display: flex;
            gap: 3px;
        }
        
        .star {
            color: #ddd;
            font-size: 20px;
        }
        
        .star.filled {
            color: #ffc107;
        }
        
        .rating-text {
            font-size: 1.1rem;
            color: #666;
            font-weight: 500;
        }
        
        .doctor-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .detail-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #0077b6;
        }
        
        .detail-card h3 {
            color: #023e8a;
            margin: 0 0 10px 0;
            font-size: 1.1rem;
        }
        
        .detail-card p {
            margin: 0;
            color: #555;
            font-size: 1rem;
        }
        
        .reviews-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .reviews-section h2 {
            color: #023e8a;
            font-size: 2rem;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .review-card {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #0077b6;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .patient-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .patient-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0077b6, #023e8a);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .patient-details h4 {
            margin: 0;
            color: #023e8a;
            font-size: 1.1rem;
        }
        
        .patient-details small {
            color: #666;
            font-size: 0.9rem;
        }
        
        .review-rating {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .review-stars {
            display: flex;
            gap: 2px;
        }
        
        .review-stars .star {
            font-size: 16px;
        }
        
        .review-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .review-text {
            color: #333;
            line-height: 1.6;
            font-size: 1rem;
        }
        
        .no-reviews {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .no-reviews-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .availability-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-top: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .availability-section h2 {
            color: #023e8a;
            font-size: 2rem;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .availability-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .availability-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border-left: 4px solid #28a745;
        }
        
        .availability-date {
            font-weight: bold;
            color: #023e8a;
            margin-bottom: 8px;
        }
        
        .availability-time {
            color: #666;
            font-size: 0.9rem;
        }
        
        .no-availability {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .doctor-info h1 {
                font-size: 2rem;
            }
            
            .review-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
            <a href="index.php">Home</a>
            <a href="about.html">About</a>
        </div>
    </nav>
    
    <div class="container">
        <a href="index.php" class="back-btn">
            ← Back to Home
        </a>
        
        <!-- Doctor Profile Section -->
        <div class="profile-section">
            <div class="profile-header">
                <div class="doctor-avatar">
                    <?php if (!empty($doctor['profile_pic'])): ?>
                        <img src="uploads/<?= htmlspecialchars($doctor['profile_pic']) ?>" alt="Dr. <?= htmlspecialchars($doctor['name']) ?>">
                    <?php else: ?>
                        <div class="avatar-placeholder">👨‍⚕️</div>
                    <?php endif; ?>
                </div>
                
                <div class="doctor-info">
                    <h1>Dr. <?= htmlspecialchars($doctor['name']) ?></h1>
                    <p class="specialization">🏥 <?= htmlspecialchars($doctor['specialization']) ?></p>
                    
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
            
            <div class="doctor-details">
                <div class="detail-card">
                    <h3>🎓 Qualification</h3>
                    <p><?= htmlspecialchars($doctor['qualification']) ?></p>
                </div>
                
                <div class="detail-card">
                    <h3>⏰ Experience</h3>
                    <p><?= htmlspecialchars($doctor['experience']) ?> years</p>
                </div>
                
                <div class="detail-card">
                    <h3>📧 Contact</h3>
                    <p><?= htmlspecialchars($doctor['email']) ?></p>
                </div>
                
                <div class="detail-card">
                    <h3>📞 Phone</h3>
                    <p><?= htmlspecialchars($doctor['contact']) ?></p>
                </div>
                
                <div class="detail-card">
                    <h3>📍 Address</h3>
                    <p><?= htmlspecialchars($doctor['address']) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Patient Reviews Section -->
        <div class="reviews-section">
            <h2>💬 Patient Reviews & Feedback</h2>
            
            <?php if ($reviews_result->num_rows > 0): ?>
                <?php while ($review = $reviews_result->fetch_assoc()): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="patient-info">
                                <div class="patient-avatar">
                                    <?= strtoupper(substr($review['patient_name'], 0, 1)) ?>
                                </div>
                                <div class="patient-details">
                                    <h4><?= htmlspecialchars($review['patient_name']) ?></h4>
                                    <small><?= htmlspecialchars($review['patient_email']) ?></small>
                                </div>
                            </div>
                            
                            <div class="review-rating">
                                <div class="review-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?= $i <= $review['rating'] ? 'filled' : '' ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <span class="review-date"><?= date('M d, Y', strtotime($review['created_at'])) ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($review['review'])): ?>
                            <div class="review-text">
                                "<?= nl2br(htmlspecialchars($review['review'])) ?>"
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-reviews">
                    <div class="no-reviews-icon">💬</div>
                    <h4>No Reviews Yet</h4>
                    <p>This doctor hasn't received any reviews yet. Be the first to share your experience!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Availability Section -->
        <div class="availability-section">
            <h2>📅 Available Appointments</h2>
            
            <?php if ($availability_result->num_rows > 0): ?>
                <div class="availability-grid">
                    <?php while ($availability = $availability_result->fetch_assoc()): ?>
                        <div class="availability-card">
                            <div class="availability-date">
                                <?= date('M d, Y', strtotime($availability['date'])) ?>
                            </div>
                            <div class="availability-time">
                                <?= date('h:i A', strtotime($availability['start_time'])) ?> - 
                                <?= date('h:i A', strtotime($availability['end_time'])) ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-availability">
                    <div class="no-reviews-icon">📅</div>
                    <h4>No Available Slots</h4>
                    <p>This doctor doesn't have any upcoming availability. Please check back later!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 