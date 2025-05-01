<?php
session_start();

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

// Check if user is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin-dashboard.php");
    exit;
}

// Initialize variables
$errors = [];
$success = false;

// Process registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $full_name = trim($_POST['full_name']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    
    // Validate inputs
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores.";
    } elseif (strlen($username) < 4) {
        $errors[] = "Username must be at least 4 characters.";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    
    // Check if username already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Username already exists.";
        }
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO admin (username, password, full_name, email) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $full_name, $email]);
            
            $success = true;
        } catch(PDOException $e) {
            $errors[] = "Error creating account. Please try again. " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - Flavour Fusion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .register-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 400px;
        }
        h1 {
            text-align: center;
            color: #e67e22;
            margin-bottom: 30px;
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
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #e67e22;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
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
            const password = document.getElementById('password').value;
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
    </script>
</head>
<body>
    <div class="register-container">
        <h1>Admin Registration</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <p>Admin account created successfully!</p>
                <p>You can now <a href="admin-login.php">login</a> to the system.</p>
            </div>
        <?php else: ?>
            <form method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required 
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           onkeyup="checkPasswordStrength(this.value)">
                    <div id="password-strength" class="password-strength"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           onkeyup="validatePasswordMatch()">
                    <div id="password-match" class="password-strength"></div>
                </div>
                
                <button type="submit">Register</button>
                
                <div class="login-link">
                    Already have an account? <a href="admin-login.php">Login here</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>