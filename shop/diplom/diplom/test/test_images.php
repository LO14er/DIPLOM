<?php
// test_images.php
require_once 'config.php';
$pdo = getDB();

// Получаем все товары
$products = $pdo->query("SELECT * FROM products")->fetchAll();

echo "<h1>Тестовый просмотр товаров</h1>";
echo "<p>Всего товаров в базе: " . count($products) . "</p>";

foreach ($products as $product) {
    echo "<div style='border:1px solid #ccc; margin:10px; padding:10px;'>";
    echo "<h3>" . htmlspecialchars($product['title']) . "</h3>";
    echo "<p>ID: " . $product['id'] . "</p>";
    echo "<p>Изображение: " . htmlspecialchars($product['image']) . "</p>";
    
    // Проверяем существование файла
    $image_path = 'images/' . $product['image'];
    if (file_exists($image_path)) {
        echo "<p style='color:green;'>✅ Файл существует: $image_path</p>";
        echo "<img src='$image_path' width='200'>";
    } else {
        echo "<p style='color:red;'>❌ Файл НЕ найден: $image_path</p>";
        // Показываем заглушку
        echo "<img src='https://via.placeholder.com/200x200?text=No+Image' width='200'>";
    }
    
    echo "</div>";
}
?>