function showLogin() {
    document.getElementById('loginForm').classList.remove('hidden');
    document.getElementById('signupForm').classList.add('hidden');
    document.querySelectorAll('.toggle-btn')[0].classList.add('active');
    document.querySelectorAll('.toggle-btn')[1].classList.remove('active');
    
    document.getElementById('formTitle').textContent = 'Welcome Back!';
    document.getElementById('formSubtitle').textContent = 'Enter your email and password';
}

function showSignup() {
    document.getElementById('loginForm').classList.add('hidden');
    document.getElementById('signupForm').classList.remove('hidden');
    document.querySelectorAll('.toggle-btn')[0].classList.remove('active');
    document.querySelectorAll('.toggle-btn')[1].classList.add('active');
    
    document.getElementById('formTitle').textContent = 'Create Account';
    document.getElementById('formSubtitle').textContent = 'Start your journey with us today';
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = '🙈';
    } else {
        input.type = 'password';
        button.textContent = '👁️';
    }
}

function showForgotPassword() {
    alert('Password reset functionality would be implemented here.');
}

// Form submission handlers
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    console.log('Login attempt:', Object.fromEntries(formData));
    alert('Login functionality would be implemented here.');
});

document.getElementById('signupForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const password = formData.get('password');
    const confirmPassword = formData.get('confirmPassword');
    
    if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return;
    }
    
    console.log('Signup attempt:', Object.fromEntries(formData));
    alert('Signup functionality would be implemented here.');
});

// Social login handlers
document.querySelectorAll('.social-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const provider = this.textContent.trim().split(' ')[1];
        alert(`${provider} authentication would be implemented here.`);
    });
});

// Wait for DOM to be fully loaded before adding event listeners
document.addEventListener('DOMContentLoaded', function() {
    // You can add any initialization code here
    console.log('Authentication page loaded successfully');
});