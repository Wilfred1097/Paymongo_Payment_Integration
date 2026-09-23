<?php
session_start();
include "conn.php";

$current_mode = $_SESSION['paymongo_mode'] ?? 'test';

if (file_exists('paymongo_keys.php')) {
    include 'paymongo_keys.php';
}

$amount_in_cents = intval(floatval($_REQUEST['amount']) * 100);
$description = $_REQUEST['description'] ?? 'Payment';

$reference = "PAY-" . time();

$data = [
    "data" => [
        "attributes" => [
            "line_items" => [
                [
                    "name" => $description,
                    "quantity" => 1,
                    "amount" => $amount_in_cents,
                    "currency" => "PHP"
                ]
            ],
            "payment_method_types" => ["gcash", "card", "qrph"],
            "reference_number" => $reference,
            "success_url" => "http://localhost:81/paymongo/index.php",
            // "success_url" => "http://localhost:81/paymongo/success.php?ref=$reference", You can use Success Payment page here
            "cancel_url"  => "http://localhost:81/paymongo/index.php"
        ]
    ]
];

$ch = curl_init("https://api.paymongo.com/v1/checkout_sessions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Basic " . base64_encode($secretKey . ":")
]);

$result = curl_exec($ch);
curl_close($ch);

$response = json_decode($result, true);

if (!isset($response['data']['attributes']['checkout_url'])) {
    echo "<pre>";
    print_r($response);
    exit;
}

$checkoutUrl = $response['data']['attributes']['checkout_url'];
$paymentIntentId = $response['data']['attributes']['payment_intent']['id'] ?? null;

// SAVE TO DATABASE WITH CHECKOUT URL AND MODE
$stmt = $conn->prepare("
    INSERT INTO payments 
    (reference_number, payment_intent_id, amount, status, description, checkout_url, mode)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $reference,
    $paymentIntentId,
    $amount_in_cents / 100,
    "pending",
    $description,
    $checkoutUrl,
    $current_mode
]);

header("Location: " . $checkoutUrl);
exit;
?>