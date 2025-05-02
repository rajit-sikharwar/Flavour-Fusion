<?php
// Database configuration
$host = 'localhost';
$dbname = 'flavour-fusion';
$username = 'root';
$password = '';

// Connect to database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Initialize variables
$order = null;
$error = '';
$order_id = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['track_order'])) {
    $order_id = trim($_POST['order_id']);
    
    if (empty($order_id)) {
        $error = "Please enter an order ID";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                $error = "No order found with ID: " . htmlspecialchars($order_id);
            }
        } catch(PDOException $e) {
            $error = "Error retrieving order: " . $e->getMessage();
        }
    }
}

// Function to get status display class
function getStatusClass($status) {
    switch ($status) {
        case 'pending':
            return 'status-pending';
        case 'preparing':
            return 'status-preparing';
        case 'on the way':
            return 'status-on-the-way';
        case 'delivered':
            return 'status-delivered';
        case 'cancelled':
            return 'status-cancelled';
        default:
            return '';
    }
}

// Function to extract ordered items from instructions
function getOrderedItems($instructions) {
    $pattern = '/Selected Items: (.*)/';
    if (preg_match($pattern, $instructions, $matches)) {
        return $matches[1];
    }
    return 'Items not specified';
}

// Function to calculate progress width based on status
function getProgressWidth($status) {
    switch ($status) {
        case 'pending':
            return 10;
        case 'preparing':
            return 40;
        case 'on the way':
            return 75;
        case 'delivered':
            return 100;
        case 'cancelled':
            return 100;
        default:
            return 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Order - Flavour Fusion</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #e67e22;
            --primary-dark: #d35400;
            --secondary: #2c3e50;
            --light: #f9f9f9;
            --dark: #333;
            --gray: #777;
            --light-gray: #eee;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --white: #fff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }
        
        .header {
            background-color: var(--white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .logo i {
            margin-right: 10px;
            color: var(--primary-dark);
        }
        
        .nav-links {
            display: flex;
            list-style: none;
        }
        
        .nav-links li {
            margin-left: 2rem;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--secondary);
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: var(--primary);
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }
        
        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--primary);
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .page-title:after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: var(--primary);
            margin: 0.5rem auto;
            border-radius: 2px;
        }
        
        .track-form {
            background: var(--white);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--secondary);
            font-size: 1.1rem;
        }
        
        .form-control {
            width: 100%;
            max-width: 400px;
            padding: 0.8rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: 6px;
            font-size: 1rem;
            margin: 0 auto;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.2);
        }
        
        .btn {
            display: inline-block;
            background: var(--primary);
            color: var(--white);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }
        
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-block {
            display: block;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .error-message {
            background: #fdecea;
            color: var(--danger);
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
        }
        
        .order-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .order-header {
            background: var(--secondary);
            color: var(--white);
            padding: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-id {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .order-id span {
            color: var(--primary);
        }
        
        .order-date {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-preparing {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-on-the-way {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .progress-tracker {
            padding: 2rem;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-top: 2rem;
        }
        
        .progress-bar {
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--light-gray);
            z-index: 1;
            border-radius: 3px;
        }
        
        .progress-completed {
            height: 100%;
            background: var(--success);
            border-radius: 3px;
            transition: width 0.5s ease;
        }
        
        .step {
            text-align: center;
            flex: 1;
            position: relative;
            z-index: 2;
        }
        
        .step-icon {
            width: 40px;
            height: 40px;
            background: var(--light-gray);
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--gray);
            position: relative;
            transition: all 0.3s;
        }
        
        .step.active .step-icon {
            background: var(--primary);
            color: var(--white);
            transform: scale(1.1);
        }
        
        .step.completed .step-icon {
            background: var(--success);
            color: var(--white);
        }
        
        .step-label {
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
        }
        
        .step.active .step-label {
            color: var(--primary);
            font-weight: 600;
        }
        
        .step.completed .step-label {
            color: var(--success);
        }
        
        .order-body {
            padding: 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .detail-section {
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            color: var(--primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light-gray);
        }
        
        .detail-group {
            display: flex;
            margin-bottom: 1rem;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--secondary);
            min-width: 150px;
        }
        
        .detail-value {
            color: var(--dark);
            flex: 1;
        }
        
        .items-list {
            list-style: none;
        }
        
        .items-list li {
            padding: 0.5rem 0;
            border-bottom: 1px dashed var(--light-gray);
            display: flex;
            justify-content: space-between;
        }
        
        .items-list li:last-child {
            border-bottom: none;
        }
        
        .footer {
            background: var(--secondary);
            color: var(--white);
            padding: 3rem 0;
            margin-top: 3rem;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .footer-logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 1rem;
            display: inline-block;
        }
        
        .footer-about {
            margin-bottom: 1.5rem;
            opacity: 0.8;
        }
        
        .footer-links h3, .footer-contact h3 {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }
        
        .footer-links h3:after, .footer-contact h3:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background: var(--primary);
        }
        
        .footer-links ul {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 0.8rem;
        }
        
        .footer-links a {
            color: var(--white);
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        
        .footer-links a:hover {
            opacity: 1;
        }
        
        .contact-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .contact-icon {
            margin-right: 1rem;
            color: var(--primary);
        }
        
        .social-links {
            display: flex;
            margin-top: 1.5rem;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            margin-right: 1rem;
            color: var(--white);
            transition: background 0.3s;
        }
        
        .social-links a:hover {
            background: var(--primary);
        }
        
        .copyright {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            opacity: 0.7;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                text-align: center;
            }
            
            .nav-links {
                margin-top: 1rem;
            }
            
            .nav-links li {
                margin: 0 0.5rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-status {
                margin-top: 1rem;
            }
            
            .progress-steps {
                flex-wrap: wrap;
            }
            
            .step {
                flex: 0 0 50%;
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header class="header">
        <div class="header-container">
            <a href="index.html" class="logo">
                <i class="fas fa-utensils"></i> Flavour Fusion
            </a>
            <ul class="nav-links">
                <li><a href="../index.html">Home</a></li>
                <li><a href="menu.html">Menu</a></li>
                <li><a href="order-track.php">Track Order</a></li>
                <li><a href="contact.html">Contact</a></li>
            </ul>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        <h1 class="page-title">Track Your Order</h1>
        
        <div class="track-form">
            <form method="post">
                <div class="form-group">
                    <label for="order_id">Enter Your Order ID</label>
                    <input type="text" id="order_id" name="order_id" class="form-control" 
                           value="<?php echo htmlspecialchars($order_id); ?>" 
                           placeholder="e.g., 12345" required>
                </div>
                <button type="submit" name="track_order" class="btn btn-block">
                    <i class="fas fa-search"></i> Track Order
                </button>
            </form>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($order): ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <div class="order-id">Order #<span><?php echo htmlspecialchars($order['id']); ?></span></div>
                        <div class="order-date">Placed on <?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></div>
                    </div>
                    <div class="order-status <?php echo getStatusClass($order['status']); ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </div>
                </div>
                
                <!-- Order Progress Tracker -->
                <div class="progress-tracker">
                    <h3 class="section-title">Order Progress</h3>
                    <div class="progress-bar">
                        <div class="progress-completed" style="width: <?php echo getProgressWidth($order['status']); ?>%"></div>
                    </div>
                    <div class="progress-steps">
                        <div class="step <?php echo $order['status'] == 'pending' ? 'active' : ($order['status'] == 'preparing' || $order['status'] == 'on the way' || $order['status'] == 'delivered' || $order['status'] == 'cancelled' ? 'completed' : ''); ?>">
                            <div class="step-icon"><i class="fas fa-receipt"></i></div>
                            <div class="step-label">Order Placed</div>
                        </div>
                        <div class="step <?php echo $order['status'] == 'preparing' ? 'active' : ($order['status'] == 'on the way' || $order['status'] == 'delivered' || $order['status'] == 'cancelled' ? 'completed' : ''); ?>">
                            <div class="step-icon"><i class="fas fa-utensils"></i></div>
                            <div class="step-label">Preparing</div>
                        </div>
                        <div class="step <?php echo $order['status'] == 'on the way' ? 'active' : ($order['status'] == 'delivered' ? 'completed' : ''); ?>">
                            <div class="step-icon"><i class="fas fa-motorcycle"></i></div>
                            <div class="step-label">On the Way</div>
                        </div>
                        <div class="step <?php echo $order['status'] == 'delivered' ? 'completed active' : ''; ?>">
                            <div class="step-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="step-label">Delivered</div>
                        </div>
                    </div>
                </div>
                
                <div class="order-body">
                    <div>
                        <div class="detail-section">
                            <h3 class="section-title">Customer Details</h3>
                            <div class="detail-group">
                                <div class="detail-label">Name:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order['name']); ?></div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Phone:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order['phone']); ?></div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Email:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order['email']); ?></div>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h3 class="section-title">Delivery Details</h3>
                            <div class="detail-group">
                                <div class="detail-label">Type:</div>
                                <div class="detail-value"><?php echo ucfirst($order['delivery_type']); ?> Delivery</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Time:</div>
                                <div class="detail-value"><?php echo date('M j, Y g:i A', strtotime($order['delivery_time'])); ?></div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Status:</div>
                                <div class="detail-value">
                                    <span class="order-status <?php echo getStatusClass($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="detail-section">
                            <h3 class="section-title">Order Summary</h3>
                            <ul class="items-list">
                                <?php 
                                $items = explode(', ', getOrderedItems($order['instructions']));
                                foreach ($items as $item): 
                                    if (!empty(trim($item))): ?>
                                        <li><?php echo htmlspecialchars(trim($item)); ?></li>
                                    <?php endif;
                                endforeach; ?>
                            </ul>
                            <div class="detail-group" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--light-gray);">
                                <div class="detail-label">Total Amount:</div>
                                <div class="detail-value" style="font-weight: 600; color: var(--primary);">
                                    ₹<?php echo number_format($order['total_amount'], 2); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h3 class="section-title">Delivery Address</h3>
                            <div class="detail-value">
                                <?php echo nl2br(htmlspecialchars($order['address'])); ?><br>
                                <?php echo htmlspecialchars($order['city'] . ', ' . $order['state'] . ' - ' . $order['postal_code']); ?><br>
                                <strong>Landmark:</strong> <?php echo htmlspecialchars($order['landmark']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-col">
                <a href="index.html" class="footer-logo">
                    <i class="fas fa-utensils"></i> Flavour Fusion
                </a>
                <p class="footer-about">
                    Bringing authentic flavors to your doorstep. Order now and experience the taste of tradition with a modern twist.
                </p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="footer-col">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="../index.html">Home</a></li>
                    <li><a href="menu.html">Our Menu</a></li>
                    <li><a href="about.html">About Us</a></li>
                    <li><a href="contact.html">Contact</a></li>
                    <li><a href="order-track.php">Track Order</a></li>
                </ul>
            </div>
            
            <div class="footer-col">
                <h3>Contact Us</h3>
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>123 Food Street, City, State - 123456</div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div>+91 9876543210</div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>info@flavourfusion.com</div>
                </div>
            </div>
        </div>
        
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> Flavour Fusion. All Rights Reserved.
        </div>
    </footer>
</body>
</html>