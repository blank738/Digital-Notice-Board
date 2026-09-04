<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: registration.php");
    exit;
}

$result = $conn->query("SELECT * FROM notices ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard - Digital Notice Board</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background-image: url("asserts/bgimage.png");
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    background-repeat: no-repeat;
    color: #1f2937;
}

.navbar {
    height: 70px;
    background: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 7%;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.logo {
    font-size: 22px;
    font-weight: bold;
    color: #16a34a;
}

.student-name {
    color: #15803d;
    font-size: 18px;
    font-weight: bold;
    text-align: center;
    white-space: nowrap;
}

.logout {
    text-decoration: none;
    background: #16a34a;
    color: white;
    padding: 10px 18px;
    border-radius: 7px;
}

.logout:hover {
    background: #15803d;
}

.container {
    width: 90%;
    max-width: 1100px;
    margin: 40px auto;
}

h1 {
    color: #15803d;
    margin-bottom: 8px;
}

.welcome {
    color: #080808;
    margin-bottom: 30px;
}

.notice-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 20px;
    background: #ecf7e9;
    box-shadow: 0 5px 20px rgba(0,0,0,0.07);
    display: flex;
    gap: 25px;
}

.notice-image {
    width: 220px;
    height: 150px;
    object-fit: cover;
    border-radius: 10px;
}

.notice-content {
    flex: 1;
}

.notice-content h2 {
    color: #15803d;
    margin-bottom: 10px;
}

.category {
    display: inline-block;
    background: #dcfce7;
    color: #166534;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    margin-bottom: 10px;
}

.description {
    color: #000000;
    line-height: 1.6;
    margin-bottom: 10px;
}

.date {
    color: #9ca3af;
    font-size: 13px;
    margin-bottom: 15px;
}

.image-buttons {
    display: flex;
    gap: 10px;
}

.view-btn,
.download-btn {
    text-decoration: none;
    padding: 9px 15px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: bold;
}

.view-btn {
    background: #16a34a;
    color: white;
}

.view-btn:hover {
    background: #15803d;
}

.download-btn {
    background: #e5e7eb;
    color: #374151;
}

.download-btn:hover {
    background: #d1d5db;
}

.no-notices {
    background: white;
    padding: 30px;
    text-align: center;
    border-radius: 12px;
    color: #6b7280;
}
.user-section {
    display: flex;
    align-items: center;
    gap: 20px;
}

@media (max-width: 700px) {
    .navbar {
        padding: 0 5%;
    }

    .student-name {
        display: none;
    }

    .container {
        width: 92%;
    }

    .notice-card {
        flex-direction: column;
    }

    .notice-image {
        width: 100%;
        height: 220px;
    }

    .image-buttons {
        flex-wrap: wrap;
    }
    
}
</style>
</head>

<body>

<div class="navbar">
    <div class="logo">Digital Notice Board</div>
    <div class="user-section">
        <span class="student-name">
            WELCOME, <?php echo strtoupper(htmlspecialchars($_SESSION['name'])); ?>
        </span>
        <a href="logout.php" class="logout">Logout</a>
    </div>
</div>

<div class="container">
    <h1>Student Dashboard</h1>
    <p class="welcome">View the latest college announcements and notices.</p>

    <?php if ($result->num_rows > 0): ?>

        <?php while ($notice = $result->fetch_assoc()): ?>

            <div class="notice-card">

                <?php if (!empty($notice['image'])): ?>
                    <img src="uploads/notices/<?php echo htmlspecialchars($notice['image']); ?>" class="notice-image" alt="Notice Image">
                <?php endif; ?>

                <div class="notice-content">

                    <span class="category">
                        <?php echo htmlspecialchars($notice['category']); ?>
                    </span>

                    <h2>
                        <?php echo htmlspecialchars($notice['title']); ?>
                    </h2>

                    <p class="description">
                        <?php echo nl2br(htmlspecialchars($notice['description'])); ?>
                    </p>

                    <p class="date">
                        Posted on:
                        <?php echo date("d M Y, h:i A", strtotime($notice['created_at'])); ?>
                    </p>

                    <?php if (!empty($notice['image'])): ?>

                        <div class="image-buttons">

                            <a href="uploads/notices/<?php echo htmlspecialchars($notice['image']); ?>" target="_blank" class="view-btn">
                                View Full Image
                            </a>

                            <a href="uploads/notices/<?php echo htmlspecialchars($notice['image']); ?>" download class="download-btn">
                                Download Image
                            </a>

                        </div>

                    <?php endif; ?>

                </div>
            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="no-notices">
            No notices have been published yet.
        </div>

    <?php endif; ?>
</div>

</body>
</html>