<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'stripe-php/init.php';

\Stripe\Stripe::setApiKey('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');

if (!isset($_GET['session_id'])) {
    die('Missing session ID');
}

try {
    $session = \Stripe\Checkout\Session::retrieve($_GET['session_id']);

    $customer_email = $session->customer_details->email ?? 'Customer';
    $customer_name = $session->customer_details->name ?? 'Guest';

    // Prepare order summary HTML
    $order_summary = "
<table style='
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background-color: #1e1e2f;
    color: #ffffff;
    margin: 20px 0;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
    font-family: Arial, sans-serif;
'>
    <thead>
        <tr style='background-color: #2a2a3c;'>
            <th style='padding: 12px;'>Item</th>
            <th style='padding: 12px;'>Qty</th>
            <th style='padding: 12px;'>Price</th>
            <th style='padding: 12px;'>Total</th>
        </tr>
    </thead>
    <tbody>
";

    $total = 0;
    foreach ($_SESSION['cart'] ?? [] as $item) {
        $item_total = $item['price'] * $item['quantity'];
        $order_summary .= "
        <tr style='text-align: center;'>
            <td style='padding: 12px;'>{$item['name']}</td>
            <td style='padding: 12px;'>{$item['quantity']}</td>
            <td style='padding: 12px;'>£{$item['price']}</td>
            <td style='padding: 12px;'>£" . number_format($item_total, 2) . "</td>
        </tr>
    ";
        $total += $item_total;
    }

    $order_summary .= "
        <tr style='background-color: #2a2a3c; font-weight: bold;'>
            <td colspan='3' style='padding: 12px; text-align: right;'>Total:</td>
            <td style='padding: 12px;'>£" . number_format($total, 2) . "</td>
        </tr>
    </tbody>
</table>
";


    // Save order to admin dashboard (MySQL DB)
    try {
        require_once 'db_config.php';
        $db = getDBConnection();

        // Insert order
        $stmt = $db->prepare("INSERT INTO orders (customer_name, email, delivery_address, order_items, total_amount, payment_status, order_status, notified) 
                             VALUES (?, ?, ?, ?, ?, 'Pending', 'Pending', 0)");
        $stmt->execute([
            $customer_name,
            $customer_email,
            'Website Order', // placeholder for delivery address
            $order_summary,
            $total
        ]);
    } catch (PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
    }

    // Set modal data
    $_SESSION['order_details'] = $order_summary;
    $_SESSION['customer_name'] = $customer_name;

    // Clear cart
    $_SESSION['cart'] = [];

    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sloughhaveli@gmail.com';
        $mail->Password = 'qxzemmditlemgqph';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Sender & Recipient
        $mail->setFrom('sloughhaveli@gmail.com', 'Haveli Restaurant');
        $mail->addAddress($customer_email, $customer_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Order Confirmation from Haveli';
        $mail->Body = "
  <div style='
    background-color: #f7f7f7;
    padding: 40px 20px;
    font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif;
    color: #333;
  '>
    <div style='
      max-width: 600px;
      margin: 0 auto;
      background-color: #ffffff;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      overflow: hidden;
    '>
      <div style='background-color: #1e1e2f; color: #fff; padding: 30px 20px; text-align: center;'>
        <h2 style='margin: 0;'>Thank you for your order, {$customer_name}!</h2>
        <p style='margin: 5px 0 0; font-size: 16px;'>We truly appreciate your business.</p>
      </div>

      <div style='padding: 20px;'>
        <p style='font-size: 16px; margin-bottom: 10px;'>Here's a summary of your order:</p>
        $order_summary

        <p style='font-size: 15px; margin-top: 20px;'>
          Our team has received your order and it’s being processed. You’ll receive another update once it's on its way. 🙌
        </p>
      </div>

      <div style='background-color: #fafafa; padding: 20px; border-top: 1px solid #eee;'>
        <p style='font-size: 14px; margin: 0; line-height: 1.6;'>
          <strong>📍 Haveli Lounge</strong><br>
          The Urban Building, 3-9 Albert Street, Slough, SL1 2BE<br>
          <strong>📞 Phone:</strong> <a href='tel:+441753297560' style='color: #1e90ff; text-decoration: none;'>+44 1753 297560</a><br>
          <strong>🌐 Website:</strong> <a href='https://www.haveli.co.uk' style='color: #1e90ff; text-decoration: none;'>havelibanqueting.co.uk</a>
        </p>
      </div>
    </div>

    <p style='font-size: 12px; color: #888; text-align: center; margin-top: 30px;'>
      If you have any questions, just reply to this email — we're always happy to help!
    </p>
  </div>
";
        $mail->AltBody = "Thank you for your order, {$customer_name}. Total: £" . number_format($total, 2);

        $mail->send();
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
    }

    // Redirect to home
    header("Location: index.php?payment=success");
    exit();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
