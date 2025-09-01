<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

require __DIR__ . '/vendor/autoload.php';
use Razorpay\Api\Api;

$key_id = "rzp_live_RBTmpFVhxFDaRh"; // Your Razorpay Key ID (public)
$key_secret = "qs9D17KZmKPYq7QcJlb9Tne3"; // Keep this secret

$input = json_decode(file_get_contents("php://input"), true);
$amount = 499;

if ($amount <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid amount"]);
    exit;
}

try {
    $api = new Api($key_id, $key_secret);

    $order = $api->order->create([
        'amount' => $amount * 100, // in paise
        'currency' => 'INR',
        'payment_capture' => 1
    ]);

    echo json_encode([
        "success" => true,
        "order_id" => $order["id"],
        "key_id" => $key_id // only send public key to frontend
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
