<?php
  include_once "config.php";

  define("PROTOCAL", isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on" ? "https" : "http");
  define("CURRENT_URL", urldecode(PROTOCAL . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"));

  $columnTypes = array(
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
  );

  function assigned($data) {
    return isset($data) && $data != "";
  }

  function concat($array1, $array2) {
    $array3 = array();

    foreach ($array1 as $element) {
      array_push($array3, $element);
    }

    foreach ($array2 as $element) {
      array_push($array3, $element);
    }

    return $array3;
  }

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
    session_start();
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

  function listFile($directory) {
    $results = array_filter(glob("$directory/*"), "is_file");

    $files = array();

    foreach ($results as $result) {
      array_push($files, str_replace("$directory/", "", $result));
    }

    return $files;
  }

  function getURLParentLocation() {
    $locations = explode("/", $_SERVER["PHP_SELF"]);
    return $locations[count($locations) - 2];
  }
?>
