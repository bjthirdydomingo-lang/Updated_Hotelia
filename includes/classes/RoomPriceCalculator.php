<?php
// includes/classes/RoomPriceCalculator.php

declare(strict_types=1);

class RoomPriceCalculator
{
    public static function calculate(
        PDO $pdo,
        int $roomTypeId,
        string $checkInDate,
        string $checkOutDate,
        float $basePrice,
        float $extraPersonRate,
        int $baseCapacity,
        int $maxExtra,
        int $adults,
        int $children
    ): array {

        try {
            $start = new DateTime($checkInDate);
            $end = new DateTime($checkOutDate);
            $start->setTime(0, 0, 0);
            $end->setTime(0, 0, 0);

            if ($start >= $end) return self::zeroResponse();

            $interval = $start->diff($end);
            $nights = (int)$interval->days;
        } catch (Exception $e) {
            return self::zeroResponse();
        }

        if ($nights < 1) return self::zeroResponse();

        // Fetch Calendar Rates
        $stmt = $pdo->prepare("
            SELECT date, price 
            FROM room_rate_calendar 
            WHERE room_type_id = ? 
            AND date >= ? 
            AND date < ?
        ");
        $stmt->execute([$roomTypeId, $checkInDate, $checkOutDate]);
        $calendarRates = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $baseTotal = 0.0;
        $currentDate = clone $start;

        for ($i = 0; $i < $nights; $i++) {
            $dateStr = $currentDate->format('Y-m-d');
            $dailyRate = isset($calendarRates[$dateStr]) ? (float)$calendarRates[$dateStr] : $basePrice;
            $baseTotal += $dailyRate;
            $currentDate->modify('+1 day');
        }

        $totalGuests = $adults + $children;
        $extraGuests = max(0, $totalGuests - $baseCapacity);
        $chargeableExtra = min($extraGuests, $maxExtra);

        $extraPersonTotal = $chargeableExtra * $extraPersonRate * $nights;
        $grandTotal = $baseTotal + $extraPersonTotal;

        return [
            'base_total' => $baseTotal,
            'extra_person_total' => $extraPersonTotal,
            'room_total' => $grandTotal,
            'nights' => $nights,
            'chargeable_extra' => $chargeableExtra
        ];
    }

    private static function zeroResponse(): array
    {
        return ['base_total' => 0, 'extra_person_total' => 0, 'room_total' => 0, 'nights' => 0, 'chargeable_extra' => 0];
    }
}
