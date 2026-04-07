<?php
require_once 'config.php';

$pdo = getDB();

// Получаем ID товара
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$product_id) {
    header('Location: index.php');
    exit;
}

// Добавляем в историю просмотров
if (!isset($_SESSION['recently_viewed'])) {
    $_SESSION['recently_viewed'] = [];
}

// Удаляем старый просмотр, если он уже есть
$key = array_search($product_id, $_SESSION['recently_viewed']);
if ($key !== false) {
    unset($_SESSION['recently_viewed'][$key]);
}

// Добавляем в начало
array_unshift($_SESSION['recently_viewed'], $product_id);

// Ограничиваем количество просмотренных товаров
$_SESSION['recently_viewed'] = array_slice($_SESSION['recently_viewed'], 0, 10);

// Увеличиваем счетчик просмотров
$pdo->prepare("UPDATE products SET views = views + 1 WHERE id = ?")->execute([$product_id]);

// Получаем данные товара
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit;
}

// Получаем изображения товара
$images_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order");
$images_stmt->execute([$product_id]);
$product_images = $images_stmt->fetchAll();

// Получаем характеристики
$attrs_stmt = $pdo->prepare("SELECT * FROM product_attributes WHERE product_id = ? ORDER BY sort_order");
$attrs_stmt->execute([$product_id]);
$attributes = $attrs_stmt->fetchAll();

// Получаем отзывы
$reviews_stmt = $pdo->prepare("SELECT * FROM reviews WHERE product_id = ? AND status = 'approved' ORDER BY created_at DESC LIMIT 5");
$reviews_stmt->execute([$product_id]);
$reviews = $reviews_stmt->fetchAll();

// Средний рейтинг
$avg_rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE product_id = ? AND status = 'approved'");
$avg_rating_stmt->execute([$product_id]);
$rating_data = $avg_rating_stmt->fetch();

// Похожие товары
$similar_stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? AND id != ? AND stock > 0 ORDER BY rating DESC LIMIT 6");
$similar_stmt->execute([$product['category'], $product_id]);
$similar_products = $similar_stmt->fetchAll();

// Проверяем наличие в корзине и избранном
$in_cart = isset($_SESSION['cart'][$product_id]) && $_SESSION['cart'][$product_id] > 0;
$is_favorite = in_array($product_id, $_SESSION['favorites']);
$has_discount = !empty($product['discount']) && $product['discount'] > 0;
$old_price = $has_discount ? $product['price'] + $product['discount'] : $product['price'];
$discount_percent = $has_discount ? round(($product['discount'] / $old_price) * 100) : 0;

// Счетчики
$cart_count = array_sum($_SESSION['cart']);
$fav_count = count($_SESSION['favorites']);
$theme = $_SESSION['theme'] ?? 'light';
?>

<!DOCTYPE html>
<html lang="ru" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($product['title']) ?> - <?= e(SITE_NAME) ?></title>
    
    <!-- Мета теги для SEO -->
    <meta name="description" content="<?= e(substr(strip_tags($product['description']), 0, 150)) ?>...">
    <meta property="og:title" content="<?= e($product['title']) ?>">
    <meta property="og:description" content="<?= e(substr(strip_tags($product['description']), 0, 150)) ?>...">
    <meta property="og:image" content="images/products/<?= e($product['image'] ?? 'default.jpg') ?>">
    
    <!-- Иконки -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Стили -->
    <link rel="stylesheet" href="/shop/diplom/diplom/css/style.css">
    <link rel="stylesheet" href="/shop/diplom/diplom/css/theme.css">
    
    <!-- Zoom плагин для изображений -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/zoom-vanilla.js/dist/zoom.css">
    
    <!-- Добавляем в корзину Schema.org -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Product",
        "name": "<?= e($product['title']) ?>",
        "description": "<?= e(strip_tags($product['description'])) ?>",
        "brand": {
            "@type": "Brand",
            "name": "<?= e($product['brand']) ?>"
        },
        "offers": {
            "@type": "Offer",
            "price": "<?= $product['price'] ?>",
            "priceCurrency": "RUB",
            "availability": "https://schema.org/<?= $product['stock'] > 0 ? 'InStock' : 'OutOfStock' ?>"
        }
    }
    </script>
