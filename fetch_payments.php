<?php
include "conn.php";

// Fetch all payments
$stmt = $conn->prepare("SELECT * FROM payments ORDER BY id DESC");
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payments List</title>
    <style>
        body { font-family: Arial; padding: 20px; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
        }

        th {
            background: #f2f2f2;
        }

        .paid { color: green; font-weight: bold; }
        .pending { color: orange; font-weight: bold; }
        .refunded { color: red; font-weight: bold; }

        .btn {
            padding: 6px 10px;
            background: red;
            color: white;
            border: none;
            cursor: pointer;
            text-decoration: none;
            border-radius: 4px;
        }

        .btn:disabled {
            background: gray;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

<h2>💳 Payments List</h2>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Reference #</th>
            <th>Payment ID</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($payments as $row): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['reference_number']) ?></td>
                <td><?= htmlspecialchars($row['payment_id'] ?? '-') ?></td>
                <td>₱<?= number_format($row['amount'], 2) ?></td>

                <td class="<?= $row['status'] ?>">
                    <?= htmlspecialchars($row['status']) ?>
                </td>

                <td>
                    <?php if ($row['status'] !== 'refunded' && !empty($row['payment_id'])): ?>
                        
                        <form action="process_refund.php" method="POST" style="display:inline;">
                            
                            <input type="hidden" name="payment_id" value="<?= htmlspecialchars($row['payment_id'] ?? '') ?>">

                            <!-- refund full amount -->
                            <input type="hidden" name="amount" value="<?= $row['amount'] ?>">

                            <input type="hidden" name="reason" value="requested_by_customer">

                            <button type="submit" class="btn"
                                onclick="return confirm('Refund this payment?')">
                                Refund
                            </button>
                        </form>

                    <?php else: ?>
                        <button class="btn" disabled>No Action</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>