<?php
// templates/product-card.php
if (!isset($product) || empty($product['id'])) {
    return;
}

$in_cart = isset($_SESSION['cart'][$product['id']]) && $_SESSION['cart'][$product['id']] > 0;
$is_favorite = in_array($product['id'], $_SESSION['favorites']);
$has_discount = !empty($product['discount']) && $product['discount'] > 0;
$old_price = $has_discount ? $product['price'] + $product['discount'] : $product['price'];
$discount_percent = $has_discount ? round(($product['discount'] / $old_price) * 100) : 0;
?>

<div class="product-card" data-id="<?= $product['id'] ?>">
    <div class="product-image">
        <a href="product.php?id=<?= $product['id'] ?>" class="image-link">
        <img src="images/<?= e($product['image'] ?? 'default.jpg') ?>" 
                 alt="<?= e($product['title']) ?>"
                 loading="lazy">
                 <!-- Сердечко избранного -->
                 <!-- Кнопка Избранное -->
<button class="btn-favorite" 
        data-action="toggle-favorite" 
        data-product-id="<?= $product['id'] ?>">
    <i class="<?= $is_fav ? 'fas fa-heart text-danger' : 'far fa-heart text-muted' ?>"></i>
</button>
            
            <?php if ($has_discount): ?>
                <span class="badge discount">-<?= $discount_percent ?>%</span>
            <?php endif; ?>
            
            <?php if ($product['stock'] < 10 && $product['stock'] > 0): ?>
                <span class="badge stock">Осталось мало</span>
            <?php endif; ?>
        </a>
    </div>

    <div class="product-info">
        <div class="product-brand"><?= e($product['brand'] ?? '') ?></div>
        
        <a href="product.php?id=<?= $product['id'] ?>" class="product-title">
            <?= e($product['title']) ?>
        </a>
        
        <?php if (!empty($product['rating'])): ?>
            <div class="product-rating">
                <div class="stars" style="--rating: <?= $product['rating'] ?>;">
                    ★★★★★
                </div>
                <span>(<?= $product['reviews_count'] ?? 0 ?>)</span>
            </div>
        <?php endif; ?>

        <div class="product-price">
            <?php if ($has_discount): ?>
                <span class="old-price"><?= number_format($old_price, 0, '', ' ') ?> ₽</span>
            <?php endif; ?>
            <span class="current-price"><?= number_format($product['price'], 0, '', ' ') ?> ₽</span>
        </div>

        <div class="product-stock">
            <?php if ($product['stock'] > 0): ?>
                <i class="fas fa-check-circle"></i>
                <span>В наличии</span>
            <?php else: ?>
                <i class="fas fa-times-circle"></i>
                <span>Нет в наличии</span>
            <?php endif; ?>
        </div>

        <!-- Корзина -->
<div class="product-actions">
    <?php if ($product['stock'] > 0): ?>
        <?php if ($in_cart): ?>
            <div class="cart-controls">
                <button class="btn-qty minus" data-action="remove-from-cart" data-product-id="<?= $product['id'] ?>">-</button>
                <span class="quantity"><?= $_SESSION['cart'][$product['id']] ?? 1 ?></span>
                <button class="btn-qty plus" data-action="add-to-cart" data-product-id="<?= $product['id'] ?>">+</button>
            </div>
        <?php else: ?>
            <button class="btn btn-cart" 
                    data-action="add-to-cart" 
                    data-product-id="<?= $product['id'] ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>В корзину</span>
            </button>
        <?php endif; ?>
    <?php endif; ?>
</div>
    </div>
</div>

<script>
function addToCart(productId) {
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add_to_cart&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Обновляем UI
            const card = document.querySelector(`.product-card[data-id="${productId}"]`);
            if (card) {
                const actions = card.querySelector('.product-actions');
                actions.innerHTML = `
                    <div class="cart-controls">
                        <button class="btn-qty minus" onclick="removeFromCart(${productId})">
                            <i class="fas fa-minus"></i>
                        </button>
                        <span class="quantity">${data.quantity}</span>
                        <button class="btn-qty plus" onclick="addToCart(${productId})">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                `;
            }
            
            // Обновляем счетчик в хедере
            updateCartCount(data.cart_count);
            showNotification(data.message, 'success');
        }
    });
}

function removeFromCart(productId) {
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove_from_cart&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const card = document.querySelector(`.product-card[data-id="${productId}"]`);
            if (card) {
                if (data.quantity > 0) {
                    const quantitySpan = card.querySelector('.quantity');
                    if (quantitySpan) quantitySpan.textContent = data.quantity;
                } else {
                    const actions = card.querySelector('.product-actions');
                    actions.innerHTML = `
                        <button class="btn btn-cart" onclick="addToCart(${productId})">
                            <i class="fas fa-shopping-cart"></i>
                            <span>В корзину</span>
                        </button>
                    `;
                }
            }
            
            updateCartCount(data.cart_count);
            showNotification(data.message, 'info');
        }
    });
}

function toggleFavorite(productId) {
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=toggle_favorite&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const btn = document.querySelector(`.favorite-btn[onclick="toggleFavorite(${productId})"]`);
            if (btn) {
                btn.classList.toggle('active');
            }
            showNotification(data.message, data.is_favorite ? 'success' : 'info');
        }
    });
}

function updateCartCount(count) {
    const cartBadge = document.querySelector('.cart-preview .badge');
    if (cartBadge) {
        cartBadge.textContent = count;
        cartBadge.style.display = count > 0 ? 'flex' : 'none';
    }
}

function showNotification(message, type) {
    // Простая реализация уведомлений
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
</script>