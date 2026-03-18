<?php
declare(strict_types=1);
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';
use Dompdf\Dompdf;

$pdo = getDB();
$today = date('Y-m-d');
$filter = $_GET['filter'] ?? 'all'; 

// [REMARK] EXCLUSIVE FILTERING LOGIC
$where_clause = "DATE(o.created_at) = '$today'";
if ($filter === 'cash') {
    // Only physical cash payments
    $where_clause .= " AND o.billing_method != 'room' AND o.is_billed = 1";
    $report_title = "EXCLUSIVE CASH SALES REPORT";
} elseif ($filter === 'room') {
    // Only bills added to rooms
    $where_clause .= " AND o.billing_method = 'room' AND o.status = 'delivered'";
    $report_title = "EXCLUSIVE ROOM CHARGES REPORT";
} else {
    // Combined revenue
    $where_clause .= " AND (o.is_billed = 1 OR (o.billing_method = 'room' AND o.status = 'delivered'))";
    $report_title = "COMBINED DAILY SALES REPORT";
}
//
$stmt = $pdo->prepare("
    SELECT o.*, 
           w.full_name as waiter_name, 
           c.full_name as cashier_name
    FROM orders o 
    LEFT JOIN staff w ON o.waiter_id = w.staff_id 
    LEFT JOIN staff c ON o.cashier_id = c.staff_id 
    WHERE $where_clause
    ORDER BY o.created_at DESC
");
$stmt->execute();
$sales = $stmt->fetchAll();

// [REMARK] CALCULATE TOTALS BASED ON FILTER
$cash_sales = 0; 
$room_charges = 0;

foreach($sales as $row) {
    if($row['billing_method'] === 'room') {
        $room_charges += (float)$row['total_amount'];
    } else {
        $cash_sales += (float)$row['total_amount'];
    }
}
$grand_total = $cash_sales + $room_charges;

$html = '
<style>
    body { font-family: "DejaVu Sans", sans-serif; color: #333; line-height: 1.5; }
    .header { text-align: center; border-bottom: 2px solid #008080; padding-bottom: 10px; margin-bottom: 20px; }
    .summary-box { width: 100%; margin-bottom: 30px; border-collapse: collapse; }
    .summary-box td { padding: 15px; border: 1px solid #eee; }
    .label { font-size: 10px; color: #666; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
    .value { font-size: 18px; color: #008080; font-weight: bold; }
    table.details { width: 100%; border-collapse: collapse; }
    th { background: #008080; color: white; padding: 10px; font-size: 10px; text-align: left; }
    td { padding: 10px; border-bottom: 1px solid #eee; font-size: 11px; }
    .badge { padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; }
    .badge-room { background: #ebf8ff; color: #2b6cb0; }
    .badge-cash { background: #f0fff4; color: #2f855a; }
    .footer-total { margin-top: 30px; text-align: right; border-top: 2px solid #008080; padding-top: 10px; }
</style>

<div class="header">
    <h1>HOTELIA F&B DAILY SALES REPORT</h1>
    <h2 style="font-size: 14px; color: #666;">'.$report_title.'</h2>
    <p>Report Date: '.date('F j, Y').'</p>
</div>

<table class="summary-box">
    <tr>';

// [REMARK] DYNAMICALLY SHOW ONLY THE SELECTED TOTAL BOX
if ($filter === 'all' || $filter === 'cash') {
    $html .= '<td>
                <div class="label">Total Cash Payments</div>
                <div class="value">&#8369;'.number_format($cash_sales, 2).'</div>
              </td>';
}

if ($filter === 'all' || $filter === 'room') {
    $html .= '<td>
                <div class="label">Total Room Charges</div>
                <div class="value">&#8369;'.number_format($room_charges, 2).'</div>
              </td>';
}

if ($filter === 'all') {
    $html .= '<td style="background: #f0fdfa;">
                <div class="label">Grand Total Revenue</div>
                <div class="value" style="color: #0d9488;">&#8369;'.number_format($grand_total, 2).'</div>
              </td>';
}

$html .= '</tr>
</table>

<h3 style="font-size: 14px; color: #008080; margin-bottom: 10px;">Transaction Details</h3>
<table class="details">
    <thead>
        <tr>
            <th>Time</th>
            <th>Order ID</th>
            <th>Location</th>
            <th>Waiter</th>
            <th>Processed By (Cashier)</th> 
            <th>Method</th> 
            <th>Method</th>
            <th>Amount</th>
            <th>Received</th> <th>Change</th>   </tr>
    </thead>
    <tbody>';

foreach ($sales as $row) {
    $method_badge = ($row['billing_method'] === 'room') ? '<span class="badge badge-room">ROOM</span>' : '<span class="badge badge-cash">CASH</span>';
    $location = ($row['billing_method'] === 'room') ? 'Room '.$row['room_number'] : 'Table #'.$row['table_number'];
    
    // REMOVED: The double-calculation logic that was here
    
    $html .= '<tr>
        <td>'.date('h:i A', strtotime($row['created_at'])).'</td>
        <td>'.str_pad((string)$row['order_id'], 5, '0', STR_PAD_LEFT).'</td>
        <td>'.$location.'</td>
        <td>'.htmlspecialchars($row['waiter_name'] ?? 'N/A').'</td>
        <td style="color: #b91c1c; font-weight: bold;">'.htmlspecialchars($row['cashier_name'] ?? 'System').'</td> 
        <td>'.$method_badge.'</td>
        <td>&#8369;'.number_format((float)$row['total_amount'], 2).'</td>
        <td>&#8369;'.number_format((float)($row['amount_received'] ?? 0), 2).'</td> <td>&#8369;'.number_format((float)($row['amount_change'] ?? 0), 2).'</td>   </tr>';
}
$html .= '</tbody></table>

<div class="footer-total">
    <span style="font-size: 12px; color: #666;">REPORT TOTAL:</span>
    <span style="font-size: 20px; color: #008080; font-weight: bold; margin-left: 10px;">&#8369;'.number_format($grand_total, 2).'</span>
</div>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Sales_Report_".$filter."_".$today.".pdf", ["Attachment" => false]);