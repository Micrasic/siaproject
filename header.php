<?php
include "app/db.php"; 
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$errorMessage = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ООО "Си Ай Проект"</title>
  <link rel="stylesheet" href="app/assets/css/style.css" />
  <link rel="icon" href="app/assets/img/icons/logo.png" type="image/png">
</head>
<body>
  <header>
    <div class="logo">
      <a href="index.php">
        <img src="app/assets/img/icons/logo.png" alt="Логотип" />
      </a>
      <div class="logo-text">
        <h1>Си Ай Проект</h1>
        <p>Проектно-строительная компания</p>
      </div>
    </div>
    <div class="menu">
      <?php if ($isLoggedIn): ?>
        <a href="app/logout.php" class="btn">Выход</a>
        <a href="admin.php" class="btn">Админ панель</a>
      <?php endif; ?>
      <button id="loginBtn" class="btn" type="button" <?php if ($isLoggedIn) echo 'style="display:none;"'; ?>>Вход</button>
      <a href="catalog.php" class="btn">Наши проекты</a>
    </div>
  </header>

  <div id="loginPopup" class="login-popup" aria-hidden="true" role="dialog" aria-labelledby="popupTitle" aria-modal="true" style="display:none;">
    <div class="login-popup-content">
      <h2 id="popupTitle">Вход в систему</h2>
      <form id="loginForm" method="POST" action="app/login.php" novalidate>
        <label for="login">Логин</label>
        <input type="text" id="login" name="login" placeholder="Введите логин" autocomplete="login" required />
        <label for="password">Пароль</label>
        <input type="password" id="password" name="password" placeholder="Введите пароль" autocomplete="current-password" required />
        <button type="submit">Войти</button>
      </form>
      <?php if ($errorMessage): ?>
        <div class="error-message" style="color:red; margin-top: 10px;"><?php echo htmlspecialchars($errorMessage); ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div id="imageModal" class="modal" aria-hidden="true">
    <span id="closeModal" class="close">&times;</span>
    <div class="modal-navigation">
      <button id="prevImageBtn" class="nav-button">❮</button>
    </div>
    <img class="modal-content" id="modalImage">
    <div class="modal-navigation">
      <button id="nextImageBtn" class="nav-button">❯</button>
    </div>
  </div>

  <script>
    const loginBtn = document.getElementById('loginBtn');
    const popup = document.getElementById('loginPopup');
    const loginForm = document.getElementById('loginForm');

    function openPopup() {
      popup.style.display = 'flex';
      popup.setAttribute('aria-hidden', 'false');
      document.getElementById('login').focus();
      document.body.style.overflow = 'hidden';
    }

    function closePopup() {
      popup.style.display = 'none';
      popup.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      loginForm.reset();
    }

    loginBtn.addEventListener('click', () => {
      openPopup();
    });

    window.addEventListener('click', (e) => {
      if (e.target === popup) {
        closePopup();
      }
    });

    window.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && popup.style.display === 'flex') {
        closePopup();
      }
    });

    const hasError = <?php echo json_encode(!empty($errorMessage)); ?>;
    if (hasError) {
      openPopup();
    }
  </script>
</body>
</html>
