<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin-login.php");
    exit;
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'flavour-fusion');
define('PROFILE_IMAGE_DIR', 'profile-images/');

// Connect to database
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Get current admin data
$admin_id = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialize variables
$errors = [];
$success = false;

// Process profile update form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $full_name = trim($_POST['full_name']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate inputs
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    
    // Check if password is being changed
    $password_changed = false;
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to change password.";
        } elseif (!password_verify($current_password, $admin['password'])) {
            $errors[] = "Current password is incorrect.";
        } elseif (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match.";
        } else {
            $password_changed = true;
        }
    }
    
    // Handle profile image upload
    $profile_image = $admin['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('admin_') . '.' . $file_ext;
            $target_path = PROFILE_IMAGE_DIR . $file_name;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
                // Delete old profile image if it's not the default
                if ($profile_image !== 'default.png' && file_exists(PROFILE_IMAGE_DIR . $profile_image)) {
                    unlink(PROFILE_IMAGE_DIR . $profile_image);
                }
                $profile_image = $file_name;
            } else {
                $errors[] = "Failed to upload profile image.";
            }
        } else {
            $errors[] = "Only JPG, PNG, and GIF images are allowed.";
        }
    }
    
    // If no errors, proceed with update
    if (empty($errors)) {
        try {
            if ($password_changed) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admin SET full_name = ?, email = ?, profile_image = ?, password = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $profile_image, $hashed_password, $admin_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE admin SET full_name = ?, email = ?, profile_image = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $profile_image, $admin_id]);
            }
            
            // Update session data
            $_SESSION['admin_full_name'] = $full_name;
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_profile_image'] = $profile_image;
            
            $success = true;
        } catch(PDOException $e) {
            $errors[] = "Error updating profile. Please try again. " . $e->getMessage();
        }
    }
}

// Refresh admin data after update
if ($_SERVER["REQUEST_METHOD"] == "POST" && $success) {
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Flavour Fusion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            width: 80%;
            max-width: 800px;
            margin: 30px auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        header {
            background-color: #e67e22;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        h1 {
            margin: 0;
            text-align: center;
            color: #e67e22;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #e67e22;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background: #d35400;
        }
        .error {
            color: #e74c3c;
            margin-bottom: 20px;
            padding: 10px;
            background: #fdecea;
            border-radius: 4px;
            text-align: center;
        }
        .success {
            color: #27ae60;
            margin-bottom: 20px;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 4px;
            text-align: center;
        }
        .profile-image-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e67e22;
        }
        .image-upload {
            text-align: center;
            margin-top: 10px;
        }
        .password-strength {
            margin-top: 5px;
            font-size: 14px;
            color: #666;
        }
        .strength-weak {
            color: #e74c3c;
        }
        .strength-medium {
            color: #f39c12;
        }
        .strength-strong {
            color: #27ae60;
        }
    </style>
    <script>
        function checkPasswordStrength(password) {
            const strengthText = document.getElementById('password-strength');
            
            if (!password) {
                strengthText.textContent = '';
                return;
            }
            
            // Check password strength
            let strength = 0;
            
            // Length >= 8
            if (password.length >= 8) strength++;
            
            // Contains lowercase
            if (/[a-z]/.test(password)) strength++;
            
            // Contains uppercase
            if (/[A-Z]/.test(password)) strength++;
            
            // Contains number
            if (/[0-9]/.test(password)) strength++;
            
            // Contains special char
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            // Determine strength level
            let strengthLevel, strengthClass;
            if (strength <= 2) {
                strengthLevel = 'Weak';
                strengthClass = 'strength-weak';
            } else if (strength <= 4) {
                strengthLevel = 'Medium';
                strengthClass = 'strength-medium';
            } else {
                strengthLevel = 'Strong';
                strengthClass = 'strength-strong';
            }
            
            strengthText.textContent = `Strength: ${strengthLevel}`;
            strengthText.className = 'password-strength ' + strengthClass;
        }
        
        function validatePasswordMatch() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('password-match');
            
            if (!password || !confirmPassword) {
                matchText.textContent = '';
                return;
            }
            
            if (password === confirmPassword) {
                matchText.textContent = 'Passwords match';
                matchText.className = 'password-strength strength-strong';
            } else {
                matchText.textContent = 'Passwords do not match';
                matchText.className = 'password-strength strength-weak';
            }
        }
        
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const preview = document.getElementById('profile-image-preview');
                preview.src = reader.result;
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</head>
<body>
    <header>
        <h1>Flavour Fusion Admin</h1>
        <div class="nav-links">
            <a href="admin-dashboard.php">Dashboard</a>
            <a href="admin-customers.php">Customers</a>
            <a href="admin-reports.php">Reports</a>
            <a href="admin-logout.php">Logout</a>
        </div>
    </header>
    
    <div class="container">
        <h1>Admin Profile</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <p>Profile updated successfully!</p>
            </div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data">
            <div class="profile-image-container">
                <img id="profile-image-preview" src="<?php echo PROFILE_IMAGE_DIR . htmlspecialchars($admin['profile_image']); ?>" 
                     alt="Profile Image" class="profile-image">
                <div class="image-upload">
                    <input type="file" id="profile_image" name="profile_image" 
                           accept="image/*" onchange="previewImage(event)">
                </div>
            </div>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo htmlspecialchars($admin['username']); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required
                       value="<?php echo htmlspecialchars($admin['full_name']); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo htmlspecialchars($admin['email']); ?>">
            </div>
            
            <div class="form-group">
                <label for="current_password">Current Password (required for password change)</label>
                <input type="password" id="current_password" name="current_password">
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password (leave blank to keep current)</label>
                <input type="password" id="new_password" name="new_password"
                       onkeyup="checkPasswordStrength(this.value)">
                <div id="password-strength" class="password-strength"></div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password"
                       onkeyup="validatePasswordMatch()">
                <div id="password-match" class="password-strength"></div>
            </div>
            
            <button type="submit">Update Profile</button>
        </form>
    </div>
</body>
</html>