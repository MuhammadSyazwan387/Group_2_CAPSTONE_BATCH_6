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

function showMessage(message, type = 'info') {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.auth-message');
    existingMessages.forEach(msg => msg.remove());
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `auth-message auth-message-${type}`;
    messageDiv.textContent = message;
    
    const formPanel = document.querySelector('.form-panel');
    formPanel.insertBefore(messageDiv, formPanel.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.remove();
        }
    }, 5000);
}

async function handleLogin(formData) {
    const submitBtn = document.querySelector('#loginForm .submit-btn');
    const originalText = submitBtn.textContent;
    
    try {
        submitBtn.textContent = 'Signing In...';
        submitBtn.disabled = true;
        
        const response = await fetch('login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: formData.get('email'),
                password: formData.get('password')
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Store user data
            localStorage.setItem('userLoggedIn', 'true');
            localStorage.setItem('userId', data.user.id);
            localStorage.setItem('userEmail', data.user.email);
            localStorage.setItem('userName', data.user.fullname);
            localStorage.setItem('userPoints', data.user.points);
            
            showMessage('Login successful! Redirecting...', 'success');
            
            // Redirect after short delay
            setTimeout(() => {
                window.location.href = 'homepage.html';
            }, 1500);
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Login error:', error);
        showMessage('An error occurred. Please try again.', 'error');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}

async function handleSignup(formData) {
    const submitBtn = document.querySelector('#signupForm .submit-btn');
    const originalText = submitBtn.textContent;
    
    try {
        submitBtn.textContent = 'Creating Account...';
        submitBtn.disabled = true;
        
        const response = await fetch('signup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                fullname: formData.get('name'),
                email: formData.get('email'),
                password: formData.get('password'),
                confirmPassword: formData.get('confirmPassword')
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Store user data
            localStorage.setItem('userLoggedIn', 'true');
            localStorage.setItem('userId', data.user.id);
            localStorage.setItem('userEmail', data.user.email);
            localStorage.setItem('userName', data.user.fullname);
            localStorage.setItem('userPoints', '0');
            
            showMessage('Account created successfully! Redirecting...', 'success');
            
            // Redirect after short delay
            setTimeout(() => {
                window.location.href = 'homepage.html';
            }, 1500);
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Signup error:', error);
        showMessage('An error occurred. Please try again.', 'error');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}

// Google Sign-In functions
function googleSignIn() {
    if (typeof google !== 'undefined' && google.accounts) {
        google.accounts.id.prompt();
    } else {
        // Wait for Google library to load
        showMessage('Loading Google Sign-In...', 'info');
        
        // Check every 100ms for up to 5 seconds
        let attempts = 0;
        const checkGoogle = setInterval(() => {
            attempts++;
            if (typeof google !== 'undefined' && google.accounts) {
                clearInterval(checkGoogle);
                initializeGoogleSignIn();
                google.accounts.id.prompt();
            } else if (attempts > 50) { // 5 seconds
                clearInterval(checkGoogle);
                showMessage('Google Sign-In failed to load. Please refresh and try again.', 'error');
            }
        }, 100);
    }
}

async function handleGoogleSignIn(response) {
    if (!response.credential) {
        showMessage('Google Sign-In failed. Please try again.', 'error');
        return;
    }
    
    try {
        showMessage('Signing in with Google...', 'info');
        
        const result = await fetch('google_auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                google_token: response.credential
            })
        });
        
        const data = await result.json();
        
        if (data.success) {
            // Store user data
            localStorage.setItem('userLoggedIn', 'true');
            localStorage.setItem('userId', data.user.id);
            localStorage.setItem('userEmail', data.user.email);
            localStorage.setItem('userName', data.user.fullname);
            localStorage.setItem('userPoints', data.user.points);
            localStorage.setItem('loginMethod', 'google');
            
            showMessage('Google Sign-In successful! Redirecting...', 'success');
            
            // Redirect after short delay
            setTimeout(() => {
                window.location.href = 'homepage.html';
            }, 1500);
        } else {
            showMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Google Sign-In error:', error);
        showMessage('Google Sign-In failed. Please try again.', 'error');
    }
}

// Initialize Google Sign-In when page loads
function initializeGoogleSignIn() {
    if (typeof google !== 'undefined' && google.accounts) {
        google.accounts.id.initialize({
            client_id: '269492663844-jjsavuri4m3movsotbabtcsa0cpssvpt.apps.googleusercontent.com',
            callback: handleGoogleSignIn,
            auto_select: false,
            cancel_on_tap_outside: false
        });
        
        // Render the sign-in button for login form
        const loginSignInDiv = document.getElementById("g_id_signin");
        if (loginSignInDiv) {
            google.accounts.id.renderButton(
                loginSignInDiv,
                { 
                    theme: "outline", 
                    size: "large",
                    text: "continue_with",
                    shape: "rectangular",
                    width: "100%"
                }
            );
        }
        
        // Render the sign-in button for signup form
        const signupSignInDiv = document.getElementById("g_id_signin_signup");
        if (signupSignInDiv) {
            google.accounts.id.renderButton(
                signupSignInDiv,
                { 
                    theme: "outline", 
                    size: "large",
                    text: "continue_with",
                    shape: "rectangular",
                    width: "100%"
                }
            );
        }
        
        console.log('Google Sign-In initialized successfully');
    } else {
        console.error('Google Sign-In library not loaded');
    }
}

// Form submission handlers
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    handleLogin(formData);
});

document.getElementById('signupForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    const password = formData.get('password');
    const confirmPassword = formData.get('confirmPassword');
    
    if (password !== confirmPassword) {
        showMessage('Passwords do not match!', 'error');
        return;
    }
    
    if (password.length < 6) {
        showMessage('Password must be at least 6 characters long!', 'error');
        return;
    }
    
    handleSignup(formData);
});

// Social login handlers
document.querySelectorAll('.social-btn:not(.google-btn)').forEach(btn => {
    btn.addEventListener('click', function() {
        const provider = this.textContent.trim().split(' ')[1];
        showMessage(`${provider} authentication would be implemented here.`, 'info');
    });
});

// Wait for DOM to be fully loaded before adding event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Check if user is already logged in
    const userLoggedIn = localStorage.getItem('userLoggedIn');
    if (userLoggedIn === 'true') {
        window.location.href = 'homepage.html';
    }
    
    // Initialize Google Sign-In after a delay to ensure the library is loaded
    setTimeout(() => {
        initializeGoogleSignIn();
    }, 500);
    
    // Also listen for the Google library load event
    window.addEventListener('load', () => {
        setTimeout(initializeGoogleSignIn, 1000);
    });
    
    console.log('Optima Bank authentication page loaded successfully');
});