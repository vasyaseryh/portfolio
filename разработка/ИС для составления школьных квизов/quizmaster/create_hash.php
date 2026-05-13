<?php
// create_hash.php
$password = '12345';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Пароль: <strong>{$password}</strong><br>";
echo "Хэш: <code>{$hash}</code><br><br>";

// Проверим что хэш работает
if (password_verify($password, $hash)) {
    echo "✅ Хэш работает правильно!";
} else {
    echo "❌ Хэш не работает!";
}
?>