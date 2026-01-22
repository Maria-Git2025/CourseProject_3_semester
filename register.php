<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_card_number = $_POST['student_card_number'];
    $room_number = $_POST['room_number'];
    $last_name = $_POST['last_name'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = "Пароли не совпадают";
    } else {
        $stmt = $pdo->prepare("
            SELECT id 
            FROM users 
            WHERE email = ?
        ");
        
        $stmt->execute([$email]);
        $existing_user = $stmt->fetch();
        
        if ($existing_user) {
            $error = "Пользователь с таким email уже зарегистрирован";
        } else {
            $stmt = $pdo->prepare("
                SELECT id 
                FROM users 
                WHERE student_card_number = ?
            ");
            
            $stmt->execute([$student_card_number]);
            $existing_student = $stmt->fetch();
            
            if ($existing_student) {
                $error = "Пользователь с таким номером студенческого уже зарегистрирован";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users 
                    (student_card_number, room_number, last_name, first_name, middle_name, email, password) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([$student_card_number, $room_number, $last_name, $first_name, $middle_name, $email, $hashed_password])) {
                    header("Location: login.php?registration=success");
                    exit();
                } else {
                    $error = "Ошибка при регистрации.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - СтирайБезОчереди</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="register-container">
        <h2 style="text-align: center; margin-bottom: 20px;">Регистрация</h2>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="student_card_number">Номер студенческого билета:</label>
                <input type="text" id="student_card_number" name="student_card_number" required>
            </div>
            
            <div class="form-group">
                <label for="room_number">Номер комнаты:</label>
                <input type="text" id="room_number" name="room_number" required>
            </div>
            
            <div class="form-group">
                <label for="last_name">Фамилия:</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
            
            <div class="form-group">
                <label for="first_name">Имя:</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            
            <div class="form-group">
                <label for="middle_name">Отчество:</label>
                <input type="text" id="middle_name" name="middle_name">
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Подтверждение пароля:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn btn-register">Зарегистрироваться</button>
        </form>
        
        <div class="links">
            <p>Уже есть аккаунт? <a href="login.php">Войдите</a></p>
        </div>
    </div>
</body>
</html>