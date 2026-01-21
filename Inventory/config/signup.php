<?php
require '../config/auth.php';
require '../config/db.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Security token expired. Please try again.";
    } else {

        $username = sanitizeInput($_POST['username'] ?? '');
        $email    = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        // Basic validation
        if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
            $errors[] = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        } elseif ($password !== $confirm) {
            $errors[] = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        } else {

            // Check if username or email already exists
            $stmt = $conn->prepare(
                "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1"
            );

            if ($stmt) {
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $errors[] = "Username or email already exists.";
                } else {
                    // Insert new user
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    $insert = $conn->prepare(
                        "INSERT INTO users (username, email, password) VALUES (?, ?, ?)"
                    );

                    if ($insert) {
                        $insert->bind_param("sss", $username, $email, $hashedPassword);
                        $insert->execute();
                        $userId = $insert->insert_id;

                        logAction($userId, 'signup', null);

                        $insert->close();
                        header("Location: login.php?success=1");
                        exit();
                    } else {
                        error_log("Insert error: " . $conn->error);
                        $errors[] = "An error occurred. Please try again later.";
                    }
                }

                $stmt->close();
            } else {
                error_log("Database error: " . $conn->error);
                $errors[] = "An error occurred. Please try again later.";
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - SHJCS Inventory</title>
    <link rel="stylesheet" href="../style/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>Create Account</h2>

            <?php foreach ($errors as $error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>

            <form method="POST" action="signup.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <input
                    type="text"
                    name="username"
                    placeholder="Username"
                    required
                    autocomplete="username"
                >

                <input
                    type="email"
                    name="email"
                    placeholder="Email"
                    required
                    autocomplete="email"
                >

                <input
                    type="password"
                    name="password"
                    placeholder="Password"
                    required
                    autocomplete="new-password"
                >

                <input
                    type="password"
                    name="confirm_password"
                    placeholder="Confirm Password"
                    required
                    autocomplete="new-password"
                >

                <button type="submit">Sign Up</button>
            </form>

            <div class="auth-link">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</body>
</html>