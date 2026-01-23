<?php
session_start();
require_once 'db.php';
include 'update_machine_status.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT DAYOFWEEK(date) as day_of_week, HOUR(start_time) as hour, COUNT(*) as count
    FROM bookings
    GROUP BY DAYOFWEEK(date), HOUR(start_time)
    ORDER BY day_of_week, hour
");
$stmt->execute();
$bookings_by_day_hour = $stmt->fetchAll();

$weekdays = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];

function getMaxCount($bookings) {
    $max = 0;
    foreach ($bookings as $booking) {
        if ($booking['count'] > $max) {
            $max = $booking['count'];
        }
    }
    return $max > 0 ? $max : 1;
}

function getBookingCount($bookings_by_day_hour, $day_index, $hour) {
    foreach ($bookings_by_day_hour as $booking) {
        $db_day_index = $booking['day_of_week'] == 1 ? 6 : $booking['day_of_week'] - 2;
        
        if ($db_day_index === $day_index && intval($booking['hour']) === $hour) {
            return intval($booking['count']);
        }
    }
    return 0;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика - СтирайБезОчереди</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>Статистика использования</h1>
        <p>Анализ загруженности прачечной</p>
    </div>
    
    <div class="register-container booking-history">
        <div class="chart-container">
            <div class="chart-title">Загруженность по дням недели и часам</div>
            
            <?php
            $maxCount = getMaxCount($bookings_by_day_hour);
            foreach ($weekdays as $index => $weekday): ?>
                <div class="day-chart-container">
                    <div class="day-header"><?= $weekday ?></div>
                    <div class="hour-chart">
                        <?php for ($hour = 0; $hour < 24; $hour++): ?>
                            <?php
                                $count = getBookingCount($bookings_by_day_hour, $index, $hour);
                                $height = $maxCount > 0 ? max(5, ($count / $maxCount) * 120) : 5;
                                
                                $dayTotal = 0;
                                foreach ($bookings_by_day_hour as $booking) {
                                    $db_day_index = $booking['day_of_week'] == 1 ? 6 : $booking['day_of_week'] - 2;
                                    if ($db_day_index === $index && intval($booking['count']) > 0) {
                                        $dayTotal += intval($booking['count']);
                                    }
                                }
                                $percentage = $dayTotal > 0 ? round(($count / $dayTotal) * 100, 1) : 0;
                            ?>
                            <div class="hour-bar-container">
                                <div class="hour-bar" style="height: <?= $height ?>px;"></div>
                                <div class="count-label"><?= $percentage ?>%</div>
                                <div class="hour-label"><?= $hour ?>:00</div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="links">
            <p><a href="index.php">Вернуться на главную</a></p>
        </div>
    </div>
</body>
</html>