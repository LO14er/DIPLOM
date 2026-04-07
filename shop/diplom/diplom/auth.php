<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($action === 'register') {
        // Регистрация
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->execute([$email, $hashed_password]);
        
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['user_email'] = $email;
        
        echo json_encode(['success' => true, 'message' => 'Регистрация успешна']);
    } else {
        // Вход
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            echo json_encode(['success' => true, 'message' => 'Вход успешен']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Неверные данные']);
        }
    }
    exit;
}
?>