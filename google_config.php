<?php
// Google OAuth2 Configuration
// You need to get these from Google Cloud Console (https://console.cloud.google.com/)
// 1. Create a new project or select existing
// 2. Enable Google+ API and Google Sign-In API
// 3. Create credentials (OAuth 2.0 Client IDs)
// 4. Add your domain to authorized domains

define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');
define('GOOGLE_REDIRECT_URI', 'http://localhost/Group_2_CAPSTONE_BATCH_6/authentication_page.html');

// Verify that the Client ID is properly configured
if (empty(GOOGLE_CLIENT_ID) || GOOGLE_CLIENT_ID === 'your-google-client-id.apps.googleusercontent.com') {
    error_log("ERROR: Google Client ID not properly configured in google_config.php");
}

// Additional Google Sign-In settings
define('GOOGLE_SCOPE', 'email profile');
define('GOOGLE_ACCESS_TYPE', 'offline');
define('GOOGLE_APPROVAL_PROMPT', 'force');

// For debugging - log the configuration (remove in production)
if ($_SERVER['SERVER_NAME'] === 'localhost') {
    error_log("Google Config - Client ID configured: " . (!empty(GOOGLE_CLIENT_ID) ? 'YES' : 'NO'));
}
?>
