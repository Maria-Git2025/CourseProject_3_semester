<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT * 
    FROM users 
    WHERE id = ?
");

$stmt->execute([$user_id]);
$user = $stmt->fetch();

$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

function getMaxBookingDays($rating) {
    if ($rating >= 80) {
        return 7;
    } elseif ($rating >= 60) {
        return 5;
    } elseif ($rating >= 40) {
        return 3;
    } elseif ($rating >= 20) {
        return 1;
    } else {
        return 1;
    }
}

$max_booking_days = getMaxBookingDays($user['rating']);
$max_booking_date = date('Y-m-d', strtotime("+$max_booking_days days"));
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выбор даты - СтирайБезОчереди</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>Бронирование машины</h1>
        <p>Шаг 1: Выберите дату</p>
    </div>
    
    <div class="register-container">
        <h3>Выберите дату для бронирования</h3>
        
        <?php if ($user['rating'] >= 80): ?>
            <div class="info info-success">
                <strong>Ваш рейтинг: <?= $user['rating'] ?> баллов</strong>
                <p>Вы можете бронировать стиральные и сушильные машины на 7 дней вперёд.</p>
            </div>
        <?php elseif ($user['rating'] >= 20): ?>
            <div class="info info-warning">
                <strong>Ваш рейтинг: <?= $user['rating'] ?> баллов</strong>
                <?php if ($user['rating'] >= 60): ?>
                    <p>Вы можете бронировать стиральные и сушильные машины на 5 дней вперёд.</p>
                <?php elseif ($user['rating'] >= 40): ?>
                    <p>Вы можете бронировать стиральные и сушильные машины на 3 дня вперёд.</p>
                <?php else: ?>
                    <p>Вы можете бронировать стиральные и сушильные машины на 1 день вперёд.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="info info-error">
                <strong>Ваш рейтинг: <?= $user['rating'] ?> баллов</strong>
                <p>Ваш рейтинг ниже 20 баллов. Вы можете бронировать стиральные и сушильные машины только в течение текущего дня.</p>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin: 30px 0;">
            <form method="GET" action="select_time.php">
                <label for="date">Выберите дату:</label>
                <input type="date" id="date" name="date"
                       value="<?= $selected_date ?>"
                       min="<?= date('Y-m-d') ?>"
                       max="<?= $user['rating'] < 20 ? date('Y-m-d') : $max_booking_date ?>"
                       required>
                <input type="submit" value="Продолжить" class="btn" style="margin-left: 10px;">
            </form>
        </div>
        
        <div class="links">
            <p><a href="index.php">Вернуться на главную</a></p>
        </div>
    </div>
</body>
</html>