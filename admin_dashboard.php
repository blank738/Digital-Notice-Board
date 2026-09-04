<?php
session_start();
require_once "db.php";

/* --------------------------------
   ADMIN ACCESS PROTECTION
--------------------------------- */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: registration.php");
    exit;
}
/* --------------------------------
   ADD NOTICE
--------------------------------- */
if (isset($_POST['add_notice'])) {

    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);

    $image_name = "";

    // Check image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {

        $image = $_FILES['image'];

        $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];

        $extension = strtolower(
            pathinfo($image['name'], PATHINFO_EXTENSION)
        );

        if (!in_array($extension, $allowed_types)) {

            $error = "Only JPG, JPEG, PNG and WEBP images are allowed.";

        } else {

            // Create unique image name
            $image_name = time() . "_" . basename($image['name']);

            $upload_path = "uploads/notices/" . $image_name;

            if (!move_uploaded_file($image['tmp_name'], $upload_path)) {

                $error = "Image upload failed.";
                $image_name = "";
            }
        }
    }


    if (!isset($error)) {

        if (empty($title) || empty($category) || empty($description)) {

            $error = "Please fill all required fields.";

        } else {

            $stmt = $conn->prepare(
                "INSERT INTO notices (title, category, description, image)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssss",
                $title,
                $category,
                $description,
                $image_name
            );

            if ($stmt->execute()) {

                $success = "Notice added successfully.";

            } else {

                $error = "Failed to add notice.";
            }

            $stmt->close();
        }
    }
}


/* --------------------------------
   DELETE NOTICE
--------------------------------- */

if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    // Get image name first
    $stmt = $conn->prepare(
        "SELECT image FROM notices WHERE id = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $notice = $result->fetch_assoc();

    $stmt->close();


    // Delete image from folder
    if ($notice && !empty($notice['image'])) {

        $image_path = "uploads/notices/" . $notice['image'];

        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }


    // Delete notice from database
    $stmt = $conn->prepare(
        "DELETE FROM notices WHERE id = ?"
    );

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        $success = "Notice deleted successfully.";

    } else {

        $error = "Failed to delete notice.";
    }

    $stmt->close();
}


/* --------------------------------
   UPDATE NOTICE
--------------------------------- */

if (isset($_POST['update_notice'])) {

    $id = intval($_POST['id']);

    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);

    if (empty($title) || empty($category) || empty($description)) {

        $error = "Please fill all required fields.";

    } else {

        // Check if new image uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {

            $image = $_FILES['image'];

            $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];

            $extension = strtolower(
                pathinfo($image['name'], PATHINFO_EXTENSION)
            );

            if (!in_array($extension, $allowed_types)) {

                $error = "Only JPG, JPEG, PNG and WEBP images are allowed.";

            } else {

                // Get old image
                $stmt = $conn->prepare(
                    "SELECT image FROM notices WHERE id = ?"
                );

                $stmt->bind_param("i", $id);
                $stmt->execute();

                $result = $stmt->get_result();
                $old_notice = $result->fetch_assoc();

                $stmt->close();


                // Delete old image
                if ($old_notice && !empty($old_notice['image'])) {

                    $old_image_path =
                        "uploads/notices/" . $old_notice['image'];

                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }


                // Upload new image
                $image_name =
                    time() . "_" . basename($image['name']);

                $upload_path =
                    "uploads/notices/" . $image_name;

                if (move_uploaded_file(
                    $image['tmp_name'],
                    $upload_path
                )) {

                    $stmt = $conn->prepare(
                        "UPDATE notices
                         SET title = ?, category = ?, description = ?, image = ?
                         WHERE id = ?"
                    );

                    $stmt->bind_param(
                        "ssssi",
                        $title,
                        $category,
                        $description,
                        $image_name,
                        $id
                    );

                } else {

                    $error = "New image upload failed.";
                }
            }

        } else {

            // Update without changing image
            $stmt = $conn->prepare(
                "UPDATE notices
                 SET title = ?, category = ?, description = ?
                 WHERE id = ?"
            );

            $stmt->bind_param(
                "sssi",
                $title,
                $category,
                $description,
                $id
            );
        }


        if (isset($stmt) && !isset($error)) {

            if ($stmt->execute()) {

                $success = "Notice updated successfully.";

            } else {

                $error = "Failed to update notice.";
            }

            $stmt->close();
        }
    }
}


