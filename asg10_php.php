<?php
// === DB CONNECTION SETUP ===
$host = 'localhost';
$dbname = 'cosc2328_asg10';
$username = 'aserna11';
$password = 'Cosc2328-Sp25-Mc';
$message = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection_status = true;
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$response = [
    'success' => false,
    'message' => '',
    'order_id' => null,
    'customer_id' => null,
    'items' => [],
    'total' => 0
];

// === HELPER FUNCTIONS ===
function getAddonIdByName($pdo, $addon_name) {
    $stmt = $pdo->prepare("SELECT addon_id FROM addons WHERE addon_name = :addon_name");
    $stmt->bindParam(':addon_name', $addon_name);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['addon_id'] : false;
}

function addOrderItem($pdo, $order_id, $item_id, $quantity, $item_price) {
    $subtotal = $quantity * $item_price;
    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, item_id, quantity, item_price, subtotal) VALUES (:order_id, :item_id, :quantity, :item_price, :subtotal)");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->bindParam(':item_id', $item_id);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':item_price', $item_price);
    $stmt->bindParam(':subtotal', $subtotal);
    $stmt->execute();
    return $pdo->lastInsertId();
}

function addItemOption($pdo, $order_item_id, $option_name, $option_value) {
    $stmt = $pdo->prepare("INSERT INTO item_options (order_item_id, option_name, option_value) VALUES (:order_item_id, :option_name, :option_value)");
    $stmt->bindParam(':order_item_id', $order_item_id);
    $stmt->bindParam(':option_name', $option_name);
    $stmt->bindParam(':option_value', $option_value);
    return $stmt->execute();
}

function addItemAddon($pdo, $order_item_id, $addon_id) {
    $stmt = $pdo->prepare("INSERT INTO item_addons (order_item_id, addon_id) VALUES (:order_item_id, :addon_id)");
    $stmt->bindParam(':order_item_id', $order_item_id);
    $stmt->bindParam(':addon_id', $addon_id);
    return $stmt->execute();
}

// === MAIN FORM HANDLING ===
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $pdo->beginTransaction();

        $phone = $_POST['phone'] ?? '';
        $name = $_POST['name'] ?? '';
        $address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? '';
        $state = $_POST['state'] ?? '';
        $zip = $_POST['zip'] ?? '';

        if (empty($phone) || empty($name) || empty($address) || empty($city) || empty($state) || empty($zip)) {
            throw new Exception("All customer information fields are required");
        }

        $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE phone = :phone");
        $stmt->bindParam(':phone', $phone);
        $stmt->execute();
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
            $customer_id = $customer['customer_id'];
            $stmt = $pdo->prepare("UPDATE customers SET name = :name, address = :address, city = :city, state = :state, zip = :zip WHERE customer_id = :customer_id");
            $stmt->execute(compact('name', 'address', 'city', 'state', 'zip', 'customer_id'));
        } else {
            $stmt = $pdo->prepare("INSERT INTO customers (phone, name, address, city, state, zip) VALUES (:phone, :name, :address, :city, :state, :zip)");
            $stmt->execute(compact('phone', 'name', 'address', 'city', 'state', 'zip'));
            $customer_id = $pdo->lastInsertId();
        }

        $total_amount = 0;
        $cart = $_POST['cart'] ?? [];

        if (!is_array($cart) || count($cart) === 0) {
            throw new Exception("No cart items submitted");
        }

        $stmt = $pdo->prepare("INSERT INTO orders (customer_id, total_amount) VALUES (:customer_id, :total_amount)");
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':total_amount', $total_amount);
        $stmt->execute();
        $order_id = $pdo->lastInsertId();

        foreach ($cart as $item) {
            $item_name = $item['name'] ?? '';
            $item_type = $item['type'] ?? null;
            $quantity = intval($item['quantity'] ?? 0);
            $price = floatval($item['price'] ?? 0);
            $subtotal = floatval($item['subtotal'] ?? 0);

            if ($quantity <= 0 || empty($item_name)) continue;

            $stmt = $pdo->prepare("SELECT item_id, item_type FROM menu_items WHERE item_name = :item_name");
            $stmt->bindParam(':item_name', $item_name);
            $stmt->execute();
            $menu_item = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$menu_item) continue;

            $item_id = $menu_item['item_id'];
            $order_item_id = addOrderItem($pdo, $order_id, $item_id, $quantity, $price);

            if ($item_type && $order_item_id) {
                $option_name = match (strtolower($item_name)) {
                    'taco' => 'meat',
                    'burrito' => 'flavor',
                    'drink' => 'type',
                    default => 'option',
                };
                addItemOption($pdo, $order_item_id, $option_name, $item_type);
            }

            if (strtolower($item_name) === 'nachos' && isset($item['addons']) && is_array($item['addons'])) {
                foreach ($item['addons'] as $addonName) {
                    $addon_id = getAddonIdByName($pdo, ucfirst(strtolower($addonName)));
                    if ($addon_id) {
                        addItemAddon($pdo, $order_item_id, $addon_id);
                    }
                }
            }

            $total_amount += $subtotal;
        }

        $stmt = $pdo->prepare("UPDATE orders SET total_amount = :total_amount WHERE order_id = :order_id");
        $stmt->bindParam(':total_amount', $total_amount);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();

        $pdo->commit();

        $response['success'] = true;
        $response['message'] = "Order submitted successfully.";
        $response['order_id'] = $order_id;
        $response['customer_id'] = $customer_id;
        $response['items'] = $cart;
        $response['total'] = $total_amount;
    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
        $response['message'] = $e->getMessage();
    }
    header('Content-Type: application/json');
    echo json_encode($response);
}
$pdo = null;
?>