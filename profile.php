<?php
session_start();
require_once 'db.php';
include 'update_machine_status.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['cancel_booking'])) {
    $booking_id = intval($_POST['booking_id']);
    
    $stmt = $pdo->prepare("
        SELECT id, id_machine 
        FROM bookings 
        WHERE id = ? 
        AND id_user = ?
    ");
    
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch();
    
    if ($booking) {
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET status = 'отменено' 
            WHERE id = ?
        ");
        
        $stmt->execute([$booking_id]);
        
        $current_time = date('H:i:s');
        $current_date = date('Y-m-d');
        
        $stmt = $pdo->prepare("
            SELECT id 
            FROM bookings 
            WHERE id_machine = ? 
            AND status = 'забронировано' 
            AND date = ? 
            AND start_time <= ? 
            AND end_time > ?
        ");
        
        $stmt->execute([
            $booking['id_machine'], 
            $current_date, 
            $current_time, 
            $current_time
        ]);
        
        $other_bookings = $stmt->fetch();
        
        if (!$other_bookings) {
            $stmt = $pdo->prepare("
                UPDATE machines 
                SET status = 'свободно' 
                WHERE id = ?
            ");
            
            $stmt->execute([$booking['id_machine']]);
        }
    }
    
    header('Location: profile.php');
    exit();
}

$stmt = $pdo->prepare("
    SELECT * 
    FROM users 
    WHERE id = ?
");

$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT b.*, m.name as machine_name 
    FROM bookings b 
    JOIN machines m ON b.id_machine = m.id 
    WHERE b.id_user = ? 
    ORDER BY b.date DESC, b.start_time DESC
");

$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - СтирайБезОчереди</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>Личный кабинет</h1>
        <p>Добро пожаловать, <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>!</p>
    </div>
    
    <div class="register-container">
        <h3>Ваш профиль</h3>
        
        <div class="form-group">
            <label>Номер студенческого:</label>
            <div><?= htmlspecialchars($user['student_card_number']) ?></div>
        </div>
        
        <div class="form-group">
            <label>Номер комнаты:</label>
            <div><?= htmlspecialchars($user['room_number']) ?></div>
        </div>
        
        <div class="form-group">
            <label>Email:</label>
            <div><?= htmlspecialchars($user['email']) ?></div>
        </div>
        
        <div class="form-group">
            <label>Рейтинг:</label>
            <div><?= $user['rating'] ?></div>
        </div>
        
        <h3>История бронирований</h3>
        
        <?php if (count($bookings) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Машина</th>
                        <th>Дата</th>
                        <th>Время</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?= htmlspecialchars($booking['machine_name']) ?></td>
                            <td><?= date('d.m.Y', strtotime($booking['date'])) ?></td>
                            <td><?= substr($booking['start_time'], 0, 5) ?> - <?= substr($booking['end_time'], 0, 5) ?></td>
                            <td>
                                <?php
                                switch ($booking['status']) {
                                    case 'забронировано': 
                                        echo 'Забронировано'; 
                                        break;
                                    case 'в процессе': 
                                        echo 'В процессе'; 
                                        break;
                                    case 'завершено': 
                                        echo 'Завершено'; 
                                        break;
                                    case 'пропущено': 
                                        echo 'Пропущено'; 
                                        break;
                                    case 'отменено': 
                                        echo 'Отменено'; 
                                        break;
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($booking['status'] == 'забронировано'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                        <button type="submit" name="cancel_booking" class="btn" style="padding: 5px 10px; font-size: 12px;">Отменить</button>
                                    </form>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>У вас пока нет бронирований.</p>
        <?php endif; ?>
        
        <div class="links">
            <p><a href="index.php">Вернуться на главную</a> |
            <a href="select_date.php">Забронировать машину</a></p>
        </div>
    </div>
</body>
</html>