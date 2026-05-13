<?php
require_once 'config.php';
checkAuth();

$db = getDB();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Получаем статистику пользователя
$attempts_stmt = $db->prepare("
    SELECT COUNT(*) as total_attempts, 
           AVG(score) as avg_score,
           MAX(score) as best_score
    FROM attempts 
    WHERE user_id = ? AND completed = 1
");
$attempts_stmt->execute([$user_id]);
$stats = $attempts_stmt->fetch(PDO::FETCH_ASSOC);

// Получаем последние попытки
$recent_stmt = $db->prepare("
    SELECT a.*, q.title 
    FROM attempts a 
    JOIN quizzes q ON a.quiz_id = q.id 
    WHERE a.user_id = ? 
    ORDER BY a.started_at DESC 
    LIMIT 5
");
$recent_stmt->execute([$user_id]);
$recent_attempts = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - QuizMaster</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php require_once 'index.php'; ?>
    
    <div class="container">
        <h1>Личный кабинет</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Всего попыток</h3>
                <p class="stat-number"><?php echo $stats['total_attempts'] ?? 0; ?></p>
            </div>
            <div class="stat-card">
                <h3>Средний балл</h3>
                <p class="stat-number"><?php echo round($stats['avg_score'] ?? 0, 1); ?>%</p>
            </div>
            <div class="stat-card">
                <h3>Лучший результат</h3>
                <p class="stat-number"><?php echo round($stats['best_score'] ?? 0, 1); ?>%</p>
            </div>
        </div>
        
        <h2>Последние попытки</h2>
        <table class="attempts-table">
            <thead>
                <tr>
                    <th>Викторина</th>
                    <th>Дата начала</th>
                    <th>Результат</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recent_attempts as $attempt): ?>
                <tr>
                    <td><?php echo htmlspecialchars($attempt['title']); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($attempt['started_at'])); ?></td>
                    <td><?php echo $attempt['score'] ? round($attempt['score'], 1) . '%' : 'Не завершена'; ?></td>
                    <td>
                        <?php if($attempt['completed']): ?>
                            <span class="status completed">Завершено</span>
                        <?php else: ?>
                            <span class="status in-progress">В процессе</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if($role == 'teacher'): ?>
            <h2>Мои викторины</h2>
            <a href="create_quiz.php" class="btn">Создать новую викторину</a>
	    <a href="teacher_stats.php" class="btn btn-secondary">Просмотреть статистику</a>
            <div id="my-quizzes" class="quizzes-grid"></div>
            <script>
                // Загружаем викторины преподавателя
                loadMyQuizzes();
                
                async function loadMyQuizzes() {
                    try {
                        const response = await fetch('api.php?action=get_my_quizzes');
                        const quizzes = await response.json();
                        const container = document.getElementById('my-quizzes');
                        
                        quizzes.forEach(quiz => {
                            const quizCard = `
                                <div class="quiz-card">
                                    <h3>${quiz.title}</h3>
                                    <p>${quiz.description || ''}</p>
                                    <p>Вопросов: ${quiz.question_count}</p>
                                    <p>Статус: ${quiz.is_published ? 'Опубликована' : 'Черновик'}</p>
                                    <a href="create_quiz.php?id=${quiz.id}" class="btn">Редактировать</a>
                                </div>
                            `;
                            container.innerHTML += quizCard;
                        });
                    } catch(error) {
                        console.error('Ошибка:', error);
                    }
                }
            </script>
        <?php endif; ?>
    </div>
</body>
</html>