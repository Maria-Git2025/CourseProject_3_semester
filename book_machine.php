<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $machine_id = intval($_POST['machine_id']);
    $date = $_POST['date'];
    $start_time = $_POST['time'];
    
    $end_time = date('H:i:s', strtotime($start_time) + 3600);
    
    $stmt = $pdo->prepare("
        SELECT *
        FROM machines
        WHERE id = ?
    ");
    $stmt->execute([$machine_id]);
    $machine = $stmt->fetch();
    
    if ($machine) {
        $stmt = $pdo->prepare("
            SELECT id
            FROM bookings
            WHERE id_machine = ?
            AND date = ?
            AND status != 'отменено'
            AND start_time < ?
            AND end_time > ?
        ");
        $stmt->execute([$machine_id, $date, $end_time, $start_time]);
        $conflicting_booking = $stmt->fetch();
        
        if (!$conflicting_booking) {
            $prev_stmt = $pdo->prepare("
                SELECT id, id_user, id_next_user
                FROM bookings
                WHERE id_machine = ?
                AND date = ?
                AND status != 'отменено'
                AND end_time = ?
                AND start_time < ?
            ");
            $prev_stmt->execute([$machine_id, $date, $start_time, $start_time]);
            $prev_booking = $prev_stmt->fetch();
            
            $next_stmt = $pdo->prepare("
                SELECT id, id_user
                FROM bookings
                WHERE id_machine = ?
                AND date = ?
                AND status != 'отменено'
                AND start_time = ?
            ");
            $next_stmt->execute([$machine_id, $date, $end_time]);
            $next_booking = $next_stmt->fetch();
            
            $stmt = $pdo->prepare("
                INSERT INTO bookings
                (id_user, id_machine, date, start_time, end_time, id_next_user)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $next_user_id = $next_booking ? $next_booking['id_user'] : null;
            
            if ($stmt->execute([$user_id, $machine_id, $date, $start_time, $end_time, $next_user_id])) {
                $new_booking_id = $pdo->lastInsertId();
                
                if ($prev_booking) {
                    $update_prev_stmt = $pdo->prepare("
                        UPDATE bookings
                        SET id_next_user = ?
                        WHERE id = ?
                    ");
                    $update_prev_stmt->execute([$user_id, $prev_booking['id']]);
                }
                
                // Не обновляем статус машины немедленно
                // Статус будет обновлен скриптом update_machine_status.php в нужное время
                
                $success = "Машина успешно забронирована!";
            } else {
                $error = "Ошибка при бронировании.";
            }
        } else {
            $error = "Выбранное время уже занято. Пожалуйста, выберите другое время.";
        }
    } else {
        $error = "Выбранная машина недоступна для бронирования.";
    }
}

$booking_info = null;
if ($success) {
    $last_id = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("
        SELECT b.*, m.name as machine_name
        FROM bookings b
        JOIN machines m ON b.id_machine = m.id
        WHERE b.id = ?
    ");
    $stmt->execute([$last_id]);
    $booking_info = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бронирование создано - СтирайБезОчереди</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>Бронирование создано</h1>
        <p>Ваша бронь успешно оформлена</p>
    </div>
    
    <div class="register-container">
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
            <div class="booking-navigation">
                <a href="select_machine.php?date=<?= $date ?>&time=<?= $start_time ?>" class="btn">← Вернуться к выбору машины</a>
            </div>
        <?php elseif ($success): ?>
            <div class="success"><?= $success ?></div>
            
            <?php if ($booking_info): ?>
                <div class="booking-details">
                    <h3>Детали бронирования</h3>
                    <p><strong>Машина:</strong> <?= htmlspecialchars($booking_info['machine_name']) ?></p>
                    <p><strong>Дата:</strong> <?= date('d.m.Y', strtotime($booking_info['date'])) ?></p>
                    <p><strong>Время:</strong> с <?= substr($booking_info['start_time'], 0, 5) ?> до <?= substr($booking_info['end_time'], 0, 5) ?></p>
                </div>
            <?php endif; ?>
            
            <div class="booking-navigation">
                <a href="index.php" class="btn">Перейти на главную</a>
                <a href="profile.php" class="btn">Мои бронирования</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>