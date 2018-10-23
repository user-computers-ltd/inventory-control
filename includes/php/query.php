<?php
  define("ROOT_PATH", "../../");
  include_once "admin.php";
  include_once ROOT_PATH . "system/config.php";

  if (!isset($_GET["database"])) {
    throwError("Missing database");
  } else if (!isset($_GET["sql"])) {
    throwError("Missing SQL");
  } else if (strpos($_GET["sql"], "SELECT") !== 0) {
    throwError("Only selective query is allowed");
  }

  selectDatabase($_GET["database"]);

  echo json_encode(array_map(function ($row) {
    return array_filter($row, function ($column) { return !is_numeric($column); }, ARRAY_FILTER_USE_KEY);
  }, query($_GET["sql"])));
?>
