<?php
// Read status or error query parameters (if redirected back from signup.php)
$status = isset($_GET['status']) ? $_GET['status'] : null;
$error  = isset($_GET['error'])  ? $_GET['error']  : null;

// Map error codes to human‑readable messages
$errorMessage = '';
if ($status === 'error' && $error) {
    switch ($error) {
        case 'empty_fields':
            $errorMessage = 'Please fill in all required fields.';
            break;
        case 'invalid_first_name':
            $errorMessage = 'First name can only contain letters and spaces.';
            break;
        case 'invalid_last_name':
            $errorMessage = 'Last name can only contain letters and spaces.';
            break;
        case 'invalid_email':
            $errorMessage = 'Please enter a valid email address.';
            break;
        case 'short_password':
            $errorMessage = 'Password should be at least 6 characters.';
            break;
        case 'terms_required':
            $errorMessage = 'You must agree to the Terms and Conditions.';
            break;
        case 'email_exists':
            $errorMessage = 'An account with this email already exists.';
            break;
        case 'database_error':
            $errorMessage = 'Database error. Please try again later.';
            break;
        default:
            $errorMessage = 'Something went wrong. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Vehicle Job Card System - Sign Up</title>
  <!-- Signup page styles -->
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="page">
    <div class="card">
      <!-- Logo area -->
      <div class="logo">
        <!-- Logo image (place your uploaded logo in /image/logo.png) -->
        <img src="../image/logo.png" alt="Vehicle Logo" class="logo-image" />
      </div>

      <h1>Vehicle Job Card System</h1>
      <p class="subtitle"><b>Create your ID<b></p>

      <?php if (!empty($errorMessage)): ?>
        <div class="error-message">
          <?php echo htmlspecialchars($errorMessage); ?>
        </div>
      <?php elseif ($status === 'success'): ?>
        <div class="success-message">
          Account created successfully. You can now log in.
        </div>
      <?php endif; ?>

      <!-- Signup form -->
      <form action="signup.php" method="POST" id="signupForm" novalidate>

        <!-- First and last name in a single row -->
        <div class="row">
          <div class="field">
            <label for="first_name">First name</label>
            <input
              type="text"
              id="first_name"
              name="first_name"
              placeholder="First name"
              required
              pattern="[A-Za-z\s]+"
              title="Only letters and spaces are allowed"
            />
          </div>
          <div class="field">
            <label for="last_name">Last name</label>
            <input
              type="text"
              id="last_name"
              name="last_name"
              placeholder="Last name"
              required
              pattern="[A-Za-z\s]+"
              title="Only letters and spaces are allowed"
            />
          </div>
        </div>

        <div class="field">
          <label for="email">Email</label>
          <div class="input-group">
            <input
              type="email"
              id="email"
              name="email"
              placeholder="Enter your email"
              required
            />
            <span class="icon">@</span>
          </div>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="input-group">
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Enter your password"
              required
              minlength="6"
            />
            <span class="icon eye-icon" id="togglePassword">👁</span>
          </div>
        </div>

        <div class="terms">
          <input type="checkbox" id="terms" name="terms" />
          <label for="terms">
            By processing, you agree to the
            <a href="#" class="terms-link">Terms and Conditions</a>
          </label>
        </div>

        <button type="submit" class="btn">Sign In</button>

        <p class="login-link">
          Already have an account?
          <a href="../login/index.php">Login now</a>
        </p>

        <p class="footer">
          © 2025 Vehicle Job Card System. All rights reserved.
        </p>
      </form>
    </div>
  </div>

  <!-- Signup page scripts -->
  <script src="script.js"></script>
</body>
</html>

