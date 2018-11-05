<?php
  define("ROOT_PATH", "../../");
  include_once "admin.php";

  if (!isset($_GET["database"])) {
    throwError("Missing database");
  } else if (!isset($_GET["sql"])) {
    throwError("Missing SQL");
  }

  selectDatabase($_GET["database"]);

  echo json_encode(query($_GET["sql"]));
?>
