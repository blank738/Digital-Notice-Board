<?php

session_start();
include "db.php";

$message = "";
$message_type = "";

/* =========================
   REGISTRATION
   ========================= */

if (isset($_POST['register'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {

    $message = "Please fill all registration fields.";
    $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";
        $message_type = "error";

    } else {

        // Check whether email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $message = "Email already registered.";
            $message_type = "error";

        } else {

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Every new registration is automatically a STUDENT
            $role = "student";

            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, password, role)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssss",
                $name,
                $email,
                $hashed_password,
                $role
            );

            if ($stmt->execute()) {

                $message = "Registration successful! You can now login.";
                $message_type = "success";

            } else {

                $message = "Registration failed. Please try again.";
                $message_type = "error";
            }

            $stmt->close();
        }

        $check->close();
    }
}


/* =========================
   LOGIN
   ========================= */

if (isset($_POST['login'])) {

    $email = trim($_POST['login_email']);
    $password = $_POST['login_password'];

    if (empty($email) || empty($password)) {

        $message = "Please enter email and password.";
        $message_type = "error";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, name, password, role
             FROM users
             WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $user = $result->fetch_assoc();

            // Verify hashed password
            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];

                // Temporary dashboard redirect
                if ($user['role'] === "admin") {
                    header("Location: admin_dashboard.php");
                    exit;
                } else {
                    header("Location: student_dashboard.php");
                    exit;
                }

            } else {

                $message = "Incorrect password.";
                $message_type = "error";
            }

        } else {

            $message = "Account not found.";
            $message_type = "error";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Digital Notice Board</title>

    <style>

        * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
}


/* =========================
   BODY
   ========================= */

body {
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    background-image: url("asserts/bgimage.png");
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    background-repeat: no-repeat;
    color: #1f2937;
}


/* =========================
   MAIN CONTAINER
   ========================= */

.container {
    width: 430px;

    max-width: 92%;

    background: white;

    padding: 35px;

    border-radius: 15px;

    box-shadow:
        0 10px 30px rgba(0, 0, 0, 0.12);

    border-top: 4px solid #16a34a;
}


/* =========================
   HEADER
   ========================= */

.header {
    text-align: center;

    margin-bottom: 30px;
}

.header h1 {
    color: #16a34a;

    margin-bottom: 8px;

    font-size: 28px;
}

.header p {
    color: #666;

    font-size: 14px;
}


/* =========================
   FORM HEADING
   ========================= */

.form-box h2 {
    text-align: center;

    color: #15803d;

    margin-bottom: 25px;
}


/* =========================
   LABEL
   ========================= */

label {
    display: block;

    margin-bottom: 7px;

    font-weight: bold;

    color: #333;

    font-size: 14px;
}


/* =========================
   INPUT
   ========================= */

input {
    width: 100%;

    padding: 12px;

    margin-bottom: 18px;

    border: 1px solid #ccc;

    border-radius: 7px;

    outline: none;

    font-size: 14px;
}

input:focus {
    border-color: #16a34a;

    box-shadow:
        0 0 0 2px rgba(22, 163, 74, 0.10);
}


/* =========================
   PASSWORD
   ========================= */

.password-box {
    position: relative;

    width: 100%;
}

.password-box input {
    padding-right: 45px;
}

.password-box span {
    position: absolute;

    right: 12px;

    top: 10px;

    cursor: pointer;

    font-size: 18px;

    user-select: none;
}


/* =========================
   MAIN BUTTON
   ========================= */

button {
    width: 100%;

    padding: 12px;

    border: none;

    border-radius: 7px;

    background: #16a34a;

    color: white;

    font-size: 16px;

    cursor: pointer;

    font-weight: bold;

    transition: 0.3s;
}

button:hover {
    background: #15803d;
}


/* =========================
   SWITCH TEXT
   ========================= */

.switch-text {
    text-align: center;

    margin-top: 20px;

    color: #666;

    font-size: 14px;

    line-height: 1.8;
}


/* =========================
   CREATE ACCOUNT / BACK LOGIN
   ========================= */

.switch-btn {
    width: auto;

    background: none;

    color: #16a34a;

    font-weight: bold;

    padding: 4px;

    margin-left: 3px;

    font-size: 14px;
}

