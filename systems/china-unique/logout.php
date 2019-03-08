<?php
  define("SYSTEM_PATH", "");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";

  session_start();

  unset($_SESSION["user"]);
  header("Location: " . SYSTEM_URL . "login.php");
  exit;
?>
