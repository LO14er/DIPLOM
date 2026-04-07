<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $price = $_POST['price'];
    $brand = $_POST['brand'];
    $image = $_FILES['image']['name'];
    
    // Загрузка изображения
    if ($image) {
        $target_dir = "images/";
        $target_file = $target_dir . basename($image);
        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
    }
    
    $stmt = $pdo->prepare("INSERT INTO products (title, price, brand, image) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $price, $brand, $image]);
    
    echo "Товар добавлен! <a href='index.php'>Вернуться</a>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Добавить товар</title>
    <style>
        body { font-family: Arial; max-width: 500px; margin: 50px auto; }
        form { background: #f9f9f9; padding: 20px; border-radius: 10px; }
        input, button { width: 100%; padding: 10px; margin: 10px 0; }
        button { background: #005bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h2>Добавить товар</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="title" placeholder="Название" required>
        <input type="number" name="price" placeholder="Цена" required>
        <input type="text" name="brand" placeholder="Бренд">
        <input type="file" name="image" accept="image/*">
        <button type="submit">Добавить товар</button>
    </form>
    <p><a href="index.php">← Назад в каталог</a></p>
</body>
</html>