<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['is_admin']) {
    header("Location: manage_machines.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $machine_id = intval($_POST['machine_id']);
    $description = $_POST['description'];
    $user_id = $_SESSION['user_id'];
    $reported_date = date('Y-m-d');
    $reported_time = date('H:i:s');
    
    $stmt = $pdo->prepare("
        INSERT INTO machine_issues 
        (id_machine, reported_by, description, reported_date, reported_time) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$machine_id, $user_id, $description, $reported_date, $reported_time])) {
        $update_stmt = $pdo->prepare("
            UPDATE machines 
            SET status = 'на ремонте' 
            WHERE id = ?
        ");
        $update_stmt->execute([$machine_id]);
        
        $success = "Сообщение о поломке успешно отправлено. Машина переведена в режим ремонта.";
    } else {
        $error = "Ошибка при отправке сообщения.";
    }
}

$stmt = $pdo->prepare("
    SELECT * 
    FROM machines 
    ORDER BY name
");
$stmt->execute();
$machines = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сообщить о поломке - СтирайБезОчереди</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>Сообщить о поломке</h1>
        <p>Сообщите нам о проблеме с машиной</p>
    </div>
    
    <div class="register-container report-issue">
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="machine_id">Выберите машину:</label>
                <select id="machine_id" name="machine_id" class="form-control" required>
                    <option value="">-- Выберите машину --</option>
                    <?php foreach ($machines as $machine): ?>
                        <option value="<?= $machine['id'] ?>"><?= htmlspecialchars($machine['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="description">Описание проблемы:</label>
                <textarea id="description" name="description" rows="5" class="form-control" required
                          placeholder="Опишите проблему с машиной..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-register">Отправить сообщение</button>
        </form>
        
        <div class="links">
            <p><a href="index.php">Вернуться на главную</a></p>
        </div>
    </div>
</body>
</html>