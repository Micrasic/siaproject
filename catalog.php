<?php
include "header.php";

$limit = 11;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

try {
    $totalStmt = $conn->query("SELECT COUNT(*) FROM project");
    $totalProjects = $totalStmt->fetchColumn();
    $totalPages = ceil($totalProjects / $limit);

    $stmt = $conn->prepare("
        SELECT p.id, p.img AS main_img, p.name, p.description, i.name AS img_name
        FROM project p
        LEFT JOIN img i ON p.id = i.project_id
        ORDER BY p.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $projects = [];
    while ($row = $stmt->fetch()) {
        $projectId = $row['id'];
        if (!isset($projects[$projectId])) {
            $projects[$projectId] = [
                'main_img' => $row['main_img'],
                'name' => $row['name'],
                'description' => $row['description'],
                'images' => []
            ];
        }
        if ($row['img_name']) {
            $projects[$projectId]['images'][] = $row['img_name'];
        }
    }
    $projects = array_values($projects);
} catch (Exception $e) {
    echo "Ошибка при загрузке проектов: " . $e->getMessage();
    $projects = [];
}
?>

<div class="projects-container">
    <section class="grid" aria-label="Каталог проектов">
        <?php foreach ($projects as $project): ?>
            <article class="project-card" tabindex="0" data-images='<?php echo htmlspecialchars(json_encode($project['images'])); ?>'>
                <img class="project-image" src="<?php echo htmlspecialchars($project['main_img']); ?>" alt="Проект <?php echo htmlspecialchars($project['name']); ?>" />
                <div class="project-content">
                    <h2 class="project-title"><?php echo htmlspecialchars($project['name']); ?></h2>
                    <p class="project-desc"><?php echo htmlspecialchars($project['description']); ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>">« Назад</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="<?php echo ($i === $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>">Вперед »</a>
        <?php endif; ?>
    </div>
</div>

<?php
include "footer.php";
?>
