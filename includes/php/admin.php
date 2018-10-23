<?php
  include_once "utils.php";
  include_once "database.php";

  function getColumnString($column) {
    $field = $column["field"];
    $type = $column["type"];
    $length = !empty($column["length"]) ? "(" . $column["length"] . ")" : "";
    $extra = !empty($column["extra"]) ? " " . $column["extra"] : "";

    return "$field $type$length$extra";
  }

  function listDatabases() {
    return array_filter(array_map(function ($db) {
      return $db["Database"];
    }, query("SHOW DATABASES")), function ($db) {
      return $db != "information_schema" && $db != "performance_schema" && $db != "mysql" && $db != "sys";
    });
  }

  function createDatabase($database) {
    query("CREATE DATABASE `$database`");
  }

  function copyDatabase($database1, $database2) {
    query("CREATE DATABASE `$database1`");

    $tables = listTables($database2);

    foreach ($tables as $table) {
      $table1 = $database1 . "." . $table["name"];
      $table2 = $database2 . "." . $table["name"];

      copyTable($table1, $table2);
    }
  }

  function dropDatabase($database) {
    query("DROP DATABASE `$database`");
  }

  function listTables($database) {
    selectDatabase($database);
    return array_map(function ($table) use ($database) {
      $name = $table["Tables_in_$database"];
      return array(
        "name" => $name,
        "count" => query("SELECT COUNT(*) FROM $name")[0]["COUNT(*)"]
      );
    }, query("SHOW TABLES"));
  }

  function createTable($database, $table, $columns) {
    selectDatabase($database);
    $columnString = join(", ", array_map(function ($c) { return getColumnString($c); }, $columns));
    query("CREATE TABLE `$table` ($columnString)");
  }

  function createAndImportTable($database, $table, $columns, $file) {
    selectDatabase($database);
    $columnString = join(", ", array_map(function ($c) { return getColumnString($c); }, $columns));
    $insertColumnString = join(", ", array_map(function ($c) { return $c["field"]; }, $columns));

    $fp = fopen($file["tmp_name"], "r");

    $columnValues = explode(",", preg_replace("/\"|\n/", "", fgets($fp)));
    $columnIndexes = array();

    for ($i = 0; $i < count($columns); $i++) {
      for ($j = 0; $j < count($columnValues); $j++) {
        if ($columns[$i]["name"] == $columnValues[$j]) {
          array_push($columnIndexes, $j);
        }
      }
    }

    $rows = array();

    while (($row = fgets($fp)) !== false) {
      $rowValues = array();

      $row = explode(",", str_replace("\"", "'", $row));

      for ($i = 0; $i < count($columnIndexes); $i++) {
        array_push($rowValues, "\"" . $row[$columnIndexes[$i]] . "\"");
      }

      array_push($rows, join(",", $rowValues));
    }

    $values = join("), (", $rows);

    execute(array(
      "SET SESSION sql_mode = ''",
      "CREATE TABLE $table ($columnString)",
      "INSERT INTO $table ($insertColumnString) VALUES ($values)"
    ));
  }

  function importTable($database, $table, $columns, $file) {
    selectDatabase($database);
    $insertColumnString = join(", ", array_map(function ($c) { return $c["field"]; }, $columns));

    $fp = fopen($file["tmp_name"], "r");

    $columnValues = explode(",", preg_replace("/\"|\n/", "", fgets($fp)));
    $columnIndexes = array();

    for ($i = 0; $i < count($columns); $i++) {
      for ($j = 0; $j < count($columnValues); $j++) {
        if ($columns[$i]["name"] == $columnValues[$j]) {
          array_push($columnIndexes, $j);
        }
      }
    }

    $rows = array();

    while (($row = fgets($fp)) !== false) {
      $rowValues = array();

      $row = explode(",", str_replace("\"", "'", $row));

      for ($i = 0; $i < count($columnIndexes); $i++) {
        array_push($rowValues, "\"" . $row[$columnIndexes[$i]] . "\"");
      }

      array_push($rows, join(",", $rowValues));
    }

    $values = join("), (", $rows);

    execute(array(
      "SET SESSION sql_mode = ''",
      "INSERT INTO `$table` ($insertColumnString) VALUES ($values)"
    ));
  }

  function copyTable($table1, $table2) {
    execute(array(
      "CREATE TABLE `$table1` LIKE $table2",
      "INSERT `$table1` SELECT * FROM $table2;"
    ));
  }

  function dropTable($database, $table) {
    selectDatabase($database);
    query("DROP TABLE `$table`");
  }

  function clearTable($database, $table) {
    selectDatabase($database);
    query("TRUNCATE TABLE `$table`");
  }

  function listColumns($database, $table) {
    selectDatabase($database);
    return array_map(function ($column) {
      return array(
        "field" => $column["Field"],
        "type" => $column["Type"],
        "default" => $column["Default"],
        "extra" => $column["Key"] . " " . $column["Extra"]
      );
    }, query("DESCRIBE $table"));
  }

  function createColumn($database, $table, $field, $type, $extra) {
    selectDatabase($database);
    query("ALTER TABLE `$table` ADD $field $type $extra");
  }

  function updateColumn($database, $table, $column, $type, $extra) {
    selectDatabase($database);
    query("ALTER TABLE `$table` MODIFY COLUMN $field $type $extra");
  }

  function dropColumn($database, $table, $column) {
    selectDatabase($database);
    query("ALTER TABLE `$table` DROP COLUMN $column");
  }
?>
