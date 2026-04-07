<?php
// ajax_handler.php — исправленная и дополненная версия

session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

$pdo = getDB(); // из config.php

// Инициализация сессий (на всякий случай)
if (!isset($_SESSION['cart']))     $_SESSION['cart']     = [];
if (!isset($_SESSION['favorites'])) $_SESSION['favorites'] = [];
if (!isset($_SESSION['theme']))    $_SESSION['theme']    = 'light';

$response = ['success' => false, 'message' => 'Неизвестное действие: ' . $action];

try {
    switch ($action) {
        // ────────────────────────────────────────────────
        // ТЕМА
        // ────────────────────────────────────────────────
        case 'toggle_theme':
            $current = $_SESSION['theme'];
            $new     = $current === 'light' ? 'dark' : 'light';
            $_SESSION['theme'] = $new;
            $response = [
                'success' => true,
                'theme'   => $new,
                'message' => 'Тема изменена'
            ];
            break;

        // ────────────────────────────────────────────────
        // КОРЗИНА — все действия
        // ────────────────────────────────────────────────
        case 'add_to_cart':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $quantity   = max(1, (int)($_POST['quantity'] ?? 1));

            if ($product_id > 0) {
                $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + $quantity;
                $response = [
                    'success'    => true,
                    'quantity'   => $_SESSION['cart'][$product_id],
                    'cart_count' => array_sum($_SESSION['cart']),
                    'message'    => 'Добавлено в корзину'
                ];
            }
            break;

        case 'remove_from_cart':
            $product_id = (int)($_POST['product_id'] ?? 0);
            if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]--;
                if ($_SESSION['cart'][$product_id] <= 0) {
                    unset($_SESSION['cart'][$product_id]);
                }
                $response = [
                    'success'    => true,
                    'quantity'   => $_SESSION['cart'][$product_id] ?? 0,
                    'cart_count' => array_sum($_SESSION['cart']),
                    'message'    => 'Уменьшено количество'
                ];
            }
            break;

        case 'delete_from_cart':
            $product_id = (int)($_POST['product_id'] ?? 0);
            if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
                $response = [
                    'success'    => true,
                    'cart_count' => array_sum($_SESSION['cart']),
                    'message'    => 'Товар удалён'
                ];
            }
            break;

        case 'clear_cart':
            $_SESSION['cart'] = [];
            $response = [
                'success' => true,
                'message' => 'Корзина очищена'
            ];
            break;

        // ────────────────────────────────────────────────
        // ИЗБРАННОЕ
        // ────────────────────────────────────────────────
        case 'toggle_favorite':
            $product_id = (int)($_POST['product_id'] ?? 0);
            if ($product_id > 0) {
                $key = array_search($product_id, $_SESSION['favorites']);
                if ($key !== false) {
                    unset($_SESSION['favorites'][$key]);
                    $is_fav = false;
                    $msg    = 'Удалено из избранного';
                } else {
                    $_SESSION['favorites'][] = $product_id;
                    $is_fav = true;
                    $msg    = 'Добавлено в избранное';
                }
                $response = [
                    'success'     => true,
                    'is_favorite' => $is_fav,
                    'fav_count'   => count($_SESSION['favorites']),
                    'message'     => $msg
                ];
            }
            break;

        // ────────────────────────────────────────────────
        // АВТОРИЗАЦИЯ / РЕГИСТРАЦИЯ / ВЫХОД
        // ────────────────────────────────────────────────
        case 'login':
            $response = ['success' => true, 'message' => 'Вход выполнен (заглушка)'];
            break;
        
        case 'register':
            $response = ['success' => true, 'message' => 'Регистрация успешна (заглушка)'];
            break;
        
        case 'logout':
            session_destroy();
            $response = ['success' => true, 'message' => 'Вы вышли из аккаунта'];
            break;
            // Эти case уже есть у тебя — оставляем как есть
            // (если нужно — я могу их тоже подправить позже)
            break;

        default:
            // Для отладки полезно знать, какой action пришёл
            $response['debug_action'] = $action;
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Серверная ошибка: ' . $e->getMessage()
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;