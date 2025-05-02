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

// Initialize variables
$error = '';
$orders = [];
$customerCount = 0;
$customers = [];
$totalOrders = 0;
$pendingOrders = 0;
$completedOrders = 0;
$cancelledOrders = 0;

try {
    // Try fetching orders with 'users' table first
    $stmt = $pdo->prepare("
        SELECT o.*, u.full_name, u.email, u.phone 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.order_date DESC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalOrders = count($orders);

    // Count unique customers (based on email)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT email) as customer_count FROM orders");
    $customerCount = $stmt->fetch(PDO::FETCH_ASSOC)['customer_count'];

    // Count pending orders
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'");
    $pendingOrders = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Count completed orders
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Completed'");
    $completedOrders = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Count cancelled orders
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Cancelled'");
    $cancelledOrders = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get unique customers list with their order counts
    $stmt = $pdo->query("
        SELECT 
            name, 
            email, 
            phone, 
            alt_phone, 
            state,
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_orders
        FROM orders 
        GROUP BY email 
        ORDER BY name
    ");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = "Could not fetch data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Orders - Flavour Fusion</title>
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
        }
        
        .container {
            width: 95%;
            margin: 0 auto;
            padding: 20px 0;
        }
        
        header {
            background-color: var(--primary);
            color: var(--white);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            margin: 0;
            font-size: 1.8rem;
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
        
        .error {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--danger);
        }
        
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            flex: 1;
            min-width: 200px;
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        
        .stat-card h3 {
            color: var(--secondary);
            margin-bottom: 10px;
            font-size: 1rem;
        }
        
        .stat-card p {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--white);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: var(--primary);
            color: var(--white);
            font-weight: 600;
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .status-pending {
            color: var(--warning);
            font-weight: 600;
        }
        
        .status-completed {
            color: var(--success);
            font-weight: 600;
        }
        
        .status-cancelled {
            color: var(--danger);
            font-weight: 600;
        }
        
        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 8px;
            font-size: 0.85rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
        }
        
        .action-btn i {
            margin-right: 5px;
        }
        
        .view-btn {
            background-color: var(--secondary);
            color: var(--white);
        }
        
        .update-btn {
            background-color: var(--success);
            color: var(--white);
        }
        
        .cancel-btn {
            background-color: var(--danger);
            color: var(--white);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .no-orders {
            text-align: center;
            padding: 40px;
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }
        
        .customer-list {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }
        
        .customer-list h2 {
            margin-bottom: 20px;
            color: var(--secondary);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
        }
        
        .customer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .customer-card {
            background-color: #f9f9f9;
            border-radius: 6px;
            padding: 15px;
            border-left: 4px solid var(--primary);
        }
        
        .customer-card h3 {
            color: var(--secondary);
            margin-bottom: 8px;
        }
        
        .customer-card p {
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .customer-card p i {
            color: var(--primary);
            margin-right: 8px;
            width: 20px;
        }
        
        .customer-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #ddd;
        }
        
        .stat-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .stat-total {
            background-color: rgba(230, 126, 34, 0.1);
            color: var(--primary);
        }
        
        .stat-pending {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }
        
        .stat-completed {
            background-color: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }
        
        .stat-cancelled {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }
    </style>
</head>
<body>
    <header>
        <h1><i class="fas fa-users"></i> Customer Orders</h1>
        <div class="nav-links">
            <a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="admin-reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="admin-profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="admin-logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>
    
    <div class="container">
        <?php if (!empty($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-container">
            <div class="stat-card">
                <h3><i class="fas fa-users"></i> Total Customers</h3>
                <p><?php echo $customerCount; ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-shopping-cart"></i> Total Orders</h3>
                <p><?php echo $totalOrders; ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-clock"></i> Pending Orders</h3>
                <p><?php echo $pendingOrders; ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-check-circle"></i> Completed Orders</h3>
                <p><?php echo $completedOrders; ?></p>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-times-circle"></i> Cancelled Orders</h3>
                <p><?php echo $cancelledOrders; ?></p>
            </div>
        </div>
        
        <div class="customer-list">
            <h2><i class="fas fa-user-friends"></i> Customer List (<?php echo $customerCount; ?>)</h2>
            <div class="customer-grid">
                <?php foreach ($customers as $customer): ?>
                    <div class="customer-card">
                        <h3><?php echo htmlspecialchars($customer['name']); ?></h3>
                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($customer['email']); ?></p>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['phone']); ?></p>
                        <?php if (!empty($customer['alt_phone'])): ?>
                            <p><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($customer['alt_phone']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($customer['state'])): ?>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($customer['state']); ?></p>
                        <?php endif; ?>
                        
                        <div class="customer-stats">
                            <span class="stat-badge stat-total" title="Total Orders">
                                <i class="fas fa-shopping-cart"></i> <?php echo $customer['total_orders']; ?>
                            </span>
                            <span class="stat-badge stat-pending" title="Pending Orders">
                                <i class="fas fa-clock"></i> <?php echo $customer['pending_orders']; ?>
                            </span>
                            <span class="stat-badge stat-completed" title="Completed Orders">
                                <i class="fas fa-check"></i> <?php echo $customer['completed_orders']; ?>
                            </span>
                            <span class="stat-badge stat-cancelled" title="Cancelled Orders">
                                <i class="fas fa-times"></i> <?php echo $customer['cancelled_orders']; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <h2 style="margin: 30px 0 20px; color: var(--secondary);"><i class="fas fa-clipboard-list"></i> Recent Orders (<?php echo $totalOrders; ?>)</h2>
        
        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <h2><i class="fas fa-box-open"></i> No Orders Found</h2>
                <p>There are currently no orders in the system.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Order Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                            <td><?php echo htmlspecialchars($order['name']); ?></td>
                            <td><?php echo htmlspecialchars($order['email']); ?></td>
                            <td><?php echo htmlspecialchars($order['phone']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></td>
                            <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td class="status-<?php echo strtolower($order['status']); ?>">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </td>
                            <td>
                                <button class="action-btn view-btn">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <button class="action-btn update-btn">
                                    <i class="fas fa-edit"></i> Update
                                </button>
                                <button class="action-btn cancel-btn">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>