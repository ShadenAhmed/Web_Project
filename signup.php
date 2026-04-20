 <!DOCTYPE html>

<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up</title>
  <link rel="stylesheet" href="style.css">
</head>

<body class="signup-page">

  <div class="auth-card">
    <h1>Create Account</h1>
 
    <form action="singupProcess.php" method="post" enctype="multipart/form-data">

      <div class="form-group">
        <label>First Name</label>
        <input type="text" name="firstName" required>
      </div>

      <div class="form-group">
        <label>Last Name</label>
        <input type="text"  name="lastName" required>
      </div>

      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" required>
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
<div class="form-group">
 <label>Profile Image </label>
 <input type="file" name="profileImage" accept="image/*"> </div>

      <button type="submit">Sign Up</button>

    </form>
    <?php
    session_start();
    if (isset($_SESSION['error_message'])) {
        echo '<div class="error-message">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']);
    }
    if (isset($_SESSION['success_message'])) {
        echo '<div class="success-message">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']);
    }
    ?>
  </div>

</body>
</html>
