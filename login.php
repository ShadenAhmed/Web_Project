<?php
// ============================================================
// Munch Login Page
// ============================================================
require_once 'DBconfig.php';


$error = ''; // Variable to store error message

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Check if user is in the blocked list
        $blockedStmt = executeQuery($pdo,
            "SELECT * FROM blockeduser WHERE emailAddress = ?", [$email]);
        if ($blockedStmt && $blockedStmt->rowCount() > 0) {
            $error = "Your account has been blocked. Please contact support.";
        } else {
            // Look up user by email
            $userStmt = executeQuery($pdo,
                "SELECT * FROM user WHERE emailAddress = ?", [$email]);
            $user = ($userStmt && $userStmt->rowCount() > 0) ? $userStmt->fetch() : null;

            if ($user && password_verify($password, $user['password'])) {
                // Login successful — set session
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_type'] = $user['userType'];

                // Redirect based on user type
                if ($user['userType'] === 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: user.php");
                }
                exit();
            } else {
                $error = "Invalid email address or password. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Log In - Munch Bakery</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
  <div class="auth-card">
    <h1>Log In</h1>

    <?php if ($error): ?>
      <p style="color:#9C0300; text-align:center; margin-bottom:15px; padding:10px; background:#FFEBEE; border-radius:5px; font-weight:bold;">
        <?php echo htmlspecialchars($error); ?>
      </p>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email"
               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit">Log In</button>
    </form>

    <p style="text-align:center; margin-top:15px; font-size:14px; color:#8E2E0D;">
      Don't have an account? <a href="signup.php" style="color:#CC3434;">Sign Up</a>
    </p>
  </div>
</body>
</html>
