<?php
require_once 'config.php';
checkRole(['admin']);

$db = getDB();
$action = $_GET['action'] ?? '';

if($action === 'delete_user' && isset($_GET['id'])) {
    $db->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'")->execute([$_GET['id']]);
    header('Location: admin.php');
    exit();
}

// Получаем статистику
$stats_stmt = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM users WHERE role = 'teacher') as teachers,
        (SELECT COUNT(*) FROM users WHERE role = 'student') as students,
        (SELECT COUNT(*) FROM quizzes) as total_quizzes,
        (SELECT COUNT(*) FROM attempts) as total_attempts
");
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Получаем список пользователей
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Получаем список викторин
$quizzes = $db->query("
    SELECT q.*, u.full_name as author_name,
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count
    FROM quizzes q
    JOIN users u ON q.author_id = u.id
    ORDER BY q.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - QuizMaster</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .tab-btn {
            padding: 10px 20px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            cursor: pointer;
        }
        
        .tab-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .export-options {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <?php require_once 'index.php'; ?>
    
    <div class="container">
        <h1>Админ-панель</h1>
        
        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Всего пользователей</h3>
                <p class="stat-number"><?php echo $stats['total_users']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Преподаватели</h3>
                <p class="stat-number"><?php echo $stats['teachers']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Студенты</h3>
                <p class="stat-number"><?php echo $stats['students']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Викторины</h3>
                <p class="stat-number"><?php echo $stats['total_quizzes']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Попытки</h3>
                <p class="stat-number"><?php echo $stats['total_attempts']; ?></p>
            </div>
        </div>
        
        <!-- Вкладки -->
        <div class="admin-tabs">
            <button class="tab-btn active" onclick="showTab('users')">Пользователи</button>
            <button class="tab-btn" onclick="showTab('quizzes')">Викторины</button>
            <button class="tab-btn" onclick="showTab('export')">Экспорт</button>
        </div>
        
        <!-- Пользователи -->
        <div id="users-tab" class="tab-content active">
            <h2>Управление пользователями</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Логин</th>
                        <th>Email</th>
                        <th>ФИО</th>
                        <th>Роль</th>
                        <th>Дата регистрации</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['login']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo $user['role']; ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if($user['role'] != 'admin'): ?>
                                <a href="admin.php?action=delete_user&id=<?php echo $user['id']; ?>" 
                                   onclick="return confirm('Удалить пользователя?')"
                                   class="btn btn-small btn-danger">Удалить</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Викторины -->
        <div id="quizzes-tab" class="tab-content">
            <h2>Все викторины</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Автор</th>
                        <th>Вопросов</th>
                        <th>Статус</th>
                        <th>Дата создания</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($quizzes as $quiz): ?>
                    <tr>
                        <td><?php echo $quiz['id']; ?></td>
                        <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                        <td><?php echo htmlspecialchars($quiz['author_name']); ?></td>
                        <td><?php echo $quiz['question_count']; ?></td>
                        <td>
                            <?php if($quiz['is_published']): ?>
                                <span class="status completed">Опубликована</span>
                            <?php else: ?>
                                <span class="status in-progress">Черновик</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($quiz['created_at'])); ?></td>
                        <td>
                            <a href="create_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn btn-small">Ред.</a>
                            <a href="teacher_stats.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-small">Стат.</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Экспорт данных в TXT -->
        <div id="export-tab" class="tab-content">
            <h2>Экспорт данных</h2>
            <div class="export-options">
                <a href="export_results.php?what=all_users&type=txt" class="btn">
                    👥 Экспорт пользователей (TXT)
                </a>
                <a href="export_results.php?what=all_quizzes&type=txt" class="btn">
                    📚 Экспорт викторин (TXT)
                </a>
                <a href="export_results.php?what=all_results&type=txt" class="btn">
                    📊 Экспорт результатов (TXT)
                </a>
            </div>
            
            <h3>Экспорт по викторинам</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Викторина</th>
                        <th>Автор</th>
                        <th>Статистика</th>
                        <th>Результаты</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($quizzes as $quiz): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                        <td><?php echo htmlspecialchars($quiz['author_name']); ?></td>
                        <td>
                            <a href="export_results.php?quiz_id=<?php echo $quiz['id']; ?>&what=quiz_stats&type=txt" class="btn btn-small">
                                📊 Статистика
                            </a>
                        </td>
                        <td>
                            <a href="export_results.php?quiz_id=<?php echo $quiz['id']; ?>&what=quiz_results&type=txt" class="btn btn-small">
                                📋 Результаты
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Скрываем все вкладки
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Убираем активный класс у всех кнопок
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Показываем выбранную вкладку
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Активируем кнопку
            event.target.classList.add('active');
        }
    </script>
</body>
</html>