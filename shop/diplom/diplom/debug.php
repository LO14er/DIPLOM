<?php
require 'config.php';

echo "<h2>Проверка данных в базе</h2>";

// Проверим структуру таблицы
$stmt = $pdo->query("DESCRIBE products");
echo "<h3>Структура таблицы products:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Поле</th><th>Тип</th><th>NULL</th><th>Ключ</th><th>По умолчанию</th></tr>";
while ($row = $stmt->fetch()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Покажем все товары
$stmt = $pdo->query("SELECT * FROM products");
echo "<h3>Все товары:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Название</th><th>Цена</th><th>Бренд</th><th>Фото</th></tr>";
while ($row = $stmt->fetch()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
    echo "<td>" . $row['price'] . " ₽</td>";
    echo "<td>" . htmlspecialchars($row['brand'] ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($row['image'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Проверим уникальные бренды
$stmt = $pdo->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL");
echo "<h3>Уникальные бренды в базе:</h3>";
echo "<ul>";
while ($row = $stmt->fetch()) {
    echo "<li>" . htmlspecialchars($row['brand']) . "</li>";
}
echo "</ul>";
?>