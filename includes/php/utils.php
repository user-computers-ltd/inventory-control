<?php
  include_once ROOT_PATH . "system/config.php";

  session_start();
  define("PROTOCAL", isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on" ? "https" : "http");
  define("CURRENT_URL", PROTOCAL . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

  define("COLUMN_TYPES", array(
    "INT",
    "DECIMAL",
    "FLOAT",
    "DOUBLE",
    "BOOLEAN",
    "CHAR",
    "VARCHAR",
    "TEXT",
    "DATE",
    "DATETIME",
    "TIMESTAMP",
    "ENUM"
  ));

  function consoleLog($data) {
    echo "<script>";
    echo "console.log(" . json_encode($data) . ")";
    echo "</script>";
  }

  function throwError($message) {
    http_response_code(500);
    die($message);
    exit(0);
  }

  function sendErrorPage($error) {
    unset($_SESSION["error"]);

    $_SESSION["error"] = $error;
    $_SESSION["error"]["url"] = CURRENT_URL;
    header("Location: " . BASE_URL . "error.php");
  }

  function listDirectory($directory) {
    $results = array_filter(glob("$directory/*"), "is_dir");

    $dirs = array();

    foreach ($results as $result) {
      $dir = str_replace("$directory/", "", $result);

      $folders = listDirectory($result);

      if (count($folders) > 0) {
        array_push($dirs, array("name" => $dir, "sub" => $folders));
      } else {
        array_push($dirs, $dir);
      }
    }

    return $dirs;
  }
?>
