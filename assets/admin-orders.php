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

// Fetch all orders with customer information
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.full_name, u.email, u.phone, u.address
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.order_date DESC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each order, fetch the order items if order_items table exists
    foreach ($orders as &$order) {
        try {
            $items_stmt = $pdo->prepare("
                SELECT oi.*, p.name, p.image
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $items_stmt->execute([$order['id']]);
            $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $order['items'] = [];
        }
    }
} catch(PDOException $e) {
    $error = "Could not fetch orders. Please check your database tables.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Flavour Fusion</title>
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
        }
        
        .container {
            width: 95%;
            max-width: 1400px;
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
        
        .orders-list {
            display: grid;
            gap: 20px;
        }
        
        .order-card {
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: #f9f9f9;
            border-bottom: 1px solid #eee;
        }
        
        .order-id {
            font-weight: 700;
            color: var(--secondary);
        }
        
        .order-date {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .order-status {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }
        
        .status-processing {
            background-color: rgba(52, 152, 219, 0.1);
            color: var(--info);
        }
        
        .status-completed {
            background-color: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }
        
        .status-cancelled {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }
        
        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 20px;
        }
        
        .customer-info, .order-summary {
            padding: 15px;
        }
        
        .section-title {
            font-size: 1.1rem;
            color: var(--secondary);
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: 600;
            width: 120px;
            color: var(--gray);
        }
        
        .info-value {
            flex: 1;
        }
        
        .order-items {
            grid-column: span 2;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .items-table th {
            background-color: #f5f5f5;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--secondary);
        }
        
        .items-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 10px;
        }
        
        .product-name {
            font-weight: 600;
        }
        
        .product-price {
            color: var(--primary);
            font-weight: 600;
        }
        
        .order-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 15px 20px;
            border-top: 1px solid #eee;
            background-color: #f9f9f9;
        }
        
        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .process-btn {
            background-color: var(--info);
            color: var(--white);
        }
        
        .complete-btn {
            background-color: var(--success);
            color: var(--white);
        }
        
        .cancel-btn {
            background-color: var(--danger);
            color: var(--white);
        }
        
        .print-btn {
            background-color: var(--secondary);
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
        
        @media (max-width: 768px) {
            .order-details {
                grid-template-columns: 1fr;
            }
            
            .order-items {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1><i class="fas fa-clipboard-list"></i> Order Management</h1>
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
        
        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <h2><i class="fas fa-box-open"></i> No Orders Found</h2>
                <p>There are currently no orders in the system.</p>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-id">Order #<?php echo htmlspecialchars($order['id']); ?></span>
                                <span class="order-date"><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></span>
                            </div>
                            <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                                <?php if ($order['status'] == 'Pending'): ?>
                                    <i class="fas fa-clock"></i>
                                <?php elseif ($order['status'] == 'Processing'): ?>
                                    <i class="fas fa-cog fa-spin"></i>
                                <?php elseif ($order['status'] == 'Completed'): ?>
                                    <i class="fas fa-check-circle"></i>
                                <?php elseif ($order['status'] == 'Cancelled'): ?>
                                    <i class="fas fa-times-circle"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </div>
                        
                        <div class="order-details">
                            <div class="customer-info">
                                <h3 class="section-title"><i class="fas fa-user"></i> Customer Information</h3>
                                <div class="info-row">
                                    <span class="info-label">Name:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($order['full_name']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($order['email']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Phone:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($order['phone']); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Address:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($order['address']); ?></span>
                                </div>
                            </div>
                            
                            <div class="order-summary">
                                <h3 class="section-title"><i class="fas fa-receipt"></i> Order Summary</h3>
                                <div class="info-row">
                                    <span class="info-label">Subtotal:</span>
                                    <span class="info-value">$<?php echo number_format($order['total_amount'] - ($order['shipping'] ?? 0) - ($order['tax'] ?? 0), 2); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Shipping:</span>
                                    <span class="info-value">$<?php echo number_format($order['shipping'] ?? 0, 2); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Tax:</span>
                                    <span class="info-value">$<?php echo number_format($order['tax'] ?? 0, 2); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Total:</span>
                                    <span class="info-value" style="font-weight: 700; color: var(--primary);">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Payment Method:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($order['payment_method'] ?? 'Credit Card'); ?></span>
                                </div>
                            </div>
                            
                            <div class="order-items">
                                <h3 class="section-title"><i class="fas fa-shopping-basket"></i> Ordered Items</h3>
                                <?php if (empty($order['items'])): ?>
                                    <p>No items found for this order.</p>
                                <?php else: ?>
                                    <table class="items-table">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($order['items'] as $item): ?>
                                                <tr>
                                                    <td>
                                                        <div style="display: flex; align-items: center;">
                                                            <?php if (!empty($item['image'])): ?>
                                                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-image">
                                                            <?php else: ?>
                                                                <div class="product-image" style="background: #f5f5f5; display: flex; align-items: center; justify-content: center;">
                                                                    <i class="fas fa-image" style="color: #ccc;"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                            <span class="product-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="product-price">$<?php echo number_format($item['price'], 2); ?></td>
                                                    <td><?php echo $item['quantity']; ?></td>
                                                    <td class="product-price">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="order-actions">
                            <?php if ($order['status'] == 'Pending'): ?>
                                <button class="action-btn process-btn">
                                    <i class="fas fa-cog"></i> Process Order
                                </button>
                            <?php elseif ($order['status'] == 'Processing'): ?>
                                <button class="action-btn complete-btn">
                                    <i class="fas fa-check"></i> Mark as Completed
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($order['status'] != 'Cancelled' && $order['status'] != 'Completed'): ?>
                                <button class="action-btn cancel-btn">
                                    <i class="fas fa-times"></i> Cancel Order
                                </button>
                            <?php endif; ?>
                            
                            <button class="action-btn print-btn">
                                <i class="fas fa-print"></i> Print Invoice
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>