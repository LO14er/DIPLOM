<?php
require '../config.php';

// Очень простая защита (в реальном проекте используйте логин/пароль + сессии!)
$secret = 'мойпароль123'; // ← ИЗМЕНИТЕ ОБЯЗАТЕЛЬНО!
if (!isset($_GET['pass']) || $_GET['pass'] !== $secret) {
    die('<h2>Доступ запрещён</h2><p>Введите правильный пароль в адресе: admin/index.php?pass=мойпароль123</p>');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Админка — добавление товаров</title>
  <style>
    body { font-family: Arial; margin: 20px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ccc; padding: 8px; }
    form { margin: 30px 0; }
  </style>
</head>
<body>

<h1>Админ-панель</h1>

<h2>Добавить новый товар</h2>

<form action="add.php" method="post" enctype="multipart/form-data">
    <p>Название:<br>
    <input type="text" name="title" required style="width:100%; padding:8px;"></p>
    
    <p>Цена (руб):<br>
    <input type="number" name="price" step="0.01" required style="width:100%; padding:8px;"></p>
    
    <p>Описание:<br>
    <textarea name="description" rows="6" style="width:100%; padding:8px;"></textarea></p>
    
    <p>Фото товара:<br>
    <input type="file" name="image" accept="image/*"></p>
    
    <button type="submit" style="padding:12px 30px; font-size:1.1em;">Добавить товар</button>
</form>

<hr>

<h2>Существующие товары</h2>

<table>
<tr>
    <th>ID</th>
    <th>Название</th>
    <th>Цена</th>
    <th>Дата</th>
</tr>
<?php
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
    echo "<td>" . number_format($row['price'], 0, '', ' ') . " ₽</td>";
    echo "<td>{$row['created_at']}</td>";
    echo "</tr>";
}
?>
</table>

</body>
</html>