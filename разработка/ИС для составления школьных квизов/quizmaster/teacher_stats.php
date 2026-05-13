<?php
require_once 'config.php';
checkRole(['teacher', 'admin']);

$db = getDB();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Получаем ID викторины если передан
$quiz_id = $_GET['quiz_id'] ?? 0;

// Если преподаватель, показываем только его викторины
if ($role === 'teacher') {
    $quizzes_stmt = $db->prepare("SELECT id, title FROM quizzes WHERE author_id = ? ORDER BY title");
    $quizzes_stmt->execute([$user_id]);
} else {
    // Админ видит все викторины
    $quizzes_stmt = $db->prepare("SELECT id, title FROM quizzes ORDER BY title");
    $quizzes_stmt->execute();
}
$quizzes = $quizzes_stmt->fetchAll(PDO::FETCH_ASSOC);

// Если выбрана конкретная викторина, получаем статистику
$quiz_stats = [];
$attempts = [];
$question_stats = [];

if ($quiz_id > 0) {
    // Проверяем права доступа для преподавателя
    if ($role === 'teacher') {
        $check_stmt = $db->prepare("SELECT id FROM quizzes WHERE id = ? AND author_id = ?");
        $check_stmt->execute([$quiz_id, $user_id]);
        if (!$check_stmt->fetch()) {
            die('Нет доступа к этой викторине');
        }
    }
    
    // Общая статистика по викторине
    $stats_stmt = $db->prepare("
        SELECT 
            q.title,
            COUNT(DISTINCT a.user_id) as total_students,
            COUNT(a.id) as total_attempts,
            COALESCE(AVG(a.score), 0) as avg_score,
            COALESCE(MAX(a.score), 0) as max_score,
            COALESCE(MIN(a.score), 0) as min_score,
            SUM(CASE WHEN a.score >= q.passing_score THEN 1 ELSE 0 END) as passed_count,
            SUM(CASE WHEN a.score < q.passing_score AND a.score IS NOT NULL THEN 1 ELSE 0 END) as failed_count
        FROM quizzes q
        LEFT JOIN attempts a ON q.id = a.quiz_id AND a.completed = 1
        WHERE q.id = ?
    ");
    $stats_stmt->execute([$quiz_id]);
    $quiz_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Получаем попытки по этой викторине
    $attempts_stmt = $db->prepare("
        SELECT 
            a.*,
            u.login,
            u.full_name,
            q.passing_score,
            (
                SELECT COALESCE(SUM(q2.points), 0)
                FROM questions q2
                WHERE q2.quiz_id = q.id
            ) as max_points,
            (
                SELECT COALESCE(SUM(CASE WHEN ua.is_correct = 1 THEN q2.points ELSE 0 END), 0)
                FROM user_answers ua
                JOIN questions q2 ON ua.question_id = q2.id
                WHERE ua.attempt_id = a.id AND q2.quiz_id = q.id
            ) as earned_points
        FROM attempts a
        JOIN users u ON a.user_id = u.id
        JOIN quizzes q ON a.quiz_id = q.id
        WHERE a.quiz_id = ? AND a.completed = 1
        ORDER BY a.finished_at DESC
    ");
    $attempts_stmt->execute([$quiz_id]);
    $attempts = $attempts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Статистика по вопросам
    $questions_stmt = $db->prepare("
        SELECT 
            q.id,
            q.question_text,
            q.question_type,
            q.points,
            (
                SELECT COUNT(DISTINCT ua.attempt_id)
                FROM user_answers ua
                JOIN attempts att ON ua.attempt_id = att.id
                WHERE ua.question_id = q.id AND att.quiz_id = ? AND att.completed = 1
            ) as total_attempts_for_question,
            (
                SELECT COUNT(*)
                FROM user_answers ua
                JOIN attempts att ON ua.attempt_id = att.id
                WHERE ua.question_id = q.id AND ua.is_correct = 1 AND att.quiz_id = ? AND att.completed = 1
            ) as correct_answers,
            (
                SELECT COUNT(*)
                FROM user_answers ua
                JOIN attempts att ON ua.attempt_id = att.id
                WHERE ua.question_id = q.id AND ua.is_correct = 0 AND att.quiz_id = ? AND att.completed = 1
            ) as incorrect_answers
        FROM questions q
        WHERE q.quiz_id = ?
        ORDER BY q.id
    ");
    $questions_stmt->execute([$quiz_id, $quiz_id, $quiz_id, $quiz_id]);
    $question_stats = $questions_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика - QuizMaster</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .stat-box {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stat-box h3 {
            margin-bottom: 0.5rem;
            font-size: 1rem;
            color: #666;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-number.passed {
            color: #27ae60;
        }
        
        .stat-number.failed {
            color: #e74c3c;
        }
        
        .quiz-selector {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .question-stat {
            background: white;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
        
        .question-stat.easy {
            border-left-color: #27ae60;
        }
        
        .question-stat.medium {
            border-left-color: #f39c12;
        }
        
        .question-stat.hard {
            border-left-color: #e74c3c;
        }
        
        .progress-bar-small {
            height: 8px;
            background: #eee;
            border-radius: 4px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: #3498db;
        }
        
        .progress-fill.easy {
            background: #27ae60;
        }
        
        .progress-fill.medium {
            background: #f39c12;
        }
        
        .progress-fill.hard {
            background: #e74c3c;
        }
        
        .points-info {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.3rem;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        
        .export-buttons a {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php require_once 'index.php'; ?>
    
    <div class="container">
        <h1>Статистика викторин</h1>
        
        <!-- Выбор викторины -->
        <div class="quiz-selector">
            <h3>Выберите викторину для просмотра статистики:</h3>
            <form method="GET" action="teacher_stats.php">
                <select name="quiz_id" onchange="this.form.submit()">
                    <option value="">-- Выберите викторину --</option>
                    <?php foreach($quizzes as $quiz): ?>
                        <option value="<?php echo $quiz['id']; ?>" <?php echo ($quiz_id == $quiz['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($quiz['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn">Показать</button>
            </form>
        </div>
        
        <?php if($quiz_id > 0): ?>
            <!-- Общая статистика -->
            <h2>Общая статистика: <?php echo htmlspecialchars($quiz_stats['title'] ?? ''); ?></h2>
            <div class="stats-summary">
                <div class="stat-box">
                    <h3>Всего студентов</h3>
                    <div class="stat-number"><?php echo $quiz_stats['total_students'] ?? 0; ?></div>
                </div>
                
                <div class="stat-box">
                    <h3>Всего попыток</h3>
                    <div class="stat-number"><?php echo $quiz_stats['total_attempts'] ?? 0; ?></div>
                </div>
                
                <div class="stat-box">
                    <h3>Средний балл</h3>
                    <div class="stat-number"><?php echo round($quiz_stats['avg_score'] ?? 0, 1); ?>%</div>
                </div>
                
                <div class="stat-box">
                    <h3>Лучший результат</h3>
                    <div class="stat-number"><?php echo round($quiz_stats['max_score'] ?? 0, 1); ?>%</div>
                </div>
                
                <div class="stat-box">
                    <h3>Сдали</h3>
                    <div class="stat-number passed"><?php echo $quiz_stats['passed_count'] ?? 0; ?></div>
                </div>
                
                <div class="stat-box">
                    <h3>Не сдали</h3>
                    <div class="stat-number failed"><?php echo $quiz_stats['failed_count'] ?? 0; ?></div>
                </div>
            </div>
            
            <!-- Статистика по вопросам -->
            <h2>Статистика по вопросам</h2>
            <?php if(!empty($question_stats)): ?>
                <?php foreach($question_stats as $index => $question): ?>
                    <?php 
                    $total_attempts = $question['total_attempts_for_question'];
                    $correct = $question['correct_answers'];
                    $incorrect = $question['incorrect_answers'];
                    $percent = $total_attempts > 0 ? round(($correct / $total_attempts) * 100, 1) : 0;
                    
                    // Определяем сложность вопроса
                    if ($percent >= 70) $difficulty = 'easy';
                    elseif ($percent >= 40) $difficulty = 'medium';
                    else $difficulty = 'hard';
                    ?>
                    
                    <div class="question-stat <?php echo $difficulty; ?>">
                        <h4>Вопрос <?php echo $index + 1; ?> (<?php echo $question['points']; ?> баллов)</h4>
                        <p><?php echo htmlspecialchars(mb_substr($question['question_text'], 0, 200) . (mb_strlen($question['question_text']) > 200 ? '...' : '')); ?></p>
                        <p>Тип: <?php echo $question['question_type']; ?></p>
                        <?php if($total_attempts > 0): ?>
                            <p>Правильных ответов: <?php echo $correct; ?> из <?php echo $total_attempts; ?> (<?php echo $percent; ?>%)</p>
                            <div class="progress-bar-small">
                                <div class="progress-fill <?php echo $difficulty; ?>" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                        <?php else: ?>
                            <p>Еще никто не ответил на этот вопрос</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>По этой викторине нет вопросов.</p>
            <?php endif; ?>
            
            <!-- Список попыток -->
            <h2>Попытки студентов</h2>
            <?php if(!empty($attempts)): ?>
                <table class="attempts-table">
                    <thead>
                        <tr>
                            <th>Студент</th>
                            <th>Дата завершения</th>
                            <th>Результат</th>
                            <th>Баллы</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($attempts as $attempt): ?>
                            <?php 
                            // Рассчитываем проценты правильно
                            $percentage = 0;
                            if ($attempt['max_points'] > 0 && $attempt['earned_points'] > 0) {
                                $percentage = ($attempt['earned_points'] / $attempt['max_points']) * 100;
                            }
                            ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($attempt['full_name']); ?><br>
                                    <small><?php echo htmlspecialchars($attempt['login']); ?></small>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($attempt['finished_at'])); ?></td>
                                <td><?php echo round($percentage, 1); ?>%</td>
                                <td>
                                    <?php echo $attempt['earned_points']; ?>/<?php echo $attempt['max_points']; ?>
                                    <br>
                                    <small class="points-info"><?php echo $attempt['max_points'] > 0 ? round(($attempt['earned_points'] / $attempt['max_points']) * 100, 1) : 0; ?>%</small>
                                </td>
                                <td>
                                    <?php if($percentage >= $attempt['passing_score']): ?>
                                        <span class="status completed">Сдал</span>
                                    <?php else: ?>
                                        <span class="status in-progress">Не сдал</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="attempt_details.php?attempt_id=<?php echo $attempt['id']; ?>" class="btn btn-small">Детали</a>
                                    <a href="export_results.php?attempt_id=<?php echo $attempt['id']; ?>&what=attempt&type=txt" class="btn btn-small">📝</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>По этой викторине еще нет завершенных попыток.</p>
            <?php endif; ?>
            
            <!-- Экспорт данных в TXT -->
            <div style="margin-top: 2rem;">
                <h3>Экспорт данных</h3>
                <div class="export-buttons">
                    <a href="export_results.php?quiz_id=<?php echo $quiz_id; ?>&what=quiz_stats&type=txt" class="btn btn-small">
                        📊 Статистика (TXT)
                    </a>
                    <a href="export_results.php?quiz_id=<?php echo $quiz_id; ?>&what=quiz_results&type=txt" class="btn btn-small">
                        📋 Результаты (TXT)
                    </a>
                </div>
            </div>
            
        <?php elseif($quiz_id == 0 && !empty($quizzes)): ?>
            <div class="info">
                <p>Выберите викторину из списка выше, чтобы просмотреть статистику.</p>
            </div>
        <?php else: ?>
            <div class="info">
                <p>У вас пока нет викторин для просмотра статистики.</p>
                <a href="create_quiz.php" class="btn">Создать викторину</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>