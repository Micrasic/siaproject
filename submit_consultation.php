<?php
session_start(); // Начинаем сессию
include "db.php";

header('Content-Type: application/json'); // Устанавливаем заголовок для JSON

try {
    // Получаем данные из POST-запроса
    $name = $_POST['name'] ?? '';
    $mail = $_POST['mail'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $message = $_POST['message'] ?? '';
    $status = 'Ожидает ответа';

    // Подготавливаем SQL-запрос
    $stmt = $conn->prepare("INSERT INTO consultation (name, mail, phone, message, status) VALUES (:name, :mail, :phone, :message, :status)");

    // Привязываем параметры
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':mail', $mail);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':status', $status);

    // Выполняем запрос
    $stmt->execute();

    // Возвращаем сообщение об успехе
    echo json_encode(['success' => true, 'message' => "Консультация успешно отправлена!"]);
    exit();
} catch (PDOException $e) {
    // Возвращаем сообщение об ошибке
    echo json_encode(['success' => false, 'message' => "Ошибка на стороне сервера. Пожалуйста, попробуйте позже."]);
    exit();
}
?>
