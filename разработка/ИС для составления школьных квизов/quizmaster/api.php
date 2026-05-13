<?php
// api.php - полная исправленная версия
require_once 'config.php';
checkAuth();

$db = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

header('Content-Type: application/json');

switch($action) {
    
    // ========== ВИКТОРИНЫ ==========
    case 'get_quizzes':
        if ($role === 'admin' || $role === 'student') {
            // Админы и студенты видят ВСЕ опубликованные викторины
            $stmt = $db->prepare("
                SELECT q.*, 
                       u.full_name as author_name,
                       (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count
                FROM quizzes q 
                JOIN users u ON q.author_id = u.id 
                WHERE q.is_published = 1
                ORDER BY q.created_at DESC
            ");
            $stmt->execute();
        } elseif ($role === 'teacher') {
            // Преподаватели видят ТОЛЬКО свои викторины
            $stmt = $db->prepare("
                SELECT q.*, 
                       u.full_name as author_name,
                       (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count
                FROM quizzes q 
                JOIN users u ON q.author_id = u.id 
                WHERE q.author_id = ?
                ORDER BY q.created_at DESC
            ");
            $stmt->execute([$user_id]);
        }
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;
        
    case 'get_my_quizzes':
        // Только для преподавателей
        if ($role !== 'teacher' && $role !== 'admin') {
            echo json_encode([]);
            break;
        }
        $stmt = $db->prepare("
            SELECT q.*, 
                   (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count
            FROM quizzes q 
            WHERE q.author_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;
        
    // ========== ВОПРОСЫ ==========
    case 'get_questions':
        $quiz_id = (int)$_GET['quiz_id'];
        
        // Проверяем доступ
        if ($role === 'admin' || $role === 'student') {
            $check = $db->prepare("SELECT * FROM quizzes WHERE id = ? AND is_published = 1");
            $check->execute([$quiz_id]);
        } elseif ($role === 'teacher') {
            $check = $db->prepare("SELECT * FROM quizzes WHERE id = ? AND author_id = ?");
            $check->execute([$quiz_id, $user_id]);
        }
        
        if (!$check->fetch()) {
            echo json_encode(['error' => 'Нет доступа']);
            exit;
        }
        
        $stmt = $db->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id");
        $stmt->execute([$quiz_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;
        
    // ========== СОХРАНЕНИЕ ОТВЕТОВ ==========
    case 'save_answer':
        $attempt_id = (int)$_POST['attempt_id'];
        $question_id = (int)$_POST['question_id'];
        $answer = $_POST['answer'];
        
        // Проверяем права
        $check = $db->prepare("SELECT id FROM attempts WHERE id = ? AND user_id = ?");
        $check->execute([$attempt_id, $user_id]);
        if (!$check->fetch()) {
            echo json_encode(['error' => 'Нет прав']);
            exit;
        }
        
        // Сохраняем ответ
        $stmt = $db->prepare("
            INSERT INTO user_answers (attempt_id, question_id, answer) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE answer = VALUES(answer)
        ");
        $stmt->execute([$attempt_id, $question_id, $answer]);
        echo json_encode(['success' => true]);
        break;
        
    // ========== ЗАВЕРШЕНИЕ ВИКТОРИНЫ ==========
    case 'submit_quiz':
        $attempt_id = (int)$_POST['attempt_id'];
        
        // Проверяем права
        $check = $db->prepare("
            SELECT a.*, q.passing_score 
            FROM attempts a 
            JOIN quizzes q ON a.quiz_id = q.id
            WHERE a.id = ? AND a.user_id = ? AND a.completed = 0
        ");
        $check->execute([$attempt_id, $user_id]);
        $attempt = $check->fetch();
        
        if (!$attempt) {
            echo json_encode(['error' => 'Попытка не найдена']);
            exit;
        }
        
        // Получаем максимальное количество баллов за викторину
        $max_points_stmt = $db->prepare("
            SELECT COALESCE(SUM(points), 0) as max_points 
            FROM questions 
            WHERE quiz_id = ?
        ");
        $max_points_stmt->execute([$attempt['quiz_id']]);
        $max_points = $max_points_stmt->fetch(PDO::FETCH_ASSOC)['max_points'];
        
        // Получаем ответы
        $answers = $db->prepare("
            SELECT 
                ua.*,
                q.correct_answers,
                q.points,
                q.question_type
            FROM user_answers ua
            JOIN questions q ON ua.question_id = q.id
            WHERE ua.attempt_id = ?
        ");
        $answers->execute([$attempt_id]);
        $user_answers = $answers->fetchAll(PDO::FETCH_ASSOC);
        
        $total_score = 0;
        
        // Проверяем ответы и начисляем баллы
        foreach($user_answers as $answer) {
            $is_correct = 0;
            
            if($answer['question_type'] === 'single') {
                $correct = json_decode($answer['correct_answers'], true);
                if($answer['answer'] == ($correct[0] ?? '')) {
                    $total_score += $answer['points'];
                    $is_correct = 1;
                }
            } elseif($answer['question_type'] === 'multiple') {
                $correct = json_decode($answer['correct_answers'], true);
                $user_answers_arr = json_decode($answer['answer'], true) ?: [];
                
                if(is_array($user_answers_arr) && is_array($correct)) {
                    sort($correct);
                    sort($user_answers_arr);
                    if($correct == $user_answers_arr) {
                        $total_score += $answer['points'];
                        $is_correct = 1;
                    }
                }
            }
            // Для текстовых вопросов пока не начисляем баллы автоматически
            
            // Обновляем правильность ответа
            $update_stmt = $db->prepare("
                UPDATE user_answers 
                SET is_correct = ?, checked_at = NOW() 
                WHERE attempt_id = ? AND question_id = ?
            ");
            $update_stmt->execute([$is_correct, $attempt_id, $answer['question_id']]);
        }
        
        // Рассчитываем процент
        $score_percent = $max_points > 0 ? ($total_score / $max_points) * 100 : 0;
        
        // Обновляем попытку
        $update = $db->prepare("UPDATE attempts SET completed = 1, score = ?, finished_at = NOW() WHERE id = ?");
        $update->execute([$score_percent, $attempt_id]);
        
        echo json_encode([
            'success' => true, 
            'score' => round($score_percent, 1),
            'points' => $total_score,
            'max_points' => $max_points,
            'passed' => $score_percent >= $attempt['passing_score']
        ]);
        break;
        
    // ========== СТАТИСТИКА ==========
    case 'get_user_stats':
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_attempts,
                COALESCE(AVG(score), 0) as avg_score,
                COALESCE(MAX(score), 0) as best_score
            FROM attempts 
            WHERE user_id = ? AND completed = 1
        ");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        break;
        
    case 'get_recent_attempts':
        $stmt = $db->prepare("
            SELECT a.*, q.title 
            FROM attempts a 
            JOIN quizzes q ON a.quiz_id = q.id 
            WHERE a.user_id = ? 
            ORDER BY a.started_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;
        
    // ========== ПОЛУЧЕНИЕ СОХРАНЕННЫХ ОТВЕТОВ ==========
    case 'get_saved_answers':
        $attempt_id = (int)$_GET['attempt_id'];
        
        // Проверяем права
        $check = $db->prepare("SELECT id FROM attempts WHERE id = ? AND user_id = ?");
        $check->execute([$attempt_id, $user_id]);
        if (!$check->fetch()) {
            echo json_encode([]);
            exit;
        }
        
        $stmt = $db->prepare("SELECT question_id, answer FROM user_answers WHERE attempt_id = ?");
        $stmt->execute([$attempt_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;
        
    // ========== СТАТИСТИКА ДЛЯ ПРЕПОДАВАТЕЛЯ ==========
    case 'get_teacher_stats':
        checkRole(['teacher', 'admin']);
        
        $quiz_id = (int)$_GET['quiz_id'];
        
        // Проверяем права доступа для преподавателя
        if ($role === 'teacher') {
            $check = $db->prepare("SELECT id FROM quizzes WHERE id = ? AND author_id = ?");
            $check->execute([$quiz_id, $user_id]);
            if (!$check->fetch()) {
                echo json_encode(['error' => 'Нет доступа']);
                exit;
            }
        }
        
        // Общая статистика по викторине
        $stats_stmt = $db->prepare("
            SELECT 
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
        
        // Попытки студентов
        $attempts_stmt = $db->prepare("
            SELECT 
                a.*,
                u.login,
                u.full_name,
                CASE 
                    WHEN a.score >= q.passing_score THEN 'Зачет'
                    WHEN a.score < q.passing_score THEN 'Незачет'
                    ELSE 'Не завершено'
                END as result_status,
                (
                    SELECT COUNT(*)
                    FROM user_answers ua2
                    JOIN questions q2 ON ua2.question_id = q2.id
                    WHERE ua2.attempt_id = a.id AND q2.quiz_id = q.id
                ) as total_questions,
                (
                    SELECT COALESCE(SUM(q2.points), 0)
                    FROM user_answers ua2
                    JOIN questions q2 ON ua2.question_id = q2.id
                    WHERE ua2.attempt_id = a.id AND ua2.is_correct = 1 AND q2.quiz_id = q.id
                ) as earned_points,
                (
                    SELECT COALESCE(SUM(q2.points), 0)
                    FROM questions q2
                    WHERE q2.quiz_id = q.id
                ) as max_points
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
        
        echo json_encode([
            'quiz_stats' => $quiz_stats,
            'attempts' => $attempts,
            'question_stats' => $question_stats
        ]);
        break;
        
    // ========== ЭКСПОРТ ДАННЫХ ==========
    case 'export_users':
        checkRole(['admin']);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="users_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Логин', 'Email', 'ФИО', 'Роль', 'Дата регистрации'], ';');
        
        $stmt = $db->query("SELECT id, login, email, full_name, role, created_at FROM users ORDER BY id");
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row, ';');
        }
        fclose($output);
        exit;
        break;
        
    case 'export_quiz_results':
        checkRole(['teacher', 'admin']);
        
        $quiz_id = (int)$_GET['quiz_id'];
        
        // Проверяем права доступа для преподавателя
        if ($role === 'teacher') {
            $check = $db->prepare("SELECT id FROM quizzes WHERE id = ? AND author_id = ?");
            $check->execute([$quiz_id, $user_id]);
            if (!$check->fetch()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Нет доступа']);
                exit;
            }
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="quiz_results_' . $quiz_id . '_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'ID попытки',
            'ФИО студента',
            'Логин студента',
            'Дата начала',
            'Дата завершения',
            'Результат (%)',
            'Баллы',
            'Макс. баллов',
            'Проходной балл (%)',
            'Статус'
        ], ';');
        
        $stmt = $db->prepare("
            SELECT 
                a.id,
                u.full_name,
                u.login,
                a.started_at,
                a.finished_at,
                a.score,
                q.passing_score,
                CASE 
                    WHEN a.score >= q.passing_score THEN 'Сдал'
                    ELSE 'Не сдал'
                END as status
            FROM attempts a
            JOIN users u ON a.user_id = u.id
            JOIN quizzes q ON a.quiz_id = q.id
            WHERE a.quiz_id = ? AND a.completed = 1
            ORDER BY a.finished_at DESC
        ");
        $stmt->execute([$quiz_id]);
        
        while($attempt = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Получаем детали по баллам
            $points_stmt = $db->prepare("
                SELECT 
                    COALESCE(SUM(q.points), 0) as max_points,
                    COALESCE(SUM(CASE WHEN ua.is_correct = 1 THEN q.points ELSE 0 END), 0) as earned_points
                FROM questions q
                LEFT JOIN user_answers ua ON q.id = ua.question_id AND ua.attempt_id = ?
                WHERE q.quiz_id = ?
            ");
            $points_stmt->execute([$attempt['id'], $quiz_id]);
            $points = $points_stmt->fetch(PDO::FETCH_ASSOC);
            
            fputcsv($output, [
                $attempt['id'],
                $attempt['full_name'],
                $attempt['login'],
                $attempt['started_at'],
                $attempt['finished_at'],
                $attempt['score'],
                $points['earned_points'],
                $points['max_points'],
                $attempt['passing_score'],
                $attempt['status']
            ], ';');
        }
        
        fclose($output);
        exit;
        break;
        
    // ========== ПРОСТАЯ ПРОВЕРКА ==========
    case 'ping':
        echo json_encode([
            'status' => 'ok', 
            'user_id' => $user_id, 
            'role' => $role,
            'time' => date('Y-m-d H:i:s')
        ]);
        break;
        
    // ========== ДОПОЛНИТЕЛЬНЫЕ ФУНКЦИИ ==========
    case 'get_quiz_info':
        $quiz_id = (int)$_GET['quiz_id'];
        
        $stmt = $db->prepare("
            SELECT q.*, u.full_name as author_name 
            FROM quizzes q 
            JOIN users u ON q.author_id = u.id 
            WHERE q.id = ?
        ");
        $stmt->execute([$quiz_id]);
        $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$quiz) {
            echo json_encode(['error' => 'Викторина не найдена']);
        } else {
            // Проверяем доступ
            if (($role === 'student' && !$quiz['is_published']) ||
                ($role === 'teacher' && $quiz['author_id'] != $user_id)) {
                echo json_encode(['error' => 'Нет доступа']);
            } else {
                echo json_encode($quiz);
            }
        }
        break;
        
    case 'get_attempt_info':
        $attempt_id = (int)$_GET['attempt_id'];
        
        $stmt = $db->prepare("
            SELECT a.*, q.title, q.passing_score 
            FROM attempts a 
            JOIN quizzes q ON a.quiz_id = q.id 
            WHERE a.id = ? AND a.user_id = ?
        ");
        $stmt->execute([$attempt_id, $user_id]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$attempt) {
            echo json_encode(['error' => 'Попытка не найдена']);
        } else {
            echo json_encode($attempt);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Неизвестное действие', 'available_actions' => [
            'get_quizzes',
            'get_my_quizzes',
            'get_questions',
            'save_answer',
            'submit_quiz',
            'get_user_stats',
            'get_recent_attempts',
            'get_saved_answers',
            'get_teacher_stats',
            'get_quiz_info',
            'get_attempt_info',
            'ping'
        ]]);
        break;
}
?>