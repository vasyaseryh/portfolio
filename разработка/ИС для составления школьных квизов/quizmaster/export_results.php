<?php
// export_results.php - полная версия с TXT экспортом
require_once 'config.php';
checkRole(['teacher', 'admin']);

$db = getDB();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Получаем параметры
$quiz_id = $_GET['quiz_id'] ?? 0;
$attempt_id = $_GET['attempt_id'] ?? 0;
$export_type = $_GET['type'] ?? 'txt';
$what = $_GET['what'] ?? '';

// Функция для очистки текста
function cleanForExport($text) {
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = strip_tags($text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

// ========== ЭКСПОРТ ПОЛЬЗОВАТЕЛЕЙ (админ) ==========
if ($what === 'all_users' && $role === 'admin') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="users_' . date('Y-m-d') . '.txt"');
    
    $users = $db->query("SELECT id, login, email, full_name, role, created_at FROM users ORDER BY id")->fetchAll();
    
    echo "========================================\n";
    echo "        СПИСОК ПОЛЬЗОВАТЕЛЕЙ QuizMaster\n";
    echo "========================================\n\n";
    echo "Дата экспорта: " . date('d.m.Y H:i:s') . "\n";
    echo "Всего пользователей: " . count($users) . "\n\n";
    
    foreach($users as $index => $user) {
        echo "Пользователь #" . ($index + 1) . "\n";
        echo "----------------------------------------\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Логин: " . cleanForExport($user['login']) . "\n";
        echo "Email: " . cleanForExport($user['email']) . "\n";
        echo "ФИО: " . cleanForExport($user['full_name'] ?? 'Не указано') . "\n";
        echo "Роль: " . $user['role'] . "\n";
        echo "Дата регистрации: " . $user['created_at'] . "\n";
        echo "----------------------------------------\n\n";
    }
    exit;
}

// ========== ЭКСПОРТ ВИКТОРИН (админ) ==========
if ($what === 'all_quizzes' && $role === 'admin') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="quizzes_' . date('Y-m-d') . '.txt"');
    
    $quizzes = $db->query("
        SELECT q.*, u.full_name as author_name,
               (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count
        FROM quizzes q
        JOIN users u ON q.author_id = u.id
        ORDER BY q.created_at DESC
    ")->fetchAll();
    
    echo "========================================\n";
    echo "        СПИСОК ВИКТОРИН QuizMaster\n";
    echo "========================================\n\n";
    echo "Дата экспорта: " . date('d.m.Y H:i:s') . "\n";
    echo "Всего викторин: " . count($quizzes) . "\n\n";
    
    foreach($quizzes as $index => $quiz) {
        echo "Викторина #" . ($index + 1) . "\n";
        echo "----------------------------------------\n";
        echo "ID: " . $quiz['id'] . "\n";
        echo "Название: " . cleanForExport($quiz['title']) . "\n";
        echo "Описание: " . cleanForExport($quiz['description'] ?? '') . "\n";
        echo "Автор: " . cleanForExport($quiz['author_name']) . "\n";
        echo "Количество вопросов: " . $quiz['question_count'] . "\n";
        echo "Статус: " . ($quiz['is_published'] ? 'Опубликована' : 'Черновик') . "\n";
        echo "Проходной балл: " . $quiz['passing_score'] . "%\n";
        echo "Максимум попыток: " . $quiz['max_attempts'] . "\n";
        if ($quiz['time_limit']) {
            echo "Лимит времени: " . gmdate("H:i:s", $quiz['time_limit']) . "\n";
        }
        echo "Дата создания: " . $quiz['created_at'] . "\n";
        echo "----------------------------------------\n\n";
    }
    exit;
}

// ========== ЭКСПОРТ ВСЕХ РЕЗУЛЬТАТОВ (админ) ==========
if ($what === 'all_results' && $role === 'admin') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="all_results_' . date('Y-m-d') . '.txt"');
    
    $results = $db->query("
        SELECT 
            a.id,
            u.login,
            u.full_name,
            q.title as quiz_title,
            a.started_at,
            a.finished_at,
            a.score,
            q.passing_score,
            CASE 
                WHEN a.completed = 0 THEN 'В процессе'
                WHEN a.score >= q.passing_score THEN 'Сдал'
                ELSE 'Не сдал'
            END as status
        FROM attempts a
        JOIN users u ON a.user_id = u.id
        JOIN quizzes q ON a.quiz_id = q.id
        ORDER BY a.finished_at DESC
    ")->fetchAll();
    
    echo "========================================\n";
    echo "        ВСЕ РЕЗУЛЬТАТЫ QuizMaster\n";
    echo "========================================\n\n";
    echo "Дата экспорта: " . date('d.m.Y H:i:s') . "\n";
    echo "Всего попыток: " . count($results) . "\n\n";
    
    foreach($results as $index => $result) {
        echo "Попытка #" . ($index + 1) . "\n";
        echo "----------------------------------------\n";
        echo "ID попытки: " . $result['id'] . "\n";
        echo "Студент: " . cleanForExport($result['full_name']) . " (" . cleanForExport($result['login']) . ")\n";
        echo "Викторина: " . cleanForExport($result['quiz_title']) . "\n";
        echo "Дата начала: " . $result['started_at'] . "\n";
        echo "Дата завершения: " . ($result['finished_at'] ?? 'Не завершена') . "\n";
        echo "Результат: " . round($result['score'] ?? 0, 1) . "%\n";
        echo "Проходной балл: " . $result['passing_score'] . "%\n";
        echo "Статус: " . $result['status'] . "\n";
        echo "----------------------------------------\n\n";
    }
    exit;
}

// ========== ЭКСПОРТ СТАТИСТИКИ ПО ВИКТОРИНЕ ==========
if ($what === 'quiz_stats' && $quiz_id > 0) {
    // Проверяем права доступа
    if ($role === 'teacher') {
        $check = $db->prepare("SELECT id FROM quizzes WHERE id = ? AND author_id = ?");
        $check->execute([$quiz_id, $user_id]);
        if (!$check->fetch()) {
            die('Нет доступа');
        }
    }
    
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="quiz_stats_' . $quiz_id . '_' . date('Y-m-d') . '.txt"');
    
    // Получаем информацию о викторине
    $quiz_stmt = $db->prepare("SELECT q.*, u.full_name as author_name FROM quizzes q JOIN users u ON q.author_id = u.id WHERE q.id = ?");
    $quiz_stmt->execute([$quiz_id]);
    $quiz = $quiz_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Получаем статистику
    $stats_stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT a.user_id) as total_students,
            COUNT(a.id) as total_attempts,
            COALESCE(AVG(a.score), 0) as avg_score,
            COALESCE(MAX(a.score), 0) as max_score,
            COALESCE(MIN(a.score), 0) as min_score,
            SUM(CASE WHEN a.score >= ? THEN 1 ELSE 0 END) as passed_count,
            SUM(CASE WHEN a.score < ? AND a.score IS NOT NULL THEN 1 ELSE 0 END) as failed_count
        FROM attempts a
        WHERE a.quiz_id = ? AND a.completed = 1
    ");
    $stats_stmt->execute([$quiz['passing_score'], $quiz['passing_score'], $quiz_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "========================================\n";
    echo "        СТАТИСТИКА ВИКТОРИНЫ\n";
    echo "========================================\n\n";
    echo "Название: " . cleanForExport($quiz['title']) . "\n";
    echo "Автор: " . cleanForExport($quiz['author_name']) . "\n";
    echo "Проходной балл: " . $quiz['passing_score'] . "%\n";
    echo "Дата создания: " . $quiz['created_at'] . "\n\n";
    
    echo "========================================\n";
    echo "        ОБЩАЯ СТАТИСТИКА\n";
    echo "========================================\n\n";
    echo "Всего студентов: " . ($stats['total_students'] ?? 0) . "\n";
    echo "Всего попыток: " . ($stats['total_attempts'] ?? 0) . "\n";
    echo "Средний балл: " . round($stats['avg_score'] ?? 0, 1) . "%\n";
    echo "Лучший результат: " . round($stats['max_score'] ?? 0, 1) . "%\n";
    echo "Худший результат: " . round($stats['min_score'] ?? 0, 1) . "%\n";
    echo "Сдали: " . ($stats['passed_count'] ?? 0) . "\n";
    echo "Не сдали: " . ($stats['failed_count'] ?? 0) . "\n\n";
    
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
            ) as total_attempts,
            (
                SELECT COUNT(*)
                FROM user_answers ua
                JOIN attempts att ON ua.attempt_id = att.id
                WHERE ua.question_id = q.id AND ua.is_correct = 1 AND att.quiz_id = ? AND att.completed = 1
            ) as correct_answers
        FROM questions q
        WHERE q.quiz_id = ?
        ORDER BY q.id
    ");
    $questions_stmt->execute([$quiz_id, $quiz_id, $quiz_id]);
    $questions = $questions_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($questions)) {
        echo "========================================\n";
        echo "        СТАТИСТИКА ПО ВОПРОСАМ\n";
        echo "========================================\n\n";
        
        foreach($questions as $index => $q) {
            echo "Вопрос " . ($index + 1) . " (" . $q['points'] . " баллов)\n";
            echo "----------------------------------------\n";
            echo "Текст: " . cleanForExport($q['question_text']) . "\n";
            echo "Тип: " . $q['question_type'] . "\n";
            echo "Всего ответов: " . ($q['total_attempts'] ?? 0) . "\n";
            echo "Правильных ответов: " . ($q['correct_answers'] ?? 0) . "\n";
            if ($q['total_attempts'] > 0) {
                $percent = round(($q['correct_answers'] / $q['total_attempts']) * 100, 1);
                echo "Процент правильных: " . $percent . "%\n";
            }
            echo "----------------------------------------\n\n";
        }
    }
    
    exit;
}

// ========== ЭКСПОРТ РЕЗУЛЬТАТОВ ПО ВИКТОРИНЕ ==========
if ($what === 'quiz_results' && $quiz_id > 0) {
    // Проверяем права доступа
    if ($role === 'teacher') {
        $check = $db->prepare("SELECT id FROM quizzes WHERE id = ? AND author_id = ?");
        $check->execute([$quiz_id, $user_id]);
        if (!$check->fetch()) {
            die('Нет доступа');
        }
    }
    
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="quiz_results_' . $quiz_id . '_' . date('Y-m-d') . '.txt"');
    
    // Получаем информацию о викторине
    $quiz_stmt = $db->prepare("SELECT * FROM quizzes WHERE id = ?");
    $quiz_stmt->execute([$quiz_id]);
    $quiz = $quiz_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Получаем попытки
    $attempts_stmt = $db->prepare("
        SELECT 
            a.*,
            u.full_name,
            u.login,
            (
                SELECT COALESCE(SUM(q2.points), 0)
                FROM questions q2
                WHERE q2.quiz_id = ?
            ) as max_points,
            (
                SELECT COALESCE(SUM(CASE WHEN ua.is_correct = 1 THEN q2.points ELSE 0 END), 0)
                FROM user_answers ua
                JOIN questions q2 ON ua.question_id = q2.id
                WHERE ua.attempt_id = a.id AND q2.quiz_id = ?
            ) as earned_points
        FROM attempts a
        JOIN users u ON a.user_id = u.id
        WHERE a.quiz_id = ? AND a.completed = 1
        ORDER BY a.finished_at DESC
    ");
    $attempts_stmt->execute([$quiz_id, $quiz_id, $quiz_id]);
    $attempts = $attempts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "========================================\n";
    echo "        РЕЗУЛЬТАТЫ ВИКТОРИНЫ\n";
    echo "========================================\n\n";
    echo "Название: " . cleanForExport($quiz['title']) . "\n";
    echo "Проходной балл: " . $quiz['passing_score'] . "%\n";
    echo "Всего попыток: " . count($attempts) . "\n\n";
    
    foreach($attempts as $index => $attempt) {
        $percentage = $attempt['max_points'] > 0 ? ($attempt['earned_points'] / $attempt['max_points']) * 100 : 0;
        
        echo "Попытка #" . ($index + 1) . "\n";
        echo "----------------------------------------\n";
        echo "Студент: " . cleanForExport($attempt['full_name']) . " (" . cleanForExport($attempt['login']) . ")\n";
        echo "Дата завершения: " . $attempt['finished_at'] . "\n";
        echo "Результат: " . round($percentage, 1) . "%\n";
        echo "Баллы: " . $attempt['earned_points'] . "/" . $attempt['max_points'] . "\n";
        echo "Статус: " . ($percentage >= $quiz['passing_score'] ? 'Сдал' : 'Не сдал') . "\n";
        echo "----------------------------------------\n\n";
    }
    
    exit;
}

// ========== ЭКСПОРТ ОДНОЙ ПОПЫТКИ ==========
if ($what === 'attempt' && $attempt_id > 0) {
    // Проверяем права доступа
    if ($role === 'teacher') {
        $check = $db->prepare("
            SELECT a.id 
            FROM attempts a 
            JOIN quizzes q ON a.quiz_id = q.id
            WHERE a.id = ? AND q.author_id = ?
        ");
        $check->execute([$attempt_id, $user_id]);
        if (!$check->fetch()) {
            die('Нет доступа');
        }
    }
    
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="attempt_' . $attempt_id . '_' . date('Y-m-d') . '.txt"');
    
    // Получаем информацию о попытке
    $attempt_stmt = $db->prepare("
        SELECT 
            a.*,
            u.full_name,
            u.login,
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
    
    // Получаем ответы
    $answers_stmt = $db->prepare("
        SELECT 
            ua.*,
            q.question_text,
            q.question_type,
            q.points,
            q.correct_answers,
            q.explanation
        FROM user_answers ua
        JOIN questions q ON ua.question_id = q.id
        WHERE ua.attempt_id = ?
        ORDER BY q.id
    ");
    $answers_stmt->execute([$attempt_id]);
    $answers = $answers_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Рассчитываем баллы
    $total_earned = 0;
    foreach($answers as $answer) {
        if ($answer['is_correct'] == 1) {
            $total_earned += $answer['points'];
        }
    }
    $percentage = $attempt['max_points'] > 0 ? ($total_earned / $attempt['max_points']) * 100 : 0;
    
    echo "========================================\n";
    echo "        ДЕТАЛИ ПОПЫТКИ\n";
    echo "========================================\n\n";
    echo "Викторина: " . cleanForExport($attempt['quiz_title']) . "\n";
    echo "Студент: " . cleanForExport($attempt['full_name']) . " (" . cleanForExport($attempt['login']) . ")\n";
    echo "Дата начала: " . $attempt['started_at'] . "\n";
    echo "Дата завершения: " . $attempt['finished_at'] . "\n";
    echo "Результат: " . round($percentage, 1) . "%\n";
    echo "Баллы: " . $total_earned . "/" . $attempt['max_points'] . "\n";
    echo "Проходной балл: " . $attempt['passing_score'] . "%\n";
    echo "Статус: " . ($percentage >= $attempt['passing_score'] ? 'Сдал' : 'Не сдал') . "\n\n";
    
    if (!empty($answers)) {
        echo "========================================\n";
        echo "        ОТВЕТЫ СТУДЕНТА\n";
        echo "========================================\n\n";
        
        foreach($answers as $index => $answer) {
            echo "Вопрос " . ($index + 1) . " (" . $answer['points'] . " баллов)\n";
            echo "----------------------------------------\n";
            echo "Текст: " . cleanForExport($answer['question_text']) . "\n\n";
            
            echo "Ответ студента: ";
            if ($answer['question_type'] === 'single' || $answer['question_type'] === 'text') {
                echo cleanForExport($answer['answer'] ?? 'Нет ответа') . "\n";
            } elseif ($answer['question_type'] === 'multiple') {
                $student_answers = json_decode($answer['answer'] ?? '[]', true);
                if (is_array($student_answers) && !empty($student_answers)) {
                    echo implode(', ', $student_answers) . "\n";
                } else {
                    echo "Нет ответа\n";
                }
            }
            
            if (!empty($answer['correct_answers'])) {
                $correct_answers = json_decode($answer['correct_answers'], true);
                echo "Правильный ответ: ";
                if ($answer['question_type'] === 'single' || $answer['question_type'] === 'text') {
                    echo cleanForExport($correct_answers[0] ?? '') . "\n";
                } elseif ($answer['question_type'] === 'multiple') {
                    echo implode(', ', $correct_answers) . "\n";
                }
            }
            
            if (!empty($answer['explanation'])) {
                echo "Пояснение: " . cleanForExport($answer['explanation']) . "\n";
            }
            
            echo "Статус: " . ($answer['is_correct'] == 1 ? 'Правильно' : 'Неправильно') . "\n";
            echo "----------------------------------------\n\n";
        }
    }
    
    echo "========================================\n";
    echo "        ИТОГО\n";
    echo "========================================\n\n";
    echo "Набрано баллов: " . $total_earned . " из " . $attempt['max_points'] . "\n";
    echo "Процент выполнения: " . round($percentage, 1) . "%\n";
    echo "Статус: " . ($percentage >= $attempt['passing_score'] ? 'Сдал' : 'Не сдал') . "\n";
    
    exit;
}

// Если ничего не выбрано
echo "Неверные параметры экспорта";
?>