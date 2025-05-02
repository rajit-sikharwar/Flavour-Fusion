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

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['new_status'];
        
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $new_status, ':id' => $order_id]);
            
            $_SESSION['message'] = "Order #$order_id status updated to $new_status successfully!";
            $_SESSION['message_type'] = "success";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        } catch(PDOException $e) {
            $error = "Could not update order status: " . $e->getMessage();
        }
    }
}

// Check if viewing a specific order
$order_details = null;
if (isset($_GET['view_order'])) {
    $order_id = $_GET['view_order'];
    
    try {
        // Get order details
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
        $stmt->execute([':id' => $order_id]);
        $order_details = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order_details) {
            $error = "Order not found!";
        } else {
            // Get order items (assuming you have an order_items table)
            $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
            $stmt->execute([':order_id' => $order_id]);
            $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch(PDOException $e) {
        $error = "Could not fetch order details: " . $e->getMessage();
    }
}

// Fetch all orders
try {
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY order_date DESC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Could not fetch orders: " . $e->getMessage();
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
            --gray: #95a5a6;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light);
            margin: 0;
            padding: 0;
        }
        
        .container {
            width: 95%;
            max-width: 1200px;
            margin: 20px auto;
        }
        
        header {
            background-color: var(--primary);
            color: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }
        
        .error {
            background-color: #ffeeee;
            color: var(--danger);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .success {
            background-color: rgba(39, 174, 96, 0.1);
            color: var(--success);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: var(--primary);
            color: white;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--warning);
        }
        
        .status-completed {
            background-color: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }
        
        .status-cancelled {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .view-btn {
            background-color: var(--secondary);
            color: white;
        }
        
        .complete-btn {
            background-color: var(--success);
            color: white;
        }
        
        .cancel-btn {
            background-color: var(--danger);
            color: white;
        }
        
        .back-btn {
            background-color: var(--gray);
            color: white;
        }
        
        .no-orders {
            text-align: center;
            padding: 40px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        /* Order Details Modal */
        .modal {
            display: <?php echo $order_details ? 'block' : 'none'; ?>;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .modal-content {
            background-color: white;
            margin: 50px auto;
            padding: 20px;
            border-radius: 5px;
            width: 80%;
            max-width: 800px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .modal-title {
            margin: 0;
            color: var(--secondary);
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }
        
        .order-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .detail-group {
            margin-bottom: 15px;
        }
        
        .detail-label {
            font-weight: bold;
            color: var(--secondary);
            margin-bottom: 5px;
            display: block;
        }
        
        .detail-value {
            padding: 8px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        
        .items-table {
            width: 100%;
            margin-top: 20px;
        }
        
        .items-table th {
            background-color: var(--secondary);
        }
        
        .total-section {
            margin-top: 20px;
            text-align: right;
            font-size: 1.1rem;
            font-weight: bold;
        }
        
        .action-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
    </style>
</head>
<body>
    <header>
        <h1><i class="fas fa-clipboard-list"></i> Order Management</h1>
        <div class="nav-links">
            <a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="admin-logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>
    
    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="<?php echo $_SESSION['message_type'] === 'success' ? 'success' : 'error'; ?>">
                <i class="fas <?php echo $_SESSION['message_type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> 
                <?php echo htmlspecialchars($_SESSION['message']); ?>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            </div>
        <?php endif; ?>
        
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
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Order Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                            <td><?php echo htmlspecialchars($order['name'] ?? 'N/A'); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></td>
                            <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="status status-<?php echo strtolower($order['status']); ?>">
                                    <?php if ($order['status'] == 'Pending'): ?>
                                        <i class="fas fa-clock"></i>
                                    <?php elseif ($order['status'] == 'Completed'): ?>
                                        <i class="fas fa-check-circle"></i>
                                    <?php elseif ($order['status'] == 'Cancelled'): ?>
                                        <i class="fas fa-times-circle"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="?view_order=<?php echo $order['id']; ?>" class="action-btn view-btn">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if ($order['status'] == 'Pending'): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="new_status" value="Completed">
                                        <button type="submit" name="update_status" class="action-btn complete-btn" onclick="return confirm('Mark order #<?php echo $order['id']; ?> as completed?')">
                                            <i class="fas fa-check"></i> Complete
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($order['status'] != 'Cancelled'): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="new_status" value="Cancelled">
                                        <button type="submit" name="update_status" class="action-btn cancel-btn" onclick="return confirm('Cancel order #<?php echo $order['id']; ?>?')">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Order Details Modal -->
    <?php if ($order_details): ?>
        <div class="modal" id="orderModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">Order Details #<?php echo htmlspecialchars($order_details['id']); ?></h2>
                    <a href="?" class="action-btn back-btn"><i class="fas fa-arrow-left"></i> Back to Orders</a>
                </div>
                
                <div class="order-details-grid">
                    <div>
                        <div class="detail-group">
                            <span class="detail-label">Customer Name</span>
                            <div class="detail-value"><?php echo htmlspecialchars($order_details['name']); ?></div>
                        </div>
                        <div class="detail-group">
                            <span class="detail-label">Email</span>
                            <div class="detail-value"><?php echo htmlspecialchars($order_details['email'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="detail-group">
                            <span class="detail-label">Phone</span>
                            <div class="detail-value"><?php echo htmlspecialchars($order_details['phone'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="detail-group">
                            <span class="detail-label">Order Date</span>
                            <div class="detail-value"><?php echo date('M j, Y g:i A', strtotime($order_details['order_date'])); ?></div>
                        </div>
                        <div class="detail-group">
                            <span class="detail-label">Status</span>
                            <div class="detail-value">
                                <span class="status status-<?php echo strtolower($order_details['status']); ?>">
                                    <?php if ($order_details['status'] == 'Pending'): ?>
                                        <i class="fas fa-clock"></i>
                                    <?php elseif ($order_details['status'] == 'Completed'): ?>
                                        <i class="fas fa-check-circle"></i>
                                    <?php elseif ($order_details['status'] == 'Cancelled'): ?>
                                        <i class="fas fa-times-circle"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($order_details['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="detail-group">
                            <span class="detail-label">Payment Method</span>
                            <div class="detail-value"><?php echo htmlspecialchars($order_details['payment_method'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="detail-group">
                    <span class="detail-label">Delivery Address</span>
                    <div class="detail-value"><?php echo nl2br(htmlspecialchars($order_details['address'] ?? 'N/A')); ?></div>
                </div>
                
                <div class="detail-group">
                    <span class="detail-label">Notes</span>
                    <div class="detail-value"><?php echo nl2br(htmlspecialchars($order_details['notes'] ?? 'No special instructions')); ?></div>
                </div>
                
                <h3>Order Items</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($order_items)): ?>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                    <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No items found for this order</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="total-section">
                    <p>Subtotal: ₹<?php echo number_format($order_details['total_amount'], 2); ?></p>
                    <p>Delivery Fee: ₹<?php echo number_format($order_details['delivery_fee'] ?? 0, 2); ?></p>
                    <p>Total Amount: ₹<?php echo number_format($order_details['total_amount'] + ($order_details['delivery_fee'] ?? 0), 2); ?></p>
                </div>
                
                <div class="action-buttons">
                    <?php if ($order_details['status'] == 'Pending'): ?>
                        <form method="post">
                            <input type="hidden" name="order_id" value="<?php echo $order_details['id']; ?>">
                            <input type="hidden" name="new_status" value="Completed">
                            <button type="submit" name="update_status" class="action-btn complete-btn">
                                <i class="fas fa-check"></i> Mark as Completed
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($order_details['status'] != 'Cancelled'): ?>
                        <form method="post">
                            <input type="hidden" name="order_id" value="<?php echo $order_details['id']; ?>">
                            <input type="hidden" name="new_status" value="Cancelled">
                            <button type="submit" name="update_status" class="action-btn cancel-btn">
                                <i class="fas fa-times"></i> Cancel Order
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>