<?php
// config.php
session_start();  // ← только здесь, один раз на всё приложение!
// Путь к папке с изображениями товаров (от корня сайта)
define('PRODUCT_IMAGES_DIR', 'shop/diplom/diplom/images/');
// Настройки БД
define('DB_HOST', 'localhost');
define('DB_NAME', 'shop');
define('DB_USER', 'root');
define('DB_PASS', '1111');

// Настройки сайта
define('SITE_NAME', 'Marketplace');
define('SITE_URL', 'http://localhost/marketplace');
define('ITEMS_PER_PAGE', 12);

// Функция подключения к БД
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Ошибка подключения к БД: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Функция для безопасного вывода
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Функция для получения пути к изображению
function getProductImage($image_name) {
    if (empty($image_name)) {
        return 'images/products/default.jpg';
    }
    
    $path = 'images/products/' . $image_name;
    
    // Проверяем существование файла
    if (file_exists($path)) {
        return $path;
    }
    
    // Если файла нет, возвращаем заглушку
    return 'https://via.placeholder.com/300x300?text=Product';
}

// Инициализация сессии (корзина, избранное, тема)
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (!isset($_SESSION['favorites'])) {
    $_SESSION['favorites'] = [];
}
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'light';
}
?>