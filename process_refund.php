<?php
header('Content-Type: application/json');
include "conn.php";

if (file_exists('paymongo_keys.php')) {
    include 'paymongo_keys.php';
}

// INPUTS
$paymentId = $_POST['payment_id'] ?? '';
$amountPHP = intval($_POST['amount'] ?? 0);
$reason = $_POST['reason'] ?? "requested_by_customer";

if (!$paymentId || !$amountPHP) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// Convert to centavos
$amount = $amountPHP * 100;

// =======================
// 1. CREATE REFUND (PayMongo)
// =======================
$url = "https://api.paymongo.com/v1/refunds";

$data = [
    "data" => [
        "attributes" => [
            "payment_id" => $paymentId,
            "amount" => $amount,
            "reason" => $reason,
            "notes" => "Refund via PHP system"
        ]
    ]
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: application/json",
    "Content-Type: application/json",
    "Authorization: Basic " . base64_encode($secretKey . ":")
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

// =======================
// 2. HANDLE ERROR
// =======================
if (isset($result['errors'])) {
    $errorDetail = $result['errors'][0]['detail'] ?? 'An unknown error occurred during refund processing.';
    echo json_encode(['success' => false, 'message' => $errorDetail]);
    exit;
}

// =======================
// 3. UPDATE MYSQL STATUS
// =======================
$stmt = $conn->prepare("
    UPDATE payments 
    SET status = 'refunded'
    WHERE payment_id = ?
");

$stmt->execute([$paymentId]);

// =======================
// 4. SUCCESS JSON OUTPUT
// =======================
echo json_encode([
    'success' => true,
    'message' => 'Refund successful! Amount: ₱' . number_format($amountPHP, 2)
]);
exit;
?>