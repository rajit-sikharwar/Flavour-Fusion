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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #e67e22;
            --primary-dark: #d35400;
            --secondary: #2c3e50;
            --light: #f5f5f5;
            --white: #ffffff;
            --danger: #e74c3c;
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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: linear-gradient(135deg, rgba(230, 126, 34, 0.1) 0%, rgba(231, 76, 60, 0.1) 100%);
            padding: 20px;
        }
        
        .register-container {
            background: var(--white);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }
        
        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        }
        
        .brand {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .brand-logo {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .brand-title {
            font-size: 1.8rem;
            color: var(--secondary);
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        
        .brand-subtitle {
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 400;
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
            color: #27ae60;
            margin-bottom: 20px;
            padding: 12px;
            background: rgba(39, 174, 96, 0.1);
            border-radius: 8px;
            text-align: center;
            font-size: 0.9rem;
            border-left: 4px solid #27ae60;
        }
        
        .footer-links {
            text-align: center;
            margin-top: 25px;
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .footer-links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .footer-links a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
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
            color: #f39c12;
        }
        
        .strength-strong {
            background-color: rgba(39, 174, 96, 0.1);
            color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="brand">
            <div class="brand-logo">
                <i class="fas fa-utensils"></i>
            </div>
            <h1 class="brand-title">Flavour Fusion</h1>
            <p class="brand-subtitle">Admin Registration</p>
        </div>
        
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
                <p>Admin account created successfully!</p>
                <p>You can now <a href="admin-login.php">login</a> to the system.</p>
            </div>
        <?php else: ?>
            <form method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           placeholder="Enter your username">
                </div>
                
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <i class="fas fa-id-card input-icon"></i>
                    <input type="text" id="full_name" name="full_name" required 
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                           placeholder="Enter your full name">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           placeholder="Enter your email">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" required 
                           onkeyup="checkPasswordStrength(this.value)"
                           placeholder="Enter your password">
                    <div id="password-strength" class="password-strength"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           onkeyup="validatePasswordMatch()"
                           placeholder="Confirm your password">
                    <div id="password-match" class="password-strength"></div>
                </div>
                
                <button type="submit">
                    <i class="fas fa-user-plus"></i> Register
                </button>
                
                <div class="footer-links">
                    Already have an account? <a href="admin-login.php">Login here</a>
                </div>
            </form>
        <?php endif; ?>
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
            const password = document.getElementById('password').value;
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