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

// Get date range for reports (default to current month)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Initialize variables
$error = '';
$sales_data = [];
$top_products = [];

try {
    // Fetch sales data
    $sales_stmt = $pdo->prepare("
        SELECT 
            DATE(order_date) as order_day,
            COUNT(*) as total_orders,
            SUM(total_amount) as total_sales
        FROM orders
        WHERE order_date BETWEEN ? AND ?
        GROUP BY DATE(order_date)
        ORDER BY order_day
    ");
    $sales_stmt->execute([$start_date, $end_date]);
    $sales_data = $sales_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Try to fetch top products (handle if order_items table doesn't exist)
    try {
        $products_stmt = $pdo->prepare("
            SELECT 
                p.name,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.quantity * oi.price) as total_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.order_date BETWEEN ? AND ?
            GROUP BY p.id
            ORDER BY total_quantity DESC
            LIMIT 5
        ");
        $products_stmt->execute([$start_date, $end_date]);
        $top_products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Could not fetch top products data. Please ensure all required tables exist.";
    }
} catch(PDOException $e) {
    $error = "Could not fetch sales data. Please check your database connection and tables.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports - Flavour Fusion</title>
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
            max-width: 1200px;
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
        
        .date-filter {
            background-color: var(--white);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }
        
        .date-filter form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 15px;
        }
        
        .date-filter label {
            font-weight: 600;
            color: var(--secondary);
        }
        
        .date-filter input {
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .date-filter button {
            padding: 10px 20px;
            background-color: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .date-filter button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background-color: var(--white);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        
        .summary-card h3 {
            margin-top: 0;
            color: var(--gray);
            font-size: 1rem;
        }
        
        .summary-card p {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin: 10px 0 0;
        }
        
        .report-section {
            background-color: var(--white);
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }
        
        .report-section h2 {
            margin-top: 0;
            color: var(--secondary);
            border-bottom: 2px solid var(--light);
            padding-bottom: 10px;
        }
        
        .chart-container {
            height: 350px;
            margin-top: 20px;
            position: relative;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
        
        .error {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--danger);
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--gray);
            font-size: 1.1rem;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <h1><i class="fas fa-chart-bar"></i> Sales Reports</h1>
        <div class="nav-links">
            <a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="admin-customers.php"><i class="fas fa-users"></i> Customers</a>
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
        
        <div class="date-filter">
            <form method="get">
                <label for="start_date">From:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
                
                <label for="end_date">To:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
                
                <button type="submit"><i class="fas fa-filter"></i> Generate Report</button>
            </form>
        </div>
        
        <div class="summary-cards">
            <div class="summary-card">
                <h3><i class="fas fa-shopping-cart"></i> Total Orders</h3>
                <p><?php echo count($sales_data); ?></p>
            </div>
            <div class="summary-card">
                <h3><i class="fas fa-money-bill-wave"></i> Total Revenue</h3>
                <p>₹<?php 
                    $total = array_sum(array_column($sales_data, 'total_sales'));
                    echo number_format($total, 2); 
                ?></p>
            </div>
            <div class="summary-card">
                <h3><i class="fas fa-calculator"></i> Average Order Value</h3>
                <p>₹<?php 
                    $avg = count($sales_data) > 0 ? $total / count($sales_data) : 0;
                    echo number_format($avg, 2); 
                ?></p>
            </div>
        </div>
        
        <div class="report-section">
            <h2><i class="fas fa-chart-line"></i> Daily Sales</h2>
            <?php if (empty($sales_data)): ?>
                <div class="no-data">
                    <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <p>No sales data available for the selected period.</p>
                </div>
            <?php else: ?>
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="report-section">
            <h2><i class="fas fa-star"></i> Top Products</h2>
            <?php if (empty($top_products)): ?>
                <div class="no-data">
                    <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <p>No product data available or order_items table doesn't exist.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity Sold</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo $product['total_quantity']; ?></td>
                                <td>₹<?php echo number_format($product['total_revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($sales_data)): ?>
    <script>
        // Sales chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php foreach ($sales_data as $sale): ?>
                        '<?php echo date("M j", strtotime($sale["order_day"])); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: 'Daily Sales ($)',
                    data: [
                        <?php foreach ($sales_data as $sale): ?>
                            <?php echo $sale['total_sales']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: 'rgba(230, 126, 34, 0.1)',
                    borderColor: 'rgba(230, 126, 34, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    },
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>