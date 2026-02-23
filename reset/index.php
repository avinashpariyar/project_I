<?php
// Read email from query (sent from login page when user clicks "Forgot password")
$email = isset($_GET['email']) ? trim($_GET['email']) : '';

// Read optional error code for validation feedback
$error  = isset($_GET['error']) ? $_GET['error'] : null;
$message = '';

if ($error) {
    switch ($error) {
        case 'empty_password':
            $message = 'Please enter your new password in both fields.';
            break;
        case 'short_password':
            $message = 'Password should be at least 6 characters.';
            break;
        case 'invalid_password_chars':
            $message = 'Password can contain only letters (no numbers or symbols).';
            break;
        case 'mismatch':
            $message = 'New password and confirm password do not match.';
            break;
        case 'invalid_email':
            $message = 'Invalid or missing email address.';
            break;
        case 'user_not_found':
            $message = 'No account found with that email.';
            break;
        case 'database_error':
            $message = 'Database error. Please try again later.';
            break;
        default:
            $message = 'Something went wrong. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create New Password - Vehicle Job Card System</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <div class="page">
    <div class="card">
      <div class="logo">
        <img src="../image/logo.png" alt="Vehicle Logo" class="logo-image" />
      </div>

      <h1>Create new password</h1>
      <p class="subtitle">
        Please enter a new password. Your new password must be different from previous password.
      </p>

      <?php if (!empty($message)): ?>
        <div class="error-message">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <form action="reset_password.php" method="POST" id="resetForm" novalidate>
        <!-- Email field (can be pre-filled from query string) -->
        <div class="field">
          <label for="email">Email</label>
          <div class="input-group">
            <input
              type="email"
              id="email"
              name="email"
              placeholder="Enter your email"
              required
              value="<?php echo htmlspecialchars($email); ?>"
            />
            <span class="icon">@</span>
          </div>
        </div>

        <div class="field">
          <label for="new_password">New password</label>
          <div class="input-group">
            <input
              type="password"
              id="new_password"
              name="new_password"
              placeholder="Enter new password"
              required
              minlength="6"
            />
            <span class="icon eye-icon" id="toggleNewPassword">👁</span>
          </div>
        </div>

        <div class="field">
          <label for="confirm_password">Confirm password</label>
          <div class="input-group">
            <input
              type="password"
              id="confirm_password"
              name="confirm_password"
              placeholder="Confirm new password"
              required
              minlength="6"
            />
            <span class="icon eye-icon" id="toggleConfirmPassword">👁</span>
          </div>
        </div>

        <button type="submit" class="btn">Reset Password</button>
      </form>
    </div>
  </div>

  <script src="script.js"></script>
</body>
</html>