.switch-btn:hover {
    background: none;

    color: #15803d;

    text-decoration: underline;
}


/* =========================
   BACK TO HOME
   ========================= */

.back-home {
    display: block;

    text-align: center;

    margin-top: 10px;

    color: #16a34a;

    font-size: 14px;

    text-decoration: none;

    font-weight: bold;
}

.back-home:hover {
    color: #15803d;

    text-decoration: underline;
}


/* =========================
   MESSAGE
   ========================= */

.message {
    margin-bottom: 20px;

    padding: 12px;

    border-radius: 6px;

    text-align: center;

    font-size: 14px;
}

.success {
    background: #dcfce7;

    color: #166534;

    border: 1px solid #bbf7d0;
}

.error {
    background: #fee2e2;

    color: #991b1b;

    border: 1px solid #fecaca;
}


/* =========================
   STUDENT NOTE
   ========================= */

.student-note {
    text-align: center;

    margin-top: 15px;

    font-size: 13px;

    color: #666;
}


/* =========================
   MOBILE
   ========================= */

@media (max-width: 500px) {

    body {
        padding: 15px;
    }

    .container {
        padding: 25px 20px;
    }

    .header h1 {
        font-size: 24px;
    }

}
    </style>

</head>

<body>

<div class="container">

    <div class="header">

        <h1>Digital Notice Board</h1>

        <p>Stay updated with college announcements</p>

    </div>


    <?php if (!empty($message)): ?>

        <div class="message <?php echo $message_type; ?>">

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <!-- LOGIN FORM -->

    <div class="form-box" id="loginForm">

        <h2>Login</h2>

        <form method="POST">

            <label>Email</label>

            <input
                type="email"
                name="login_email"
                placeholder="Enter your email"
                required
            >

            <label>Password</label>

            <div class="password-box">
                <input
                    type="password"
                    id="loginPassword"
                    name="login_password"
                    placeholder="Enter your password"
                    required
                >

                <span onclick="togglePassword('loginPassword', this)">
                    👁
                </span>
            </div>

            <button type="submit" name="login">
                Login
            </button>

        </form>

        <div class="switch-text">

            Don't have an account?

            <button
                type="button"
                class="switch-btn"
                onclick="showRegister()"
            >
                Create Account
            </button>

            <a href="index.php" class="back-home">
                Back to Home
            </a>

        </div>

    </div>


    <!-- REGISTRATION FORM -->

    <div class="form-box" id="registerForm" style="display: none;">

        <h2>Create Account</h2>

        <form method="POST">

            <label>Name</label>

            <input
                type="text"
                name="name"
                placeholder="Enter your name"
                required
            >

            <label>Email</label>

            <input
                type="email"
                name="email"
                placeholder="Enter your email"
                required
            >

            <label>Password</label>

            <div class="password-box">

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Create a password"
                    required
                >

                <span onclick="togglePassword('password', this)">
                    👁
                </span>

            </div>

            <label>Confirm Password</label>

            <div class="password-box">

                <input
                    type="password"
                    id="registerPassword"
                    name="confirm_password"
                    placeholder="Confirm your password"
                    required
                >

                <span onclick="togglePassword('registerPassword', this)">
                    👁
                </span>

            </div>

            <button type="submit" name="register">
                Create Account
            </button>

        </form>

        <p class="student-note">
            New accounts are automatically registered as students.
        </p>

        <div class="switch-text">

            Already have an account?

            <button
                type="button"
                class="switch-btn"
                onclick="showLogin()"
            >
                Back to Login
            </button>
            

        </div>

    </div>

</div>


<script>

    function showRegister() {

        document.getElementById("loginForm").style.display = "none";

        document.getElementById("registerForm").style.display = "block";

    }


    function showLogin() {

        document.getElementById("registerForm").style.display = "none";

        document.getElementById("loginForm").style.display = "block";

    }

    function togglePassword(inputId, icon) {

    const input = document.getElementById(inputId);

    if (input.type === "password") {

        input.type = "text";
        icon.textContent = "🙈";

    } else {

        input.type = "password";
        icon.textContent = "👁";

    }
}

</script>

</body>

</html>
