<?php
require_once 'config.php';
checkRole(['teacher', 'admin']);

$db = getDB();
$quiz_id = $_GET['id'] ?? 0;
$quiz = null;
$questions = [];

if($quiz_id) {
    if ($_SESSION['role'] === 'teacher') {
        $stmt = $db->prepare("SELECT * FROM quizzes WHERE id = ? AND author_id = ?");
        $stmt->execute([$quiz_id, $_SESSION['user_id']]);
    } else {
        $stmt = $db->prepare("SELECT * FROM quizzes WHERE id = ?");
        $stmt->execute([$quiz_id]);
    }
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Загружаем существующие вопросы
    if ($quiz) {
        $questions_stmt = $db->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id");
        $questions_stmt->execute([$quiz_id]);
        $questions = $questions_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $time_limit = !empty($_POST['time_limit']) ? (int)$_POST['time_limit'] : null;
    $max_attempts = !empty($_POST['max_attempts']) ? (int)$_POST['max_attempts'] : 1;
    $passing_score = !empty($_POST['passing_score']) ? (int)$_POST['passing_score'] : 60;
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    // ВАЖНОЕ ИСПРАВЛЕНИЕ: проверяем что поля не пустые
    if (empty(trim($title))) {
        die("<script>alert('Название викторины обязательно для заполнения'); window.history.back();</script>");
    }
    
    if($quiz) {
        // Обновление существующей викторины
        if ($_SESSION['role'] === 'teacher') {
            $stmt = $db->prepare("
                UPDATE quizzes SET 
                title = ?, description = ?, time_limit = ?, 
                max_attempts = ?, passing_score = ?, is_published = ?
                WHERE id = ? AND author_id = ?
            ");
            $stmt->execute([
                $title, $description, $time_limit, 
                $max_attempts, $passing_score, $is_published,
                $quiz_id, $_SESSION['user_id']
            ]);
        } else {
            $stmt = $db->prepare("
                UPDATE quizzes SET 
                title = ?, description = ?, time_limit = ?, 
                max_attempts = ?, passing_score = ?, is_published = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $title, $description, $time_limit, 
                $max_attempts, $passing_score, $is_published,
                $quiz_id
            ]);
        }
    } else {
        // Создание новой викторины
        $stmt = $db->prepare("
            INSERT INTO quizzes (title, description, author_id, time_limit, max_attempts, passing_score, is_published)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $title, $description, $_SESSION['user_id'], 
            $time_limit, $max_attempts, $passing_score, $is_published
        ]);
        $quiz_id = $db->lastInsertId();
    }
    
    // Обработка вопросов
    if(isset($_POST['questions']) && is_array($_POST['questions'])) {
        // Сначала удаляем все старые вопросы
        $delete_stmt = $db->prepare("DELETE FROM questions WHERE quiz_id = ?");
        $delete_stmt->execute([$quiz_id]);
        
        foreach($_POST['questions'] as $q_index => $question_data) {
            // Проверяем что текст вопроса не пустой
            if(empty(trim($question_data['text'] ?? ''))) {
                continue;
            }
            
            $question_text = sanitize($question_data['text']);
            $question_type = $question_data['type'] ?? 'single';
            $points = isset($question_data['points']) ? (int)$question_data['points'] : 1;
            $explanation = sanitize($question_data['explanation'] ?? '');
            
            // Обрабатываем варианты ответов
            $options = [];
            $correct_answers = [];
            
            if(isset($question_data['options']) && is_array($question_data['options'])) {
                foreach($question_data['options'] as $key => $value) {
                    if(!empty(trim($value))) {
                        $options[$key] = sanitize($value);
                    }
                }
            }
            
            // Обрабатываем правильные ответы
            if($question_type === 'single') {
                if(isset($question_data['correct_single']) && !empty(trim($question_data['correct_single']))) {
                    $correct_answers = [trim($question_data['correct_single'])];
                }
            } elseif($question_type === 'multiple') {
                if(isset($question_data['correct_multiple']) && is_array($question_data['correct_multiple'])) {
                    $correct_answers = array_filter($question_data['correct_multiple'], function($v) {
                        return !empty(trim($v));
                    });
                }
            } elseif($question_type === 'text') {
                if(isset($question_data['correct_text']) && !empty(trim($question_data['correct_text']))) {
                    $answers = array_map('trim', explode(',', $question_data['correct_text']));
                    $correct_answers = array_filter($answers, function($v) {
                        return !empty($v);
                    });
                }
            }
            
            // Подготавливаем данные для JSON
            $options_json = !empty($options) ? json_encode($options, JSON_UNESCAPED_UNICODE) : '{}';
            $correct_answers_json = !empty($correct_answers) ? json_encode($correct_answers, JSON_UNESCAPED_UNICODE) : '[]';
            
            // Добавляем вопрос
            $insert_stmt = $db->prepare("
                INSERT INTO questions (quiz_id, question_text, question_type, points, options, correct_answers, explanation)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insert_stmt->execute([
                $quiz_id,
                $question_text,
                $question_type,
                $points,
                $options_json,
                $correct_answers_json,
                $explanation
            ]);
        }
    }
    
    echo '<script>alert("Викторина сохранена!"); window.location.href = "create_quiz.php?id=' . $quiz_id . '";</script>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $quiz ? 'Редактирование' : 'Создание'; ?> викторины - QuizMaster</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .question-editor {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid #dee2e6;
        }
        
        .option-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        
        .option-row input[type="text"] {
            flex: 1;
        }
        
        .question-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .btn-small {
            padding: 5px 10px;
            font-size: 14px;
        }
        
        .btn-danger {
            background: #e74c3c;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>
    <?php require_once 'index.php'; ?>
    
    <div class="container">
        <h1><?php echo $quiz ? 'Редактирование викторины' : 'Создание новой викторины'; ?></h1>
        
        <?php if(!$quiz && $_SESSION['role'] === 'teacher'): ?>
            <div class="alert info">
                <p><strong>Важно:</strong> Чтобы студенты могли видеть и проходить вашу викторину, отметьте её как "Опубликована" после создания.</p>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="quiz-form">
            <div class="form-group">
                <label>Название викторины *:</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($quiz['title'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Описание:</label>
                <textarea name="description" rows="3"><?php echo htmlspecialchars($quiz['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Лимит времени (секунды):</label>
                    <input type="number" name="time_limit" value="<?php echo $quiz['time_limit'] ?? ''; ?>" min="0">
                </div>
                
                <div class="form-group">
                    <label>Максимум попыток:</label>
                    <input type="number" name="max_attempts" value="<?php echo $quiz['max_attempts'] ?? 1; ?>" min="1">
                </div>
                
                <div class="form-group">
                    <label>Проходной балл (%):</label>
                    <input type="number" name="passing_score" value="<?php echo $quiz['passing_score'] ?? 60; ?>" min="0" max="100">
                </div>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_published" value="1" <?php echo ($quiz['is_published'] ?? 0) ? 'checked' : ''; ?>>
                    <strong>Опубликовать викторину</strong> (студенты и админы смогут её видеть)
                </label>
            </div>
            
            <h2>Вопросы</h2>
            <div id="questions-container">
                <!-- Вопросы будут добавляться динамически -->
            </div>
            
            <button type="button" class="btn" onclick="addQuestion()">Добавить вопрос</button>
            <button type="submit" class="btn btn-primary">Сохранить викторину</button>
        </form>
    </div>
    
    <script>
        let questionCount = <?php echo count($questions); ?>;
        let currentQuestions = <?php echo json_encode($questions, JSON_UNESCAPED_UNICODE); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            if (currentQuestions.length > 0) {
                currentQuestions.forEach((question, index) => {
                    loadQuestionFromData(question, index + 1);
                });
                questionCount = currentQuestions.length;
            } else {
                addQuestion();
            }
        });
        
        function loadQuestionFromData(questionData, questionNum) {
            const container = document.getElementById('questions-container');
            
            // Декодируем JSON данные
            const options = questionData.options ? JSON.parse(questionData.options) : {};
            const correctAnswers = questionData.correct_answers ? JSON.parse(questionData.correct_answers) : [];
            
            let optionsHtml = '';
            let correctSingle = '';
            let correctMultiple = [];
            
            if (questionData.question_type === 'single') {
                Object.entries(options).forEach(([key, value], index) => {
                    const isCorrect = correctAnswers.includes(key);
                    if (isCorrect) correctSingle = key;
                    
                    optionsHtml += `
                        <div class="option-row">
                            <input type="radio" name="questions[${questionNum}][correct_single]" value="${key}" ${isCorrect ? 'checked' : ''}>
                            <input type="text" name="questions[${questionNum}][options][${key}]" value="${value}" placeholder="Текст варианта">
                            <button type="button" class="btn btn-small btn-danger" onclick="removeOption(this)">Удалить</button>
                        </div>
                    `;
                });
            } else if (questionData.question_type === 'multiple') {
                Object.entries(options).forEach(([key, value], index) => {
                    const isCorrect = correctAnswers.includes(key);
                    if (isCorrect) correctMultiple.push(key);
                    
                    optionsHtml += `
                        <div class="option-row">
                            <input type="checkbox" name="questions[${questionNum}][correct_multiple][]" value="${key}" ${isCorrect ? 'checked' : ''}>
                            <input type="text" name="questions[${questionNum}][options][${key}]" value="${value}" placeholder="Текст варианта">
                            <button type="button" class="btn btn-small btn-danger" onclick="removeOption(this)">Удалить</button>
                        </div>
                    `;
                });
            } else if (questionData.question_type === 'text') {
                optionsHtml = `
                    <div class="form-group">
                        <label>Правильные ответы (через запятую):</label>
                        <input type="text" name="questions[${questionNum}][correct_text]" value="${correctAnswers.join(', ')}">
                    </div>
                `;
            }
            
            const questionHtml = `
                <div class="question-editor" id="question-${questionNum}">
                    <h3>Вопрос ${questionNum}</h3>
                    
                    <div class="form-group">
                        <label>Текст вопроса *:</label>
                        <textarea name="questions[${questionNum}][text]" rows="2" required>${questionData.question_text}</textarea>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Тип вопроса:</label>
                            <select name="questions[${questionNum}][type]" onchange="changeQuestionType(${questionNum})">
                                <option value="single" ${questionData.question_type === 'single' ? 'selected' : ''}>Один верный ответ</option>
                                <option value="multiple" ${questionData.question_type === 'multiple' ? 'selected' : ''}>Несколько верных ответов</option>
                                <option value="text" ${questionData.question_type === 'text' ? 'selected' : ''}>Текстовый ответ</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Баллы:</label>
                            <input type="number" name="questions[${questionNum}][points]" value="${questionData.points || 1}" min="1">
                        </div>
                    </div>
                    
                    <div id="options-container-${questionNum}">
                        <h4>Варианты ответов:</h4>
                        ${optionsHtml}
                        ${questionData.question_type !== 'text' ? '<button type="button" class="btn btn-small" onclick="addOption(' + questionNum + ')">Добавить вариант</button>' : ''}
                    </div>
                    
                    <div class="form-group">
                        <label>Пояснение (показывается после ответа):</label>
                        <textarea name="questions[${questionNum}][explanation]" rows="2">${questionData.explanation || ''}</textarea>
                    </div>
                    
                    <div class="question-actions">
                        <button type="button" class="btn btn-small btn-danger" onclick="removeQuestion(${questionNum})">Удалить вопрос</button>
                    </div>
                </div>
            `;
            
            container.innerHTML += questionHtml;
        }
        
        function addQuestion() {
            questionCount++;
            const container = document.getElementById('questions-container');
            
            const questionHtml = `
                <div class="question-editor" id="question-${questionCount}">
                    <h3>Вопрос ${questionCount}</h3>
                    
                    <div class="form-group">
                        <label>Текст вопроса *:</label>
                        <textarea name="questions[${questionCount}][text]" rows="2" required></textarea>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Тип вопроса:</label>
                            <select name="questions[${questionCount}][type]" onchange="changeQuestionType(${questionCount})">
                                <option value="single">Один верный ответ</option>
                                <option value="multiple">Несколько верных ответов</option>
                                <option value="text">Текстовый ответ</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Баллы:</label>
                            <input type="number" name="questions[${questionCount}][points]" value="1" min="1">
                        </div>
                    </div>
                    
                    <div id="options-container-${questionCount}">
                        <!-- Опции будут добавляться динамически -->
                    </div>
                    
                    <div class="form-group">
                        <label>Пояснение (показывается после ответа):</label>
                        <textarea name="questions[${questionCount}][explanation]" rows="2"></textarea>
                    </div>
                    
                    <div class="question-actions">
                        <button type="button" class="btn btn-small btn-danger" onclick="removeQuestion(${questionCount})">Удалить вопрос</button>
                    </div>
                </div>
            `;
            
            container.innerHTML += questionHtml;
            changeQuestionType(questionCount);
        }
        
        function changeQuestionType(questionNum) {
            const type = document.querySelector(`select[name="questions[${questionNum}][type]"]`).value;
            const container = document.getElementById(`options-container-${questionNum}`);
            
            if (type === 'text') {
                container.innerHTML = `
                    <div class="form-group">
                        <label>Правильные ответы (через запятую):</label>
                        <input type="text" name="questions[${questionNum}][correct_text]" placeholder="Например: Москва, москва">
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <h4>Варианты ответов:</h4>
                    <div id="options-list-${questionNum}"></div>
                    <button type="button" class="btn btn-small" onclick="addOption(${questionNum})">Добавить вариант</button>
                `;
                addOption(questionNum);
                addOption(questionNum);
            }
        }
        
        function addOption(questionNum) {
            const type = document.querySelector(`select[name="questions[${questionNum}][type]"]`).value;
            const container = document.getElementById(`options-list-${questionNum}`);
            if (!container) return;
            
            const optionCount = container.children.length;
            const key = String.fromCharCode(65 + optionCount); // A, B, C, ...
            
            let inputType = type === 'single' ? 'radio' : 'checkbox';
            let inputName = type === 'single' ? `questions[${questionNum}][correct_single]` : `questions[${questionNum}][correct_multiple][]`;
            
            const optionHtml = `
                <div class="option-row">
                    <input type="${inputType}" name="${inputName}" value="${key}">
                    <input type="text" name="questions[${questionNum}][options][${key}]" placeholder="Текст варианта">
                    <button type="button" class="btn btn-small btn-danger" onclick="removeOption(this)">Удалить</button>
                </div>
            `;
            
            container.innerHTML += optionHtml;
        }
        
        function removeOption(button) {
            button.parentElement.remove();
        }
        
        function removeQuestion(questionNum) {
            if (confirm('Удалить этот вопрос?')) {
                document.getElementById(`question-${questionNum}`).remove();
                // Перенумеровываем оставшиеся вопросы
                const questions = document.querySelectorAll('.question-editor');
                questions.forEach((question, index) => {
                    const newNum = index + 1;
                    question.id = `question-${newNum}`;
                    question.querySelector('h3').textContent = `Вопрос ${newNum}`;
                    
                    // Обновляем все input names
                    const inputs = question.querySelectorAll('[name^="questions["]');
                    inputs.forEach(input => {
                        const name = input.name.replace(/questions\[\d+\]/, `questions[${newNum}]`);
                        input.name = name;
                    });
                });
                questionCount = questions.length;
            }
        }
        
        // Проверка формы перед отправкой
        document.getElementById('quiz-form').addEventListener('submit', function(e) {
            const questions = document.querySelectorAll('.question-editor');
            if (questions.length === 0) {
                e.preventDefault();
                alert('Добавьте хотя бы один вопрос');
                return;
            }
            
            let hasErrors = false;
            questions.forEach((question, index) => {
                const questionText = question.querySelector('textarea[name*="[text]"]').value.trim();
                if (!questionText) {
                    e.preventDefault();
                    alert(`Вопрос ${index + 1}: Введите текст вопроса`);
                    hasErrors = true;
                }
            });
            
            if (hasErrors) {
                return;
            }
        });
    </script>
</body>
</html>