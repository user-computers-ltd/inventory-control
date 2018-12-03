<?php
  define("ROOT_PATH", "../");
  include_once "../includes/php/utils.php";
  include_once "../includes/php/admin.php";

  if (assigned($_POST["action"])) {
    switch ($_POST["action"]) {
      case "create-database":
        if ($_POST["database"]) {
          createDatabase($_POST["database"]);
        } else {
          throwError("missing database name");
        }
        break;
      case "copy-database":
        if ($_POST["database1"] && $_POST["database2"]) {
          copyDatabase($_POST["database1"], $_POST["database2"]);
        } else {
          throwError("missing database names");
        }
        break;
      case "delete-database":
        if ($_POST["database"]) {
          dropDatabase($_POST["database"]);
        } else {
          throwError("missing database name");
        }
        break;
      case "create-table":
        if ($_POST["database"] && $_POST["table"]) {
          createTable($_POST["database"], $_POST["table"], array(array(
            "field" => "id",
            "type" => "INT",
            "length" => "12",
            "extra" => "UNSIGNED AUTO_INCREMENT PRIMARY KEY"
          )));
        } else {
          throwError("missing database or table name");
        }
        break;
      case "create-import-table":
        if ($_POST["database"] && $_POST["table"] && $_POST["field"] && $_POST["name"] && $_POST["type"] && $_FILES["import"]) {
          $columns = array();

          for ($i = 0; $i < count($_POST["field"]); $i++) {
            array_push($columns, array(
              "field" => $_POST["field"][$i],
              "name" => $_POST["name"][$i],
              "type" => $_POST["type"][$i],
              "length" => $_POST["length"][$i],
              "extra" => $_POST["extra"][$i]
            ));
          }

          createAndImportTable($_POST["database"], $_POST["table"], $columns, $_FILES["import"]);
        } else {
          throwError("missing database, table, columns or import file");
        }
        break;
      case "import-table":
        if ($_POST["database"] && $_POST["table"] && $_POST["field"] && $_POST["name"] && $_FILES["import"]) {
          $columns = array();

          for ($i = 0; $i < count($_POST["field"]); $i++) {
            array_push($columns, array(
              "field" => $_POST["field"][$i],
              "name" => $_POST["name"][$i]
            ));
          }

          importTable($_POST["database"], $_POST["table"], $columns, $_FILES["import"]);
        } else {
          throwError("missing database, table, columns or import file");
        }
        break;
      case "export-table":
        if ($_POST["database"] && $_POST["table"]) {
          echo json_encode(exportTable($_POST["database"], $_POST["table"]));
        } else {
          throwError("missing database or table");
        }
        break;
      case "copy-table":
        if ($_POST["table1"] && $_POST["table2"]) {
          copyTable($_POST["table1"], $_POST["table2"]);
        } else {
          throwError("missing table names");
        }
        break;
      case "delete-table":
        if ($_POST["database"] && $_POST["table"]) {
          dropTable($_POST["database"], $_POST["table"]);
        } else {
          throwError("missing database or table name");
        }
        break;
      case "clear-table":
        if ($_POST["database"] && $_POST["table"]) {
          clearTable($_POST["database"], $_POST["table"]);
        } else {
          throwError("missing database or table name");
        }
        break;
      case "create-column":
        if ($_POST["database"] && $_POST["table"] && $_POST["field"] && $_POST["type"]) {
          createColumn($_POST["database"], $_POST["table"], $_POST["field"], $_POST["type"], $_POST["extra"]);
        } else {
          throwError("missing database, table or column");
        }
        break;
      case "update-column":
        if ($_POST["database"] && $_POST["table"] && $_POST["field"] && $_POST["type"]) {
          updateColumn($_POST["database"], $_POST["table"], $_POST["field"], $_POST["type"], $_POST["extra"]);
        } else {
          throwError("missing database, table or column");
        }
        break;
      case "delete-column":
        if ($_POST["database"] && $_POST["table"] && $_POST["column"]) {
          dropColumn($_POST["database"], $_POST["table"], $_POST["column"]);
        } else {
          throwError("missing database, table or column");
        }
        break;
      case "reinitialize-database":
        if ($_POST["system"] && $_POST["overwrite"]) {
          $system = $_POST["system"];

          if ($_POST["overwrite"] == "true") {
            dropDatabase($system);
          }

          createDatabase($system);
          executeSQLFiles($_POST["system"], array_map(function ($table) use ($system) {
            return ROOT_PATH . "systems/$system/data-model/tables/$table";
          }, listFile(ROOT_PATH . "systems/$system/data-model/tables")));
        } else {
          throwError("missing system");
        }
        break;
      default:
        throwError("invalid action: " . $_POST["action"]);
    }
  } else {
    throwError("missing action");
  }

  exit;
?>
