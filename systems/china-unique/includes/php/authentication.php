<?php
  session_start();

  include_once "config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  if (!isset($_SESSION["user"])) {
    $_SESSION["previous_url"] = CURRENT_URL;
    header("Location: " . SYSTEM_URL . "login.php");
    exit;
  }

  function logout() {
    unset($_SESSION["user"]);
    header("Location: " . CURRENT_URL);
    exit;
  }

  function isSupervisor() {
    if (isset($_SESSION["user"])) {
      $result = query("SELECT access_level FROM `user` WHERE username=\"". $_SESSION["user"] . "\"");

      if (isset($result[0])) {
        return $result[0]["access_level"] === "supervisor";
      }
    }

    return false;
  }
?>
