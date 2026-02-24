// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
  
  // Get references to password input and toggle icon
  const passwordInput = document.getElementById('password');
  const togglePassword = document.getElementById('togglePassword');
  const loginForm = document.getElementById('loginForm');

  
  // Toggle password visibility when eye icon is clicked
  if (togglePassword && passwordInput) {
    togglePassword.addEventListener('click', function() {
      // Check current input type
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      
      // Toggle input type between password and text
      passwordInput.setAttribute('type', type);
      
      // Change eye icon emoji based on visibility
      if (type === 'password') {
        togglePassword.textContent = '👁';
      } else {
        togglePassword.textContent = '🙈';
      }
    });
  }
  
  // Login form validation:
  // We rely on built-in browser validation (type="email", required fields),
  // so we don't show any alert pop-ups here.
  
  
  
  // Optional: Add input field focus effects
  const inputs = document.querySelectorAll('.input-group input');
  inputs.forEach(input => {
    // Add focus class to parent when input is focused
    input.addEventListener('focus', function() {
      this.parentElement.style.transform = 'scale(1.01)';
      this.parentElement.style.transition = 'transform 0.2s ease';
    });
    
    // Remove focus class when input loses focus
    input.addEventListener('blur', function() {
      this.parentElement.style.transform = 'scale(1)';
    });
  });
  
});

