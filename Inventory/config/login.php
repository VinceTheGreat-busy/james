<?php
require '../config/auth.php';
require '../config/db.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

$errors = [];
$success = $_GET['success'] ?? false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Security token expired. Please try again.";
    } else {
        $identifier = sanitizeInput($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate inputs
        if (empty($identifier) || empty($password)) {
            $errors[] = "All fields are required.";
        } else {
            // Use prepared statement to prevent SQL injection
            $stmt = $conn->prepare(
                "SELECT id, username, email, password 
                FROM users 
                WHERE username = ? OR email = ? 
                LIMIT 1"
            );
            
            if ($stmt) {
                $stmt->bind_param("ss", $identifier, $identifier);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();

                // Verify password
                if ($user && password_verify($password, $user['password'])) {
                    login($user['id'], $user['username'], $user['email']);
                    logAction($user['id'], 'login', null);
                    header("Location: ../index.php");
                    exit();
                } else {
                    $errors[] = "Invalid username/email or password.";
                }
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
    <title>Login - SHJCS Inventory</title>
    <link rel="stylesheet" href="../style/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>SHJCS Login</h2>
            
            <?php if ($success): ?>
                <div class="success">Account created successfully! You can now log in.</div>
            <?php endif; ?>
            
            <?php foreach ($errors as $error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>

            <form method="POST" action="login.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <input 
                    type="text" 
                    name="identifier" 
                    placeholder="Username or Email" 
                    required
                    autocomplete="username"
                >
                
                <input 
                    type="password" 
                    name="password" 
                    placeholder="Password" 
                    required
                    autocomplete="current-password"
                >
                
                <button type="submit">Login</button>
            </form>
            
            <div class="auth-link">
                <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
            </div>
        </div>
    </div>
</body>
</html>