<?php
include "conn.php";

$reference = $_GET['ref'] ?? '';

if (!$reference) {
    die("No payment reference.");
}

// 1. Get payment using reference_number
$stmt = $conn->prepare("SELECT * FROM payments WHERE reference_number = ?");
$stmt->execute([$reference]);
$payment = $stmt->fetch();

if (!$payment) {
    die("Payment not found.");
}

// 2. Use REAL payment_intent_id from DB
$paymentIntentId = $payment['payment_intent_id'];

if (file_exists('paymongo_keys.php')) {
    include 'paymongo_keys.php';
}

$url = "https://api.paymongo.com/v1/payment_intents/" . $paymentIntentId;

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: application/json",
    "Authorization: Basic " . base64_encode($secretKey . ":")
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

// 3. Extract payment_id
$paymentId = $result['data']['attributes']['payments'][0]['id'] ?? null;

// 4. Update DB properly
$update = $conn->prepare("
    UPDATE payments 
    SET status = ?, payment_id = ?
    WHERE reference_number = ?
");

$update->execute([
    "paid",
    $paymentId,
    $reference
]);

// 5. Output
echo "<h2>✅ Payment Successful!</h2>";
echo "Reference: " . $reference;
echo "<br>Payment ID: " . $paymentId;
echo "<br>Amount: ₱" . number_format($payment['amount'], 2);

echo "<br><br>";
echo "<a href='index.php' style='
    display:inline-block;
    padding:10px 15px;
    background:#009039;
    color:#fff;
    text-decoration:none;
    border-radius:6px;
'>⬅ Go Back to Home</a>";
?>