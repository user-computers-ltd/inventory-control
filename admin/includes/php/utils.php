<?php
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

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
    $queries = [];
    array_push($queries, "CREATE DATABASE `$database1`");

    $tables = array_map(function ($i) { return $i["name"]; }, listTables($database2));

    foreach ($tables as $table) {
      array_push($queries, "CREATE TABLE `$database1`.`$table` LIKE `$database2`.`$table`");
      array_push($queries, "INSERT `$database1`.`$table` SELECT * FROM `$database2`.`$table`");
    }

    execute($queries);
  }

  function clearDatabase($database) {
    $queries = array();

    $tables = array_map(function ($i) { return $i["name"]; }, listTables($database));

    foreach ($tables as $table) {
      array_push($queries, "TRUNCATE TABLE `$table`");
    }

    execute($queries);
  }

  function dropDatabase($database) {
    query("DROP DATABASE `$database`");
  }

  function queryDatabase($database, $sql) {
    selectDatabase($database);
    query($sql);
  }

  function exportDatabase($database) {
    $tables = array_map(function ($i) { return $i["name"]; }, listTables($database));

    $filename = "export.zip";
    $path = TEMP_DIRECTORY . $filename;
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);

    foreach ($tables as $table) {
      $zip->addFromString("$table.csv", getTableContent($database, $table));
    }

    $success = $zip->close();

    header("Content-Type: application/zip");
    header("Content-disposition: attachment; filename=$filename");
    header("Content-Length: " . filesize($path));
    readfile($path);
    unlink($path);
    exit();
  }

  function restartDatabase($system, $overwrite) {
    $queries = array();

    if ($overwrite == "true") {
      array_push($queries, "DROP DATABASE `$system`");
    }

    array_push($queries, "CREATE DATABASE `$system`");
    array_push($queries, "USE `$system`");
    array_push($queries, "SET SESSION sql_mode = ''");

    $files = array_map(function ($table) use ($system) {
      return ROOT_PATH . "systems/$system/data-model/tables/$table";
    }, listFile(ROOT_PATH . "systems/$system/data-model/tables"));

    for ($i = 0; $i < count($files); $i++) {
      $file = $files[$i];
      $handle = fopen($file, "r");
      $contents = fread($handle, filesize($file));

      array_push($queries, $contents);

      fclose($handle);
    }

    execute($queries);
  }

  function listTables($database) {
    selectDatabase($database);

    return array_map(function ($table) use ($database) {
      $name = $table["Tables_in_$database"];
      return array(
        "name" => $name,
        "columns" => array_map(function ($column) { return $column["Field"]; }, query("DESCRIBE `$name`")),
        "count" => query("SELECT COUNT(*) FROM `$name`")[0]["COUNT(*)"]
      );
    }, query("SHOW TABLES"));
  }

  function createTable($database, $table, $columns) {
    selectDatabase($database);

    $columnString = join(", ", array_map(function ($c) { return getColumnString($c); }, $columns));
    query("CREATE TABLE `$table` ($columnString)");
  }

  function generateInsertStatment($table, $columns, $file) {
    $statement = "";

    $insertColumnString = join(", ", array_map(function ($c) { return $c["field"]; }, $columns));

    $handle = fopen($file["tmp_name"], "r");
    $contents = fread($handle, filesize($file["tmp_name"]));
    $lines = preg_split("/\"\r\n\"|\"\n\"/", substr($contents, strpos($contents, "\"") + 1, strrpos($contents, "\"") - 1));

    if (count($lines) > 0) {
      $firstLine = $lines[0];
      $columnValues = explode("\",\"", $firstLine);

      $columnIndexes = array();

      for ($i = 0; $i < count($columns); $i++) {
        for ($j = 0; $j < count($columnValues); $j++) {
          if ($columns[$i]["name"] == $columnValues[$j]) {
            array_push($columnIndexes, $j);
          }
        }
      }

      $rows = array();

      for ($i = 1; $i < count($lines); $i++) {
        $rowValues = array();

        $line = preg_replace("/,,/", ",\"\",", preg_replace("/,,/", ",\"\",", $lines[$i]));
        $row = explode("\",\"", $line);

        for ($j = 0; $j < count($columnIndexes); $j++) {
          array_push($rowValues, $row[$columnIndexes[$j]]);
        }

        array_push($rows, "\"" . join("\",\"", $rowValues) . "\"");
      }

      $values = join("), (", $rows);

      $statement = "INSERT INTO `$table` ($insertColumnString) VALUES ($values)";
    }

    fclose($handle);

    return $statement;
  }

  function importTable($database, $table, $columns, $file) {
    selectDatabase($database);

    execute(array(
      "SET SESSION sql_mode = ''",
      generateInsertStatment($table, $columns, $file)
    ));
  }

  function clearImportTable($database, $table, $columns, $file) {
    selectDatabase($database);

    execute(array(
      "SET SESSION sql_mode = ''",
      "TRUNCATE TABLE `$table`",
      generateInsertStatment($table, $columns, $file)
    ));
  }

  function getTableContent($database, $table) {
    selectDatabase($database);

    $results = query("SELECT * FROM `$table`");

    $content = array();

    if (count($results) > 0) {
      $columns = array();
      $row = array();

      foreach ($results[0] as $column => $value) {
        array_push($columns, $column);
        array_push($row, "\"$column\"");
      }

      array_push($content, join(",", $row));

      foreach ($results as $result) {
        $row = array();

        foreach ($columns as $column) {
          $value = $result[$column];
          array_push($row, "\"$value\"");
        }

        array_push($content, join(",", $row));
      }
    }

    return join("\r\n", $content);
  }

  function copyTable($database1, $table1, $database2, $table2) {
    execute(array(
      "CREATE TABLE `$database1`.`$table1` LIKE `$database2`.`$table2`",
      "INSERT `$database1`.`$table1` SELECT * FROM `$database2`.`$table2`;"
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
    }, query("DESCRIBE `$table`"));
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

  function exportTable($database, $table) {
    $filename = "$table.csv";
    $path = TEMP_DIRECTORY . $filename;
    $CSVFile = fopen($path, "w");

    fwrite($CSVFile, getTableContent($database, $table));
    fclose($CSVFile);
    header("Content-type: application/csv");
    header("Content-disposition: attachment; filename=$filename");
    header("Content-Length: " . filesize($path));
    readfile($path);
    unlink($path);
    exit();
  }
?>
