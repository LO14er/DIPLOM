<?php
// cart.php
require_once 'config.php';  // ← подключаем config один раз — session_start() и всё остальное уже там

$pdo = getDB();

// Получаем товары из сессии (самый простой и надёжный способ сейчас)
$cart_items = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        $stmt = $pdo->prepare("
            SELECT id, title, brand, price, image
            FROM products 
            WHERE id IN ($placeholders)
        ");
        $stmt->execute($ids);
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $p) {
            $qty = (int)($_SESSION['cart'][$p['id']] ?? 0);
            if ($qty > 0) {
                $cart_items[] = [
                    'id'       => $p['id'],
                    'title'    => $p['title'],
                    'brand'    => $p['brand'] ?? '',
                    'price'    => $p['price'],
                    'image'    => $p['image'] ?? 'default.jpg',
                    'quantity' => $qty,
                    'subtotal' => $p['price'] * $qty
                ];
                $total += $p['price'] * $qty;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'templates/header.php'; // или как у тебя подключается шапка ?>

    <main class="main-content">
        <div class="container">
            <h1>Корзина</h1>

            <?php if (empty($cart_items)): ?>
                <div class="empty-cart" style="text-align:center; padding:60px 20px;">
                    <i class="fas fa-shopping-cart fa-5x" style="color:#ccc; margin-bottom:20px;"></i>
                    <h3>Корзина пуста</h3>
                    <p>Добавьте товары, чтобы оформить заказ</p>
                    <a href="index.php" class="btn btn-primary" style="padding:12px 30px; font-size:18px;">
                        Продолжить покупки
                    </a>
                </div>
            <?php else: ?>
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item" style="display:flex; align-items:center; border-bottom:1px solid #eee; padding:20px 0; gap:20px;">
                            <img src="<?= getProductImage($item['image']) ?>" 
                                 alt="<?= e($item['title']) ?>" 
                                 style="width:100px; height:100px; object-fit:contain; border-radius:8px;">
                            
                            <div style="flex:1;">
                                <h4 style="margin:0 0 8px;"><?= e($item['title']) ?></h4>
                                <p style="margin:0; color:#666;"><?= e($item['brand']) ?> • <?= number_format($item['price'], 0, '', ' ') ?> ₽</p>
                            </div>
                            
                            <div class="quantity-controls" style="display:flex; align-items:center; gap:10px;">
                                <button class="qty-btn" onclick="updateCart(<?= $item['id'] ?>, 'remove')">-</button>
                                <span style="min-width:40px; text-align:center;"><?= $item['quantity'] ?></span>
                                <button class="qty-btn" onclick="updateCart(<?= $item['id'] ?>, 'add')">+</button>
                            </div>
                            
                            <div style="min-width:120px; text-align:right; font-weight:bold;">
                                <?= number_format($item['subtotal'], 0, '', ' ') ?> ₽
                            </div>
                            
                            <button class="btn-remove" onclick="deleteFromCart(<?= $item['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary" style="margin-top:30px; text-align:right;">
                    <h3 style="margin:0 0 15px;">Итого: <?= number_format($total, 0, '', ' ') ?> ₽</h3>
                    
                    <div style="display:flex; justify-content:flex-end; gap:15px;">
                        <a href="checkout.php" class="btn btn-primary" style="padding:14px 40px; font-size:18px;">
                            <i class="fas fa-credit-card"></i> Оформить заказ
                        </a>
                        <button class="btn btn-outline" onclick="clearCart()" style="padding:14px 30px;">
                            Очистить корзину
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'templates/footer.php'; // или как у тебя подвал ?>

    <script>
    function updateCart(productId, action) {
        fetch('ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=${action}_to_cart&product_id=${productId}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
        });
    }

    function deleteFromCart(productId) {
        if (!confirm('Удалить товар?')) return;
        fetch('ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_from_cart&product_id=${productId}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
        });
    }

    function clearCart() {
        if (!confirm('Очистить всю корзину?')) return;
        fetch('ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=clear_cart'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
        });
    }
    </script>
</body>
</html>