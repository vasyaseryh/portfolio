<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizMaster - Образовательные викторины</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">QuizMaster</div>
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="user-menu">
                    <span><?php echo $_SESSION['full_name']; ?> (<?php echo $_SESSION['role']; ?>)</span>
                    <a href="dashboard.php">Кабинет</a>
                    <?php if($_SESSION['role'] == 'teacher'): ?>
                        <a href="create_quiz.php">Создать викторину</a>
			<a href="teacher_stats.php">Статистика</a> <!-- Новая ссылка -->
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

    <main class="container">
        <?php if(!isset($_SESSION['user_id'])): ?>
            <section class="hero">
                <h1>Добро пожаловать в QuizMaster!</h1>
                <p>Платформа для создания и прохождения образовательных викторин</p>
                <a href="auth.php" class="btn">Начать</a>
            </section>
        <?php else: ?>
            <section class="dashboard">
                <h2>Доступные викторины</h2>
                <div id="quizzes-list" class="quizzes-grid"></div>
            </section>
        <?php endif; ?>
    </main>

    <script src="script.js"></script>
    <script>
        // Загружаем список викторин
        if(document.getElementById('quizzes-list')) {
            loadQuizzes();
        }
        
        async function loadQuizzes() {
            try {
                const response = await fetch('api.php?action=get_quizzes');
                const quizzes = await response.json();
                const container = document.getElementById('quizzes-list');
                
                quizzes.forEach(quiz => {
                    const quizCard = `
                        <div class="quiz-card">
                            <h3>${quiz.title}</h3>
                            <p>${quiz.description || ''}</p>
                            <p><small>Вопросов: ${quiz.question_count}</small></p>
                            <a href="quiz.php?id=${quiz.id}" class="btn">Начать</a>
                        </div>
                    `;
                    container.innerHTML += quizCard;
                });
            } catch(error) {
                console.error('Ошибка загрузки викторин:', error);
            }
        }
    </script>
</body>
</html>