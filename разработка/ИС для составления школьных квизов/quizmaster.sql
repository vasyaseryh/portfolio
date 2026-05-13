-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Май 13 2026 г., 18:25
-- Версия сервера: 8.0.30
-- Версия PHP: 8.1.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `quizmaster`
--

-- --------------------------------------------------------

--
-- Структура таблицы `attempts`
--

CREATE TABLE `attempts` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `quiz_id` int NOT NULL,
  `started_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `finished_at` timestamp NULL DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `completed` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `attempts`
--

INSERT INTO `attempts` (`id`, `user_id`, `quiz_id`, `started_at`, `finished_at`, `score`, `completed`) VALUES
(9, 8, 5, '2026-02-03 11:05:09', '2026-02-03 11:05:14', '50.00', 1),
(10, 6, 5, '2026-02-03 11:05:50', '2026-02-03 11:05:54', '100.00', 1),
(11, 8, 6, '2026-02-03 11:46:08', '2026-02-03 11:46:18', '50.00', 1),
(12, 6, 6, '2026-02-03 11:46:37', '2026-02-03 11:46:45', '0.00', 1),
(13, 8, 5, '2026-02-22 13:43:05', '2026-02-22 13:43:13', '0.00', 1),
(14, 6, 6, '2026-03-18 20:17:25', '2026-03-18 20:17:58', '0.00', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `questions`
--

CREATE TABLE `questions` (
  `id` int NOT NULL,
  `quiz_id` int NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('single','multiple','matching','text') NOT NULL,
  `points` int DEFAULT '1',
  `options` json DEFAULT NULL COMMENT 'Варианты ответов, например {"А": "Текст", "В": "Текст2"}',
  `correct_answers` json DEFAULT NULL COMMENT 'Правильные ответы, например ["А"] или {"А": "1", "B": "2"}',
  `explanation` text COMMENT 'Пояснение после ответа'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `questions`
--

INSERT INTO `questions` (`id`, `quiz_id`, `question_text`, `question_type`, `points`, `options`, `correct_answers`, `explanation`) VALUES
(17, 6, 'Как называется угол больше 90 градусов', 'single', 1, '{\"A\": \"прямой\", \"B\": \"тупой\", \"C\": \"острый\"}', '[]', ''),
(18, 6, 'Четырёхугольник, у которого противолежащие стороны попарно параллельны', 'single', 1, '{\"A\": \"Треугольник\", \"B\": \"Параллелограмм\", \"C\": \"Круг\"}', '[\"B\"]', ''),
(19, 5, '1+1', 'single', 1, '[\"2\", \"3\", \"4\", \"5\"]', '[]', ''),
(20, 5, '3-1', 'single', 1, '[\"2\", \"3\", \"4\", \"5\"]', '[]', ''),
(21, 5, '5+5', 'single', 1, '{\"A\": \"5\", \"B\": \"10\"}', '[]', '');

-- --------------------------------------------------------

--
-- Структура таблицы `question_statistics`
--

CREATE TABLE `question_statistics` (
  `id` int NOT NULL,
  `question_id` int NOT NULL,
  `total_attempts` int DEFAULT '0',
  `correct_attempts` int DEFAULT '0',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `question_statistics`
--

INSERT INTO `question_statistics` (`id`, `question_id`, `total_attempts`, `correct_attempts`, `last_updated`) VALUES
(20, 17, 1, 0, '2026-02-03 11:46:14'),
(21, 18, 1, 0, '2026-02-03 11:46:18'),
(22, 17, 1, 0, '2026-02-03 11:46:43'),
(23, 18, 1, 0, '2026-02-03 11:46:45'),
(26, 17, 1, 0, '2026-03-18 20:17:56'),
(27, 18, 1, 0, '2026-03-18 20:17:58');

-- --------------------------------------------------------

--
-- Структура таблицы `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text,
  `category_id` int DEFAULT NULL,
  `author_id` int NOT NULL,
  `time_limit` int DEFAULT NULL COMMENT 'В секундах',
  `max_attempts` int DEFAULT '1',
  `passing_score` int DEFAULT '60',
  `is_published` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `quizzes`
--

INSERT INTO `quizzes` (`id`, `title`, `description`, `category_id`, `author_id`, `time_limit`, `max_attempts`, `passing_score`, `is_published`, `created_at`) VALUES
(5, 'математика', 'викторина по математике', NULL, 7, 600, 1, 60, 1, '2026-02-03 10:53:09'),
(6, 'Геометрия', 'Викторина по геометрии', NULL, 9, 600, 1, 60, 1, '2026-02-03 11:45:38');

