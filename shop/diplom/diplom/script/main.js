document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ main.js успешно загружен');

    // ====================== СМЕНА ТЕМЫ (ИСПРАВЛЕННЫЙ) ======================
    const themeBtn = document.getElementById('theme-toggle');
    
    // Функция установки темы
    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        
        // Меняем иконку
        if (themeBtn) {
            const icon = themeBtn.querySelector('i');
            if (icon) {
                icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }
        
        console.log('🌙 Тема установлена на:', theme);
    }
    
    // Инициализация темы при загрузке
    let savedTheme = localStorage.getItem('theme');
    if (!savedTheme) {
        savedTheme = document.documentElement.getAttribute('data-theme') || 'light';
    }
    setTheme(savedTheme);
    
    // Обработчик клика по кнопке
    if (themeBtn) {
        themeBtn.addEventListener('click', function() {
            let current = document.documentElement.getAttribute('data-theme') || 'light';
            let newTheme = current === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        });
    } else {
        console.warn('⚠️ Кнопка темы не найдена!');
    }

    // ====================== AJAX ======================
    function sendAction(action, productId = null) {
        let body = `action=${action}`;
        if (productId) body += `&product_id=${productId}`;

        fetch('ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        })
        .then(r => r.json())
        .then(data => {
            console.log('Ответ сервера:', data);
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Ошибка');
            }
        })
        .catch(err => console.error('Ошибка:', err));
    }

    // ====================== ОБРАБОТКА КЛИКОВ ======================
    document.addEventListener('click', function(e) {
        // Избранное
        const favBtn = e.target.closest('.btn-favorite');
        if (favBtn) {
            e.preventDefault();
            const productId = favBtn.getAttribute('data-product-id');
            
            if (productId) {
                fetch('ajax_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=toggle_favorite&product_id=${productId}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const icon = favBtn.querySelector('i');
                        if (icon) {
                            if (data.is_favorite) {
                                icon.className = 'fas fa-heart text-danger';
                            } else {
                                icon.className = 'far fa-heart';
                            }
                        }
                        console.log('❤️ Избранное обновлено');
                    }
                });
            }
        }

        // Добавление в корзину
        const addBtn = e.target.closest('[data-action="add-to-cart"]');
        if (addBtn) {
            const productId = addBtn.getAttribute('data-product-id');
            if (productId) sendAction('add_to_cart', productId);
        }

        // Уменьшение количества
        const removeBtn = e.target.closest('[data-action="remove-from-cart"]');
        if (removeBtn) {
            const productId = removeBtn.getAttribute('data-product-id');
            if (productId) sendAction('remove_from_cart', productId);
        }
    });

    // ====================== МОДАЛКИ ======================
    window.showModal = function(id) {
        const modal = document.getElementById(id);
        if (modal) modal.style.display = 'flex';
    };

    window.closeModal = function(id) {
        const modal = document.getElementById(id);
        if (modal) modal.style.display = 'none';
    };

    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal(modal.id);
        });
    });
});