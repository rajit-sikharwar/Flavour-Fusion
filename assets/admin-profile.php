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
    
    // If no errors, proceed with update
    if (empty($errors)) {
        try {
            if ($password_changed) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admin SET full_name = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $hashed_password, $admin_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE admin SET full_name = ?, email = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $admin_id]);
            }
            
            // Update session data
            $_SESSION['admin_full_name'] = $full_name;
            $_SESSION['admin_email'] = $email;
            
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #e67e22;
            --primary-dark: #d35400;
            --secondary: #2c3e50;
            --light: #f5f5f5;
            --white: #ffffff;
            --danger: #e74c3c;
            --warning: #f39c12;
            --success: #27ae60;
            --info: #3498db;
            --gray: #95a5a6;
            --dark: #34495e;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light);
            color: var(--dark);
            min-height: 100vh;
        }
        
        header {
            background-color: var(--primary);
            color: var(--white);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .container {
            width: 90%;
            max-width: 800px;
            margin: 30px auto;
            background-color: var(--white);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        }
        
        h1 {
            text-align: center;
            color: var(--secondary);
            margin-bottom: 30px;
            font-size: 2rem;
        }
        
        .nav-links a {
            color: var(--white);
            text-decoration: none;
            margin-left: 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .nav-links a:hover {
            color: #f8f8f8;
            text-decoration: underline;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95rem;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 40px;
            color: var(--gray);
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            color: var(--dark);
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.2);
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(230, 126, 34, 0.3);
        }
        
        .error {
            color: var(--danger);
            margin-bottom: 20px;
            padding: 12px;
            background: rgba(231, 76, 60, 0.1);
            border-radius: 8px;
            text-align: center;
            font-size: 0.9rem;
            border-left: 4px solid var(--danger);
        }
        
        .success {
            color: var(--success);
            margin-bottom: 20px;
            padding: 12px;
            background: rgba(39, 174, 96, 0.1);
            border-radius: 8px;
            text-align: center;
            font-size: 0.9rem;
            border-left: 4px solid var(--success);
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 0.85rem;
            padding: 5px;
            border-radius: 4px;
        }
        
        .strength-weak {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }
        
        .strength-medium {
            background-color: rgba(241, 196, 15, 0.1);
            color: var(--warning);
        }
        
        .strength-strong {
            background-color: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }
        
        .profile-icon {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile-icon i {
            font-size: 5rem;
            color: var(--primary);
            background-color: rgba(230, 126, 34, 0.1);
            padding: 30px;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <header>
        <h1>Flavour Fusion Admin</h1>
        <div class="nav-links">
            <a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="admin-customers.php"><i class="fas fa-users"></i> Customers</a>
            <a href="admin-reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="admin-logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>
    
    <div class="container">
        <h1><i class="fas fa-user-cog"></i> Admin Profile</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i>
                <p>Profile updated successfully!</p>
            </div>
        <?php endif; ?>
        
        <div class="profile-icon">
            <i class="fas fa-user-shield"></i>
        </div>
        
        <form method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <i class="fas fa-user input-icon"></i>
                <input type="text" id="username" name="username" 
                       value="<?php echo htmlspecialchars($admin['username']); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <i class="fas fa-id-card input-icon"></i>
                <input type="text" id="full_name" name="full_name" required
                       value="<?php echo htmlspecialchars($admin['full_name']); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" id="email" name="email" required
                       value="<?php echo htmlspecialchars($admin['email']); ?>">
            </div>
            
            <div class="form-group">
                <label for="current_password">Current Password (required for password change)</label>
                <i class="fas fa-lock input-icon"></i>
                <input type="password" id="current_password" name="current_password">
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password (leave blank to keep current)</label>
                <i class="fas fa-key input-icon"></i>
                <input type="password" id="new_password" name="new_password"
                       onkeyup="checkPasswordStrength(this.value)">
                <div id="password-strength" class="password-strength"></div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <i class="fas fa-lock input-icon"></i>
                <input type="password" id="confirm_password" name="confirm_password"
                       onkeyup="validatePasswordMatch()">
                <div id="password-match" class="password-strength"></div>
            </div>
            
            <button type="submit">
                <i class="fas fa-save"></i> Update Profile
            </button>
        </form>
    </div>

    <script>
        function checkPasswordStrength(password) {
            const strengthText = document.getElementById('password-strength');
            
            if (!password) {
                strengthText.textContent = '';
                strengthText.className = 'password-strength';
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
                strengthLevel = 'Weak password';
                strengthClass = 'strength-weak';
            } else if (strength <= 4) {
                strengthLevel = 'Medium strength password';
                strengthClass = 'strength-medium';
            } else {
                strengthLevel = 'Strong password';
                strengthClass = 'strength-strong';
            }
            
            strengthText.textContent = strengthLevel;
            strengthText.className = 'password-strength ' + strengthClass;
        }
        
        function validatePasswordMatch() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('password-match');
            
            if (!password || !confirmPassword) {
                matchText.textContent = '';
                matchText.className = 'password-strength';
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
    </script>
</body>
</html>