</head>
<body>
    <!-- Верхняя панель -->
    <?php include 'templates/header.php'; ?>

    <!-- Основной контент -->
    <main class="main-content">
        <div class="container">
            <!-- Хлебные крошки -->
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <ol>
                    <li><a href="index.php">Главная</a></li>
                    <li><a href="?category=<?= urlencode($product['category']) ?>"><?= e(ucfirst($product['category'])) ?></a></li>
                    <li><a href="?brand=<?= urlencode($product['brand']) ?>"><?= e($product['brand']) ?></a></li>
                    <li aria-current="page"><?= e($product['title']) ?></li>
                </ol>
            </nav>

            <!-- Основная информация о товаре -->
            <div class="product-detail">
                <!-- Левая колонка: изображения -->
                <div class="product-gallery">
                    <!-- Главное изображение -->
                    <div class="main-image">
                        <div class="image-container">
                            <img src="/shop/diplom/diplom/images/<?= e($product['image']) ?>"> 
                            
                            <!-- Бейджи -->
                            <div class="product-badges">
                                <?php if ($has_discount): ?>
                                    <span class="badge discount-badge">-<?= $discount_percent ?>%</span>
                                <?php endif; ?>
                                
                                <?php if ($product['stock'] == 0): ?>
                                    <span class="badge out-of-stock-badge">Нет в наличии</span>
                                <?php elseif ($product['stock'] < 10): ?>
                                    <span class="badge low-stock-badge">Осталось мало</span>
                                <?php endif; ?>
                                
                                <?php if (!empty($product['is_new']) && $product['is_new']): ?>
                                    <span class="badge new-badge">Новинка</span>
                                <?php endif; ?>
                                
                                <?php if (!empty($product['is_bestseller']) && $product['is_bestseller']): ?>
                                    <span class="badge bestseller-badge">Хит продаж</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Миниатюры -->
                    <?php if (!empty($product_images) || !empty($product['image'])): ?>
                    <div class="thumbnail-gallery">
                        <?php 
                        $all_images = [];
                        if (!empty($product['image'])) {
                            $all_images[] = ['image_url' => $product['image'], 'is_main' => 1];
                        }
                        $all_images = array_merge($all_images, $product_images);
                        
                        foreach ($all_images as $index => $img): 
                            $img_url = is_array($img) ? $img['image_url'] : $img;
                            if (!empty($img_url)):
                        ?>
                            <div class="thumbnail <?= ($index === 0) ? 'active' : '' ?>" 
                                 onclick="changeMainImage('shop/diplom/diplom/images/<?= e($img_url) ?>', this)">
                                <img src="shop/diplom/diplom/images/<?= e($img_url) ?>" 
                                     alt="<?= e($product['title']) ?> - изображение <?= $index + 1 ?>"
                                     loading="lazy">
                            </div>
                        <?php endif; endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Действия с изображением -->
                    <div class="image-actions">
                        <button class="btn-action" onclick="zoomImage()" title="Увеличить">
                            <i class="fas fa-search-plus"></i>
                        </button>
                        <button class="btn-action" onclick="shareProduct()" title="Поделиться">
                            <i class="fas fa-share-alt"></i>
                        </button>
                        <button class="btn-action <?= $is_favorite ? 'active' : '' ?>" 
                                onclick="toggleFavorite(<?= $product_id ?>)" 
                                title="<?= $is_favorite ? 'Удалить из избранного' : 'Добавить в избранное' ?>">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                </div>

                <!-- Центральная колонка: информация о товаре -->
                <div class="product-info">
                    <!-- Заголовок и рейтинг -->
                    <div class="product-header">
                        <h1 class="product-title"><?= e($product['title']) ?></h1>
                        
                        <div class="product-rating-section">
                            <?php if ($rating_data['avg_rating']): ?>
                                <div class="rating-display">
                                    <div class="stars" style="--rating: <?= $rating_data['avg_rating'] ?>;">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <span class="rating-value"><?= number_format($rating_data['avg_rating'], 1) ?></span>
                                    <span class="reviews-count"><?= $rating_data['count'] ?> отзывов</span>
                                </div>
                            <?php else: ?>
                                <div class="no-reviews">
                                    <i class="fas fa-star"></i>
                                    <span>Нет отзывов</span>
                                </div>
                            <?php endif; ?>
                            
                            <button class="btn-write-review" onclick="scrollToReviews()">
                                <i class="fas fa-pen"></i> Написать отзыв
                            </button>
                        </div>
                        
                        <div class="product-sku">
                            <span>Артикул: <?= e($product['sku'] ?? $product['id']) ?></span>
                            <span class="product-code">Код товара: <?= str_pad($product['id'], 6, '0', STR_PAD_LEFT) ?></span>
                        </div>
                    </div>

                    <!-- Цена и акции -->
                    <div class="pricing-section">
                        <div class="price-display">
                            <?php if ($has_discount): ?>
                                <div class="old-price"><?= number_format($old_price, 0, '', ' ') ?> ₽</div>
                                <div class="discount-info">
                                    <span class="discount-percent">-<?= $discount_percent ?>%</span>
                                    <span class="discount-amount">-<?= number_format($product['discount'], 0, '', ' ') ?> ₽</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="current-price"><?= number_format($product['price'], 0, '', ' ') ?> ₽</div>
                            
                            <?php if ($has_discount): ?>
                                <div class="price-per-month">
                                    <span><?= number_format(round($product['price'] / 12), 0, '', ' ') ?> ₽/мес</span>
                                    <small>в рассрочку на 12 месяцев</small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Акции и скидки -->
                        <div class="promotions">
                            <?php if ($has_discount): ?>
                                <div class="promo-badge">
                                    <i class="fas fa-percentage"></i>
                                    <span>Скидка до <?= date('d.m.Y', strtotime('+7 days')) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($product['price'] > 5000): ?>
                                <div class="promo-badge free-delivery">
                                    <i class="fas fa-truck"></i>
                                    <span>Бесплатная доставка</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="promo-badge cashback">
                                <i class="fas fa-coins"></i>
                                <span>Кэшбэк до 10%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Наличие -->
                    <div class="stock-section">
                        <div class="stock-info">
                            <i class="fas fa-<?= $product['stock'] > 0 ? 'check-circle' : 'times-circle' ?>"></i>
                            <span>
                                <?php if ($product['stock'] > 0): ?>
                                    <strong>В наличии</strong> — <?= $product['stock'] ?> шт.
                                <?php else: ?>
                                    <strong>Нет в наличии</strong>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <div class="delivery-info">
                            <i class="fas fa-shipping-fast"></i>
                            <span>
                                <strong>Доставка:</strong> 
                                <?php if ($product['price'] > 5000): ?>
                                    бесплатно завтра
                                <?php else: ?>
                                    от 250 ₽, завтра
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <div class="seller-info">
                            <i class="fas fa-store"></i>
                            <span>
                                <strong>Продавец:</strong> <?= e($product['seller'] ?? 'Marketplace') ?>
                                <span class="seller-rating">4.9 ★</span>
                            </span>
                        </div>
                    </div>

                    <!-- Варианты (цвет, размер и т.д.) -->
                    <?php 
                    $variants = [];
                    if (!empty($product['color'])) $variants['Цвет'] = explode(',', $product['color']);
                    if (!empty($product['size'])) $variants['Размер'] = explode(',', $product['size']);
                    ?>
                    
                    <?php if (!empty($variants)): ?>
                    <div class="variants-section">
                        <?php foreach ($variants as $variant_name => $variant_values): ?>
                            <div class="variant-group">
                                <h4><?= e($variant_name) ?>:</h4>
                                <div class="variant-options">
                                    <?php foreach ($variant_values as $value): ?>
                                        <label class="variant-option">
                                            <input type="radio" 
                                                   name="<?= strtolower($variant_name) ?>" 
                                                   value="<?= trim($value) ?>"
                                                   <?= ($loop->first) ? 'checked' : '' ?>>
                                            <span class="variant-label"><?= trim($value) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Кнопки действий -->
                    <div class="actions-section">
                        <?php if ($product['stock'] > 0): ?>
                            <div class="quantity-selector">
                                <button class="qty-btn minus" onclick="changeQuantity(-1)">-</button>
                                <input type="number" 
                                       id="product-quantity" 
                                       value="<?= $in_cart ? $_SESSION['cart'][$product_id] : 1 ?>" 
                                       min="1" 
                                       max="<?= min($product['stock'], 10) ?>">
                                <button class="qty-btn plus" onclick="changeQuantity(1)">+</button>
                                <span class="max-qty">Макс: <?= min($product['stock'], 10) ?> шт.</span>
                            </div>
                            
                            <div class="action-buttons">
                                <?php if ($in_cart): ?>
                                    <button class="btn btn-cart in-cart" onclick="updateCart(<?= $product_id ?>, 'update')">
                                        <i class="fas fa-check"></i>
                                        <span>В корзине (<?= $_SESSION['cart'][$product_id] ?>)</span>
                                        <small>Изменить</small>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-cart" onclick="updateCart(<?= $product_id ?>, 'add')">
                                        <i class="fas fa-shopping-cart"></i>
                                        <span>Добавить в корзину</span>
                                    </button>
                                <?php endif; ?>
                                
                                <button class="btn btn-buy-now" onclick="buyNow(<?= $product_id ?>)">
                                    <i class="fas fa-bolt"></i>
                                    <span>Купить сейчас</span>
                                </button>
                                
                                <button class="btn btn-credit" onclick="showCreditModal()">
                                    <i class="fas fa-credit-card"></i>
                                    <span>В кредит</span>
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="out-of-stock-actions">
                                <button class="btn btn-notify" onclick="notifyWhenAvailable()">
                                    <i class="fas fa-bell"></i>
                                    <span>Уведомить о поступлении</span>
                                </button>
                                <button class="btn btn-similar" onclick="showSimilarProducts()">
                                    <i class="fas fa-exchange-alt"></i>
                                    <span>Показать похожие</span>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Гарантии и преимущества -->
                    <div class="benefits-section">
                        <div class="benefit-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>Гарантия <?= $product['warranty'] ?? 12 ?> месяцев</span>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-sync-alt"></i>
                            <span>Возврат в течение 14 дней</span>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-certificate"></i>
                            <span>Официальная гарантия</span>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-headset"></i>
                            <span>Поддержка 24/7</span>
                        </div>
                    </div>
                </div>

                <!-- Правая колонка: информация о доставке и продавце -->
                <div class="product-sidebar">
                    <!-- Блок доставки -->
                    <div class="delivery-widget">
                        <h3><i class="fas fa-truck"></i> Доставка</h3>
                        <div class="delivery-options">
                            <div class="delivery-option selected">
                                <i class="fas fa-home"></i>
                                <div class="option-info">
                                    <span class="option-title">Курьером</span>
                                    <span class="option-desc">Завтра, 10:00–22:00</span>
                                    <?php if ($product['price'] > 5000): ?>
                                        <span class="option-price free">Бесплатно</span>
                                    <?php else: ?>
                                        <span class="option-price">от 250 ₽</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="delivery-option">
                                <i class="fas fa-store"></i>
                                <div class="option-info">
                                    <span class="option-title">Пункт выдачи</span>
                                    <span class="option-desc">Послезавтра, с 10:00</span>
                                    <span class="option-price free">Бесплатно</span>
                                </div>
                            </div>
                            <div class="delivery-option">
                                <i class="fas fa-shipping-fast"></i>
                                <div class="option-info">
                                    <span class="option-title">Экспресс-доставка</span>
                                    <span class="option-desc">Сегодня, 2–4 часа</span>
                                    <span class="option-price">590 ₽</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="delivery-calc">
                            <input type="text" placeholder="Введите индекс" class="zip-input">
                            <button class="btn-calc" onclick="calculateDelivery()">Рассчитать</button>
                        </div>
                    </div>

                    <!-- Блок продавца -->
                    <div class="seller-widget">
                        <h3><i class="fas fa-store"></i> Продавец</h3>
                        <div class="seller-info">
                            <div class="seller-header">
                                <img src="images/sellers/<?= e($product['seller_logo'] ?? 'default.png') ?>" 
                                     alt="<?= e($product['seller'] ?? 'Marketplace') ?>">
                                <div class="seller-details">
                                    <span class="seller-name"><?= e($product['seller'] ?? 'Marketplace') ?></span>
                                    <div class="seller-rating">
                                        <span class="stars">★★★★★</span>
                                        <span class="rating">4.9</span>
                                    </div>
                                </div>
                            </div>
                            <div class="seller-stats">
                                <div class="stat-item">
                                    <span class="stat-value">98%</span>
                                    <span class="stat-label">Положительных отзывов</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value">2.5K</span>
                                    <span class="stat-label">Товаров</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value">5 лет</span>
                                    <span class="stat-label">На Marketplace</span>
                                </div>
                            </div>
                            <div class="seller-actions">
                                <button class="btn btn-seller" onclick="viewSellerProducts()">
                                    <i class="fas fa-boxes"></i>
                                    <span>Все товары продавца</span>
                                </button>
                                <button class="btn btn-contact" onclick="contactSeller()">
                                    <i class="fas fa-comment"></i>
                                    <span>Написать продавцу</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Специальное предложение -->
                    <div class="special-offer">
                        <h3><i class="fas fa-gift"></i> С этим товаром покупают</h3>
                        <?php if (!empty($similar_products)): ?>
                            <div class="offer-products">
                                <?php foreach (array_slice($similar_products, 0, 2) as $similar): ?>
                                    <div class="offer-product">
                                        <img src="images/products/<?= e($similar['image'] ?? 'default.jpg') ?>" 
                                             alt="<?= e($similar['title']) ?>">
                                        <div class="offer-info">
                                            <div class="offer-title"><?= e($similar['title']) ?></div>
                                            <div class="offer-price"><?= number_format($similar['price'], 0, '', ' ') ?> ₽</div>
                                            <button class="btn-add-offer" onclick="addToCart(<?= $similar['id'] ?>)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="btn btn-block" onclick="showAllOffers()">
                                <i class="fas fa-eye"></i>
                                <span>Показать все предложения</span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Рекомендации -->
                    <div class="recommendations">
                        <h3><i class="fas fa-fire"></i> Часто покупают вместе</h3>
                        <div class="combo-products">
                            <?php 
                            $combo_ids = [1, 2, 3]; // Здесь должен быть алгоритм подбора
                            $combo_stmt = $pdo->prepare("SELECT * FROM products WHERE id IN (1,2,3) LIMIT 3");
                            $combo_stmt->execute();
                            $combo_products = $combo_stmt->fetchAll();
                            ?>
                            <?php foreach ($combo_products as $combo): ?>
                                <div class="combo-product">
                                    <img src="images/products/<?= e($combo['image'] ?? 'default.jpg') ?>" 
                                         alt="<?= e($combo['title']) ?>">
                                    <span class="combo-plus">+</span>
                                </div>
                            <?php endforeach; ?>
                            <div class="combo-total">
                                <span class="total-price">5 490 ₽</span>
                                <button class="btn btn-combo" onclick="addComboToCart()">
                                    <i class="fas fa-cart-plus"></i>
                                    <span>Добавить комплект</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Табы с дополнительной информацией -->
            <div class="product-tabs">
                <div class="tabs-header">
                    <button class="tab-btn active" onclick="switchTab('description')">Описание</button>
                    <button class="tab-btn" onclick="switchTab('characteristics')">Характеристики</button>
                    <button class="tab-btn" onclick="switchTab('reviews')">
                        Отзывы
                        <?php if ($rating_data['count']): ?>
                            <span class="tab-badge"><?= $rating_data['count'] ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="tab-btn" onclick="switchTab('questions')">Вопросы и ответы</button>
                    <button class="tab-btn" onclick="switchTab('delivery')">Доставка и оплата</button>
                    <button class="tab-btn" onclick="switchTab('warranty')">Гарантия</button>
                </div>

                <div class="tabs-content">
                    <!-- Описание -->
                    <div class="tab-pane active" id="description">
                        <div class="description-content">
                            <div class="description-text">
                                <?= nl2br(e($product['description'])) ?>
                            </div>
                            <div class="description-features">
                                <h4>Особенности:</h4>
                                <ul>
                                    <?php 
                                    $features = explode("\n", $product['features'] ?? '');
                                    foreach ($features as $feature):
                                        if (trim($feature)):
                                    ?>
                                        <li><i class="fas fa-check"></i> <?= e(trim($feature)) ?></li>
                                    <?php endif; endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Характеристики -->
                    <div class="tab-pane" id="characteristics">
                        <?php if (!empty($attributes)): ?>
                            <table class="characteristics-table">
                                <tbody>
                                    <?php foreach ($attributes as $attr): ?>
                                        <tr>
                                            <th><?= e($attr['attribute_name']) ?></th>
                                            <td><?= e($attr['attribute_value']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="no-data">Характеристики не указаны</p>
                        <?php endif; ?>
                    </div>

                    <!-- Отзывы -->
                    <div class="tab-pane" id="reviews">
                        <div class="reviews-section">
                            <!-- Рейтинг-статистика -->
                            <div class="reviews-summary">
                                <div class="summary-rating">
                                    <div class="overall-rating">
                                        <span class="rating-number"><?= number_format($rating_data['avg_rating'] ?? 0, 1) ?></span>
                                        <div class="stars" style="--rating: <?= $rating_data['avg_rating'] ?? 0 ?>;">
                                            ★★★★★
                                        </div>
                                        <span class="reviews-count"><?= $rating_data['count'] ?? 0 ?> отзывов</span>
                                    </div>
                                    <div class="rating-distribution">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <div class="rating-row">
                                                <span class="rating-star"><?= $i ?> ★</span>
                                                <div class="rating-bar">
                                                    <div class="bar-fill" style="width: <?= rand(20, 100) ?>%"></div>
                                                </div>
                                                <span class="rating-count"><?= rand(1, 50) ?></span>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Список отзывов -->
                            <div class="reviews-list">
                                <?php if (!empty($reviews)): ?>
                                    <?php foreach ($reviews as $review): ?>
                                        <div class="review-item">
                                            <div class="review-header">
                                                <div class="review-author">
                                                    <img src="https://i.pravatar.cc/40?u=<?= $review['id'] ?>" 
                                                         alt="<?= e($review['user_name']) ?>">
                                                    <div class="author-info">
                                                        <span class="author-name"><?= e($review['user_name']) ?></span>
                                                        <span class="review-date"><?= date('d.m.Y', strtotime($review['created_at'])) ?></span>
                                                    </div>
                                                </div>
                                                <div class="review-rating">
                                                    <div class="stars" style="--rating: <?= $review['rating'] ?>;">
                                                        ★★★★★
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="review-content">
                                                <?php if (!empty($review['advantages'])): ?>
                                                    <div class="review-pros">
                                                        <strong>Достоинства:</strong>
                                                        <p><?= e($review['advantages']) ?></p>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($review['disadvantages'])): ?>
                                                    <div class="review-cons">
                                                        <strong>Недостатки:</strong>
                                                        <p><?= e($review['disadvantages']) ?></p>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($review['comment'])): ?>
                                                    <div class="review-comment">
                                                        <p><?= e($review['comment']) ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="review-footer">
                                                <button class="btn-helpful" onclick="markHelpful(<?= $review['id'] ?>)">
                                                    <i class="fas fa-thumbs-up"></i>
                                                    <span>Полезно (<?= $review['likes'] ?>)</span>
                                                </button>
                                                <button class="btn-unhelpful" onclick="markUnhelpful(<?= $review['id'] ?>)">
                                                    <i class="fas fa-thumbs-down"></i>
                                                    <span>Не полезно (<?= $review['dislikes'] ?>)</span>
                                                </button>
                                                <button class="btn-report" onclick="reportReview(<?= $review['id'] ?>)">
                                                    <i class="fas fa-flag"></i>
                                                    <span>Пожаловаться</span>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-reviews-message">
                                        <i class="fas fa-comment-slash"></i>
                                        <p>Пока нет отзывов о товаре. Будьте первым!</p>
                                        <button class="btn btn-primary" onclick="showReviewForm()">
                                            Написать отзыв
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Форма отзыва -->
                            <div class="review-form-container" id="review-form" style="display: none;">
                                <h4>Написать отзыв</h4>
                                <form id="new-review-form">
                                    <div class="form-group">
                                        <label>Ваше имя:</label>
                                        <input type="text" name="user_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Ваша оценка:</label>
                                        <div class="rating-input">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star" data-rating="<?= $i ?>"></i>
                                            <?php endfor; ?>
                                            <input type="hidden" name="rating" value="5">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Достоинства:</label>
                                        <textarea name="advantages" placeholder="Что понравилось?"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Недостатки:</label>
                                        <textarea name="disadvantages" placeholder="Что не понравилось?"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Комментарий:</label>
                                        <textarea name="comment" placeholder="Ваш отзыв..." required></textarea>
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary">Отправить отзыв</button>
                                        <button type="button" class="btn btn-secondary" onclick="hideReviewForm()">Отмена</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Остальные вкладки -->
                    <div class="tab-pane" id="questions">Вопросы и ответы</div>
                    <div class="tab-pane" id="delivery">Доставка и оплата</div>
                    <div class="tab-pane" id="warranty">Гарантия</div>
                </div>
            </div>

            <!-- Похожие товары -->
            <?php if (!empty($similar_products)): ?>
                <section class="similar-products">
                    <h2>Похожие товары</h2>
                    <div class="products-grid">
                        <?php foreach ($similar_products as $similar): ?>
                            <?php 
                            $product = $similar;
                            include 'templates/product-card.php';
                            ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Недавно просмотренные -->
            <?php if (!empty($_SESSION['recently_viewed'])): ?>
                <?php 
                $recent_ids = array_slice($_SESSION['recently_viewed'], 1, 6); // Пропускаем текущий товар
                if (!empty($recent_ids)):
                    $ids_placeholder = str_repeat('?,', count($recent_ids) - 1) . '?';
                    $recent_stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($ids_placeholder)");
                    $recent_stmt->execute($recent_ids);
                    $recent_products = $recent_stmt->fetchAll();
                ?>
                    <?php if (!empty($recent_products)): ?>
                        <section class="recently-viewed">
                            <h2>Вы недавно смотрели</h2>
                            <div class="products-grid">
                                <?php foreach ($recent_products as $recent): ?>
                                    <?php 
                                    $product = $recent;
                                    include 'templates/product-card.php';
                                    ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Футер -->
    <?php include 'templates/footer.php'; ?>

    <!-- Скрипты -->
    <script src="js/main.js"></script>
    <script src="js/product.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/zoom-vanilla.js/dist/zoom.min.js"></script>
    
    <script>
    // Инициализация зума
    document.addEventListener('DOMContentLoaded', function() {
        const image = document.getElementById('main-product-image');
        if (image) {
            new Zoom(image, {
                background: 'rgba(0, 0, 0, 0.8)',
                on: 'click'
            });
        }
    });
    
    // Смена основного изображения
    function changeMainImage(src, element) {
        const mainImage = document.getElementById('main-product-image');
        if (mainImage) {
            mainImage.src = src;
            mainImage.dataset.zoom = src;
            
            // Обновляем активную миниатюру
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            element.classList.add('active');
            
            // Переинициализируем зум
            new Zoom(mainImage, {
                background: 'rgba(0, 0, 0, 0.8)',
                on: 'click'
            });
        }
    }
    
    // Изменение количества
    function changeQuantity(delta) {
        const input = document.getElementById('product-quantity');
        let value = parseInt(input.value) || 1;
        const max = parseInt(input.max) || 10;
        const min = parseInt(input.min) || 1;
        
        value += delta;
        if (value < min) value = min;
        if (value > max) value = max;
        
        input.value = value;
    }
    
    // Покупка сейчас
    function buyNow(productId) {
        const quantity = document.getElementById('product-quantity').value;
        updateCart(productId, 'add', quantity);
        setTimeout(() => {
            window.location.href = 'cart.php?checkout=true';
        }, 500);
    }
    
    // Обновление корзины
    function updateCart(productId, action, quantity = null) {
        if (!quantity) {
            quantity = document.getElementById('product-quantity').value;
        }
        
        const formData = new FormData();
        formData.append('action', action === 'add' ? 'add_to_cart' : 'update_cart');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        
        fetch('ajax_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Обновляем кнопку корзины
                const cartBtn = document.querySelector('.btn-cart');
                if (cartBtn) {
                    if (action === 'add') {
                        cartBtn.innerHTML = `
                            <i class="fas fa-check"></i>
                            <span>В корзине (${data.quantity})</span>
                            <small>Изменить</small>
                        `;
                        cartBtn.classList.add('in-cart');
                    }
                }
                
                // Обновляем счетчик в хедере
                updateHeaderCartCount(data.cart_count);
                
                // Показываем уведомление
                showNotification('Товар добавлен в корзину', 'success');
            }
        });
    }
    
    // Переключение вкладок
    function switchTab(tabName) {
        // Обновляем активную кнопку
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
        
        // Показываем активную вкладку
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('active');
        });
        document.getElementById(tabName).classList.add('active');
    }
    
    // Прокрутка к отзывам
    function scrollToReviews() {
        document.querySelector('.tab-btn[onclick="switchTab(\'reviews\')"]').click();
        document.getElementById('reviews').scrollIntoView({ behavior: 'smooth' });
    }
    
    // Показать форму отзыва
    function showReviewForm() {
        document.getElementById('review-form').style.display = 'block';
        scrollToReviews();
    }
    
    function hideReviewForm() {
        document.getElementById('review-form').style.display = 'none';
    }
    
    // Рейтинг в форме
    document.querySelectorAll('.rating-input i').forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.dataset.rating;
            document.querySelector('input[name="rating"]').value = rating;
            
            // Обновляем отображение звезд
            document.querySelectorAll('.rating-input i').forEach((s, index) => {
                if (index < rating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });
    });
    
    // Отправка формы отзыва
    document.getElementById('new-review-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'add_review');
        formData.append('product_id', <?= $product_id ?>);
        
        fetch('ajax_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Отзыв отправлен на модерацию', 'success');
                hideReviewForm();
                setTimeout(() => location.reload(), 2000);
            }
        });
    });
    </script>
</body>
</html>