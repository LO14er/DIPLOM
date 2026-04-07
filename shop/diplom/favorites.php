<?php
require_once 'config.php';

$fav_ids = $_SESSION['favorites'] ?? [];
$products = [];

if (!empty($fav_ids)) {
    $placeholders = implode(',', array_fill(0, count($fav_ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($fav_ids);
    $products = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Избранное - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Избранное (<?= count($products) ?>)</h1>

        <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <i class="far fa-heart fa-5x text-muted mb-4"></i>
                <h3>В избранном пока пусто</h3>
                <a href="index.php" class="btn btn-primary mt-3">Добавить товары</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <?php include 'templates/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>