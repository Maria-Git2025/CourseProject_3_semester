<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выбор времени - СтирайБезОчереди</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>Бронирование машины</h1>
        <p>Шаг 2: Выберите время</p>
    </div>
    
    <div class="register-container">
        <h3>Выберите время для бронирования</h3>
        <p>Дата: <?= date('d.m.Y', strtotime($selected_date)) ?></p>
        
        <div class="time-selection">
            <form method="GET" action="select_machine.php">
                <input type="hidden" name="date" value="<?= $selected_date ?>">
                
                <label for="time">Выберите время начала:</label>
                <select id="time" name="time" class="form-control" required>
                    <option value="">-- Выберите время --</option>
                    <?php for ($hour = 0; $hour < 24; $hour++): ?>
                        <option value="<?= sprintf("%02d:00:00", $hour) ?>"><?= sprintf("%02d:00", $hour) ?></option>
                    <?php endfor; ?>
                </select>
                
                <input type="submit" value="Продолжить" class="btn">
            </form>
        </div>
        
        <div class="links">
            <p><a href="select_date.php">Назад к выбору даты</a> | <a href="index.php">Вернуться на главную</a></p>
        </div>
    </div>
</body>
</html>