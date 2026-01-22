<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$stmt = $pdo->prepare("
    SELECT * 
    FROM users 
    WHERE id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

$stmt = $pdo->prepare("
    SELECT id, name 
    FROM machines 
    ORDER BY name
");
$stmt->execute();
$machines = $stmt->fetchAll();

if ($is_admin) {
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name 
        FROM users 
        ORDER BY last_name, first_name
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();
}

$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$filter_start_time = isset($_GET['start_time']) ? $_GET['start_time'] : '';
$filter_machine = isset($_GET['machine']) ? $_GET['machine'] : '';
$filter_user = isset($_GET['user']) ? $_GET['user'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';

$sql = "
    SELECT b.*, m.name as machine_name, u.first_name, u.last_name 
    FROM bookings b 
    JOIN machines m ON b.id_machine = m.id 
    JOIN users u ON b.id_user = u.id 
    WHERE 1=1
";

$params = [];

if ($filter_date) {
    $sql .= " AND b.date = ?";
    $params[] = $filter_date;
}

if ($filter_start_time) {
    $sql .= " AND b.start_time = ?";
    $params[] = $filter_start_time;
}

if ($filter_machine) {
    $sql .= " AND b.id_machine = ?";
    $params[] = $filter_machine;
}

if ($is_admin && $filter_user) {
    $sql .= " AND b.id_user = ?";
    $params[] = $filter_user;
} else if (!$is_admin) {
    $sql .= " AND b.id_user = ?";
    $params[] = $_SESSION['user_id'];
}

switch ($sort) {
    case 'date_asc':
        $sql .= " ORDER BY b.date ASC, b.start_time ASC";
        break;
    case 'datetime_asc':
        $sql .= " ORDER BY b.date ASC, b.start_time ASC";
        break;
    case 'datetime_desc':
        $sql .= " ORDER BY b.date DESC, b.start_time DESC";
        break;
    case 'date_desc':
    default:
        $sql .= " ORDER BY b.date DESC, b.start_time DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>История бронирований - СтирайБезОчереди</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>История бронирований</h1>
        <p><?= $is_admin ? 'Все бронирования в системе' : 'Ваши прошлые и будущие бронирования' ?></p>
    </div>
    
    <div class="register-container booking-history">
        <div class="sort-container">
            <form method="GET">
                <div class="filter-container">
                    <?php if ($is_admin): ?>
                        <div class="filter-group">
                            <label>Пользователь:</label>
                            <select name="user" class="form-control">
                                <option value="">Все пользователи</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= ($filter_user == $u['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['last_name'] . ' ' . $u['first_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div class="filter-group">
                        <label>Дата:</label>
                        <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>" class="form-control">
                    </div>
                    
                    <div class="filter-group">
                        <label>Время:</label>
                        <select name="start_time" class="form-control">
                            <option value="">Любое время</option>
                            <?php for ($hour = 0; $hour < 23; $hour++): ?>
                                <option value="<?= sprintf("%02d:00:00", $hour) ?>" <?= ($filter_start_time == sprintf("%02d:00:00", $hour)) ? 'selected' : '' ?>><?= sprintf("%02d:00-%02d:00", $hour, $hour+1) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Машина:</label>
                        <select name="machine" class="form-control">
                            <option value="">Все машины</option>
                            <?php foreach ($machines as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= ($filter_machine == $m['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-buttons">
                    <button type="submit" class="btn">Применить</button>
                    <a href="booking.php" class="btn">Сбросить</a>
                </div>
            </form>
        </div>
        
        <?php if (empty($bookings)): ?>
            <p>Нет бронирований для отображения.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <?php if ($is_admin): ?>
                            <th>Пользователь</th>
                        <?php endif; ?>
                        <th>Дата</th>
                        <th>Время</th>
                        <th>Машина</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <?php if ($is_admin): ?>
                                <td><?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></td>
                            <?php endif; ?>
                            <td><?= date('d.m.Y', strtotime($booking['date'])) ?></td>
                            <td><?= substr($booking['start_time'], 0, 5) ?> - <?= substr($booking['end_time'], 0, 5) ?></td>
                            <td><?= htmlspecialchars($booking['machine_name']) ?></td>
                            <td><?= htmlspecialchars($booking['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div class="links">
            <p><a href="index.php">Вернуться на главную</a></p>
        </div>
    </div>
</body>
</html>