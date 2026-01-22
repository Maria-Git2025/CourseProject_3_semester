<?php
require_once 'db.php';

$current_time = date('H:i:s');
$current_date = date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT b.*, m.status as machine_status
    FROM bookings b
    JOIN machines m ON b.id_machine = m.id
    WHERE b.date = ?
    AND b.status = 'забронировано'
    AND b.start_time <= ?
    AND b.end_time > ?
");

$stmt->execute([$current_date, $current_time, $current_time]);
$active_bookings = $stmt->fetchAll();

foreach ($active_bookings as $booking) {
    $update_stmt = $pdo->prepare("
        UPDATE bookings
        SET status = 'в процессе'
        WHERE id = ?
    ");
    $update_stmt->execute([$booking['id']]);

    if ($booking['machine_status'] != 'занято') {
        $update_machine_stmt = $pdo->prepare("
            UPDATE machines
            SET status = 'занято'
            WHERE id = ?
        ");
        $update_machine_stmt->execute([$booking['id_machine']]);
    }
}

$stmt = $pdo->prepare("
    SELECT *
    FROM bookings
    WHERE date = ?
    AND status = 'в процессе'
    AND end_time <= ?
");

$stmt->execute([$current_date, $current_time]);
$finished_bookings = $stmt->fetchAll();

foreach ($finished_bookings as $booking) {
    $update_stmt = $pdo->prepare("
        UPDATE bookings
        SET status = 'завершено'
        WHERE id = ?
    ");
    $update_stmt->execute([$booking['id']]);
    
    $check_stmt = $pdo->prepare("
        SELECT *
        FROM bookings
        WHERE id_machine = ?
        AND date = ?
        AND status = 'забронировано'
        AND start_time <= ?
        AND end_time > ?
    ");
    
    $check_stmt->execute([
        $booking['id_machine'],
        $current_date,
        $current_time,
        $current_time
    ]);
    
    $next_booking = $check_stmt->fetch();
    
    if (!$next_booking) {
        $update_machine_stmt = $pdo->prepare("
            UPDATE machines
            SET status = 'свободно'
            WHERE id = ?
            AND status != 'на ремонте'
        ");
        $update_machine_stmt->execute([$booking['id_machine']]);
    }
}

$stmt = $pdo->prepare("
    SELECT *
    FROM bookings
    WHERE date = ?
    AND status = 'забронировано'
    AND end_time <= ?
");

$stmt->execute([$current_date, $current_time]);
$expired_bookings = $stmt->fetchAll();

foreach ($expired_bookings as $booking) {
    $update_stmt = $pdo->prepare("
        UPDATE bookings
        SET status = 'завершено'
        WHERE id = ?
    ");
    $update_stmt->execute([$booking['id']]);
}
?>
