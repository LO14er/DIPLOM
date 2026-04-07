<?php
// Настройки подключения к базе данных
$host = 'localhost';
$dbname = 'shop';
$username = 'root';
$password = '1111';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo "✅ Подключение к базе данных успешно<br><br>";
} catch (PDOException $e) {
    die("❌ Ошибка подключения к базе данных: " . $e->getMessage());
}

// ============================================================================
// ФУНКЦИЯ ДЛЯ ВЫПОЛНЕНИЯ SQL
// ============================================================================
function executeSQL($pdo, $sql, $errorMessage = "") {
    try {
        $pdo->exec($sql);
        return true;
    } catch (PDOException $e) {
        echo "❌ $errorMessage: " . $e->getMessage() . "<br>";
        return false;
    }
}


// ============================================================================
// 1. ТАБЛИЦА КАТЕГОРИЙ
// ============================================================================
echo "<h3>1. Создаем таблицу categories</h3>";
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    parent_id INT DEFAULT NULL,
    description TEXT,
    image VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_slug (slug),
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeSQL($pdo, $sql, "Ошибка создания таблицы 'categories'");

// ============================================================================
// 2. ОБНОВЛЯЕМ ТАБЛИЦУ PRODUCTS
// ============================================================================
echo "<h3>2. Обновляем таблицу products</h3>";

// Проверяем существование таблицы products
$tableExists = $pdo->query("SHOW TABLES LIKE 'products'")->rowCount() > 0;

