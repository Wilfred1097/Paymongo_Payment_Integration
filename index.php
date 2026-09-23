<?php
session_start();
include "conn.php";

// Handle Mode Switch Request
if (isset($_GET['mode'])) {
    if ($_GET['mode'] === 'test' || $_GET['mode'] === 'live') {
        $_SESSION['paymongo_mode'] = $_GET['mode'];
    }
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Handle Saving Configuration Keys
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_keys'])) {
    $live_key = trim($_POST['live_key']);
    $test_key = trim($_POST['test_key']);

    // Formatted strictly as requested
    $config_content = "<?php\n";
    $config_content .= "\$secretKey = " . var_export($live_key, true) . "; // you can enter here your Live API Key\n";
    $config_content .= "\$secretKey = " . var_export($test_key, true) . "; // you can enter here your Test API Key\n";

    file_put_contents('paymongo_keys.php', $config_content);
    
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . "?success=1");
    exit;
}

// Default to 'test' mode if not set yet
$current_mode = $_SESSION['paymongo_mode'] ?? 'test';

// Handle New Payment Submission with Key Validation
$payment_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_payment'])) {
    // Check if configuration file exists
    if (!file_exists('paymongo_keys.php')) {
        $payment_error = "API keys are not configured yet! Please click 'Configure Paymongo Key' first.";
    } else {
        $keys_file_content = file_get_contents('paymongo_keys.php');
        
        // Simple check to ensure keys aren't empty strings
        if (strpos($keys_file_content, '$secretKey = "";') !== false || empty($keys_file_content)) {
            $payment_error = "API keys are empty! Please update your configurations via 'Configure Paymongo Key'.";
        } else {
            // Keys are set! Proceed to checkout / create_payment.php
            $amount = $_POST['amount'];
            $description = $_POST['description'];
            
            // Redirect to your payment processing script with the details
            header("Location: create_payment.php?amount=" . urlencode($amount) . "&description=" . urlencode($description));
            exit;
        }
    }
}

// Fetch all payments
$stmt = $conn->prepare("SELECT * FROM payments ORDER BY id DESC");
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PayMongo Payment Dashboard</title>

<!-- Favicon -->
<link rel="icon" type="image/png" href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTHUi0NufS_UVL09ljWDGYbyQ1WabllnXRR89CnaexEdzFbn3wuP0AIMPSg&s=10">

<!-- Google Fonts & Plus Jakarta Sans -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<!-- FontAwesome for Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" type="text/css" href="style.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

