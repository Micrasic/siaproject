<?php
//выход из сессии
session_start();
session_destroy();
header('Location: ../index.php');
?>