<?php
require_once 'config.php';

// Обработка AJAX запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'ajax_handler.php';
    exit;
}

$pdo = getDB();

// Параметры фильтрации
$search = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;
$selected_brands = isset($_GET['brand']) ? (array)$_GET['brand'] : [];
$sort = $_GET['sort'] ?? 'popular';
$page = max(1, intval($_GET['page'] ?? 1));

// Сбор условий WHERE
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(title LIKE ? OR brand LIKE ? OR description LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($min_price > 0) {
    $where[] = "price >= ?";
    $params[] = $min_price;
}

if ($max_price > 0) {
    $where[] = "price <= ?";
    $params[] = $max_price;
}

if (!empty($selected_brands)) {
    $placeholders = str_repeat('?,', count($selected_brands) - 1) . '?';
    $where[] = "brand IN ($placeholders)";
    $params = array_merge($params, $selected_brands);
}

if (!empty($category)) {
    $where[] = "category = ?";
    $params[] = $category;
}

$sql_where = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

// Сортировка
$sort_options = [
    'popular' => 'views DESC, rating DESC',
    'price_asc' => 'price ASC',
    'price_desc' => 'price DESC',
    'new' => 'created_at DESC',
    'discount' => 'discount DESC',
    'rating' => 'rating DESC'
];
$order_by = $sort_options[$sort] ?? 'views DESC';

// Пагинация
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Получение товаров
$sql = "SELECT * FROM products $sql_where ORDER BY $order_by LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Общее количество
$count_sql = "SELECT COUNT(*) FROM products $sql_where";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute(array_slice($params, 0, -2));
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Получение брендов
$brands_sql = "SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL ORDER BY brand";
$brands = $pdo->query($brands_sql)->fetchAll(PDO::FETCH_COLUMN);

// Получение категорий
$categories_sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL";
$categories = $pdo->query($categories_sql)->fetchAll(PDO::FETCH_COLUMN);

// Счетчики
$cart_count = array_sum($_SESSION['cart']);
$fav_count = count($_SESSION['favorites']);
?>

<!DOCTYPE html>
<html lang="ru" data-theme="<?= $_SESSION['theme'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(SITE_NAME) ?> - Интернет-магазин</title>
    
    <!-- Иконки -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Стили -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
    <!-- Верхняя панель -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-content">
                <div class="location">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Донецк</span>
                </div>
                <div class="top-links">
                    <a href="#"><i class="fas fa-truck"></i> Доставка</a>
                    <a href="#"><i class="fas fa-shield-alt"></i> Гарантия</a>
                    <a href="#"><i class="fas fa-question-circle"></i> Помощь</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Основной хедер -->
    <header class="main-header">
        <div class="container">
            <div class="header-grid">
                <!-- Логотип -->
                <a href="index.php" class="logo">
                    <i class="fas fa-store"></i>
                    <span>Marketplace</span>
                </a>

                <!-- Поиск -->
                <div class="search-container">
                    <form method="GET" class="search-form">
                        <input type="text" 
                               name="search" 
                               placeholder="Искать товары и бренды"
                               value="<?= e($search) ?>"
                               autocomplete="off">
                        <button type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
              

<!-- Модальные окна для авторизации (добавьте в конец body) -->
<div id="login-modal" style="display: none;">
    <form id="login-form">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit">Войти</button>
    </form>
</div>

<div id="register-modal" style="display: none;">
    <form id="register-form">
        <input type="text" name="first_name" placeholder="Имя" required>
        <input type="text" name="last_name" placeholder="Фамилия" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit">Зарегистрироваться</button>
    </form>
</div>
                <!-- Действия -->
                <div class="header-actions">
    <!-- Тема (если есть) -->
    <button class="theme-toggle" id="theme-toggle" aria-label="Переключить тему">
    <i class="fas fa-moon"></i>
</button>

    <!-- Избранное -->
    <div class="action-item">
        <a href="#">
            <i class="fas fa-heart"></i>
            <span>Избранное</span>
            <?php if ($fav_count > 0): ?>
                <span class="badge fav-badge"><?= $fav_count ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- Корзина -->
    <div class="action-item cart-preview">
        <a href="cart.php">  <!-- ← сделай ссылку на cart.php -->
            <i class="fas fa-shopping-cart"></i>
            <span>Корзина</span>
            <?php if ($cart_count > 0): ?>
                <span class="badge"><?= $cart_count ?></span>
            <?php endif; ?>
        </a>
    </div>
    <!-- ← Вот этот блок должен остаться справа -->
    <div class="auth-block">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span class="user-greeting">Привет, <?= e($_SESSION['user_name'] ?? 'Гость') ?></span>
            <?php if (!empty($_SESSION['is_admin'])): ?>
                <a href="#" onclick="showAddProductModal()" class="btn btn-small">+ Товар</a>
            <?php endif; ?>
            <a href="#" onclick="logout()" class="btn btn-small btn-outline">Выйти</a>
        <?php else: ?>
            <button class="btn btn-outline" onclick="showModal('login-modal')">Войти</button>
            <button class="btn btn-primary" onclick="showModal('register-modal')">Регистрация</button>
        <?php endif; ?>
    </div>
</div>
            </div>
        </div>
    </header>

    <!-- Навигация по категориям -->
    <nav class="categories-nav">
        <div class="container">
            <div class="categories-list">
                <a href="index.php" class="category-item <?= empty($category) ? 'active' : '' ?>">
                    <i class="fas fa-th"></i>
                    <span>Все товары</span>
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="?category=<?= urlencode($cat) ?>" 
                       class="category-item <?= $category === $cat ? 'active' : '' ?>">
                        <i class="fas fa-tag"></i>
                        <span><?= e(ucfirst($cat)) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </nav>

    <!-- Основной контент -->
    <main class="main-content">
        <div class="container">
            <div class="content-layout">
                <!-- Сайдбар с фильтрами -->
                <aside class="sidebar">
                    <div class="filter-card">
                        <div class="filter-header">
                            <h3><i class="fas fa-filter"></i> Фильтры</h3>
                        </div>

                        <form method="GET" class="filter-form">
                            <!-- Цена -->
                            <div class="filter-section">
                                <h4>Цена, ₽</h4>
                                <div class="price-slider">
                                    <div class="price-inputs">
                                        <input type="number" 
                                               name="min_price" 
                                               placeholder="0"
                                               value="<?= $min_price > 0 ? $min_price : '' ?>"
                                               min="0">
                                        <span>—</span>
                                        <input type="number" 
                                               name="max_price" 
                                               placeholder="100000"
                                               value="<?= $max_price > 0 ? $max_price : '' ?>"
                                               min="0">
                                    </div>
                                </div>
                            </div>

                            <!-- Бренды -->
                            <?php if (!empty($brands)): ?>
                            <div class="filter-section">
                                <h4>Бренды</h4>
                                <div class="brands-list">
                                    <?php foreach ($brands as $brand): ?>
                                        <label class="checkbox-label">
                                            <input type="checkbox" 
                                                   name="brand[]" 
                                                   value="<?= e($brand) ?>"
                                                   <?= in_array($brand, $selected_brands) ? 'checked' : '' ?>
                                                   onchange="this.form.submit()">
                                            <span class="checkmark"></span>
                                            <span class="brand-name"><?= e($brand) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Скрытые поля -->
                            <?php if ($search): ?>
                                <input type="hidden" name="search" value="<?= e($search) ?>">
                            <?php endif; ?>
                            <?php if ($category): ?>
                                <input type="hidden" name="category" value="<?= e($category) ?>">
                            <?php endif; ?>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-check"></i> Применить фильтры
                            </button>
                        </form>
                    </div>
                </aside>

                <!-- Основная область -->
                <div class="main-area">
                    <!-- Инфо-бар -->
                    <div class="info-bar">
                        <div class="results-count">
                            Найдено товаров: <strong><?= $total_products ?></strong>
                        </div>
                    </div>

                    <!-- Сетка товаров -->
                    <div class="products-grid">
                        <?php if (empty($products)): ?>
                            <div class="empty-products">
                                <i class="fas fa-search fa-3x"></i>
                                <h3>Товары не найдены</h3>
                                <p>Попробуйте изменить параметры поиска</p>
                                <a href="index.php" class="btn btn-primary">Смотреть все товары</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <?php include 'templates/product-card.php'; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Пагинация -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                                   class="page-link prev">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                                       class="page-link <?= $i == $page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <span class="page-dots">...</span>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                                   class="page-link next">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Футер -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Marketplace. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <!-- Скрипты -->
    <script src="/shop/diplom/diplom/script/main.js"></script>
<script>
// Дополнительная инициализация после загрузки main.js
document.addEventListener('DOMContentLoaded', function() {
    // Если что-то не сработало в main.js
    console.log('✅ main.js загружен и DOM готов');
});
</script>
    
    <script>
    // Инициализация темы
    document.documentElement.setAttribute('data-theme', '<?= $_SESSION['theme'] ?>');
    
    function toggleTheme() {
        fetch('ajax_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=toggle_theme'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.documentElement.setAttribute('data-theme', data.theme);
                document.querySelector('.theme-toggle i').className = 
                    data.theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        });
    }
    </script>

    <div id="add-product-modal" style="display: none;">
    <form id="add-product-form" enctype="multipart/form-data">
        <input type="text" name="title" placeholder="Название" required>
        <input type="text" name="brand" placeholder="Бренд" required>
        <input type="number" name="price" placeholder="Цена" required>
        <textarea name="description" placeholder="Описание" required></textarea>
        <input type="text" name="category" placeholder="Категория" required>
        <input type="number" name="stock" placeholder="Количество на складе" required>
        <input type="file" name="image" accept="image/*" required>
        <button type="submit">Добавить товар</button>
    </form>
</div>
</body>
</html>