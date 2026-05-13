<?php
require_once 'config.php';
checkAuth();

$quiz_id = $_GET['id'] ?? 0;
$db = getDB();

// Получаем информацию о викторине
$quiz_stmt = $db->prepare("
    SELECT q.*, u.full_name as author_name 
    FROM quizzes q 
    JOIN users u ON q.author_id = u.id 
    WHERE q.id = ? AND (q.is_published = 1 OR q.author_id = ?)
");
$quiz_stmt->execute([$quiz_id, $_SESSION['user_id']]);
$quiz = $quiz_stmt->fetch(PDO::FETCH_ASSOC);

if(!$quiz) {
    die('Викторина не найдена или недоступна');
}

// Создаем новую попытку или продолжаем существующую
$attempt_stmt = $db->prepare("
    SELECT * FROM attempts 
    WHERE user_id = ? AND quiz_id = ? AND completed = 0 
    ORDER BY started_at DESC LIMIT 1
");
$attempt_stmt->execute([$_SESSION['user_id'], $quiz_id]);
$attempt = $attempt_stmt->fetch(PDO::FETCH_ASSOC);

if(!$attempt) {
    // Создаем новую попытку
    $db->prepare("INSERT INTO attempts (user_id, quiz_id) VALUES (?, ?)")
       ->execute([$_SESSION['user_id'], $quiz_id]);
    $attempt_id = $db->lastInsertId();
} else {
    $attempt_id = $attempt['id'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quiz['title']); ?> - QuizMaster</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php require_once 'index.php'; ?>
    
    <div class="container">
        <div class="quiz-header">
            <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
            <p><?php echo htmlspecialchars($quiz['description']); ?></p>
            <p>Автор: <?php echo htmlspecialchars($quiz['author_name']); ?></p>
            
            <?php if($quiz['time_limit']): ?>
                <div class="timer" id="timer">
                    Осталось времени: <span id="time-remaining"><?php echo gmdate("H:i:s", $quiz['time_limit']); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <div id="question-container" class="question-container">
            <!-- Вопросы будут загружены через AJAX -->
        </div>
        
        <div class="quiz-controls">
            <button id="prev-btn" class="btn" onclick="prevQuestion()">Назад</button>
            <button id="next-btn" class="btn" onclick="nextQuestion()">Вперед</button>
            <button id="submit-btn" class="btn btn-primary" onclick="submitQuiz()">Завершить тест</button>
        </div>
        
        <div class="progress-container">
            <div class="progress-bar" id="progress-bar"></div>
        </div>
    </div>
    
    <script>
        const quizId = <?php echo $quiz_id; ?>;
        const attemptId = <?php echo $attempt_id; ?>;
        const timeLimit = <?php echo $quiz['time_limit'] ?: 0; ?>;
        let currentQuestion = 0;
        let questions = [];
        let answers = {};
        
        // Загружаем вопросы
        async function loadQuestions() {
            try {
                const response = await fetch(`api.php?action=get_questions&quiz_id=${quizId}`);
                questions = await response.json();
                showQuestion(currentQuestion);
                updateProgress();
            } catch(error) {
                console.error('Ошибка загрузки вопросов:', error);
            }
        }
        
        // Показываем вопрос
        function showQuestion(index) {
            if(index < 0 || index >= questions.length) return;
            
            currentQuestion = index;
            const question = questions[index];
            const container = document.getElementById('question-container');
            
            let html = `
                <div class="question-card" data-question-id="${question.id}">
                    <h3>Вопрос ${index + 1} из ${questions.length}</h3>
                    <p class="question-text">${question.question_text}</p>
                    <p>Баллы: ${question.points}</p>
            `;
            
            // Генерация вариантов ответов в зависимости от типа вопроса
            if(question.question_type === 'single') {
                const options = JSON.parse(question.options);
                html += '<div class="options">';
                for(const [key, value] of Object.entries(options)) {
                    html += `
                        <label class="option">
                            <input type="radio" name="answer" value="${key}" 
                                   ${answers[question.id] === key ? 'checked' : ''}>
                            ${value}
                        </label>
                    `;
                }
                html += '</div>';
            } else if(question.question_type === 'multiple') {
                const options = JSON.parse(question.options);
                const selected = answers[question.id] ? JSON.parse(answers[question.id]) : [];
                html += '<div class="options">';
                for(const [key, value] of Object.entries(options)) {
                    html += `
                        <label class="option">
                            <input type="checkbox" name="answer" value="${key}"
                                   ${selected.includes(key) ? 'checked' : ''}>
                            ${value}
                        </label>
                    `;
                }
                html += '</div>';
            } else if(question.question_type === 'text') {
                html += `
                    <textarea class="text-answer" 
                              oninput="saveAnswer(${question.id}, this.value)">${answers[question.id] || ''}</textarea>
                `;
            }
            
            if(question.explanation) {
                html += `<div class="explanation">${question.explanation}</div>`;
            }
            
            html += '</div>';
            container.innerHTML = html;
            
            // Обновляем состояние кнопок
            document.getElementById('prev-btn').disabled = index === 0;
            document.getElementById('next-btn').disabled = index === questions.length - 1;
        }
        
        // Сохраняем ответ
        function saveAnswer(questionId, answer) {
            answers[questionId] = answer;
            
            // Сохраняем в БД через AJAX
            fetch('api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=save_answer&attempt_id=${attemptId}&question_id=${questionId}&answer=${encodeURIComponent(answer)}`
            });
        }
        
        // Навигация
        function nextQuestion() {
            saveCurrentAnswer();
            showQuestion(currentQuestion + 1);
            updateProgress();
        }
        
        function prevQuestion() {
            saveCurrentAnswer();
            showQuestion(currentQuestion - 1);
            updateProgress();
        }
        
        function saveCurrentAnswer() {
            const questionId = questions[currentQuestion].id;
            if(questions[currentQuestion].question_type === 'single') {
                const selected = document.querySelector('input[name="answer"]:checked');
                if(selected) saveAnswer(questionId, selected.value);
            } else if(questions[currentQuestion].question_type === 'multiple') {
                const selected = Array.from(document.querySelectorAll('input[name="answer"]:checked'))
                                     .map(cb => cb.value);
                saveAnswer(questionId, JSON.stringify(selected));
            }
        }
        
        // Обновление прогресса
        function updateProgress() {
            const progress = ((currentQuestion + 1) / questions.length) * 100;
            document.getElementById('progress-bar').style.width = progress + '%';
        }
        
        // Таймер
        if(timeLimit > 0) {
            let timeLeft = timeLimit;
            const timerInterval = setInterval(() => {
                timeLeft--;
                if(timeLeft <= 0) {
                    clearInterval(timerInterval);
                    submitQuiz();
                }
                document.getElementById('time-remaining').textContent = 
                    new Date(timeLeft * 1000).toISOString().substr(11, 8);
            }, 1000);
        }
        
        // Завершение теста
        async function submitQuiz() {
            saveCurrentAnswer();
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=submit_quiz&attempt_id=${attemptId}`
                });
                
                const result = await response.json();
                
                if(result.success) {
                    alert(`Тест завершен! Ваш результат: ${result.score}%`);
                    window.location.href = 'dashboard.php';
                }
            } catch(error) {
                console.error('Ошибка:', error);
            }
        }
        
        // Загружаем вопросы при загрузке страницы
        document.addEventListener('DOMContentLoaded', loadQuestions);
    </script>
</body>
</html>