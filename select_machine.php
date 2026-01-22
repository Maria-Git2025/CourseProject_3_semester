<?php
session_start();
require_once 'db.php';
include 'update_machine_status.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_time = isset($_GET['time']) ? $_GET['time'] : null;

$stmt = $pdo->prepare("
    SELECT * 
    FROM machines 
    ORDER BY position_y, position_x
");

$stmt->execute();
$machines = $stmt->fetchAll();

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

$available_machines = [];
$booked_machines = [];
$under_repair_machines = [];

if ($selected_time) {
    $end_time = date('H:i:s', strtotime($selected_time) + 3600);
    
    foreach ($machines as $machine) {
        if ($machine['status'] == 'на ремонте') {
            $under_repair_machines[] = $machine['id'];
            continue;
        }
        
        $stmt = $pdo->prepare("
            SELECT id, start_time, end_time, status 
            FROM bookings 
            WHERE id_machine = ? 
            AND date = ? 
            AND status = 'забронировано' 
            AND start_time < ? 
            AND end_time > ?
        ");
        
        $stmt->execute([
            $machine['id'], 
            $selected_date, 
            $end_time, 
            $selected_time
        ]);
        
        $conflicting_booking = $stmt->fetch();
        
        if ($conflicting_booking) {
            $booked_machines[] = $machine['id'];
        } else {
            $available_machines[] = $machine['id'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выбор машины - СтирайБезОчереди</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>Бронирование машины</h1>
        <p>Шаг 3: Выберите машину</p>
    </div>
    
    <div class="container">
        <div class="booking-info">
            <h3>Выберите машину для бронирования</h3>
            <p>Дата: <?= date('d.m.Y', strtotime($selected_date)) ?> | Время: <?= substr($selected_time, 0, 5) ?> - <?= date('H:i', strtotime($selected_time) + 3600) ?></p>
        </div>
        
        <?php if ($selected_time): ?>
            <div class="laundry-grid">
                <!-- Первая строка (сушилки) -->
                <?php foreach ($rows['1'] as $position => $machine): ?>
                    <?php if ($machine): ?>
                        <?php
                        $is_available = in_array($machine['id'], $available_machines);
                        $is_booked = in_array($machine['id'], $booked_machines);
                        $is_under_repair = in_array($machine['id'], $under_repair_machines);
                        ?>
                        <div class="machine <?= $machine['type'] ?> <?= $is_available ? 'свободно' : ($is_booked ? 'занято' : 'на-ремонте') ?>">
                            <div class="machine-name"><?= htmlspecialchars($machine['name']) ?></div>
                            <div class="machine-status status-<?= $is_available ? 'свободно' : ($is_booked ? 'занято' : 'на-ремонте') ?>">
                                <?php 
                                if ($is_under_repair) {
                                    echo 'На ремонте';
                                } elseif ($is_booked) {
                                    echo 'Занята';
                                } elseif ($is_available) {
                                    echo 'Свободна';
                                }
                                ?>
                            </div>
                            <div class="payment-method">
                                Оплата: <?= $machine['payment_method'] ?>
                            </div>
                            
                            <?php if ($is_available): ?>
                                <button class="book-button" onclick="bookMachine(<?= $machine['id'] ?>, '<?= $selected_date ?>', '<?= $selected_time ?>')">
                                    Забронировать
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Пустая ячейка -->
                        <div class="machine empty"></div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <!-- Вторая строка (стиралки) -->
                <?php foreach ($rows['2'] as $position => $machine): ?>
                    <?php if ($machine): ?>
                        <?php
                        $is_available = in_array($machine['id'], $available_machines);
                        $is_booked = in_array($machine['id'], $booked_machines);
                        $is_under_repair = in_array($machine['id'], $under_repair_machines);
                        ?>
                        <div class="machine <?= $machine['type'] ?> <?= $is_available ? 'свободно' : ($is_booked ? 'занято' : 'на-ремонте') ?>">
                            <div class="machine-name"><?= htmlspecialchars($machine['name']) ?></div>
                            <div class="machine-status status-<?= $is_available ? 'свободно' : ($is_booked ? 'занято' : 'на-ремонте') ?>">
                                <?php 
                                if ($is_under_repair) {
                                    echo 'На ремонте';
                                } elseif ($is_booked) {
                                    echo 'Занята';
                                } elseif ($is_available) {
                                    echo 'Свободна';
                                }
                                ?>
                            </div>
                            <div class="payment-method">
                                Оплата: <?= $machine['payment_method'] ?>
                            </div>
                            
                            <?php if ($is_available): ?>
                                <button class="book-button" onclick="bookMachine(<?= $machine['id'] ?>, '<?= $selected_date ?>', '<?= $selected_time ?>')">
                                    Забронировать
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Пустая ячейка -->
                        <div class="machine empty"></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Ошибка: не выбрано время.</p>
        <?php endif; ?>
        
        <div class="links">
            <p><a href="select_time.php?date=<?= $selected_date ?>">Назад к выбору времени</a> | <a href="index.php">Вернуться на главную</a></p>
        </div>
    </div>
    
    <script>
        function bookMachine(machineId, date, time) {
            const dateObj = new Date(date);
            const formattedDate = dateObj.toLocaleDateString('ru-RU');
            const formattedTime = time.substring(0, 5);
            
            if (confirm('Вы уверены, что хотите забронировать эту машину на ' + formattedDate + ' в ' + formattedTime + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'book_machine.php';
                
                const machineInput = document.createElement('input');
                machineInput.type = 'hidden';
                machineInput.name = 'machine_id';
                machineInput.value = machineId;
                form.appendChild(machineInput);
                
                const dateInput = document.createElement('input');
                dateInput.type = 'hidden';
                dateInput.name = 'date';
                dateInput.value = date;
                form.appendChild(dateInput);
                
                const timeInput = document.createElement('input');
                timeInput.type = 'hidden';
                timeInput.name = 'time';
                timeInput.value = time;
                form.appendChild(timeInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>