<div class="container">

    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success alert-dismissible" id="autoAlert">
            <i class="fa-solid fa-circle-check"></i> PayMongo configuration keys saved successfully to <strong>paymongo_keys.php</strong>!
        </div>
    <?php endif; ?>

    <?php if ($payment_error): ?>
        <div class="alert-error alert-dismissible" id="autoAlert">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($payment_error) ?>
        </div>
    <?php endif; ?>

    <!-- TOP HEADER -->
    <div class="header-bar">
        <div class="brand">
            <div class="brand-icon">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div>
                <h1>PayMongo Gateway</h1>
                <p style="margin-top: 5px;">Secure payment processing & transaction monitoring</p>
            </div>
        </div>

        <div class="header-actions">
            <!-- Configure Keys Button -->
            <button class="config-btn" onclick="openConfigModal()">
                <i class="fa-solid fa-key"></i> Configure Paymongo Key
            </button>

            <!-- Mode Switcher -->
            <div class="mode-switcher">
                <a href="?mode=test" class="mode-btn <?= $current_mode === 'test' ? 'active-test' : '' ?>">
                    <i class="fa-solid fa-flask"></i> Test Mode
                </a>
                <a href="?mode=live" class="mode-btn <?= $current_mode === 'live' ? 'active-live' : '' ?>">
                    <i class="fa-solid fa-bolt"></i> Live Mode
                </a>
            </div>
        </div>
    </div>

    <!-- MAIN DASHBOARD CONTENT -->
    <div class="dashboard-grid">

        <!-- PAYMENT FORM -->
        <div class="card" style="height: fit-content;">
            <div class="card-header">
                <h2><i class="fa-solid fa-credit-card" style="color: var(--primary);"></i> New Payment</h2>
                <span class="badge-mode <?= $current_mode ?>"><?= strtoupper($current_mode) ?></span>
            </div>

            <form action="" method="POST">
                <div class="form-group">
                    <label>Amount (PHP)</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-peso-sign"></i>
                        <input type="number" name="amount" min="1" placeholder="0.00" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-file-lines"></i>
                        <input type="text" name="description" placeholder="e.g., Monthly Subscription" required>
                    </div>
                </div>

                <button type="submit" name="make_payment" class="btn">
                    <i class="fa-solid fa-shield-halved"></i> Proceed to Pay
                </button>
            </form>
        </div>

        <!-- PAYMENTS TABLE -->
        <div class="card">
            <div class="card-header" style="flex-wrap: wrap; gap: 15px;">
                <div>
                    <h2><i class="fa-solid fa-list-check" style="color: var(--primary);"></i> Recent Transactions</h2>
                    <span style="font-size: 0.85rem; color: var(--text-muted);"><?= count($payments) ?> total records</span>
                </div>

                <!-- SEARCH INPUT -->
                <div style="position: relative; min-width: 240px;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
                    <input type="text" id="tableSearch" placeholder="Search reference, status..." onkeyup="filterTable()" style="width: 100%; padding: 8px 12px 8px 35px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; outline: none; background: #fff;">
                </div>
            </div>

            <!-- SCROLLABLE TABLE CONTAINER -->
            <div class="table-responsive" style="max-height: 420px; overflow-y: auto; border-top: 1px solid #f1f5f9;">
                <table style="width: 100%; border-collapse: collapse;">
                    <!-- STICKY TABLE HEADER -->
                    <thead style="position: sticky; top: 0; background: #ffffff; z-index: 2; box-shadow: inset 0 -1px 0 #e2e8f0;">
                        <tr>
                            <th>Reference #</th>
                            <th>Payment ID</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Mode</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody id="transactionTableBody">
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <i class="fa-solid fa-folder-open" style="font-size: 2rem; margin-bottom: 10px; display:block; color: #cbd5e1;"></i>
                                    No payments recorded yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $row): 
                                $tx_mode = $row['mode'] ?? 'test';
                            ?>
                                <tr class="transaction-row">
                                    <td><strong><?= htmlspecialchars($row['reference_number']) ?></strong></td>
                                    <td style="color: var(--text-muted); font-size: 0.85rem;"><?= htmlspecialchars($row['payment_id'] ?? $row['payment_intent_id'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['description'] ?? '-') ?></td>
                                    <td><strong>₱<?= number_format($row['amount'], 2) ?></strong></td>
                                    
                                    <!-- TRANSACTION MODE BADGE -->
                                    <td>
                                        <span class="badge-mode <?= $tx_mode ?>" style="font-size: 0.65rem; padding: 3px 8px;">
                                            <?= strtoupper($tx_mode) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="status-badge <?= strtolower($row['status']) ?>">
                                            <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor;"></span>
                                            <?= htmlspecialchars(ucfirst($row['status'])) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if (strtolower($row['status']) === 'pending' && !empty($row['checkout_url'])): ?>
                                            <!-- PAY NOW BUTTON -->
                                            <a href="<?= htmlspecialchars($row['checkout_url']) ?>" target="_blank" class="btn btn-sm" style="background: #2563eb; text-decoration: none;">
                                                <i class="fa-solid fa-credit-card"></i> Pay Now
                                            </a>
                                        <?php elseif ($row['status'] !== 'refunded' && !empty($row['payment_id'])): ?>
                                            <!-- REFUND BUTTON WITH AJAX SWEETALERT2 -->
                                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmRefund('<?= $row['payment_id'] ?>', '<?= $row['amount'] ?>')">
                                                <i class="fa-solid fa-rotate-left"></i> Refund
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm" disabled style="background: #f1f5f9; color: #94a3b8;">N/A</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<!-- CONFIGURATION MODAL -->
<div class="modal-overlay" id="configModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-key" style="color: var(--primary);"></i> Configure API Keys</h3>
            <button class="modal-close" onclick="closeConfigModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <div class="form-group">
                    <label>Live API Key</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-bolt"></i>
                        <input type="text" name="live_key" placeholder="sk_live_..." required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Test API Key</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-flask"></i>
                        <input type="text" name="test_key" placeholder="sk_test_..." required>
                    </div>
                </div>

                <button type="submit" name="save_keys" class="btn" style="margin-top: 10px;">
                    <i class="fa-solid fa-floppy-disk"></i> Save Configurations
                </button>
            </form>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer class="footer">
    <p>Created with <i class="fa-solid fa-heart" style="color: #ef4444;"></i> by <a href="https://github.com/Wilfred1097" target="_blank"><i class="fa-brands fa-github"></i> Wilfred</a></p>
</footer>

<script>
function openConfigModal() {
    document.getElementById('configModal').style.display = 'flex';
}

function filterTable() {
    let input = document.getElementById('tableSearch');
    let filter = input.value.toLowerCase();
    let tbody = document.getElementById('transactionTableBody');
    let rows = tbody.getElementsByClassName('transaction-row');

    for (let i = 0; i < rows.length; i++) {
        let textValue = rows[i].textContent || rows[i].innerText;
        if (textValue.toLowerCase().indexOf(filter) > -1) {
            rows[i].style.display = "";
        } else {
            rows[i].style.display = "none";
        }
    }
}

function closeConfigModal() {
    document.getElementById('configModal').style.display = 'none';
}

function confirmRefund(paymentId, amount) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You are about to refund this payment. This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, refund it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading spinner
            Swal.fire({
                title: 'Processing Refund...',
                text: 'Please wait while we communicate with PayMongo.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Prepare POST payload
            let formData = new URLSearchParams();
            formData.append('payment_id', paymentId);
            formData.append('amount', amount);
            formData.append('reason', 'requested_by_customer');

            // Send AJAX request
            fetch('process_refund.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#008236'
                    }).then(() => {
                        location.reload(); // Refresh table status
                    });
                } else {
                    Swal.fire({
                        title: 'Refund Failed',
                        text: data.message, // Displays the exact PayMongo error detail (e.g. QRPH error)
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error!',
                    text: 'Something went wrong with the network request.',
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            });
        }
    });
}

// Close modal when clicking outside of content
window.onclick = function(event) {
    let modal = document.getElementById('configModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

// Auto-dismiss alert boxes after 4 seconds and clear URL query parameter
setTimeout(function() {
    let alertBox = document.getElementById('autoAlert');
    if (alertBox) {
        alertBox.style.opacity = '0';
        alertBox.style.transform = 'translateY(-10px)';
        setTimeout(function() {
            alertBox.style.display = 'none';
        }, 500);
    }
    
    // Clean up the URL query parameters so refreshing won't trigger the success alert again
    if (window.history.replaceState) {
        let cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({path: cleanUrl}, '', cleanUrl);
    }
}, 4000);
</script>

</body>
</html>