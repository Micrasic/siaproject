<?php
include "db.php"; // Adjust path as needed

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'] ?? null;

    if ($project_id) {
        try {
            // Delete additional images files
            $img_stmt = $conn->prepare("SELECT name FROM img WHERE project_id = :project_id");
            $img_stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
            $img_stmt->execute();
            $images_to_delete = $img_stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($images_to_delete as $img_path) {
                if (file_exists($img_path)) {
                    unlink($img_path);
                }
            }

            // Delete additional images DB records
            $del_img_stmt = $conn->prepare("DELETE FROM img WHERE project_id = :project_id");
            $del_img_stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
            $del_img_stmt->execute();

            // Delete main image file
            $main_img_stmt = $conn->prepare("SELECT img FROM project WHERE id = :project_id");
            $main_img_stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
            $main_img_stmt->execute();
            $main_img_path = $main_img_stmt->fetchColumn();
            if ($main_img_path && file_exists($main_img_path)) {
                unlink($main_img_path);
            }

            // Delete project DB record
            $del_stmt = $conn->prepare("DELETE FROM project WHERE id = :project_id");
            $del_stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
            $del_stmt->execute();

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID проекта не указан.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса.']);
}
?>