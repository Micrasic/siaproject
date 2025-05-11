<?php
session_start();
require 'db.php'; // Подключение к базе данных

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'];
    $password = $_POST['password'];

    // Подготовка SQL-запроса
    $stmt = $conn->prepare("SELECT * FROM users WHERE login = :login");
    $stmt->bindParam(':login', $login);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        
        // Проверка пароля (без хеширования)
        if ($password === $user['password']) {
            // Успешный вход
            $_SESSION['user_id'] = $user['id'];
            header("Location: ../admin.php");
            exit();
        } else {
            // Неверный пароль
            $_SESSION['error'] = "Неверный пароль.";
            header("Location: ../index.php");
            exit();
        }
    } else {
        // Пользователь не найден
        $_SESSION['error'] = "Пользователь не найден.";
        header("Location: ../index.php");
        exit();
    }
}
?>