<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: index.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_machine_status':
                $machine_id = intval($_POST['machine_id']);
                $status = $_POST['status'];
                
                $stmt = $pdo->prepare("
                    UPDATE machines 
                    SET status = ? 
                    WHERE id = ?
                ");
                if ($stmt->execute([$status, $machine_id])) {
                    $message = "Статус машины успешно обновлен.";
                } else {
                    $message = "Ошибка при обновлении статуса машины.";
                }
                break;
                
            case 'resolve_issue':
                $issue_id = intval($_POST['issue_id']);
                $admin_notes = $_POST['admin_notes'];
                $resolved_date = date('Y-m-d');
                $resolved_time = date('H:i:s');
                
                $stmt = $pdo->prepare("SELECT * FROM machine_issues WHERE id = ?");
                $stmt->execute([$issue_id]);
                $issue = $stmt->fetch();
                
                if ($issue) {
                    $machine_id = $issue['id_machine'];
                    
                    $stmt = $pdo->prepare("
                        UPDATE machine_issues
                        SET status = 'решено',
                            resolved_date = ?,
                            resolved_time = ?,
                            admin_notes = ?
                        WHERE id = ?
                    ");
                    if ($stmt->execute([$resolved_date, $resolved_time, $admin_notes, $issue_id])) {
                        $stmt = $pdo->prepare("
                            UPDATE machines
                            SET status = 'свободно'
                            WHERE id = ?
                        ");
                        if ($stmt->execute([$machine_id])) {
                            $message = "Проблема успешно решена. Машина переведена в режим свободна.";
                        } else {
                            $message = "Проблема решена, но произошла ошибка при обновлении статуса машины.";
                        }
                    } else {
                        $message = "Ошибка при решении проблемы.";
                    }
                } else {
                    $message = "Проблема не найдена.";
                }
                break;
        }
    }
}

$stmt = $pdo->prepare("
    SELECT * 
    FROM machines 
    ORDER BY name
");
$stmt->execute();
$machines = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT mi.*, m.name as machine_name, u.first_name, u.last_name
    FROM machine_issues mi
    JOIN machines m ON mi.id_machine = m.id
    JOIN users u ON mi.reported_by = u.id
    WHERE mi.status != 'решено'
    ORDER BY mi.reported_date DESC, mi.reported_time DESC
");
$stmt->execute();
$issues = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление машинами - СтирайБезОчереди</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h1>Управление машинами</h1>
        <p>Управление статусами машин и решение проблем</p>
    </div>
    
    <div class="register-container report-issue">
        <?php if ($message): ?>
            <div class="success"><?= $message ?></div>
        <?php endif; ?>
        
        <h3>Управление машинами</h3>
        <table>
            <thead>
                <tr>
                    <th>Машина</th>
                    <th>Тип</th>
                    <th>Текущий статус</th>
                    <th>Новый статус</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($machines as $machine): ?>
                    <tr>
                        <td><?= htmlspecialchars($machine['name']) ?></td>
                        <td><?= $machine['type'] ?></td>
                        <td><?= $machine['status'] ?></td>
                        <form method="POST">
                            <td>
                                <input type="hidden" name="action" value="update_machine_status">
                                <input type="hidden" name="machine_id" value="<?= $machine['id'] ?>">
                                <select name="status" class="status-select">
                                    <option value="свободно" <?= $machine['status'] == 'свободно' ? 'selected' : '' ?>>Свободно</option>
                                    <option value="занято" <?= $machine['status'] == 'занято' ? 'selected' : '' ?>>Занято</option>
                                    <option value="на ремонте" <?= $machine['status'] == 'на ремонте' ? 'selected' : '' ?>>На ремонте</option>
                                </select>
                            </td>
                            <td>
                                <button type="submit" class="btn update-btn">Обновить</button>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h3>Сообщения о поломках</h3>
        <?php if (count($issues) > 0): ?>
            <?php foreach ($issues as $issue): ?>
                <div class="issue-item">
                    <h4>Проблема с <?= htmlspecialchars($issue['machine_name']) ?></h4>
                    <p><strong>Сообщил:</strong> <?= htmlspecialchars($issue['first_name'] . ' ' . $issue['last_name']) ?></p>
                    <p><strong>Дата и время:</strong> <?= $issue['reported_date'] ?> в <?= $issue['reported_time'] ?></p>
                    <p><strong>Описание:</strong> <?= htmlspecialchars($issue['description']) ?></p>
                    
                    <form method="POST" class="resolve-form">
                        <input type="hidden" name="action" value="resolve_issue">
                        <input type="hidden" name="issue_id" value="<?= $issue['id'] ?>">
                        <div class="form-group">
                            <label for="admin_notes_<?= $issue['id'] ?>">Заметки администратора:</label>
                            <textarea id="admin_notes_<?= $issue['id'] ?>" name="admin_notes" rows="2" class="form-control"></textarea>
                        </div>
                        <button type="submit" class="btn resolve-btn">Отметить как решенную</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Нет нерешенных проблем.</p>
        <?php endif; ?>
        
        <div class="links">
            <p><a href="index.php">Вернуться на главную</a></p>
        </div>
    </div>
</body>
</html>