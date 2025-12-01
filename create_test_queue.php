<?php
// Create a valid test queue file
$queue_data = [
    'customer' => [
        'to' => 'kalakaarstudios@gmail.com', // Using the email from previous context
        'to_name' => 'Test User',
        'subject' => 'Test Email from Haveli',
        'template' => 'confirmation',
        'data' => [
            'customer_name' => 'Test User',
            'reservation_date' => '2025-11-25',
            'reservation_time' => '19:00',
            'num_guests' => '2',
            'customer_email' => 'kalakaarstudios@gmail.com',
            'customer_phone' => '1234567890',
            'day_of_week' => 'Tuesday'
        ]
    ]
];

$file = __DIR__ . '/email_queue_request_test_' . time() . '.json';
file_put_contents($file, json_encode($queue_data, JSON_PRETTY_PRINT));

echo "Created test queue file: " . basename($file) . "\n";
?>
