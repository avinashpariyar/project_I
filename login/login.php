<?php
// Start session to store user login information
session_start();

// Include database configuration file
require_once '../config/database.php';

// Check if form was submitted via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get email and password from form submission
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // Basic validation - check if fields are not empty
    if (empty($email) || empty($password)) {
        // Redirect back to login page with error message
        header("Location: index.php?error=empty_fields");
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: index.php?error=invalid_email");
        exit();
    }
    
    try {
        // Get database connection using centralized function
        $pdo = getDBConnection();
        
        // Prepare SQL query to check user credentials
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if user exists and password matches
        if ($user && password_verify($password, $user['password'])) {
            // Login successful - set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $_SESSION['logged_in'] = true;
            
            // If "Remember me" is checked, set cookie (optional)
            if ($remember) {
                // Set cookie to expire in 30 days
                setcookie('remember_user', $user['id'], time() + (30 * 24 * 60 * 60), '/');
            }
            
            // Redirect to dashboard or home page after successful login
            header("Location: ../dashboard/index.php"); // Change to your dashboard path
            exit();
            
        } else {
            // Invalid credentials - redirect back to login
            header("Location: index.php?error=invalid_credentials");
            exit();
        }
        
    } catch (PDOException $e) {
        // Database query error
        // In production, log this error instead of displaying it
        error_log("Database query error: " . $e->getMessage());
        header("Location: index.php?error=database_error");
        exit();
    } catch (Exception $e) {
        // Database connection error
        error_log("Database connection error: " . $e->getMessage());
        header("Location: index.php?error=database_error");
        exit();
    }
    
} else {
    // If accessed directly without POST, redirect to login page
    header("Location: index.php");
    exit();
}
?>

