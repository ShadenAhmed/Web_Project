<?php
// ============================================================
// Munch Admin Dashboard 
// ============================================================
require_once 'DBconfig.php';

// Check admin session - redirect if not admin
requireAdmin();

// Get admin info
$adminID = (int)$_SESSION['user_id'];
$stmt = executeQuery($pdo, "SELECT * FROM user WHERE id = ?", [$adminID]);
if (!$stmt || $stmt->rowCount() === 0) {
    session_destroy();
    header("Location: login.php");
    exit();
}
$admin = $stmt->fetch();

// Get all recipe reports
$reportsStmt = executeQuery($pdo,
    "SELECT 
         rp.id AS report_id,
         rec.id AS recipe_id,
         rec.name AS recipe_name,
         u.id AS creator_id,
         u.firstName AS creator_first,
         u.lastName AS creator_last,
         u.photoFileName AS creator_photo
     FROM report rp
     JOIN recipe rec ON rp.recipeID = rec.id
     JOIN user u ON rec.userID = u.id
     ORDER BY rp.id DESC"
);
$reports = ($reportsStmt) ? $reportsStmt->fetchAll() : [];

// Get all blocked users
$blockedStmt = executeQuery($pdo, "SELECT * FROM blockeduser ORDER BY id DESC");
$blockedUsers = ($blockedStmt) ? $blockedStmt->fetchAll() : [];

// Handle success/error messages
$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Munch</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-page">

<div class="page admin-page">

    <!-- Sign-out link -->
    <div class="logout-box">
        <a href="signout.php">LOG-OUT</a>
    </div>

    <div class="admin-container">

        <!-- Welcome message -->
        <h1>Welcome <?php echo htmlspecialchars($admin['firstName']); ?>!</h1>

        <!-- Admin Information -->
        <div class="admin-info-box">
            <h3>My Information</h3>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($admin['firstName'] . ' ' . $admin['lastName']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($admin['emailAddress']); ?></p>
        </div>

        <!-- Display Messages -->
        <?php if ($successMessage): ?>
            <p style="color:green; font-weight:bold; margin:10px 0; padding:10px; background:#e8f5e9; border-radius:5px;">
                <?php echo htmlspecialchars($successMessage); ?>
            </p>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <p style="color:#9C0300; font-weight:bold; margin:10px 0; padding:10px; background:#ffebee; border-radius:5px;">
                <?php echo htmlspecialchars($errorMessage); ?>
            </p>
        <?php endif; ?>

        <!-- Reported Recipes Table -->
        <div class="section">
            <h3>Reported Recipes</h3>
            <?php if (empty($reports)): ?>
                <p>No reported recipes at this time.</p>
            <?php else: ?>
                <table class="bakery-table">
                    <thead>
                        <tr>
                            <th>Recipe Name</th>
                            <th>Recipe Creator</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                        <form method="POST" action="handle_report_action.php">
                            <tr>
                                <!-- Recipe name as link -->
                                <td>
                                    <a href="view_recipe.php?id=<?php echo (int)$report['recipe_id']; ?>" class="recipe-link">
                                        <?php echo htmlspecialchars($report['recipe_name']); ?>
                                    </a>
                                </td>
                                
                                <!-- Creator info -->
                                <td>
                                    <div class="creator-info">
                                        <span><?php echo htmlspecialchars($report['creator_first'] . ' ' . $report['creator_last']); ?></span>
                                        <img src="<?php echo !empty($report['creator_photo']) ? 'uploads/users/' . htmlspecialchars($report['creator_photo']) : 'images/defult-image.png'; ?>" 
                                             class="table-avatar" 
                                             alt="User Photo"
                                             onerror="this.src='images/defult-image.png'">
                                    </div>
                                </td>
                                
                                <!-- Action form with hidden inputs -->
                                <td>
                                    <input type="hidden" name="report_id" value="<?php echo (int)$report['report_id']; ?>">
                                    <input type="hidden" name="recipe_id" value="<?php echo (int)$report['recipe_id']; ?>">
                                    <input type="hidden" name="creator_id" value="<?php echo (int)$report['creator_id']; ?>">
                                    
                                    <label>
                                        <input type="radio" name="action" value="block_user" required> Block User
                                    </label>
                                    <br>
                                    <label>
                                        <input type="radio" name="action" value="dismiss_report"> Dismiss Report
                                    </label>
                                    <br>
                                    <button type="submit" class="table-btn">Submit</button>
                                </td>
                            </tr>
                        </form>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Blocked Users Table -->
        <div class="section">
            <h3>Blocked Users List</h3>
            <?php if (empty($blockedUsers)): ?>
                <p>No blocked users at this time.</p>
            <?php else: ?>
                <table class="bakery-table blocked-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blockedUsers as $blocked): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($blocked['firstName'] . ' ' . $blocked['lastName']); ?></td>
                                <td><?php echo htmlspecialchars($blocked['emailAddress']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>
</div>

<footer class="site-footer">
    <div class="container footer-box">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>Find us</h4>
                <ul class="social">
                    <li><a href="#">X</a></li>
                    <li><a href="#">f</a></li>
                    <li><a href="#">in</a></li>
                </ul>
            </div>
            <div class="footer-col center">
                <div class="brand">
                    <img src="images/Bakery1.png" alt="Bakery logo">
                </div>
                <small>&copy;2026 Munch Bakery. All rights reserved</small>
            </div>
            <div class="footer-col right">
                <h4>Contact Info</h4>
                <p>+966537282741</p>
                <p><a href="mailto:bakery@gmail.com" style="color:#FFB575;">Bakery@gmail.com</a></p>
            </div>
        </div>
    </div>
</footer>

</body>
</html>
