<!DOCTYPE html>
<html lang="ru" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина - <?= SITE_NAME ?></title>
    
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <!-- Здесь можно вставить твою шапку сайта позже -->
    <header style="background:#0056ff; color:white; padding:15px; text-align:center;">
        <div class="container">
            <h1><a href="index.php" style="color:white; text-decoration:none;"><?= SITE_NAME ?></a></h1>
            <nav>
                <a href="index.php" style="color:white; margin:0 15px;">Главная</a>
                <a href="cart.php" style="color:white; margin:0 15px;">Корзина</a>
                <!-- Добавь другие ссылки -->
            </nav>
        </div>
    </header>