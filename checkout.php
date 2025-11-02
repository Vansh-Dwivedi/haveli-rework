<?php
session_start();
require 'stripe-php/init.php'; // Path to your Stripe library

// Set your Stripe secret key
// \Stripe\Stripe::setApiKey('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'); // Replace with your actual secret key
\Stripe\Stripe::setApiKey('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');

header('Content-Type: application/json');

$YOUR_DOMAIN = 'https://haveli.co.uk'; // Replace with your domain

// Initialize an array to hold the line items
$line_items = [];
$total_amount = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $line_items[] = [
            'price_data' => [
                'currency' => 'gbp',
                'product_data' => [
                    'name' => $item['name'],
                ],
                'unit_amount' => $item['price'] * 100, // Price in cents/pence
            ],
            'quantity' => $item['quantity'],
        ];
        $total_amount += $item['price'] * $item['quantity'];
    }
} else {
    // Handle empty cart - redirect or show an error
    header("Location: index.php");
    exit();
}

try {
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        'success_url' => $YOUR_DOMAIN . '/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $YOUR_DOMAIN . '/index.php',
    ]);

    header("HTTP/1.1 303 See Other");
    header("Location: " . $checkout_session->url);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
