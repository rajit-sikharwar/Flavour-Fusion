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

// Get statistics
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_earnings = $pdo->query("SELECT SUM(total_amount) FROM orders")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$preparing_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'preparing'")->fetchColumn();
$ontheway_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'on the way'")->fetchColumn();
$delivered_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn();
$cancelled_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn();

// Get recent orders (last 10)
$stmt = $pdo->query("
    SELECT *
    FROM orders
    ORDER BY order_date DESC
    LIMIT 10
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
    
    header("Location: admin-dashboard.php");
    exit;
}

// Process logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin-login.php");
    exit;
}

// Function to extract ordered items from instructions
function getOrderedItems($instructions) {
    $pattern = '/Selected Items: (.*)/';
    if (preg_match($pattern, $instructions, $matches)) {
        return $matches[1];
    }
    return 'Items not specified';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Flavour Fusion</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            --info: #3498db;
            --white: #fff;
            --sidebar-width: 250px;
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
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--secondary);
            color: var(--white);
            position: fixed;
            height: 100vh;
            transition: all 0.3s;
            z-index: 100;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }
        
        .sidebar-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 1rem;
        }
        
        .sidebar-header h3 {
            font-size: 1.1rem;
            margin-bottom: 0.2rem;
        }
        
        .sidebar-header p {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            color: var(--white);
            text-decoration: none;
            transition: all 0.3s;
            opacity: 0.8;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(0, 0, 0, 0.2);
            opacity: 1;
        }
        
        .sidebar-menu a i {
            margin-right: 1rem;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            padding: 1.5rem;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .header h1 {
            font-size: 1.8rem;
            color: var(--secondary);
        }
        
        .user-profile {
            display: flex;
            align-items: center;
        }
        
        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 1rem;
        }
        
        .user-profile .logout-btn {
            background: var(--danger);
            color: var(--white);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s;
        }
        
        .user-profile .logout-btn:hover {
            background: #c82333;
        }
        
        /* Stats Cards */
        .stats-container {
            display: flex;
            overflow-x: auto;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
        }
        
        .stats-container::-webkit-scrollbar {
            height: 6px;
        }
        
        .stats-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .stats-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        
        .stats-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        .stats-card {
            background: var(--white);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            transition: transform 0.3s;
            min-width: 200px;
            flex-shrink: 0;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.5rem;
            font-size: 1.3rem;
        }
        
        .stats-icon.primary {
            background: rgba(230, 126, 34, 0.1);
            color: var(--primary);
        }
        
        .stats-icon.success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }
        
        .stats-icon.warning {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }
        
        .stats-icon.info {
            background: rgba(52, 152, 219, 0.1);
            color: var(--info);
        }
        
        .stats-icon.danger {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }
        
        .stats-info h3 {
            font-size: 1.5rem;
            margin-bottom: 0.2rem;
        }
        
        .stats-info p {
            color: var(--gray);
            font-size: 0.85rem;
        }
        
        /* Orders Section */
        .section-title {
            font-size: 1.5rem;
            color: var(--secondary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light-gray);
        }
        
        .order-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.02);
            border-bottom: 1px solid var(--light-gray);
        }
        
        .order-id {
            font-weight: 600;
            color: var(--primary);
            font-size: 1.1rem;
        }
        
        .order-date {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
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
            background: #ffeeba;
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
        
        .order-body {
            padding: 1.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .order-section {
            margin-bottom: 1.5rem;
        }
        
        .section-subtitle {
            font-size: 1.1rem;
            color: var(--secondary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .section-subtitle i {
            margin-right: 0.5rem;
            color: var(--primary);
        }
        
        .detail-group {
            display: flex;
            margin-bottom: 0.8rem;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--secondary);
            min-width: 120px;
        }
        
        .detail-value {
            color: var(--dark);
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
        
        .order-actions {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-total {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .order-total span {
            color: var(--primary);
        }
        
        .update-form {
            display: flex;
            align-items: center;
        }
        
        .update-form select {
            padding: 0.5rem;
            border: 1px solid var(--light-gray);
            border-radius: 4px;
            margin-right: 0.5rem;
            font-family: inherit;
        }
        
        .update-form button {
            background: var(--primary);
            color: var(--white);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
            font-family: inherit;
        }
        
        .update-form button:hover {
            background: var(--primary-dark);
        }
        
        /* Responsive Styles */
        @media (max-width: 1200px) {
            .stats-card {
                min-width: 180px;
            }
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                overflow: hidden;
            }
            
            .sidebar-header h3, 
            .sidebar-header p,
            .sidebar-menu a span {
                display: none;
            }
            
            .sidebar-menu a {
                justify-content: center;
            }
            
            .sidebar-menu a i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            
            .main-content {
                margin-left: 80px;
                width: calc(100% - 80px);
            }
        }
        
        @media (max-width: 768px) {
            .order-body {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-profile {
                margin-top: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .stats-card {
                min-width: 160px;
                padding: 1rem;
            }
            
            .stats-icon {
                width: 40px;
                height: 40px;
                margin-right: 1rem;
            }
            
            .stats-info h3 {
                font-size: 1.3rem;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-status {
                margin-top: 0.5rem;
            }
            
            .order-actions {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .update-form {
                margin-top: 1rem;
                width: 100%;
            }
            
            .update-form select {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../images/default.png" alt="User Profile" style="border: 2px solid #000000; border-radius: 50%; width: 50px; height: 50px;">
            <div>
                <h3><?php echo htmlspecialchars($_SESSION['admin_name']); ?></h3>
                <p>Administrator</p>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <a href="admin-dashboard.php" class="active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin-orders.php">
                <i class="fas fa-shopping-bag"></i>
                <span>Orders</span>
            </a>
            <a href="admin-menu.php">
                <i class="fas fa-utensils"></i>
                <span>Menu Items</span>
            </a>
            <a href="admin-customers.php">
                <i class="fas fa-users"></i>
                <span>Customers</span>
            </a>
            <a href="admin-reports.php">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <a href="admin-profile.php">
                <i class="fas fa-user-cog"></i>
                <span>Profile Settings</span>
            </a>
            <a href="?logout=1">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="header">
            <h1>Dashboard Overview</h1>
            <div class="user-profile">
                <img src="../images/default.png" alt="User Profile" style="border: 2px solid #000000; border-radius: 50%;">
                <div class="user-name" style="margin-right: 40px;"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></div>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
        </div>
        
        <!-- Statistics Cards - Horizontal Scrollable -->
        <div class="stats-container">
            <div class="stats-card">
                <div class="stats-icon primary">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stats-info">
                    <h3><?php echo number_format($total_orders); ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon success">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="stats-info">
                    <h3>₹<?php echo number_format($total_earnings, 2); ?></h3>
                    <p>Total Earnings</p>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stats-info">
                    <h3><?php echo number_format($pending_orders); ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon info">
                    <i class="fas fa-blender"></i>
                </div>
                <div class="stats-info">
                    <h3><?php echo number_format($preparing_orders); ?></h3>
                    <p>Preparing</p>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon warning">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stats-info">
                    <h3><?php echo number_format($ontheway_orders); ?></h3>
                    <p>On the Way</p>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stats-info">
                    <h3><?php echo number_format($delivered_orders); ?></h3>
                    <p>Delivered</p>
                </div>
            </div>

            <div class="stats-card">
                <div class="stats-icon danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stats-info">
                    <h3><?php echo number_format($cancelled_orders); ?></h3>
                    <p>Cancelled</p>
                </div>
            </div>
        </div>
        
        <!-- Recent Orders Section -->
        <h2 class="section-title">Recent Orders</h2>
        
        <?php if (empty($orders)): ?>
            <div class="order-card" style="text-align: center; padding: 2rem;">
                <p>No orders found.</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-id">Order #<?php echo $order['id']; ?></div>
                            <div class="order-date"><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></div>
                        </div>
                        <div class="order-status status-<?php echo str_replace(' ', '-', strtolower($order['status'])); ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </div>
                    </div>
                    
                    <div class="order-body">
                        <div class="order-section">
                            <h3 class="section-subtitle"><i class="fas fa-user"></i> Customer Details</h3>
                            <div class="detail-group">
                                <div class="detail-label">Name:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order['name']); ?></div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Email:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order['email']); ?></div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Phone:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order['phone']); ?></div>
                            </div>
                        </div>
                        
                        <div class="order-section">
                            <h3 class="section-subtitle"><i class="fas fa-truck"></i> Delivery Details</h3>
                            <div class="detail-group">
                                <div class="detail-label">Address:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order['address']); ?></div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">City/State:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order['city'] . ', ' . $order['state']); ?></div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Landmark:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order['landmark']); ?></div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Delivery Time:</div>
                                <div class="detail-value"><?php echo date('M j, Y g:i A', strtotime($order['delivery_time'])); ?></div>
                            </div>
                        </div>
                        
                        <div class="order-section">
                            <h3 class="section-subtitle"><i class="fas fa-list"></i> Order Summary</h3>
                            <ul class="items-list">
                                <?php 
                                $items = explode(', ', getOrderedItems($order['instructions']));
                                foreach ($items as $item): 
                                    if (!empty(trim($item))): ?>
                                        <li><?php echo htmlspecialchars(trim($item)); ?></li>
                                    <?php endif;
                                endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="order-section">
                            <h3 class="section-subtitle"><i class="fas fa-comment"></i> Special Instructions</h3>
                            <div class="detail-value">
                                <?php 
                                $instructions = preg_replace('/Selected Items:.*/', '', $order['instructions']);
                                echo !empty(trim($instructions)) ? nl2br(htmlspecialchars(trim($instructions))) : 'None'; 
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <div class="order-total">Total: <span>₹<?php echo number_format($order['total_amount'], 2); ?></span></div>
                        <form method="post" class="update-form">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="status" required>
                                <option value="pending" <?php echo ($order['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="preparing" <?php echo ($order['status'] == 'preparing') ? 'selected' : ''; ?>>Preparing</option>
                                <option value="on the way" <?php echo ($order['status'] == 'on the way') ? 'selected' : ''; ?>>On the way</option>
                                <option value="delivered" <?php echo ($order['status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status">Update Status</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>