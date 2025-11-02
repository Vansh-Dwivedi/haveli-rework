<?php
session_start();
// cart_manager.php
// Helper function to create a slug-like ID from item name (if not already defined)
if (!function_exists('slugify')) {
    function slugify($text)
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        if (empty($text)) {
            return 'n-a-' . substr(md5(uniqid(rand(), true)), 0, 8);
        }
        return $text;
    }
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'add':
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? 'Unknown Item';
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

        if (empty($id) || $price <= 0 || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid item data.']);
            exit;
        }

        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$id] = [
                'name' => $name,
                'price' => $price,
                'quantity' => $quantity
            ];
        }
        echo json_encode(['success' => true, 'message' => htmlspecialchars($name) . ' added to cart.', 'cart' => $_SESSION['cart'], 'totalItems' => getCartItemCountServer()]);
        break;

    case 'remove':
        $id = $_POST['id'] ?? '';
        if (isset($_SESSION['cart'][$id])) {
            unset($_SESSION['cart'][$id]);
            echo json_encode(['success' => true, 'message' => 'Item removed.', 'cart' => $_SESSION['cart'], 'totalItems' => getCartItemCountServer()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found in cart.']);
        }
        break;

    case 'update_quantity':
        $id = $_POST['id'] ?? '';
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

        if (isset($_SESSION['cart'][$id])) {
            if ($quantity > 0) {
                $_SESSION['cart'][$id]['quantity'] = $quantity;
                echo json_encode(['success' => true, 'message' => 'Quantity updated.', 'cart' => $_SESSION['cart'], 'totalItems' => getCartItemCountServer()]);
            } elseif ($quantity <= 0) { // If quantity is 0 or less, remove item
                unset($_SESSION['cart'][$id]);
                echo json_encode(['success' => true, 'message' => 'Item removed due to zero quantity.', 'cart' => $_SESSION['cart'], 'totalItems' => getCartItemCountServer()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found in cart.']);
        }
        break;

    case 'get_cart':
        echo json_encode(['success' => true, 'cart' => $_SESSION['cart'], 'totalItems' => getCartItemCountServer()]);
        break;

    case 'clear_cart':
        $_SESSION['cart'] = [];
        echo json_encode(['success' => true, 'message' => 'Cart cleared.', 'cart' => $_SESSION['cart'], 'totalItems' => 0]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

// Function to get total items in cart (server-side for cart_manager.php)
function getCartItemCountServer()
{
    $count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
    }
    return $count;
}
