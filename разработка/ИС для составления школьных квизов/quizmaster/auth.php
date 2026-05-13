<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    $login = sanitize($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = sanitize($_POST['email'] ?? '');
    $full_name = sanitize($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'student';
    
    $db = getDB();
    
    if ($action === 'register') {
        // Проверка существования пользователя
        $stmt = $db->prepare("SELECT id FROM users WHERE login = ? OR email = ?");
        $stmt->execute([$login, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Пользователь с таким логином или email уже существует';
        } else {
            // Регистрация
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (login, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$login, $email, $password_hash, $full_name, $role])) {
                $success = 'Регистрация успешна! Теперь войдите в систему.';
            } else {
                $error = 'Ошибка регистрации';
            }
        }
    } else {
        // Авторизация
        $stmt = $db->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['login'] = $user['login'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            redirect('dashboard.php');
        } else {
            $error = 'Неверный логин или пароль';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход / Регистрация - QuizMaster</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-tabs">
            <button class="tab-btn active" onclick="showTab('login')">Вход</button>
            <button class="tab-btn" onclick="showTab('register')">Регистрация</button>
        </div>
        
        <?php if($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div id="login-form" class="auth-form">
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <input type="text" name="login" placeholder="Логин" required>
                <input type="password" name="password" placeholder="Пароль" required>
                <button type="submit" class="btn">Войти</button>
            </form>
        </div>
        
        <div id="register-form" class="auth-form" style="display: none;">
            <form method="POST">
                <input type="hidden" name="action" value="register">
                <input type="text" name="login" placeholder="Логин" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Пароль" required>
                <input type="text" name="full_name" placeholder="ФИО" required>
                <select name="role">
                    <option value="student">Студент</option>
                    <option value="teacher">Преподаватель</option>
                </select>
                <button type="submit" class="btn">Зарегистрироваться</button>
            </form>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Скрыть все формы
            document.querySelectorAll('.auth-form').forEach(form => {
                form.style.display = 'none';
            });
            
            // Убрать активный класс у всех кнопок
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Показать выбранную форму
            document.getElementById(tabName + '-form').style.display = 'block';
            
            // Активировать кнопку
            event.target.classList.add('active');
        }
    </script>
</body>
</html>