-- --------------------------------------------------------

--
-- Структура таблицы `quiz_categories`
--

CREATE TABLE `quiz_categories` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `quiz_categories`
--

INSERT INTO `quiz_categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Общие знания', 'Тесты на общую эрудицию', '2026-02-03 09:13:48');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `login` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL DEFAULT 'student',
  `full_name` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `login`, `email`, `password_hash`, `role`, `full_name`, `created_at`) VALUES
(6, 'admin', 'admin@quizmaster.ru', '$2y$10$GZP7je/exEYvXl2Dz8hDkeCk3IaNa09P0T3QqYxyJYmsmQ8Ec8peO', 'admin', 'Администратор системы', '2026-02-03 09:50:41'),
(7, 'teacher', 'teacher@gmail.com', '$2y$10$GZP7je/exEYvXl2Dz8hDkeCk3IaNa09P0T3QqYxyJYmsmQ8Ec8peO', 'teacher', 'Иванова Екатерина Борисовна', '2026-02-03 10:05:33'),
(8, 'student', 'student@gmail.com', '$2y$10$GZP7je/exEYvXl2Dz8hDkeCk3IaNa09P0T3QqYxyJYmsmQ8Ec8peO', 'student', 'Дубин Альберт Игоревич', '2026-02-03 10:07:02'),
(9, 'teacher2', 'teacher2@gmail.com', '$2y$10$GZP7je/exEYvXl2Dz8hDkeCk3IaNa09P0T3QqYxyJYmsmQ8Ec8peO', 'teacher', 'Шумина Зоя Ивановна', '2026-02-03 11:41:43');

-- --------------------------------------------------------

--
-- Структура таблицы `user_answers`
--

CREATE TABLE `user_answers` (
  `id` int NOT NULL,
  `attempt_id` int NOT NULL,
  `question_id` int NOT NULL,
  `answer` text,
  `is_correct` tinyint(1) DEFAULT NULL,
  `checked_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `user_answers`
--

INSERT INTO `user_answers` (`id`, `attempt_id`, `question_id`, `answer`, `is_correct`, `checked_at`) VALUES
(59, 11, 17, 'B', 0, '2026-02-03 11:46:18'),
(60, 11, 18, 'B', 1, '2026-02-03 11:46:18'),
(61, 12, 17, 'A', 0, '2026-02-03 11:46:45'),
(62, 12, 18, 'C', 0, '2026-02-03 11:46:45'),
(65, 14, 17, 'B', 0, '2026-03-18 20:17:58'),
(66, 14, 18, 'C', 0, '2026-03-18 20:17:58');

--
-- Триггеры `user_answers`
--
DELIMITER $$
CREATE TRIGGER `update_question_stats` AFTER INSERT ON `user_answers` FOR EACH ROW BEGIN
    INSERT INTO question_statistics (question_id, total_attempts, correct_attempts)
    VALUES (NEW.question_id, 1, IF(NEW.is_correct = 1, 1, 0))
    ON DUPLICATE KEY UPDATE 
        total_attempts = total_attempts + 1,
        correct_attempts = correct_attempts + IF(NEW.is_correct = 1, 1, 0);
END
$$
DELIMITER ;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `attempts`
--
ALTER TABLE `attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `idx_user_quiz` (`user_id`,`quiz_id`),
  ADD KEY `idx_started` (`started_at`),
  ADD KEY `idx_attempts_completed` (`completed`);

--
-- Индексы таблицы `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_questions_quiz` (`quiz_id`);

--
-- Индексы таблицы `question_statistics`
--
ALTER TABLE `question_statistics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Индексы таблицы `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_quizzes_published` (`is_published`);

--
-- Индексы таблицы `quiz_categories`
--
ALTER TABLE `quiz_categories`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Индексы таблицы `user_answers`
--
ALTER TABLE `user_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_answer` (`attempt_id`,`question_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `idx_user_answers_attempt` (`attempt_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `attempts`
--
ALTER TABLE `attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT для таблицы `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT для таблицы `question_statistics`
--
ALTER TABLE `question_statistics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT для таблицы `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `quiz_categories`
--
ALTER TABLE `quiz_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `user_answers`
--
ALTER TABLE `user_answers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `attempts`
--
ALTER TABLE `attempts`
  ADD CONSTRAINT `attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attempts_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `question_statistics`
--
ALTER TABLE `question_statistics`
  ADD CONSTRAINT `question_statistics_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quizzes_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `quiz_categories` (`id`);

--
-- Ограничения внешнего ключа таблицы `user_answers`
--
ALTER TABLE `user_answers`
  ADD CONSTRAINT `user_answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
