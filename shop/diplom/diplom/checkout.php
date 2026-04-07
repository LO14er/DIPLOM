<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Оформление заказа - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Оформление заказа</h1>
        <p>Здесь будет форма: адрес доставки, способ оплаты, комментарий и т.д.</p>
        
        <!-- Пока просто заглушка -->
        <div style="margin-top: 40px; text-align: center;">
            <a href="success.php" class="btn btn-primary btn-large">
                Подтвердить и оплатить
            </a>
        </div>
    </div>
</body>
</html>