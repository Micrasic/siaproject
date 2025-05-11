<?php
include "db.php"; // Подключение к базе данных

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $errors = [];

    if ($project_id === null) {
        $errors[] = "ID проекта не указан.";
    }
    if ($name === '') {
        $errors[] = "Название проекта обязательно.";
    }
    if ($description === '') {
        $errors[] = "Описание проекта обязательно.";
    }

    $main_img = null;
    if (isset($_FILES['main_img']) && $_FILES['main_img']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../app/assets/img/projects/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $main_img_name = basename($_FILES['main_img']['name']);
        $main_img_path = $upload_dir . uniqid() . '_' . $main_img_name;
        if (move_uploaded_file($_FILES['main_img']['tmp_name'], $main_img_path)) {
            $main_img = $main_img_path;
        } else {
            $errors[] = "Не удалось загрузить главное изображение.";
        }
    }

    $additional_images = [];
    if (isset($_FILES['additional_images'])) {
        foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                $img_name = basename($_FILES['additional_images']['name'][$key]);
                $img_path = $upload_dir . uniqid() . '_' . $img_name;
                if (move_uploaded_file($tmp_name, $img_path)) {
                    $additional_images[] = $img_path;
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            // Получаем старые изображения из базы данных
            $stmt_old_images = $conn->prepare("SELECT name FROM img WHERE project_id = :project_id");
            $stmt_old_images->bindParam(':project_id', $project_id);
            $stmt_old_images->execute();
            $old_images = $stmt_old_images->fetchAll(PDO::FETCH_COLUMN);

            // Удаляем старые изображения из файловой системы
            foreach ($old_images as $old_image) {
                if (file_exists($old_image)) {
                    unlink($old_image);
                }
            }

            // Удаляем старые записи из базы данных
            $stmt_delete_old_images = $conn->prepare("DELETE FROM img WHERE project_id = :project_id");
            $stmt_delete_old_images->bindParam(':project_id', $project_id);
            $stmt_delete_old_images->execute();

            // Обновляем проект
            $stmt = $conn->prepare("UPDATE project SET name = :name, description = :description" . ($main_img ? ", img = :main_img" : "") . " WHERE id = :project_id");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            if ($main_img) {
                $stmt->bindParam(':main_img', $main_img);
            }
            $stmt->bindParam(':project_id', $project_id);
            $stmt->execute();

            // Вставляем новые изображения в базу данных
            if (!empty($additional_images)) {
                $stmt_img = $conn->prepare("INSERT INTO img (project_id, name) VALUES (:project_id, :img_name)");
                foreach ($additional_images as $img_path) {
                    $stmt_img->bindParam(':project_id', $project_id);
                    $stmt_img->bindParam(':img_name', $img_path);
                    $stmt_img->execute();
                }
            }

            echo json_encode(['success' => true, 'main_img' => $main_img]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => "Ошибка при обновлении проекта: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => implode(", ", $errors)]);
    }
}
?>