/* --------------------------------
   GET NOTICE FOR EDIT
--------------------------------- */

$edit_notice = null;

if (isset($_GET['edit'])) {

    $edit_id = intval($_GET['edit']);

    $stmt = $conn->prepare(
        "SELECT * FROM notices WHERE id = ?"
    );

    $stmt->bind_param("i", $edit_id);
    $stmt->execute();

    $result = $stmt->get_result();

    $edit_notice = $result->fetch_assoc();

    $stmt->close();
}


/* --------------------------------
   GET ALL NOTICES
--------------------------------- */

$result = $conn->query(
    "SELECT * FROM notices ORDER BY created_at DESC"
);

?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - Digital Notice Board</title>

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


        /* NAVBAR */

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

        .admin-name {
            color: #374151;
            margin-right: 20px;
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


        /* CONTAINER */

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
            color: #6b7280;
            margin-bottom: 30px;
        }


        /* MESSAGE */

        .success {
            background: #dcfce7;
            color: #166534;

            padding: 14px;
            border-radius: 8px;

            margin-bottom: 20px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;

            padding: 14px;
            border-radius: 8px;

            margin-bottom: 20px;
        }


        /* FORM CARD */

        .form-card {
            background: white;

            padding: 30px;

            border-radius: 15px;

            box-shadow: 0 8px 25px rgba(0,0,0,0.08);

            margin-bottom: 40px;

            border-top: 4px solid #16a34a;
        }

        .form-card h2 {
            color: #15803d;
            margin-bottom: 20px;
        }


        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;

            margin-bottom: 7px;

            font-weight: bold;

            color: #374151;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {

            width: 100%;

            padding: 12px;

            border: 1px solid #d1d5db;

            border-radius: 7px;

            font-size: 15px;

            outline: none;
        }

        .form-group textarea {
            height: 120px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {

            border-color: #16a34a;
        }


        /* BUTTON */

        .button {

            background: #16a34a;

            color: white;

            border: none;

            padding: 12px 25px;

            border-radius: 7px;

            cursor: pointer;

            font-size: 15px;

            font-weight: bold;
        }

        .button:hover {
            background: #15803d;
        }

        .cancel {
            display: inline-block;

            margin-left: 10px;

            padding: 12px 25px;

            background: #6b7280;

            color: white;

            text-decoration: none;

            border-radius: 7px;
        }


        /* NOTICE LIST */

        .notices-title {
            color: #15803d;

            margin-bottom: 20px;
        }

        .notice-card {
            background: #ecf7e9;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
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

        .notice-content h3 {

            color: #15803d;

            margin-bottom: 8px;
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

        .notice-description {

            color: #6b7280;

            line-height: 1.6;

            margin-bottom: 10px;
        }

        .date {

            color: #9ca3af;

            font-size: 13px;

            margin-bottom: 15px;
        }


        /* ACTION BUTTONS */

        .edit-btn {

            display: inline-block;

            text-decoration: none;

            background: #16a34a;

            color: white;

            padding: 8px 15px;

            border-radius: 6px;

            margin-right: 5px;
        }

        .delete-btn {

            display: inline-block;

            text-decoration: none;

            background: #dc2626;

            color: white;

            padding: 8px 15px;

            border-radius: 6px;
        }


        /* RESPONSIVE */

        @media (max-width: 700px) {

            .navbar {
                padding: 0 5%;
            }

            .admin-name {
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

        }

    </style>

</head>


<body>


<!-- NAVBAR -->

<div class="navbar">

    <div class="logo">
        Digital Notice Board
    </div>

    <div>

        <span class="admin-name">
            Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>
        </span>

        <a href="logout.php" class="logout">
            Logout
        </a>

    </div>

</div>


<div class="container">


    <h1>Admin Dashboard</h1>

    <p class="welcome">
        Manage and publish notices for students.
    </p>


    <!-- SUCCESS / ERROR -->

    <?php if (isset($success)): ?>

        <div class="success">
            <?php echo htmlspecialchars($success); ?>
        </div>

    <?php endif; ?>


    <?php if (isset($error)): ?>

        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <!-- ADD / EDIT FORM -->

    <div class="form-card">

        <?php if ($edit_notice): ?>

            <h2>Edit Notice</h2>

            <form method="POST"
                  enctype="multipart/form-data">

                <input type="hidden"
                       name="id"
                       value="<?php echo $edit_notice['id']; ?>">


                <div class="form-group">

                    <label>Notice Title</label>

                    <input type="text"
                           name="title"
                           value="<?php echo htmlspecialchars($edit_notice['title']); ?>"
                           required>

                </div>


                <div class="form-group">

                    <label>Category</label>

                    <select name="category" required>

                        <option value="">
                            Select Category
                        </option>

                        <option value="Exam"
                            <?php if ($edit_notice['category'] == 'Exam') echo 'selected'; ?>>
                            Exam
                        </option>

                        <option value="Event"
                            <?php if ($edit_notice['category'] == 'Event') echo 'selected'; ?>>
                            Event
                        </option>

                        <option value="Holiday"
                            <?php if ($edit_notice['category'] == 'Holiday') echo 'selected'; ?>>
                            Holiday
                        </option>

                        <option value="Announcement"
                            <?php if ($edit_notice['category'] == 'Announcement') echo 'selected'; ?>>
                            Announcement
                        </option>

                        <option value="Other"
                            <?php if ($edit_notice['category'] == 'Other') echo 'selected'; ?>>
                            Other
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>Description</label>

                    <textarea name="description"
                              required><?php echo htmlspecialchars($edit_notice['description']); ?></textarea>

                </div>


                <div class="form-group">

                    <label>
                        Replace Image
                    </label>

                    <input type="file"
                           name="image"
                           accept=".jpg,.jpeg,.png,.webp">

                </div>


                <button type="submit"
                        name="update_notice"
                        class="button">

                    Update Notice

                </button>


                <a href="admin_dashboard.php"
                   class="cancel">

                    Cancel

                </a>

            </form>


        <?php else: ?>


            <h2>Add New Notice</h2>

            <form method="POST"
                  enctype="multipart/form-data">


                <div class="form-group">

                    <label>Notice Title</label>

                    <input type="text"
                           name="title"
                           placeholder="Enter notice title"
                           required>

                </div>


                <div class="form-group">

                    <label>Category</label>

                    <select name="category" required>

                        <option value="">
                            Select Category
                        </option>

                        <option value="Exam">
                            Exam
                        </option>

                        <option value="Event">
                            Event
                        </option>

                        <option value="Holiday">
                            Holiday
                        </option>

                        <option value="Announcement">
                            Announcement
                        </option>

                        <option value="Other">
                            Other
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>Description</label>

                    <textarea name="description"
                              placeholder="Enter notice details"
                              required></textarea>

                </div>


                <div class="form-group">

                    <label>
                        Notice Image
                    </label>

                    <input type="file"
                           name="image"
                           accept=".jpg,.jpeg,.png,.webp">

                </div>


                <button type="submit"
                        name="add_notice"
                        class="button">

                    Publish Notice

                </button>

            </form>


        <?php endif; ?>

    </div>


    <!-- ALL NOTICES -->

    <h2 class="notices-title">
        All Notices
    </h2>


    <?php if ($result->num_rows > 0): ?>


        <?php while ($notice = $result->fetch_assoc()): ?>

            <div class="notice-card">


                <?php if (!empty($notice['image'])): ?>

                    <img src="uploads/notices/<?php
                        echo htmlspecialchars($notice['image']);
                    ?>"
                    class="notice-image"
                    alt="Notice Image">
                <?php endif; ?>
                <div class="notice-content">
                    <span class="category">
                        <?php
                        echo htmlspecialchars(
                            $notice['category']
                        );
                        ?>
                    </span>
                    <h3>
                        <?php
                        echo htmlspecialchars(
                            $notice['title']
                        );
                        ?>
                    </h3>
                    <p class="notice-description">

                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $notice['description']
                            )
                        );
                        ?>
                    </p>
                    <p class="date">
                        Posted on:
                        <?php
                        echo date(
                            "d M Y, h:i A",
                            strtotime($notice['created_at'])
                        );
                        ?>
                    </p>
                    <a href="admin_dashboard.php?edit=<?php
                        echo $notice['id'];
                    ?>"
                    class="edit-btn">
                        Edit
                    </a>
                    <a href="admin_dashboard.php?delete=<?php
                        echo $notice['id'];
                    ?>"
                    class="delete-btn"
                    onclick="return confirm('Are you sure you want to delete this notice?');">
                        Delete
                </a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="form-card">
            <p>
                No notices have been published yet.
            </p>
        </div>
    <?php endif; ?>
</div>
</body>
</html>