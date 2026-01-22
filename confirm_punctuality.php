<?php
session_start();
require_once 'db.php';
include 'update_machine_status.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$stmt = $pdo->prepare("
    SELECT b.*, m.name as machine_name, u.first_name, u.last_name
    FROM bookings b
    JOIN machines m ON b.id_machine = m.id
    JOIN users u ON b.id_user = u.id
    WHERE b.id_next_user = ? 
    AND b.status = 'завершено' 
    AND b.was_on_time IS NULL
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_id = intval($_POST['booking_id']);
    $was_on_time = intval($_POST['was_on_time']);
    
    $stmt = $pdo->prepare("
        SELECT b.*, u.rating as previous_user_rating
        FROM bookings b
        JOIN users u ON b.id_user = u.id
        WHERE b.id = ? 
        AND b.id_next_user = ?
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch();
    
    if ($booking) {
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET was_on_time = ? 
            WHERE id = ?
        ");
        $stmt->execute([$was_on_time, $booking_id]);
        
        if ($was_on_time == 0) {
            $new_rating = max(0, $booking['previous_user_rating'] - 5);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET rating = ? 
                WHERE id = ?
            ");
            $stmt->execute([$new_rating, $booking['id_user']]);
        }
        
        $success = "Спасибо за подтверждение!";
        
        $stmt = $pdo->prepare("
            SELECT b.*, m.name as machine_name, u.first_name, u.last_name
            FROM bookings b
            JOIN machines m ON b.id_machine = m.id
            JOIN users u ON b.id_user = u.id
            WHERE b.id_next_user = ? 
            AND b.was_on_time IS NULL 
            AND b.status = 'завершено'
        ");
        $stmt->execute([$user_id]);
        $bookings = $stmt->fetchAll();
    } else {
        $error = "Ошибка: недостаточно прав для подтверждения этого бронирования.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подтверждение пунктуальности - СтирайБезОчереди</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>Подтверждение пунктуальности</h1>
        <p>Пожалуйста, подтвердите, забрал ли предыдущий пользователь вещи вовремя</p>
    </div>
    
    <div class="register-container">
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (count($bookings) > 0): ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-confirmation">
                    <h4>Бронирование на <?= htmlspecialchars($booking['machine_name']) ?></h4>
                    <p><strong>Пользователь:</strong> <?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></p>
                    <p><strong>Дата и время:</strong> <?= date('d.m.Y', strtotime($booking['date'])) ?> с <?= substr($booking['start_time'], 0, 5) ?> до <?= substr($booking['end_time'], 0, 5) ?></p>
                    
                    <form method="POST" class="confirmation-form">
                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                        <p><strong>Предыдущий пользователь забрал вещи вовремя?</strong></p>
                        <div class="confirmation-buttons">
                            <button type="submit" name="was_on_time" value="1" class="confirm-btn btn-register">Да, вовремя</button>
                            <button type="submit" name="was_on_time" value="0" class="confirm-btn btn-register">Нет, с опозданием</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Нет бронирований, требующих подтверждения пунктуальности.</p>
        <?php endif; ?>
        
        <div class="links">
            <p><a href="index.php">Вернуться на главную</a></p>
        </div>
    </div>
</body>
</html>