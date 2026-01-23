<?php
session_start();
require_once 'db.php';
include 'update_machine_status.php';

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: index.php");
    exit();
}

$current_time = date('H:i:s');
$current_date = date('Y-m-d');

$stmt = $pdo->prepare("SELECT * 
                       FROM machines 
                       ORDER BY position_y, position_x");

$stmt->execute();
$machines = $stmt->fetchAll();

$machine_bookings = [];

foreach ($machines as $machine) {
    if ($machine['status'] == 'занято') {
        // Ищем активное бронирование (в процессе)
        $stmt = $pdo->prepare("SELECT *
                               FROM bookings
                               WHERE id_machine = ?
                               AND date = ?
                               AND status = 'в процессе'
                               AND start_time <= ?
                               AND end_time > ?");
        
        $stmt->execute([$machine['id'],
                        $current_date,
                        $current_time,
                        $current_time]);
        
        $booking = $stmt->fetch();
        
        // Если нет активного бронирования, ищем забронированное
        if (!$booking) {
            $stmt = $pdo->prepare("SELECT *
                                   FROM bookings
                                   WHERE id_machine = ?
                                   AND date = ?
                                   AND status = 'забронировано'
                                   AND start_time <= ?
                                   AND end_time > ?");
            
            $stmt->execute([$machine['id'],
                            $current_date,
                            $current_time,
                            $current_time]);
            
            $booking = $stmt->fetch();
        }
        
        if ($booking) {
            $machine_bookings[$machine['id']] = $booking;
        }
    }
}

$rows = [
    '1' => [],
    '2' => []
];

foreach ($machines as $machine) {
    $rows[$machine['position_y']][$machine['position_x']] = $machine;
}

for ($i = 1; $i <= 5; $i++) {
    if (!isset($rows['1'][(string)$i])) {
        $rows['1'][(string)$i] = null;
    }
    if (!isset($rows['2'][(string)$i])) {
        $rows['2'][(string)$i] = null;
    }
}

ksort($rows['1']);
ksort($rows['2']);

function showMachine($machine, $machine_bookings, $current_time) {
    if ($machine) {
        echo '<div class="machine ' . $machine['type'] . ' ' . $machine['status'] . '">';
        echo '<div class="machine-name">' . htmlspecialchars($machine['name']) . '</div>';
        echo '<div class="machine-status status-' . str_replace(' ', '-', $machine['status']) . '">';
        
        switch ($machine['status']) {
            case 'свободно': 
                echo 'Свободна'; 
                break;
            case 'занято': 
                echo 'Занята'; 
                break;
            case 'на ремонте': 
                echo 'На ремонте'; 
                break;
        }
        
        echo '</div>';
        echo '<div class="payment-method">Оплата: ' . $machine['payment_method'] . '</div>';
        
        if ($machine['status'] == 'занято' && isset($machine_bookings[$machine['id']])) {
            $end_time = strtotime($machine_bookings[$machine['id']]['end_time']);
            $current_time_timestamp = strtotime($current_time);
            $minutes_left = ceil(($end_time - $current_time_timestamp) / 60);
            
            if ($minutes_left > 0) {
                $remaining_time = $minutes_left . ' мин';
            } else {
                $remaining_time = 'менее 1 мин';
            }
            
            echo '<div class="countdown">Осталось: ' . $remaining_time . '</div>';
        }
        
        echo '</div>';
    } else {
        echo '<div class="machine empty"></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>СтирайБезОчереди - Прачечная общежития №6</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>СтирайБезОчереди</h1>
        <p>Система бронирования прачечной общежития №6 Московского Политеха</p>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <div style="margin-top: 20px; display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                <?php if ($_SESSION['is_admin']): ?>
                    <a href="booking.php" class="btn">История бронирования</a>
                    <a href="statistics.php" class="btn">Статистика</a>
                    <a href="manage_machines.php" class="btn">Управление машинами</a>
                <?php else: ?>
                    <a href="profile.php" class="btn">Личный кабинет</a>
                    <a href="select_date.php" class="btn">Забронировать</a>
                    <a href="report_issue.php" class="btn">Сообщить о поломке</a>
                    <a href="confirm_punctuality.php" class="btn">Подтвердить пунктуальность</a>
                <?php endif; ?>
                <a href="index.php?action=logout" class="btn">Выйти</a>
            </div>
        <?php else: ?>
            <div style="margin-top: 20px; display: flex; justify-content: center; gap: 15px;">
                <a href="login.php" class="btn">Войти</a>
                <a href="register.php" class="btn">Зарегистроваться</a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="laundry-grid">
        <?php 
        foreach ($rows['1'] as $position => $machine) {
            showMachine($machine, $machine_bookings, $current_time);
        }
        
        foreach ($rows['2'] as $position => $machine) {
            showMachine($machine, $machine_bookings, $current_time);
        }
        ?>
    </div>
</body>
</html>