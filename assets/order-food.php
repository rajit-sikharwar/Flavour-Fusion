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
$errors = [];
$success = false;

// Process form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $name = trim($_POST['name']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone']);
    $altPhone = trim($_POST['altPhone']);
    $address = trim($_POST['address']);
    $state = trim($_POST['state']);
    $city = trim($_POST['city']);
    $postal_code = trim($_POST['postal_code']);
    $landmark = trim($_POST['landmark']);
    $delivery_time = trim($_POST['delivery_time']);
    $delivery_type = trim($_POST['delivery_type']);
    $instructions = trim($_POST['instructions']);
    $food_items = isset($_POST['food']) ? $_POST['food'] : [];
    
    // Validate required fields
    if (empty($name)) $errors[] = "Full name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($phone) || !preg_match('/^[0-9]{10}$/', $phone)) $errors[] = "Valid 10-digit phone number is required.";
    if (empty($altPhone) || !preg_match('/^[0-9]{10}$/', $altPhone)) $errors[] = "Valid 10-digit alternative phone number is required.";
    if (empty($address)) $errors[] = "Delivery address is required.";
    if (empty($state)) $errors[] = "State is required.";
    if (empty($city)) $errors[] = "City is required.";
    if (empty($postal_code) || !preg_match('/^[0-9]{6}$/', $postal_code)) $errors[] = "Valid 6-digit postal code is required.";
    if (empty($landmark)) $errors[] = "Landmark is required.";
    if (empty($delivery_time)) $errors[] = "Delivery time is required.";
    if (empty($delivery_type)) $errors[] = "Delivery type is required.";
    if (empty($food_items)) $errors[] = "Please select at least one food item.";
    
    // If no errors, proceed with database insertion
    if (empty($errors)) {
        try {
            // Calculate total amount
            $total_amount = 0;
            $food_prices = [
                'Paneer Butter Masala' => 220,
                'Chole Bhature' => 100,
                'Masala Dosa' => 90,
                'Dal Makhani' => 180,
                'Palak Paneer' => 200,
                'Rajma Chawal' => 120,
                'Aloo Paratha' => 60,
                'Idli Sambar' => 80,
                'Vada Pav' => 30,
                'Kadhai Paneer' => 210,
                'Veg Pulao' => 150,
                'Mix Veg Curry' => 170,
                'Veg Hakka Noodles' => 140,
                'Paneer Tikka' => 190,
                'Gulab Jamun' => 50
            ];
            
            // Create a string of selected food items
            $selected_items = implode(", ", $food_items);
            
            foreach ($food_items as $item) {
                if (isset($food_prices[$item])) {
                    $total_amount += $food_prices[$item];
                }
            }
            
            // Add express delivery charge if selected
            if ($delivery_type == 'express') {
                $total_amount += 50;
            }
            
            // Insert order into orders table
            $stmt = $pdo->prepare("INSERT INTO orders (
                name, email, phone, alt_phone, address, 
                state, city, postal_code, landmark, 
                delivery_time, delivery_type, instructions, 
                total_amount
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $name, 
                $email, 
                $phone, 
                $altPhone, 
                $address, 
                $state, 
                $city, 
                $postal_code, 
                $landmark, 
                $delivery_time, 
                $delivery_type, 
                $instructions . "\n\nSelected Items: " . $selected_items,
                $total_amount
            ]);
            
            // Get the last inserted order ID
            $order_id = $pdo->lastInsertId();
            $success = true;
            
            // Return JSON response for AJAX
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'order_id' => $order_id,
                'delivery_time' => getDeliveryEstimate($delivery_type, $delivery_time)
            ]);
            exit();
            
        } catch(PDOException $e) {
            $errors[] = "Error processing your order. Please try again. " . $e->getMessage();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'errors' => $errors
            ]);
            exit();
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'errors' => $errors
        ]);
        exit();
    }
}

function getDeliveryEstimate($delivery_type, $delivery_time) {
    if ($delivery_time) {
        $deliveryDate = new DateTime($delivery_time);
        $now = new DateTime();
        
        // If delivery time is in the future
        if ($deliveryDate > $now) {
            return 'Today by ' . $deliveryDate->format('h:i A');
        }
    }
    
    // Default estimate based on delivery type
    if ($delivery_type === 'express') {
        return 'Within 30-45 minutes';
    } else {
        return 'Within 60-90 minutes';
    }
}
?>