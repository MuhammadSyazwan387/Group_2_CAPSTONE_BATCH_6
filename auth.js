function showLogin() {
    document.getElementById('loginForm').classList.remove('hidden');
    document.getElementById('signupForm').classList.add('hidden');
    document.querySelectorAll('.toggle-btn')[0].classList.add('active');
    document.querySelectorAll('.toggle-btn')[1].classList.remove('active');
    
    document.getElementById('formTitle').textContent = 'Welcome Back!';
    document.getElementById('formSubtitle').textContent = 'Access your banking dashboard';
}

function showSignup() {
    document.getElementById('loginForm').classList.add('hidden');
    document.getElementById('signupForm').classList.remove('hidden');
    document.querySelectorAll('.toggle-btn')[0].classList.remove('active');
    document.querySelectorAll('.toggle-btn')[1].classList.add('active');
    
    document.getElementById('formTitle').textContent = 'Open Account';
    document.getElementById('formSubtitle').textContent = 'Start your banking journey with Optima';
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
    const email = formData.get('email');
    const password = formData.get('password');
    
    // Simple validation - in real app, this would be server-side
    if (email && password) {
        console.log('Login attempt:', Object.fromEntries(formData));
        
        // Store login status
        localStorage.setItem('userLoggedIn', 'true');
        localStorage.setItem('userEmail', email);
        
        // Redirect to homepage
        window.location.href = 'homepage.html';
    } else {
        alert('Please enter both email and password.');
    }
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
    
    const email = formData.get('email');
    const name = formData.get('name');
    
    if (email && password && name) {
        console.log('Signup attempt:', Object.fromEntries(formData));
        
        // Store login status after successful signup
        localStorage.setItem('userLoggedIn', 'true');
        localStorage.setItem('userEmail', email);
        localStorage.setItem('userName', name);
        
        // Redirect to homepage
        window.location.href = 'homepage.html';
    } else {
        alert('Please fill in all required fields.');
    }
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
    // Check if user is already logged in
    const userLoggedIn = localStorage.getItem('userLoggedIn');
    if (userLoggedIn === 'true') {
        // User is already logged in, redirect to homepage
        window.location.href = 'homepage.html';
    }
    
    console.log('Optima Bank authentication page loaded successfully');
});