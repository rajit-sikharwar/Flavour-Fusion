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

// Get all orders with their items
$stmt = $pdo->query("
    SELECT o.*, GROUP_CONCAT(oi.item_name SEPARATOR ', ') AS items
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.order_date DESC
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Flavour Fusion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .header {
            background: #e67e22;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .order-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .order-id {
            font-weight: bold;
            color: #e67e22;
        }
        .order-date {
            color: #777;
        }
        .order-status {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
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
        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .detail-group {
            margin-bottom: 15px;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }
        .order-items {
            margin-top: 15px;
        }
        .update-form {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        select, button {
            padding: 8px 12px;
            border-radius: 4px;
        }
        select {
            border: 1px solid #ddd;
        }
        button {
            background: #e67e22;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background: #d35400;
        }
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
        }
        .logout-btn:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Flavour Fusion - Admin Dashboard</h1>
        <div>
            Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <h2>Recent Orders</h2>
        
        <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <span class="order-id">Order #<?php echo $order['id']; ?></span>
                        <span class="order-date"><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></span>
                    </div>
                    <div class="order-status status-<?php echo str_replace(' ', '-', strtolower($order['status'])); ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </div>
                </div>
                
                <div class="order-details">
                    <div>
                        <div class="detail-group">
                            <div class="detail-label">Customer</div>
                            <div><?php echo htmlspecialchars($order['name']); ?></div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Contact</div>
                            <div><?php echo htmlspecialchars($order['phone']); ?> / <?php echo htmlspecialchars($order['alt_phone']); ?></div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Email</div>
                            <div><?php echo htmlspecialchars($order['email']); ?></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="detail-group">
                            <div class="detail-label">Delivery Address</div>
                            <div><?php echo nl2br(htmlspecialchars($order['address'])); ?></div>
                            <div><?php echo htmlspecialchars($order['city'] . ', ' . $order['state'] . ' - ' . $order['postal_code']); ?></div>
                            <div>Landmark: <?php echo htmlspecialchars($order['landmark']); ?></div>
                        </div>
                        <div class="detail-group">
                            <div class="detail-label">Delivery Time</div>
                            <div><?php echo date('M j, Y g:i A', strtotime($order['delivery_time'])); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="order-items">
                    <div class="detail-label">Items Ordered</div>
                    <div><?php echo htmlspecialchars($order['items']); ?></div>
                </div>
                
                <div class="detail-group">
                    <div class="detail-label">Special Instructions</div>
                    <div><?php echo !empty($order['instructions']) ? nl2br(htmlspecialchars($order['instructions'])) : 'None'; ?></div>
                </div>
                
                <div class="detail-group">
                    <div class="detail-label">Total Amount</div>
                    <div>₹<?php echo number_format($order['total_amount'], 2); ?></div>
                </div>
                
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
        <?php endforeach; ?>
    </div>
</body>
</html>