<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Meta tags for character encoding and responsive design -->
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Vehicle Job Card System - Login</title>
  <!-- Link to external CSS stylesheet -->
  <link rel="stylesheet" href="style.css"/>
</head>
<body>
  <!-- Main page container -->
  <div class="page">
    <!-- Login card container -->
    <div class="card">
      <!-- Logo section -->
      <div class="logo">
        <!-- Use relative path so it works under /project_i/ -->
        <img src="../image/logo.png" alt="Vehicle Logo" class="logo-image">
      </div>

      <!-- Main heading -->
      <h1>Vehicle Job Card System</h1>
      <!-- Subtitle -->
      <p class="subtitle">Professional Workshop Management</p>

      <!-- Error message display -->
      <?php
      // Display error messages if any
      if (isset($_GET['error'])) {
        $error = $_GET['error'];
        $errorMessage = '';
        
        switch($error) {
          case 'empty_fields':
            $errorMessage = 'Please fill in all fields';
            break;
          case 'invalid_email':
            $errorMessage = 'Invalid email format';
            break;
          case 'invalid_credentials':
            $errorMessage = 'Invalid email or password';
            break;
          case 'database_error':
            $errorMessage = 'Database connection error. Please try again later.';
            break;
          default:
            $errorMessage = 'An error occurred. Please try again.';
        }
        
        echo '<div class="error-message">' . htmlspecialchars($errorMessage) . '</div>';
      }
      ?>

      <!-- Login form -->
      <!-- Disable browser validation pop-ups (we show errors via PHP) -->
      <form action="login.php" method="POST" id="loginForm" novalidate>
        <!-- Email input field -->
        <label>Email</label>
        <div class="input-group">
          <input type="email" name="email" id="email" placeholder="Enter your email" required>
          <span class="icon">@</span>
        </div>

        <!-- Password input field -->
        <label>Password</label>
        <div class="input-group">
          <input type="password" name="password" id="password" placeholder="Enter your password" required>
          <span class="icon eye-icon" id="togglePassword">👁</span>
        </div>

        <!-- Remember me checkbox and forgot password link -->
        <div class="remember-forgot">
          <div class="remember-me">
            <input type="checkbox" name="remember" id="remember">
            <label for="remember" class="checkbox-label">Remember me</label>
          </div>
          <!-- Forgot password opens the reset password UI -->
          <a href="../reset/index.php" class="forgot-link">Forgot password?</a>
        </div>

        <!-- Sign In button -->
        <button type="submit" class="btn">Sign In</button>

        <!-- Sign up link -->
        <p class="signup-link">Don't have an account? <a href="../signup/index.php">Sign up</a></p>
        
        <!-- Copyright footer -->
        <p class="footer">© 2025 Vehicle Job Card System. All rights reserved.</p>
      </form>
    </div>
  </div>

  <!-- Link to external JavaScript file (cache-busted) -->
  <script src="script.js?v=1"></script>
</body>
</html>

