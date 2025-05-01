<?php
$host = 'localhost';
$db = 'flavour_fusion';
$user = 'root';
$pass = ''; // Change if your MySQL has a password

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Sanitize and get inputs
$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$altPhone = $_POST['altPhone'];
$address = $_POST['address'];
$state = $_POST['state'];
$city = $_POST['city'];
$postal = $_POST['postal_code'];
$landmark = $_POST['landmark'];
$deliveryTime = $_POST['delivery_time'];
$deliveryType = $_POST['delivery_type'];
$instructions = $_POST['instructions'];

// Get food items and calculate total
$foodItems = isset($_POST['food']) ? $_POST['food'] : [];
$total = 0;
$foodList = [];

foreach ($foodItems as $item) {
    $price = $_POST['food'] ? $_POST['food'] : 0;
    $foodList[] = $item;
}

foreach ($_POST['food'] as $index => $food) {
    $price = $_POST['food'][$index] ?? 0;
    $total += (float) $_POST['food'][$index];
}

$foods = implode(', ', $foodItems);

// Add delivery charge if express
if ($deliveryType == 'express') {
    $total += 50;
}

// Prepare SQL
$sql = "INSERT INTO orders (
    full_name, email, phone, alt_phone, address, state, city, postal_code, landmark,
    food_items, total_price, delivery_time, delivery_type, instructions
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssssssdsss", $name, $email, $phone, $altPhone, $address, $state, $city, $postal, $landmark,
                  $foods, $total, $deliveryTime, $deliveryType, $instructions);

if ($stmt->execute()) {
    echo "Order placed successfully!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
