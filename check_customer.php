<?php
/**
 * check_customer.php
 * Checks if a customer exists by phone number and returns their information
 * Used for auto-filling customer information in the order form
 */

// Database connection configuration
$host = 'localhost';
$dbname = 'cosc2328_asg10';
$username = 'your_username'; // Change to your database username
$password = 'your_password'; // Change to your database password

// Initialize response array
$response = array(
    'exists' => false,
    'name' => '',
    'address' => '',
    'city' => '',
    'state' => '',
    'zip' => ''
);

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if request is POST and phone is provided
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['phone'])) {
        $phone = $_POST['phone'];
        
        // Prepare and execute query to check if customer exists
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE phone = :phone");
        $stmt->bindParam(':phone', $phone);
        $stmt->execute();
        
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            // Customer exists, populate response with their information
            $response['exists'] = true;
            $response['name'] = $customer['name'];
            $response['address'] = $customer['address'];
            $response['city'] = $customer['city'];
            $response['state'] = $customer['state'];
            $response['zip'] = $customer['zip'];
        }
    }
    
} catch (PDOException $e) {
    // Handle database errors
    $response['error'] = "Database error: " . $e->getMessage();
}

// Close the connection
$pdo = null;

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>