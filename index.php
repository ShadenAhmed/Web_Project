<?php
// ============================================================
// handle_report_action.php — Admin: Block User or Dismiss Report
// ============================================================
require_once 'DBconfig.php';

// (a) Verify admin access
requireAdmin();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin.php");
    exit();
}

// Get and validate POST data
$reportID = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
$recipeID = isset($_POST['recipe_id']) ? (int)$_POST['recipe_id'] : 0;
$creatorID = isset($_POST['creator_id']) ? (int)$_POST['creator_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Validate inputs
if ($reportID <= 0 || $recipeID <= 0 || $creatorID <= 0 || !in_array($action, ['block_user', 'dismiss_report'])) {
    $_SESSION['error_message'] = "Invalid request. Please try again.";
    header("Location: admin.php");
    exit();
}

// Helper function to delete recipe files
function deleteRecipeFiles($recipe) {
    if (!empty($recipe['photoFileName'])) {
        $filePath = 'uploads/recipes/' . $recipe['photoFileName'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    if (!empty($recipe['videoFilePath'])) {
        $filePath = 'uploads/videos/' . $recipe['videoFilePath'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // ===========================================
    // BLOCK USER ACTION
    // ===========================================
    if ($action === 'block_user') {
        
        // 1. FIRST, delete ONLY the specific report we're handling
        executeQuery($pdo, "DELETE FROM report WHERE id = ?", [$reportID]);
        
        // 2. Get user information before deletion
        $userStmt = executeQuery($pdo, "SELECT * FROM user WHERE id = ?", [$creatorID]);
        
        if ($userStmt && $userStmt->rowCount() > 0) {
            $user = $userStmt->fetch();
            
            // 3. Get all recipes by this user
            $recipesStmt = executeQuery($pdo, "SELECT * FROM recipe WHERE userID = ?", [$creatorID]);
            
            if ($recipesStmt && $recipesStmt->rowCount() > 0) {
                $recipes = $recipesStmt->fetchAll();
                
                // 4. Delete each recipe and its associated data
                foreach ($recipes as $recipe) {
                    $currentRecipeID = $recipe['id'];
                    
                    // Delete recipe files
                    deleteRecipeFiles($recipe);
                    
                    // Delete recipe data from all related tables
                    executeQuery($pdo, "DELETE FROM ingredients WHERE recipeID = ?", [$currentRecipeID]);
                    executeQuery($pdo, "DELETE FROM instructions WHERE recipeID = ?", [$currentRecipeID]);
                    executeQuery($pdo, "DELETE FROM likes WHERE recipeID = ?", [$currentRecipeID]);
                    executeQuery($pdo, "DELETE FROM favourites WHERE recipeID = ?", [$currentRecipeID]);
                    executeQuery($pdo, "DELETE FROM comment WHERE recipeID = ?", [$currentRecipeID]);
                    
                    // Delete ANY remaining reports for this recipe (but NOT all reports)
                    executeQuery($pdo, "DELETE FROM report WHERE recipeID = ?", [$currentRecipeID]);
                }
                
                // 5. Delete all recipes by this user
                executeQuery($pdo, "DELETE FROM recipe WHERE userID = ?", [$creatorID]);
            }
            
            // 6. Delete user's activity from other tables
            executeQuery($pdo, "DELETE FROM comment WHERE userID = ?", [$creatorID]);
            executeQuery($pdo, "DELETE FROM likes WHERE userID = ?", [$creatorID]);
            executeQuery($pdo, "DELETE FROM favourites WHERE userID = ?", [$creatorID]);
            
            // 7. Delete ANY reports made BY this user (not reports ABOUT this user's recipes)
            executeQuery($pdo, "DELETE FROM report WHERE userID = ?", [$creatorID]);
            
            // 8. Delete user's profile photo
            if (!empty($user['photoFileName'])) {
                $photoPath = 'uploads/users/' . $user['photoFileName'];
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }
            
            // 9. Add user to blockeduser table (check if not already there)
            $checkBlocked = executeQuery($pdo, "SELECT * FROM blockeduser WHERE emailAddress = ?", [$user['emailAddress']]);
            if ($checkBlocked && $checkBlocked->rowCount() == 0) {
                executeQuery($pdo, 
                    "INSERT INTO blockeduser (firstName, lastName, emailAddress) VALUES (?, ?, ?)",
                    [$user['firstName'], $user['lastName'], $user['emailAddress']]
                );
            }
            
            // 10. Delete user from user table
            executeQuery($pdo, "DELETE FROM user WHERE id = ?", [$creatorID]);
        }
        
        $_SESSION['success_message'] = "User has been blocked and all their content has been removed.";
        
    // ===========================================
    // DISMISS REPORT ACTION  
    // ===========================================
    } elseif ($action === 'dismiss_report') {
        
        // Delete ONLY the specific report
        $result = executeQuery($pdo, "DELETE FROM report WHERE id = ?", [$reportID]);
        
        if ($result) {
            $_SESSION['success_message'] = "Report has been dismissed.";
        } else {
            $_SESSION['error_message'] = "Failed to dismiss report.";
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
} catch (Exception $e) {
    // Rollback on error
    $pdo->rollBack();
    $_SESSION['error_message'] = "An error occurred while processing your request.";
    error_log("Admin action error: " . $e->getMessage());
}

// Redirect back to admin page
header("Location: admin.php");
exit();
?>
