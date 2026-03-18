<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../../includes/classes/RoomPriceCalculator.php';

if (session_status() === PHP_SESSION_NONE) {
    configureSecureSessions();
    session_start();
}

require_once __DIR__ . '/../../../../includes/auth.php';

class PaymentHandler
{
    private $pdo;
    private $auth;

    public function __construct()
    {
        $this->pdo = getDB();
        $this->pdo->exec("SET time_zone = '+08:00';");
        $this->auth = new Auth();
    }

    public function initiateBookingTransaction($method_id, $amount_paid, $guest_id, array $selection_ids, $account_ref = null)
    {
        if ((float)$amount_paid <= 0) {
            throw new Exception("Payment amount must be greater than zero. Received: " . $amount_paid);
        }
        try {

            $recover = $this->pdo->prepare("
                SELECT b.booking_id, p.payment_id, p.payment_reference 
                FROM bookings b
                LEFT JOIN payments p ON b.booking_id = p.booking_id
                WHERE b.guest_id = ? 
                AND b.status IN ('pending_submission', 'pending_verification')
                AND p.user_reference = 'PENDING_SUBMISSION'

                AND b.created_at > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
                ORDER BY b.created_at DESC
                LIMIT 1
            ");
            $recover->execute([$guest_id]);
            $existing = $recover->fetch(PDO::FETCH_ASSOC);

            if ($existing && $existing['payment_reference']) {
                return [
                    'success' => true,
                    'payment_id' => $existing['payment_id'],
                    'reference_no' => $existing['payment_reference'],
                    'recovered' => true
                ];
            }

            // --- NORMAL PROCESSING ---
            if (empty($selection_ids)) {
                throw new Exception("No rooms selected or session expired.");
            }

            $this->pdo->beginTransaction();

            // 1. Fetch Room Data
            $placeholders = implode(',', array_fill(0, count($selection_ids), '?'));
            $cart_sql = "SELECT rsi.*, rs.check_in_date, rs.check_out_date, rt.base_price, rt.extra_person_rate,
                    (SELECT capacity FROM rooms WHERE room_type_id = rt.room_type_id LIMIT 1) as base_capacity,
                    (SELECT max_extra FROM rooms WHERE room_type_id = rt.room_type_id LIMIT 1) as max_extra
                    FROM room_selection_instances rsi
                    JOIN room_selections rs ON rsi.selection_id = rs.selection_id
                    JOIN room_types rt ON rsi.room_type_id = rt.room_type_id
                    WHERE rs.selection_id IN ($placeholders)";

            $stmt_cart = $this->pdo->prepare($cart_sql);
            $stmt_cart->execute($selection_ids);
            $cart_items = $stmt_cart->fetchAll(PDO::FETCH_ASSOC);



            if (empty($cart_items)) {
                throw new Exception("Your booking session has expired or was already processed. Please return to the booking page.");
            }

            $check_in = $cart_items[0]['check_in_date'];
            $check_out = $cart_items[0]['check_out_date'];
            $nights = (int) (new DateTime($check_in))->diff(new DateTime($check_out))->days;
            $payment_reference = $this->generatePaymentReference((int)$method_id);

            // 2. Create Booking
            $this->pdo->prepare("INSERT INTO bookings (guest_id, check_in_date, check_out_date, nights, status, created_at) VALUES (?, ?, ?, ?, 'pending_submission', NOW())")
                ->execute([$guest_id, $check_in, $check_out, $nights]);
            $booking_id = (int)$this->pdo->lastInsertId();

            // 3. Create Items
            $subtotal = 0;
            foreach ($cart_items as $item) {
                $calc = RoomPriceCalculator::calculate($this->pdo, (int)$item['room_type_id'], $item['check_in_date'], $item['check_out_date'], (float)$item['base_price'], (float)$item['extra_person_rate'], (int)($item['base_capacity'] ?? 2), (int)($item['max_extra'] ?? 0), (int)$item['adults'], (int)$item['children']);
                $subtotal += $calc['room_total'];
                $avg_rate = ($calc['nights'] > 0) ? ($calc['base_total'] / $calc['nights']) : 0;

                $this->pdo->prepare("INSERT INTO booking_items (booking_id, room_type_id, adults, children, room_rate_applied, extra_person_rate_applied, extra_persons, item_total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending_submission')")
                    ->execute([$booking_id, $item['room_type_id'], $item['adults'], $item['children'], $avg_rate, $item['extra_person_rate'], $calc['chargeable_extra'], $calc['room_total']]);
            }

            // 4. Update Totals
            $tax = $subtotal * 0.12;
            $this->pdo->prepare("UPDATE bookings SET room_subtotal = ?, tax_amount = ?, grand_total = ? WHERE booking_id = ?")
                ->execute([$subtotal, $tax, ($subtotal + $tax), $booking_id]);

            // 5. Create Payment
            $this->pdo->prepare("INSERT INTO payments (booking_id, guest_id, payment_method_id, paid_amount, payment_reference, user_reference, status, created_at) VALUES (?, ?, ?, ?, ?, 'PENDING_SUBMISSION', 'pending_verification', NOW())")
                ->execute([$booking_id, $guest_id, $method_id, $amount_paid, $payment_reference]);
            $payment_id = (int)$this->pdo->lastInsertId();

            // 6. Gateway Details (Dynamic Type)
            if ($account_ref) {
                $method_map = [1 => 'credit_card', 2 => 'debit_card', 3 => 'gcash', 4 => 'paymaya'];
                $method_type = $method_map[$method_id] ?? 'other';

                $this->pdo->prepare("INSERT INTO payment_method_details (payment_id, method_type, account_reference, gateway_response) VALUES (?, ?, ?, '{}')")
                    ->execute([$payment_id, $method_type, $account_ref]);
            }

            // 7. Clear Selections (Locking the rooms)
            $this->pdo->prepare("DELETE FROM room_selections WHERE selection_id IN ($placeholders)")->execute($selection_ids);

            $this->pdo->commit();
            return ['success' => true, 'payment_id' => $payment_id, 'reference_no' => $payment_reference];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    private function generatePaymentReference($method_id)
    {
        $prefixes = [1 => 'CC', 2 => 'DC', 3 => 'GC', 4 => 'PM'];
        $prefix = $prefixes[$method_id] ?? 'PY';
        return $prefix . '-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }

    public function validatePaymentAccess()
    {
        if (!$this->auth->isLoggedIn()) {
            error_log("[PaymentHandler] Auth Failed");
            header('Location: ../../../../login.php');
            exit;
        }
        return $_SESSION['payment_context'] ?? null;
    }

    public function linkBookingToPayment($payment_id, $booking_id, $user_input_reference)
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Update Payment record (Convert PENDING_SUBMISSION to the real user reference)
            $sql = "UPDATE payments SET user_reference = ?, status = 'pending_verification', updated_at = NOW() WHERE payment_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([trim($user_input_reference), $payment_id]);

            // 2. Update Booking record via JOIN (This fixes the "0" ID issue)
            // We find the booking that belongs to this payment_id
            $sqlBooking = "UPDATE bookings b
                       INNER JOIN payments p ON b.booking_id = p.booking_id
                       SET b.status = 'pending_verification', 
                           b.updated_at = NOW() 
                       WHERE p.payment_id = ?";

            $stmtBooking = $this->pdo->prepare($sqlBooking);
            $stmtBooking->execute([$payment_id]);

            $this->pdo->commit();

            // Return true if the payment was actually updated
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("[PaymentHandler] linkBookingToPayment Error: " . $e->getMessage());
            return false;
        }
    }

    public function checkReferenceExists($reference, $guest_id)
    {
        $stmt = $this->pdo->prepare("
        SELECT payment_id, paid_amount 
        FROM payments 
        WHERE payment_reference = ? AND guest_id = ? AND user_reference = 'PENDING_SUBMISSION'
    ");
        $stmt->execute([$reference, $guest_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function checkPendingBooking($guest_id)
    {
        $stmt = $this->pdo->prepare("
        SELECT b.booking_id, b.created_at, p.payment_id, p.payment_reference 
        FROM bookings b
        JOIN payments p ON b.booking_id = p.booking_id
        WHERE b.guest_id = ? 
        AND b.status = 'pending_submission'
        AND p.user_reference = 'PENDING_SUBMISSION'
        LIMIT 1
    ");
        $stmt->execute([$guest_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function cancelPendingBooking($booking_id)
    {
        try {
            $this->pdo->beginTransaction();
            // 1. Update booking status to cancelled
            $this->pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?")->execute([$booking_id]);
            // 2. Note: You might want to move room_selections logic here if you need to restore them
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}
