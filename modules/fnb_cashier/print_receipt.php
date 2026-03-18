<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/database.php';

$order_id = $_GET['id'] ?? null;
if (!$order_id) { die("Order ID required"); }

$pdo = getDB();
$stmt = $pdo->prepare("SELECT o.*, s.full_name as waiter_name FROM orders o LEFT JOIN staff s ON o.waiter_id = s.staff_id WHERE o.order_id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

$items = $pdo->prepare("SELECT oi.*, mi.item_name FROM order_items oi JOIN menu_items mi ON oi.menu_item_id = mi.item_id WHERE oi.order_id = ?");
$items->execute([$order_id]);
$order_items = $items->fetchAll();

// Calculate VAT - Cast to float to avoid string errors
$grand_total = (float)($order['total_amount'] ?? 0);
$subtotal_net = $grand_total / 1.12; 
$vat_amount = $grand_total - $subtotal_net;

// Payment details - Cast to float
$received = (float)($order['amount_received'] ?? 0);
$change = (float)($order['amount_change'] ?? 0);

// Generate transaction ID
$transaction_id = 'POS' . date('ymd') . sprintf('%04d', $order_id);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Receipt #<?php echo $order_id; ?></title>
    <style>
        /* Thermal Printer Styles - Matches POS receipt design */
        body { 
            font-family: 'Courier New', monospace; 
            margin: 0;
            padding: 0;
            background: white;
            color: black;
        }
        
        .receipt-container {
            max-width: 300px;
            margin: 0 auto;
            background: white;
            color: black;
            font-family: 'Courier New', monospace;
            padding: 20px;
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .logo-container {
            margin-bottom: 10px;
        }

        .logo {
            width: 80px;
            height: auto;
            margin: 0 auto 5px;
            display: block;
            filter: grayscale(1); /* Makes logo black and white for thermal printer */
        }

        .receipt-header h2 {
            font-size: 18px;
            font-weight: bold;
            margin: 5px 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .receipt-header p {
            font-size: 12px;
            margin: 2px 0;
        }

        .receipt-line {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 14px;
        }

        .receipt-line.total {
            border-top: 1px dashed #000;
            padding-top: 8px;
            margin-top: 8px;
            font-weight: bold;
            font-size: 16px;
        }

        .receipt-line.label {
            font-weight: bold;
        }

        .receipt-divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 15px;
            font-size: 11px;
            border-top: 2px dashed #000;
            padding-top: 10px;
        }

        .items-table {
            width: 100%;
            margin: 10px 0;
        }

        .items-table th {
            text-align: left;
            font-size: 12px;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }

        .items-table td {
            padding: 3px 0;
            font-size: 12px;
        }

        .items-table .item-qty {
            text-align: center;
            width: 40px;
        }

        .items-table .item-name {
            text-align: left;
        }

        .items-table .item-price {
            text-align: right;
            width: 70px;
        }

        .items-table .item-total {
            text-align: right;
            width: 80px;
            font-weight: bold;
        }

        .print-btn {
            background: #008080;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-bottom: 20px;
        }
        .print-btn:hover {
            background: #006666;
        }

        /* Print styles - Exactly like POS system */
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
                padding: 0;
            }

            html, body {
                width: 80mm !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                color: black !important;
                font-size: 9pt !important;
                line-height: 1.3;
                font-family: 'Courier New', monospace !important;
            }

            body * {
                visibility: hidden !important;
            }

            .receipt-container,
            .receipt-container * {
                visibility: visible !important;
            }

            .receipt-container {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 72mm !important;
                max-width: 72mm !important;
                margin: 0 auto !important;
                padding: 8mm 4mm !important;
                background: white !important;
                color: black !important;
                font-family: 'Courier New', monospace !important;
                font-size: 9pt !important;
                line-height: 1.3 !important;
                box-shadow: none !important;
                border: none !important;
            }

            .logo {
                filter: grayscale(1) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .print-btn {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- Print Button - Matches POS style -->
    <div style="text-align: center; padding: 20px; background: #f5f5f5;">
        <button onclick="window.print()" class="print-btn">
            🖨️ PRINT RECEIPT
        </button>
    </div>

    <!-- Receipt Container - Exact match to POS receipt design -->
    <div class="receipt-container">
        <div class="receipt-header">
            <!-- Logo added here -->
            <div class="logo-container">
                <img src="../../assets/images/hot.png" alt="Hotelia" class="logo">
            </div>
            <h2>HOTELIA RESORT</h2>
            <p>Food & Beverage Services</p>
            <p>143 Beachfront Avenue</p>
            <p>Balatong, Laoag City</p>
            <p>Tel: (036) 288-1234</p>
        </div>

        <!-- Transaction Details -->
        <div class="receipt-line">
            <span>Transaction ID:</span>
            <span><?= $transaction_id ?></span>
        </div>

        <div class="receipt-line">
            <span>Order #:</span>
            <span><?= str_pad((string)$order_id, 6, '0', STR_PAD_LEFT) ?></span>
        </div>

        <div class="receipt-line">
            <span>Table:</span>
            <span>#<?= htmlspecialchars($order['table_number'] ?? 'N/A') ?></span>
        </div>

        <div class="receipt-line">
            <span>Server:</span>
            <span><?= htmlspecialchars($order['waiter_name'] ?? 'N/A') ?></span>
        </div>

        <div class="receipt-line">
            <span>Date:</span>
            <span><?= date('M j, Y', strtotime($order['created_at'])) ?></span>
        </div>

        <div class="receipt-line">
            <span>Time:</span>
            <span><?= date('h:i A', strtotime($order['created_at'])) ?></span>
        </div>

        <?php if($order['billing_method'] === 'room'): ?>
        <div class="receipt-line">
            <span>Room #:</span>
            <span><?= htmlspecialchars($order['room_number'] ?? 'N/A') ?></span>
        </div>
        <?php endif; ?>

        <div class="receipt-divider"></div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="item-qty">QTY</th>
                    <th class="item-name">ITEM</th>
                    <th class="item-price">PRICE</th>
                    <th class="item-total">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): 
                    // FIX: Cast values to float to prevent type errors
                    $price = (float)($item['price_at_order'] ?? 0);
                    $quantity = (int)($item['quantity'] ?? 1);
                    $item_total = $price * $quantity;
                ?>
                <tr>
                    <td class="item-qty"><?= $quantity ?></td>
                    <td class="item-name"><?= htmlspecialchars($item['item_name']) ?></td>
                    <td class="item-price">₱<?= number_format($price, 2) ?></td>
                    <td class="item-total">₱<?= number_format($item_total, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Special Notes Section (if any) -->
        <?php 
        $has_notes = false;
        foreach ($order_items as $item):
            if (!empty($item['notes'])):
                if (!$has_notes): $has_notes = true; ?>
                <div class="receipt-divider"></div>
                <div style="font-size: 11px; margin: 5px 0;">
                    <strong>SPECIAL INSTRUCTIONS:</strong>
                </div>
                <?php endif; ?>
                <div style="font-size: 10px; margin: 2px 0 2px 10px;">
                    • <?= (int)($item['quantity'] ?? 1) ?>x <?= htmlspecialchars($item['item_name']) ?>: <?= htmlspecialchars($item['notes']) ?>
                </div>
            <?php endif;
        endforeach;
        if ($has_notes): ?>
            <div style="margin-bottom: 5px;"></div>
        <?php endif; ?>

        <div class="receipt-divider"></div>

        <!-- Summary -->
        <div class="receipt-line">
            <span>Subtotal (Net):</span>
            <span>₱<?= number_format($subtotal_net, 2) ?></span>
        </div>

        <div class="receipt-line">
            <span>VAT (12%):</span>
            <span>₱<?= number_format($vat_amount, 2) ?></span>
        </div>

        <div class="receipt-line total">
            <span>GRAND TOTAL:</span>
            <span>₱<?= number_format($grand_total, 2) ?></span>
        </div>

        <!-- Payment Details - Exactly like POS -->
        <?php if($order['billing_method'] !== 'room'): ?>
            <div class="receipt-divider"></div>
            
            <div class="receipt-line label">
                <span>Payment Method:</span>
                <span>CASH</span>
            </div>
            
            <div class="receipt-line">
                <span>Amount Received:</span>
                <span>₱<?= number_format($received, 2) ?></span>
            </div>
            
            <div class="receipt-line">
                <span>Change:</span>
                <span>₱<?= number_format($change, 2) ?></span>
            </div>
        <?php else: ?>
            <div class="receipt-divider"></div>
            <div class="receipt-line label">
                <span>Payment Method:</span>
                <span>ROOM CHARGE</span>
            </div>
            <div class="receipt-line">
                <span>Room #:</span>
                <span><?= htmlspecialchars($order['room_number'] ?? 'N/A') ?></span>
            </div>
        <?php endif; ?>

        <div class="receipt-divider"></div>

        <!-- Cashier Info -->
        <div class="receipt-line">
            <span>Cashier:</span>
            <span><?= htmlspecialchars($order['waiter_name'] ?? 'System') ?></span>
        </div>

        <!-- Footer - Exact match to POS -->
        <div class="receipt-footer">
            <p>Thank you for dining with us!</p>
            <p>We hope to see you again soon!</p>
            <p>*** THIS IS YOUR OFFICIAL RECEIPT ***</p>
        </div>
    </div>

    <script>
        // Auto-open print dialog on load (like original)
        window.onload = () => { 
            // Optional: Uncomment to auto-print
            // window.print(); 
        };
    </script>
</body>
</html>