if ($tableExists) {
    echo "✅ Таблица 'products' уже существует, обновляем...<br>";
    
    // Список колонок для добавления
    $columns = [
        'category' => "VARCHAR(100) DEFAULT 'computers'",
        'rating' => "DECIMAL(3,2) DEFAULT 0.00",
        'discount' => "DECIMAL(10,2) DEFAULT 0",
        'stock' => "INT DEFAULT 0",
        'sku' => "VARCHAR(50)",
        'status' => "VARCHAR(20) DEFAULT 'active'",
        'updated_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];
    
    // Проверяем и добавляем отсутствующие колонки
    foreach ($columns as $columnName => $columnType) {
        $checkSql = "SHOW COLUMNS FROM products LIKE '$columnName'";
        $columnExists = $pdo->query($checkSql)->rowCount() > 0;
        
        if (!$columnExists) {
            $alterSql = "ALTER TABLE products ADD COLUMN $columnName $columnType";
            if (executeSQL($pdo, $alterSql, "Ошибка добавления колонки $columnName")) {
                echo "✅ Добавлена колонка: $columnName<br>";
            }
        }
    }
} else {
    // Создаем таблицу с нуля
    echo "✅ Создаем таблицу 'products' с нуля...<br>";
    $sql = "CREATE TABLE products (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        brand VARCHAR(100),
        price DECIMAL(10,2) NOT NULL,
        description TEXT NOT NULL,
        image VARCHAR(255) NOT NULL,
        category VARCHAR(100) DEFAULT 'computers',
        rating DECIMAL(3,2) DEFAULT 0.00,
        discount DECIMAL(10,2) DEFAULT 0,
        stock INT DEFAULT 0,
        sku VARCHAR(50) UNIQUE,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    executeSQL($pdo, $sql, "Ошибка создания таблицы 'products'");
}

// ============================================================================
// 3. ТАБЛИЦА ОТЗЫВОВ
// ============================================================================
echo "<h3>3. Создаем таблицу reviews</h3>";
$sql = "CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    user_name VARCHAR(100) NOT NULL,
    user_email VARCHAR(255),
    rating TINYINT NOT NULL,
    advantages TEXT,
    disadvantages TEXT,
    comment TEXT,
    is_verified TINYINT(1) DEFAULT 0,
    likes INT DEFAULT 0,
    dislikes INT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeSQL($pdo, $sql, "Ошибка создания таблицы 'reviews'");

// ============================================================================
// 4. ТАБЛИЦА ПРОСМОТРОВ
// ============================================================================
echo "<h3>4. Создаем таблицу product_views</h3>";
$sql = "CREATE TABLE IF NOT EXISTS product_views (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    views INT DEFAULT 0,
    views_today INT DEFAULT 0,
    views_week INT DEFAULT 0,
    views_month INT DEFAULT 0,
    last_viewed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_view (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeSQL($pdo, $sql, "Ошибка создания таблицы 'product_views'");

// ============================================================================
// 5. ТАБЛИЦА ПОЛЬЗОВАТЕЛЕЙ
// ============================================================================
echo "<h3>5. Создаем таблицу users</h3>";
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    avatar VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    is_admin TINYINT(1) DEFAULT 0,
    email_verified TINYINT(1) DEFAULT 0,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeSQL($pdo, $sql, "Ошибка создания таблицы 'users'");

// ============================================================================
// 6. ТАБЛИЦА ИЗБРАННОГО
// ============================================================================
echo "<h3>6. Создаем таблицу favorites</h3>";
$sql = "CREATE TABLE IF NOT EXISTS favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    product_id INT NOT NULL,
    session_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite_user (user_id, product_id),
    UNIQUE KEY unique_favorite_session (session_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeSQL($pdo, $sql, "Ошибка создания таблицы 'favorites'");

// ============================================================================
// 7. ТАБЛИЦА ЗАКАЗОВ
// ============================================================================
echo "<h3>7. Создаем таблицу orders</h3>";
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(20) NOT NULL,
    user_id INT DEFAULT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    shipping_address TEXT NOT NULL,
    billing_address TEXT,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_cost DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    final_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(20) DEFAULT 'online',
    payment_status VARCHAR(20) DEFAULT 'pending',
    order_status VARCHAR(20) DEFAULT 'new',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_order_number (order_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeSQL($pdo, $sql, "Ошибка создания таблицы 'orders'");

// ============================================================================
// 8. ТАБЛИЦА ТОВАРОВ В ЗАКАЗЕ
// ============================================================================
echo "<h3>8. Создаем таблицу order_items</h3>";
$sql = "CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeSQL($pdo, $sql, "Ошибка создания таблицы 'order_items'");

// ============================================================================
// 9. ТАБЛИЦА ХАРАКТЕРИСТИК ТОВАРОВ
// ============================================================================
echo "<h3>9. Создаем таблицу product_attributes</h3>";
$sql = "CREATE TABLE IF NOT EXISTS product_attributes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    attribute_name VARCHAR(100) NOT NULL,
    attribute_value TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeSQL($pdo, $sql, "Ошибка создания таблицы 'product_attributes'");

// ============================================================================
// 10. ТАБЛИЦА КОРЗИНЫ
// ============================================================================
echo "<h3>10. Создаем таблицу cart</h3>";
$sql = "CREATE TABLE IF NOT EXISTS cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(100),
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeSQL($pdo, $sql, "Ошибка создания таблицы 'cart'");

// ============================================================================
// 11. ТАБЛИЦА АКЦИЙ
// ============================================================================
echo "<h3>11. Создаем таблицу promotions</h3>";
$sql = "CREATE TABLE IF NOT EXISTS promotions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    discount_type VARCHAR(20) DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    max_discount_amount DECIMAL(10,2),
    usage_limit INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeSQL($pdo, $sql, "Ошибка создания таблицы 'promotions'");

// ============================================================================
// 12. ТАБЛИЦА СПОСОБОВ ДОСТАВКИ
// ============================================================================
echo "<h3>12. Создаем таблицу shipping_methods</h3>";
$sql = "CREATE TABLE IF NOT EXISTS shipping_methods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    free_shipping_threshold DECIMAL(10,2),
    estimated_days VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeSQL($pdo, $sql, "Ошибка создания таблицы 'shipping_methods'");

// ============================================================================
// 13. ТАБЛИЦА КУПОНОВ
// ============================================================================
echo "<h3>13. Создаем таблицу coupons</h3>";
$sql = "CREATE TABLE IF NOT EXISTS coupons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL,
    description TEXT,
    discount_type VARCHAR(20) DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    max_uses INT DEFAULT 1,
    used_count INT DEFAULT 0,
    start_date DATETIME,
    end_date DATETIME,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_coupon_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeSQL($pdo, $sql, "Ошибка создания таблицы 'coupons'");

// ============================================================================
// 14. ТАБЛИЦА ИСТОРИИ ЦЕН
// ============================================================================
echo "<h3>14. Создаем таблицу price_history</h3>";
$sql = "CREATE TABLE IF NOT EXISTS price_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    old_price DECIMAL(10,2),
    new_price DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    changed_by VARCHAR(100),
    change_reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeSQL($pdo, $sql, "Ошибка создания таблицы 'price_history'");

// ============================================================================
// 15. ТАБЛИЦА ИЗОБРАЖЕНИЙ ТОВАРОВ
// ============================================================================
echo "<h3>15. Создаем таблицу product_images</h3>";
$sql = "CREATE TABLE IF NOT EXISTS product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    is_main TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    alt_text VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

executeSQL($pdo, $sql, "Ошибка создания таблицы 'product_images'");

// ============================================================================
// СОЗДАЕМ ИНДЕКСЫ ДЛЯ БЫСТРОГО ПОИСКА
// ============================================================================
echo "<h3>Создаем индексы для быстрого поиска</h3>";

$indexes = [
    // Индексы для products
    "CREATE INDEX idx_products_price ON products(price)",
    "CREATE INDEX idx_products_brand ON products(brand)",
    "CREATE INDEX idx_products_category ON products(category)",
    "CREATE INDEX idx_products_status ON products(status)",
    "CREATE INDEX idx_products_rating ON products(rating DESC)",
    
    // Индексы для reviews
    "CREATE INDEX idx_reviews_product_id ON reviews(product_id)",
    "CREATE INDEX idx_reviews_status ON reviews(status)",
    "CREATE INDEX idx_reviews_created ON reviews(created_at DESC)",
    "CREATE INDEX idx_reviews_rating ON reviews(rating)",
    
    // Индексы для favorites
    "CREATE INDEX idx_favorites_user_id ON favorites(user_id)",
    "CREATE INDEX idx_favorites_session_id ON favorites(session_id)",
    
    // Индексы для orders
    "CREATE INDEX idx_orders_user_id ON orders(user_id)",
    "CREATE INDEX idx_orders_status ON orders(order_status)",
    "CREATE INDEX idx_orders_created ON orders(created_at DESC)",
    
    // Индексы для order_items
    "CREATE INDEX idx_order_items_order_id ON order_items(order_id)",
    "CREATE INDEX idx_order_items_product_id ON order_items(product_id)",
    
    // Индексы для cart
    "CREATE INDEX idx_cart_user_id ON cart(user_id)",
    "CREATE INDEX idx_cart_session_id ON cart(session_id)",
    "CREATE INDEX idx_cart_product_id ON cart(product_id)",
    
    // Индексы для product_attributes
    "CREATE INDEX idx_attributes_product_id ON product_attributes(product_id)",
    
    // Индексы для promotions
    "CREATE INDEX idx_promotions_active ON promotions(is_active, start_date, end_date)",
    
    // Индексы для coupons
    "CREATE INDEX idx_coupons_active ON coupons(code, is_active, start_date, end_date)",
    
    // Индексы для price_history
    "CREATE INDEX idx_price_history_product ON price_history(product_id, created_at DESC)",
    
    // Индексы для product_images
    "CREATE INDEX idx_product_images_main ON product_images(product_id, is_main)"
];

foreach ($indexes as $index) {
    try {
        $pdo->exec($index);
        echo "✅ Создан индекс<br>";
    } catch (PDOException $e) {
        // Игнорируем ошибки "индекс уже существует"
        if (strpos($e->getMessage(), 'already exists') === false) {
            echo "⚠️ Ошибка создания индекса: " . $e->getMessage() . "<br>";
        }
    }
}

// ============================================================================
// ЗАПОЛНЯЕМ ТЕСТОВЫМИ ДАННЫМИ
// ============================================================================
echo "<h3>Заполняем таблицы тестовыми данными</h3>";

// 1. Заполняем categories
echo "1. Заполняем categories...<br>";
$pdo->exec("DELETE FROM categories WHERE name IS NULL");

$categories = [
    ['Электроника', 'electronics', 'Техника и электроника', 1],
    ['Компьютеры и ноутбуки', 'computers', 'Компьютеры, ноутбуки и комплектующие', 2],
    ['Периферия', 'peripherals', 'Клавиатуры, мыши, мониторы', 3],
    ['Клавиатуры', 'keyboards', 'Проводные и беспроводные клавиатуры', 4],
    ['Мыши', 'mice', 'Компьютерные мыши и аксессуары', 5],
    ['Наушники', 'headphones', 'Наушники, гарнитуры, колонки', 6],
    ['Игровые устройства', 'gaming', 'Игровые клавиатуры, мыши, наушники', 7],
    ['Офисная техника', 'office', 'Техника для офиса', 8],
    ['Аксессуары', 'accessories', 'Кабели, коврики, подставки', 9]
];

$stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug, description, sort_order, is_active) VALUES (?, ?, ?, ?, 1)");
foreach ($categories as $cat) {
    $stmt->execute($cat);
}
echo "✅ Категории заполнены<br>";

// 2. Добавляем тестовые товары
echo "2. Добавляем тестовые товары...<br>";
$productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

if ($productCount < 5) {
    $products = [
        ['Беспроводная клавиатура Logitech MX Keys', 'logitech', 8990, 'Профессиональная беспроводная клавиатура с подсветкой', 'logitech_mx_keys.jpg', 'keyboards', 25, 1000],
        ['Игровая мышь Razer DeathAdder V2', 'razer', 5490, 'Игровая мышь с оптическим сенсором 20000 DPI', 'razer_deathadder.jpg', 'mice', 18, 500],
        ['Беспроводные наушники Sony WH-1000XM4', 'sony', 24990, 'Наушники с активным шумоподавлением', 'sony_wh1000xm4.jpg', 'headphones', 12, 2000],
        ['Механическая клавиатура HyperX Alloy FPS', 'hyperx', 7990, 'Игровая механическая клавиатура с красными свичами', 'hyperx_alloy.jpg', 'keyboards', 15, 800],
        ['Беспроводная мышь Apple Magic Mouse', 'apple', 6990, 'Мультитач мышь для Mac', 'apple_magic_mouse.jpg', 'mice', 20, 0],
        ['Игровые наушники SteelSeries Arctis 5', 'steelseries', 8990, 'Игровые наушники с RGB подсветкой', 'steelseries_arctis.jpg', 'headphones', 10, 1200],
        ['Клавиатура для Mac Keychron K2', 'keychron', 6590, 'Механическая клавиатура с Bluetooth', 'keychron_k2.jpg', 'keyboards', 8, 600],
        ['Вертикальная мышь Logitech MX Vertical', 'logitech', 7490, 'Эргономичная вертикальная мышь', 'logitech_mx_vertical.jpg', 'mice', 5, 400]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO products (title, brand, price, description, image, category, stock, discount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($products as $product) {
        try {
            $stmt->execute($product);
        } catch (PDOException $e) {
            // Игнорируем ошибки дубликатов
        }
    }
    echo "✅ Добавлены тестовые товары<br>";
} else {
    echo "✅ В таблице уже есть товары ($productCount шт.)<br>";
}

// 3. Добавляем тестового пользователя
echo "3. Добавляем тестового пользователя...<br>";
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

if ($userCount == 0) {
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO users (email, password, first_name, last_name, is_admin, is_active) VALUES 
        ('admin@market.ru', '$hashedPassword', 'Админ', 'Системный', 1, 1),
        ('user@market.ru', '$hashedPassword', 'Иван', 'Петров', 0, 1)");
    echo "✅ Добавлены тестовые пользователи<br>";
} else {
    echo "✅ Пользователи уже существуют<br>";
}

// 4. Добавляем способы доставки
echo "4. Добавляем способы доставки...<br>";
$shippingCount = $pdo->query("SELECT COUNT(*) FROM shipping_methods")->fetchColumn();

if ($shippingCount == 0) {
    $pdo->exec("INSERT INTO shipping_methods (name, description, cost, free_shipping_threshold, estimated_days, is_active) VALUES
        ('Курьерская доставка', 'Доставка курьером до двери', 299, 5000, '1-2 дня', 1),
        ('Самовывоз из пункта выдачи', 'Самовывоз из пункта выдачи СДЭК', 0, NULL, '1-3 дня', 1),
        ('Почта России', 'Доставка почтой России', 199, 10000, '3-7 дней', 1)");
    echo "✅ Добавлены способы доставки<br>";
} else {
    echo "✅ Способы доставки уже существуют<br>";
}

// 5. Добавляем купоны
echo "5. Добавляем купоны...<br>";
$couponCount = $pdo->query("SELECT COUNT(*) FROM coupons")->fetchColumn();

if ($couponCount == 0) {
    $pdo->exec("INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount, max_uses) VALUES
        ('WELCOME10', 'Скидка 10% для новых клиентов', 'percentage', 10, 0, 100),
        ('FREESHIP', 'Бесплатная доставка', 'fixed', 299, 1000, 50),
        ('SUMMER20', 'Летняя скидка 20%', 'percentage', 20, 3000, 200)");
    echo "✅ Добавлены купоны<br>";
} else {
    echo "✅ Купоны уже существуют<br>";
}

// 6. Добавляем просмотры для товаров
echo "6. Добавляем просмотры для товаров...<br>";
$stmt = $pdo->query("SELECT id FROM products");
$productIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($productIds as $productId) {
    $views = rand(100, 1000);
    $pdo->exec("INSERT INTO product_views (product_id, views) VALUES ($productId, $views) 
               ON DUPLICATE KEY UPDATE views = $views");
}
echo "✅ Добавлены просмотры товаров<br>";

// 7. Добавляем отзывы
echo "7. Добавляем отзывы...<br>";
$reviewCount = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();

if ($reviewCount == 0) {
    $reviews = [
        [1, 'Алексей Иванов', 5, 'Отличная клавиатура, очень удобная!'],
        [1, 'Мария Петрова', 4, 'Хорошее качество, но дороговато'],
        [2, 'Дмитрий Сидоров', 5, 'Лучшая мышь для игр, рекомендую!'],
        [3, 'Ольга Козлова', 5, 'Наушники просто супер, шумоподавление работает отлично'],
        [4, 'Игорь Николаев', 4, 'Клавиатура хорошая, но клавиши немного громкие'],
        [5, 'Светлана Воробьева', 3, 'Мышь неудобная для правой руки']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_name, rating, comment, status) VALUES (?, ?, ?, ?, 'approved')");
    
    foreach ($reviews as $review) {
        $stmt->execute($review);
    }
    echo "✅ Добавлены отзывы<br>";
} else {
    echo "✅ Отзывы уже существуют<br>";
}

// 8. Обновляем рейтинги товаров
echo "8. Обновляем рейтинги товаров...<br>";
$pdo->exec("
    UPDATE products p 
    SET rating = (
        SELECT COALESCE(AVG(rating), 0) 
        FROM reviews r 
        WHERE r.product_id = p.id AND r.status = 'approved'
    )
");
echo "✅ Обновлены рейтинги товаров<br>";

// ============================================================================
// ФИНАЛЬНОЕ СООБЩЕНИЕ
// ============================================================================
echo "<h2 style='color: green; margin-top: 30px; padding: 20px; background: #e8f5e9; border-radius: 10px;'>";
echo "🎉 ВСЕ ТАБЛИЦЫ УСПЕШНО СОЗДАНЫ И ЗАПОЛНЕНЫ!";
echo "</h2>";

echo "<div style='background: #f5f5f5; padding: 20px; border-radius: 10px; margin-top: 20px;'>";
echo "<h3>📊 Что было создано:</h3>";
echo "<ul>";
echo "<li>✅ 15 таблиц базы данных</li>";
echo "<li>✅ Индексы для быстрого поиска</li>";
echo "<li>✅ Тестовые категории товаров</li>";
echo "<li>✅ Тестовые товары (если их было мало)</li>";
echo "<li>✅ Тестовые пользователи</li>";
echo "<li>✅ Способы доставки</li>";
echo "<li>✅ Промокоды и купоны</li>";
echo "<li>✅ Просмотры товаров</li>";
echo "<li>✅ Отзывы и рейтинги</li>";
echo "</ul>";

echo "<h3>🔑 Данные для входа:</h3>";
echo "<ul>";
echo "<li><strong>Админ:</strong> admin@market.ru / admin123</li>";
echo "<li><strong>Пользователь:</strong> user@market.ru / admin123</li>";
echo "</ul>";

echo "<p style='margin-top: 20px;'><strong>📁 Структура базы данных:</strong></p>";
echo "<pre style='background: white; padding: 15px; border-radius: 5px;'>";
echo "shop (база данных)
├── products (товары)
├── categories (категории)
├── reviews (отзывы)
├── product_views (просмотры)
├── users (пользователи)
├── favorites (избранное)
├── orders (заказы)
├── order_items (товары в заказах)
├── product_attributes (характеристики)
├── cart (корзина)
├── promotions (акции)
├── shipping_methods (доставка)
├── coupons (купоны)
├── price_history (история цен)
└── product_images (изображения товаров)";
echo "</pre>";
echo "</div>";

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='index.php' style='display: inline-block; padding: 15px 30px; background: #005bff; color: white; text-decoration: none; border-radius: 8px; font-size: 18px; font-weight: bold;'>";
echo "🚀 Перейти на главную страницу";
echo "</a>";
echo "<br><br>";
echo "<a href='admin/' style='display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;'>";
echo "⚙️ Перейти в админ-панель";
echo "</a>";
echo "</div>";

// Проверяем, все ли таблицы созданы
echo "<div style='margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 10px;'>";
echo "<h3>🔍 Проверка созданных таблиц:</h3>";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "<p>Найдено таблиц: <strong>" . count($tables) . "</strong></p>";
echo "<ul>";
foreach ($tables as $table) {
    echo "<li>✅ $table</li>";
}
echo "</ul>";
echo "</div>";
?>