<?php
/**
 * Signup Processing Page - Munch Healthy Bakery
 * This file processes the signup form submission
**/
require_once 'DBconfig.php';

// Check if form was submitted via POST 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('signup.php', 'Invalid request method', 'error');
    exit();
}

// Get and sanitize form data (using sanitizeInput from DBconfig)
$firstName = sanitizeInput($_POST['firstName'] ?? '');
$lastName = sanitizeInput($_POST['lastName'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$password = $_POST['password'] ?? ''; // not to sanitize password, they will be hashed 

// Default profile image path
$defaultProfileImage = 'default-profile.png';
$photoFileName = $defaultProfileImage;

// Validate required fields
if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
    redirectWithMessage('signup.php', 'Please fill all the required fields', 'error');
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectWithMessage('signup.php', 'Invalid email format', 'error');
    exit();
}

// Validate password strength (minimum 8 characters)
if (strlen($password) < 8) {
    redirectWithMessage('signup.php', 'Password must be at least 8 characters long', 'error');
    exit();
}

try {
    // Check if email already exists in database
    $sql = "SELECT id, userType FROM User WHERE emailAddress = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        redirectWithMessage('signup.php', 'This email address is already registered. Please use a different email or login.', 'error');
        exit();
    }
    // Check if email exists in blockeduser table
    $sql = "SELECT id, firstName, lastName FROM blockeduser WHERE emailAddress = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Email found in blockeduser table
        $blockedUser = $stmt->fetch();
        $message = "This email address (" . $email . ") is blocked. Please contact administrator.";
        redirectWithMessage('signup.php', $message, 'error');
        exit();
    }
    
    // Handle profile image upload if provided
    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/profiles/';
        
        // temporary value senice we dont have a userID yet
        // The uploadFile function will be called again after we have userID
        $tempUserID = 'temp_' . time();
        $uploadedFileName = uploadFile($_FILES['profileImage'], $uploadDir, $tempUserID);
        
        if ($uploadedFileName !== false) {
            $photoFileName = $uploadDir . $uploadedFileName;
        } else {
            // If upload fails, log error and use default image
            error_log("Profile image upload failed for user: $email");
            // Keep using default image (already set)
        }
    } else {
        // No file uploaded or upload error - use default image
        error_log("No profile image uploaded for user: $email. Using default image.");
        
    }
    
    // Hash the password 
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user into database (userType is 'user')
    $sql = "INSERT INTO User (userType, firstName, lastName, emailAddress, password, photoFileName) 
            VALUES (:userType, :firstName, :lastName, :email, :password, :photoFileName)";
    
    $userType = 'user';
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':userType', $userType);
    $stmt->bindParam(':firstName', $firstName);
    $stmt->bindParam(':lastName', $lastName);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':photoFileName', $photoFileName);
    
    if ($stmt->execute()) {
        // Get the new user's ID
        $userId = $pdo->lastInsertId();
        
        // If a custom image was uploaded, rename it to include the actual user ID
        if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK && $uploadedFileName !== false) {
            $oldPath = $uploadDir . $uploadedFileName;
            $fileExtension = strtolower(pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION));
            $newFileName = "user_{$userId}_" . time() . "." . $fileExtension;
            $newPath = $uploadDir . $newFileName;
            
            if (rename($oldPath, $newPath)) {
                // Update the photoFileName in database with the new path
                $photoFileName = $uploadDir . $newFileName;
                $updateSql = "UPDATE User SET photoFileName = :photo WHERE id = :id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->bindParam(':photo', $photoFileName);
                $updateStmt->bindParam(':id', $userId);
                $updateStmt->execute();
            }
        }
        
        // Set session variables (session already started in DBconfig.php)
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_type'] = $userType;
        $_SESSION['user_name'] = $firstName . ' ' . $lastName;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_photo'] = $photoFileName;
        
        // Redirect to user page with success message
        redirectWithMessage('user.php', 'Account created successfully! Welcome to Munch!', 'success');
    } else {
        redirectWithMessage('signup.php', 'Registration failed. Please try again.', 'error');
    }
    
} catch (PDOException $e) {
    // Log the error for debugging
    error_log("Signup error: " . $e->getMessage() . " - Email: $email");
    
    // Show user-friendly message
    redirectWithMessage('signup.php', 'An error occurred during registration. Please try again later.', 'error');
    exit();
}
?>
