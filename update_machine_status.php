<?php
require_once 'db.php';

$current_time = date('H:i:s');
$current_date = date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT * 
    FROM machines
");

$stmt->execute();
$machines = $stmt->fetchAll();

foreach ($machines as $machine) {
    $stmt = $pdo->prepare("
        SELECT * 
        FROM bookings 
        WHERE id_machine = ? 
        AND date = ? 
        AND status = 'забронировано' 
        AND start_time <= ? 
        AND end_time > ?
    ");
    
    $stmt->execute([
        $machine['id'], 
        $current_date, 
        $current_time, 
        $current_time
    ]);
    
    $active_booking = $stmt->fetch();
    
    if ($active_booking) {
        if ($machine['status'] != 'занято') {
            $stmt = $pdo->prepare("
                UPDATE machines 
                SET status = 'занято' 
                WHERE id = ?
            ");
            
            $stmt->execute([$machine['id']]);
        }
    } else {
        if ($machine['status'] == 'занято') {
            $stmt = $pdo->prepare("
                UPDATE machines 
                SET status = 'свободно' 
                WHERE id = ? 
                AND status != 'на ремонте'
            ");
            
            $stmt->execute([$machine['id']]);
        }
    }
}
?>
