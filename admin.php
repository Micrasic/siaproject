<?php 
include "header.php"; 

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$errors = []; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_project'])) { 
    $name = trim($_POST['name'] ?? ''); 
    $description = trim($_POST['description'] ?? ''); 

    if ($name === '') { 
        $errors[] = "Название проекта обязательно."; 
    } 
    if ($description === '') { 
        $errors[] = "Описание проекта обязательно."; 
    } 

    $main_img = null; 
    if (isset($_FILES['main_img']) && $_FILES['main_img']['error'] === UPLOAD_ERR_OK) { 
        $upload_dir = 'app/assets/img/projects/'; 
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
    } else { 
        $errors[] = "Главное изображение обязательно."; 
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
            $stmt = $conn->prepare("INSERT INTO project (name, description, img) VALUES (:name, :description, :main_img)"); 
            $stmt->bindParam(':name', $name); 
            $stmt->bindParam(':description', $description); 
            $stmt->bindParam(':main_img', $main_img); 
            $stmt->execute(); 
            $project_id = $conn->lastInsertId(); 
            $stmt_img = $conn->prepare("INSERT INTO img (project_id, name) VALUES (:project_id, :img_name)"); 
            foreach ($additional_images as $img_path) { 
                $stmt_img->bindParam(':project_id', $project_id); 
                $stmt_img->bindParam(':img_name', $img_path); 
                $stmt_img->execute(); 
            } 
            header("Location: " . $_SERVER['PHP_SELF']); 
            exit; 
        } catch (Exception $e) { 
            $errors[] = "Ошибка при добавлении проекта: " . $e->getMessage(); 
        } 
    } 
} 

$consultations_per_page = 10; 
$current_page = isset($_GET['consultation_page']) && is_numeric($_GET['consultation_page']) && $_GET['consultation_page'] > 0 ? (int)$_GET['consultation_page'] : 1; 
$offset = ($current_page - 1) * $consultations_per_page; 
$total_stmt = $conn->prepare("SELECT COUNT(*) FROM consultation WHERE status = 'Ожидает ответа'"); 
$total_stmt->execute(); 
$total_consultations = $total_stmt->fetchColumn(); 
$total_pages = ceil($total_consultations / $consultations_per_page); 
$stmt = $conn->prepare("SELECT * FROM consultation WHERE status = 'Ожидает ответа' ORDER BY created_at DESC LIMIT :limit OFFSET :offset"); 
$stmt->bindValue(':limit', $consultations_per_page, PDO::PARAM_INT); 
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT); 
$stmt->execute(); 
$consultations = $stmt->fetchAll(); 

$projects_per_page = 10; 
$current_project_page = isset($_GET['project_page']) && is_numeric($_GET['project_page']) && $_GET['project_page'] > 0 ? (int)$_GET['project_page'] : 1; 
$project_offset = ($current_project_page - 1) * $projects_per_page; 

