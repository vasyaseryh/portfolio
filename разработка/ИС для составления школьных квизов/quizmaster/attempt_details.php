<?php
require_once 'config.php';
checkRole(['teacher', 'admin']);

$attempt_id = $_GET['attempt_id'] ?? 0;
$db = getDB();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Получаем информацию о попытке
$attempt_stmt = $db->prepare("
    SELECT 
        a.*,
        u.login,
        u.full_name,
        q.title as quiz_title,
        q.passing_score,
        (
            SELECT COALESCE(SUM(q2.points), 0)
            FROM questions q2
            WHERE q2.quiz_id = q.id
        ) as max_points
    FROM attempts a
    JOIN users u ON a.user_id = u.id
    JOIN quizzes q ON a.quiz_id = q.id
    WHERE a.id = ?
");
$attempt_stmt->execute([$attempt_id]);
$attempt = $attempt_stmt->fetch(PDO::FETCH_ASSOC);

if(!$attempt) {
    die('Попытка не найдена');
}

if ($role === 'teacher') {
    $check_stmt = $db->prepare("SELECT id FROM quizzes WHERE id = ? AND author_id = ?");
    $check_stmt->execute([$attempt['quiz_id'], $user_id]);
    if (!$check_stmt->fetch()) {
        die('Нет доступа к этой попытке');
    }
}

// Получаем ответы студента
$answers_stmt = $db->prepare("
    SELECT 
        ua.*,
        q.question_text,
        q.question_type,
        q.options,
        q.correct_answers,
        q.explanation,
        q.points
    FROM user_answers ua
    JOIN questions q ON ua.question_id = q.id
    WHERE ua.attempt_id = ?
    ORDER BY q.id
");
$answers_stmt->execute([$attempt_id]);
$answers = $answers_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Детали попытки - QuizMaster</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .attempt-summary {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .answer-details {
            background: white;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            border-left: 4px solid #ddd;
        }
        
        .answer-details.correct {
            border-left-color: #27ae60;
            background: #f0fff4;
        }
        
        .answer-details.incorrect {
            border-left-color: #e74c3c;
            background: #fff0f0;
        }
        
        .answer-details.partial {
            border-left-color: #f39c12;
            background: #fff8e1;
        }
        
        .correct-answer {
            color: #27ae60;
            font-weight: bold;
        }
        
        .student-answer {
            color: #3498db;
            font-weight: bold;
        }
        
        .points-badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            background: #3498db;
            color: white;
            border-radius: 10px;
            font-size: 0.9rem;
            margin-left: 0.5rem;
        }
        
        .points-badge.correct {
            background: #27ae60;
        }
        
        .points-badge.incorrect {
            background: #e74c3c;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .export-buttons a {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php 
    // Включаем только навигацию
    require_once 'config.php'; 
    ?>
    <header>
        <nav class="navbar">
            <div class="logo">QuizMaster</div>
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="user-menu">
                    <span><?php echo htmlspecialchars($_SESSION['full_name']); ?> (<?php echo $_SESSION['role']; ?>)</span>
                    <a href="dashboard.php">Кабинет</a>
                    <?php if($_SESSION['role'] == 'teacher'): ?>
                        <a href="create_quiz.php">Создать викторину</a>
                        <a href="teacher_stats.php">Статистика</a>
                    <?php endif; ?>
                    <?php if($_SESSION['role'] == 'admin'): ?>
                        <a href="admin.php">Админ-панель</a>
                    <?php endif; ?>
                    <a href="logout.php">Выход</a>
                </div>
            <?php else: ?>
                <a href="auth.php">Вход / Регистрация</a>
            <?php endif; ?>
        </nav>
    </header>
    
    <div class="container">
        <h1>Детали попытки</h1>
        
        <!-- Информация о попытке -->
        <div class="attempt-summary">
            <h2><?php echo htmlspecialchars($attempt['quiz_title']); ?></h2>
            <p><strong>Студент:</strong> <?php echo htmlspecialchars($attempt['full_name']); ?> (<?php echo htmlspecialchars($attempt['login']); ?>)</p>
            <p><strong>Дата начала:</strong> <?php echo date('d.m.Y H:i', strtotime($attempt['started_at'])); ?></p>
            <p><strong>Дата завершения:</strong> <?php echo date('d.m.Y H:i', strtotime($attempt['finished_at'])); ?></p>
            
            <?php
            // Расчет баллов
            $total_earned = 0;
            $total_max = $attempt['max_points'];
            foreach($answers as $answer) {
                if ($answer['is_correct'] == 1) {
                    $total_earned += $answer['points'];
                }
            }
            $percentage = $total_max > 0 ? ($total_earned / $total_max) * 100 : 0;
            ?>
            
            <p><strong>Результат:</strong> 
                <span style="font-size: 1.5rem; font-weight: bold;"><?php echo round($percentage, 1); ?>%</span>
                <br>
                <small>Баллы: <?php echo $total_earned; ?>/<?php echo $total_max; ?></small>
            </p>
            
            <p><strong>Статус:</strong> 
                <?php if($percentage >= $attempt['passing_score']): ?>
                    <span class="status completed">Сдал</span>
                <?php else: ?>
                    <span class="status in-progress">Не сдал</span>
                <?php endif; ?>
                (проходной балл: <?php echo $attempt['passing_score']; ?>%)
            </p>
            
            <div class="export-buttons">
                <a href="teacher_stats.php?quiz_id=<?php echo $attempt['quiz_id']; ?>" class="btn">Вернуться к статистике</a>
                <a href="export_results.php?attempt_id=<?php echo $attempt_id; ?>&what=attempt&type=txt" class="btn btn-small">
                    📝 Экспорт в TXT
                </a>
                <a href="export_results.php?attempt_id=<?php echo $attempt_id; ?>&what=attempt&type=csv" class="btn btn-small">
                    📊 Экспорт в CSV
                </a>
                <a href="export_results.php?attempt_id=<?php echo $attempt_id; ?>&what=attempt&type=html" class="btn btn-small">
                    🌐 Экспорт в HTML
                </a>
            </div>
        </div>
        
        <!-- Детали ответов -->
        <h2>Ответы студента</h2>
        
        <?php if(empty($answers)): ?>
            <p>Нет данных об ответах.</p>
        <?php else: ?>
            <?php foreach($answers as $index => $answer): ?>
                <?php 
                $answer_class = '';
                if ($answer['is_correct'] == 1) {
                    $answer_class = 'correct';
                } elseif ($answer['is_correct'] == 0) {
                    $answer_class = 'incorrect';
                } else {
                    $answer_class = 'partial';
                }
                ?>
                
                <div class="answer-details <?php echo $answer_class; ?>">
                    <h3>
                        Вопрос <?php echo $index + 1; ?>
                        <span class="points-badge <?php echo $answer_class; ?>">
                            <?php echo $answer['points']; ?> баллов
                            <?php if($answer['is_correct'] == 1): ?>
                                ✓
                            <?php elseif($answer['is_correct'] == 0): ?>
                                ✗
                            <?php endif; ?>
                        </span>
                    </h3>
                    <p><strong><?php echo htmlspecialchars($answer['question_text']); ?></strong></p>
                    
                    <!-- Отображение ответа студента -->
                    <p><strong>Ответ студента:</strong> 
                        <span class="student-answer">
                            <?php 
                            if ($answer['question_type'] === 'single' || $answer['question_type'] === 'text') {
                                echo htmlspecialchars($answer['answer'] ?? 'Нет ответа');
                            } elseif ($answer['question_type'] === 'multiple') {
                                $student_answers = json_decode($answer['answer'] ?? '[]', true);
                                if (is_array($student_answers) && !empty($student_answers)) {
                                    echo htmlspecialchars(implode(', ', $student_answers));
                                } else {
                                    echo 'Нет ответа';
                                }
                            }
                            ?>
                        </span>
                    </p>
                    
                    <!-- Правильный ответ -->
                    <?php if(!empty($answer['correct_answers'])): ?>
                        <p><strong>Правильный ответ:</strong> 
                            <span class="correct-answer">
                                <?php 
                                $correct_answers = json_decode($answer['correct_answers'], true);
                                if (is_array($correct_answers)) {
                                    if ($answer['question_type'] === 'single' || $answer['question_type'] === 'text') {
                                        echo htmlspecialchars($correct_answers[0] ?? '');
                                    } elseif ($answer['question_type'] === 'multiple') {
                                        echo htmlspecialchars(implode(', ', $correct_answers));
                                    }
                                }
                                ?>
                            </span>
                        </p>
                    <?php endif; ?>
                    
                    <!-- Пояснение -->
                    <?php if(!empty($answer['explanation'])): ?>
                        <div class="explanation">
                            <p><strong>Пояснение:</strong> <?php echo htmlspecialchars($answer['explanation']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <!-- Итоговая статистика -->
            <div class="attempt-summary">
                <h3>Итоговая статистика попытки</h3>
                <p><strong>Набрано баллов:</strong> <?php echo $total_earned; ?> из <?php echo $total_max; ?></p>
                <p><strong>Процент выполнения:</strong> <?php echo round($percentage, 1); ?>%</p>
                <p><strong>Проходной балл:</strong> <?php echo $attempt['passing_score']; ?>%</p>
                <p><strong>Статус:</strong> 
                    <?php if($percentage >= $attempt['passing_score']): ?>
                        <span class="status completed">Сдал</span>
                    <?php else: ?>
                        <span class="status in-progress">Не сдал</span>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
        
        <a href="teacher_stats.php?quiz_id=<?php echo $attempt['quiz_id']; ?>" class="btn">Вернуться к статистике</a>
    </div>
</body>
</html>