try { 
    $totalStmt = $conn->query("SELECT COUNT(*) FROM project"); 
    $totalProjects = $totalStmt->fetchColumn(); 
    $totalProjectPages = ceil($totalProjects / $projects_per_page); 

    $stmt = $conn->prepare("SELECT p.id, p.img AS main_img, p.name, p.description FROM project p ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset"); 
    $stmt->bindParam(':limit', $projects_per_page, PDO::PARAM_INT); 
    $stmt->bindParam(':offset', $project_offset, PDO::PARAM_INT); 
    $stmt->execute(); 
    $projects = []; 
    $projectIds = [];

    while ($row = $stmt->fetch()) { 
        $projects[$row['id']] = [ 
            'id' => $row['id'], 
            'main_img' => $row['main_img'], 
            'name' => $row['name'], 
            'description' => $row['description'],
            'images' => []
        ]; 
        $projectIds[] = $row['id'];
    }

    if (!empty($projectIds)) {
        $inQuery = implode(',', array_fill(0, count($projectIds), '?'));
        $stmt_imgs = $conn->prepare("SELECT project_id, name FROM img WHERE project_id IN ($inQuery)");
        foreach ($projectIds as $k => $id) {
            $stmt_imgs->bindValue(($k+1), $id, PDO::PARAM_INT);
        }
        $stmt_imgs->execute();
        while ($imgRow = $stmt_imgs->fetch()) {
            $pid = $imgRow['project_id'];
            $imgName = $imgRow['name'];
            if (isset($projects[$pid])) {
                $projects[$pid]['images'][] = $imgName;
            }
        }
    }

    foreach ($projects as &$project) {
        $project['all_images'] = $project['images'];
    }
    unset($project);

} catch (Exception $e) { 
    echo "Ошибка загрузки проектов: " . $e->getMessage(); 
    $projects = []; 
} 
?> 
<div class="main-content">
<div class="admin-panel">
    <h1>Управление проектами и консультациями</h1>
    
    <div class="tabs">
        <button class="tab-button active" onclick="openTab('projects')">Проекты</button>
        <button class="tab-button" onclick="openTab('consultations')">Консультации</button>
    </div>

    <div id="projects" class="tab-content" style="display: block;">
        <?php if (!empty($errors)): ?>
            <div class="error-messages" style="color:red; margin-bottom: 10px;">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <h1>Проекты</h1>
        <div class="grid">
            <div class="project-card">
                <form method="POST" enctype="multipart/form-data">
                    <h3>Добавить новый проект</h3>
                    <label for="name">Название проекта:</label><br>
                    <input type="text" name="name" required placeholder="Введите название"><br>
                    <label for="description">Описание проекта:</label><br>
                    <textarea name="description" required placeholder="Введите описание"></textarea><br>
                    <label for="main_img">Главное изображение:</label>
                    <input type="file" name="main_img" accept="image/*" required><br>
                    <label for="additional_images">Доп. изображения:</label>
                    <input type="file" name="additional_images[]" accept="image/*" multiple><br>
                    <button type="submit" name="add_project">Добавить</button>
                </form>
            </div>

            <?php foreach ($projects as $project): ?>
                <div class="project-card" data-id="<?php echo htmlspecialchars($project['id']); ?>" data-images='<?php echo htmlspecialchars(json_encode($project['all_images'])); ?>'>
                <img src="<?php echo htmlspecialchars($project['main_img'] ?? 'default_image.jpg'); ?>" alt="Проект <?php echo htmlspecialchars($project['name'] ?? 'Без названия'); ?>" class="project-image">
                    <div class="project-content">
                        <h3 class="project-title"><?php echo htmlspecialchars($project['name'] ?? 'Без названия'); ?></h3>
                        <p class="project-desc"><?php echo htmlspecialchars($project['description'] ?? 'Нет описания'); ?></p>
                        <button type="button" onclick="deleteProject(<?php echo $project ['id']; ?>)">Удалить</button>
                        <button type="button" onclick="openEditForm( 
                            <?php echo $project['id'] ?? 0; ?>, 
                            '<?php echo htmlspecialchars(addslashes($project['name'])); ?>', 
                            '<?php echo htmlspecialchars(addslashes($project['description'])); ?>' 
                        )">Изменить</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="pagination">
            <?php if ($current_project_page > 1): ?>
                <a href="?project_page=<?php echo $current_project_page - 1; ?>">« Назад</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalProjectPages; $i++): ?>
                <a href="?project_page=<?php echo $i; ?>" class="<?php echo ($i === $current_project_page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($current_project_page < $totalProjectPages): ?>
                <a href="?project_page=<?php echo $current_project_page + 1; ?>">Вперед »</a>
            <?php endif; ?>
        </div>
    </div>

    <div id="consultations" class="tab-content" style="display: none;">
    <h1>Консультации</h1>
    <table id="consultationsTable">
        <thead>
            <tr>
                <th>Имя</th>
                <th>Email</th>
                <th>Телефон</th>
                <th>Сообщение</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($consultations): ?>
            <?php foreach ($consultations as $consultation): ?>
            <tr id="consultation-row-<?php echo htmlspecialchars($consultation['id']); ?>">
                <td><?php echo htmlspecialchars($consultation['name']); ?></td>
                <td><?php echo htmlspecialchars($consultation['mail']); ?></td>
                <td><?php echo htmlspecialchars($consultation['phone']); ?></td>
                <td><?php echo nl2br(htmlspecialchars($consultation['message'])); ?></td>
                <td class="status-cell"><?php echo htmlspecialchars($consultation['status']); ?></td>
                <td>
    <span class="icon delete" onclick="deleteConsultation(<?php echo $consultation['id']; ?>)" title="Удалить">&#10006;</span>
    <?php if ($consultation['status'] !== 'Завершено'): ?>
        <span class="icon complete" onclick="updateConsultationStatus(<?php echo $consultation['id']; ?>, this)" title="Завершено">&#10004;</span>
    <?php else: ?>
        <span class="icon complete disabled" title="Завершено">&#10004;</span>
    <?php endif; ?>
</td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center;">Консультации не найдены</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <div class="pagination">
        <?php 
        for ($page = 1; $page <= $total_pages; $page++): 
            $active_class = ($page === $current_page) ? 'active' : '';
        ?>
        <a href="?consultation_page=<?php echo $page; ?>&tab=consultations" class="<?php echo $active_class; ?>"><?php echo $page; ?></a>
        <?php endfor; ?>
    </div>
</div>

</div>
<div id="editProjectModal" class="modal" style="display:none;">
    <span id="cl" class="close" onclick="closeEditForm()">&times;</span>
    <h3>Изменить проект</h3>
    <form id="editProjectForm" enctype="multipart/form-data">
        <input type="hidden" name="project_id" id="editProjectId">
        <label for="editProjectName">Название проекта:</label>
        <input type="text" name="name" id="editProjectName"><br>
        <label for="editProjectDescription">Описание проекта:</label>
        <textarea name="description" id="editProjectDescription"></textarea><br>
        <label for="editMainImg">Главное изображение:</label>
        <input type="file" name="main_img" id="editMainImg" accept="image/*"><br>
        <label for="editAdditionalImages">Доп. изображения:</label>
        <input type="file" name="additional_images[]" id="editAdditionalImages" accept="image/*" multiple><br>
        <button type="submit">Сохранить изменения</button>
        <button type="button" onclick="closeEditForm()">Отмена</button>
    </form>
</div>
</div>

<script>
function openTab(tabName) {
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => {
        content.style.display = 'none';
    });

    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.classList.remove('active');
    });

    document.getElementById(tabName).style.display = 'block';
    document.querySelector(`.tab-button[onclick="openTab('${tabName}')"]`).classList.add('active');
}

const urlParams = new URLSearchParams(window.location.search);
const activeTab = urlParams.get('tab') || 'projects';
openTab(activeTab);
</script>

<?php include "footer.php"; ?>