<?php
  define("ADMIN_PATH", "../");
  include_once ADMIN_PATH . "includes/php/config.php";
  include_once ADMIN_PATH . "includes/php/utils.php";

  $database = $_GET["db"];
  $table = $_GET["table"];
  $sql = assigned($_POST["sql"]) ? $_POST["sql"] : "SELECT * FROM `$table`";
  $count = isset($_POST["count"]) ? $_POST["count"] : 100;
  $offset = isset($_POST["offset"]) ? $_POST["offset"] : 0;
  $pageNo = $offset / $count;

  if (!isset($table)) {
    if (!isset($database)) {
      header("Location: " . BASE_URL . "admin");
    } else {
      header("Location: " . BASE_URL . "admin/db/?db=" . $database);
    }
    exit(1);
  }

  try {
    selectDatabase($database);
    $results = query($sql, true);

    if (!is_array($results)) {
      $sql = "SELECT * FROM `$table`";
      $results = query($sql, true);
    }
  } catch (\Exception $e) {
    $error = $e;
  }

  $pageCount = ceil(count($results) / $count);

  $columns = listColumns($database, $table);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once ADMIN_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <div id="table-wrapper">
      <div id="table">
        <h4>
          <?php echo "<a href='" . BASE_URL . "admin'>Databases</a>"; ?>
          <span>></span>
          <?php echo "<a href='" . BASE_URL . "admin/db?db=$database'>$database</a>"; ?>
          <span>></span>
        </h4>
        <h2><?php echo $table; ?></h2>
        <div id="table-tab">
          <div class="tablink active" onclick="openTab(event, 'content')">
            content
          </div><div class="tablink" onclick="openTab(event, 'structure')">
            structure
          </div>
        </div>
        <div id="content" class="tabcontent show">
          <form id="table-query" method="post">
            <textarea name="sql"><?php echo $sql; ?></textarea>
            <button type="submit">query</button>
            <div id="table-query-settings">
              <?php
                for ($i = 0; $i < $pageCount; $i++) {
                  $index = $i + 1;
                  if ($pageNo == $i) {
                    echo "<span>$index</span>";
                  } else if ($i == 0 || $i == ($pageCount - 1) || ($pageNo != $i && abs($pageNo - $i) < 4)) {
                    $offsetValue = $i * $count;
                    echo "<button type=\"submit\" name=\"offset\" value=\"$offsetValue\">$index</button>";
                  } else if (abs($pageNo - $i) == 4) {
                    echo "...";
                  }
                }
              ?>
              <select name="count" onchange="this.form.submit()">
                <option value="100" <?php echo $count == 100 ? "selected" : ""; ?>>100</option>
                <option value="200" <?php echo $count == 200 ? "selected" : ""; ?>>200</option>
                <option value="500" <?php echo $count == 500 ? "selected" : ""; ?>>500</option>
              </select>
            </div>
          </form>
          <?php if (count($results) > 0) : ?>
            <table id="table-query-results">
              <thead>
                <tr>
                  <?php
                    foreach ($results[0] as $key => $value) {
                      echo "<th>$key</th>";
                    }
                  ?>
                </tr>
              </thead>
              <tbody>
                <?php
                  for ($i = $offset; $i < count($results) && $i < $offset + $count; $i++) {
                    echo "<tr>";

                    foreach ($results[$i] as $key => $value) {
                      echo "<td>$value</td>";
                    }

                    echo "</tr>";
                  }
                ?>
              </tbody>
            </table>
          <?php elseif (isset($error)) : ?>
            <div id="table-query-error"><?php echo $error; ?></div>
          <?php else: ?>
            <div id="table-query-no-results">No results</div>
          <?php endif ?>
        </div>
        <div id="structure" class="tabcontent">
          <table id="structure-table">
            <thead>
              <tr>
                <?php
                  if (count($columns) > 0) {
                    foreach ($columns[0] as $key => $value) {
                      echo "<th>$key</th>";
                    }
                  }
                ?>
              </tr>
            </thead>
            <tbody>
              <?php
                foreach ($columns as $column) {
                  $name = $column["field"];
                  echo "<tr>";

                  foreach ($column as $key => $value) {
                    echo "<td>$value</td>";
                  }

                  echo "</tr>";
                }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php include_once ROOT_PATH . "includes/components/confirm-dialog/index.php"; ?>
    <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
    <script>
      function openTab(event, tabName) {
        var tabcontent = document.querySelectorAll(".tabcontent");
        var tablink = document.querySelectorAll(".tablink");

        for (var i = 0; i < tabcontent.length; i++) {
          toggleClass(tabcontent[i], "show", false);
        }

        for (var i = 0; i < tablink.length; i++) {
          toggleClass(tablink[i], "active", false);
        }

        toggleClass(document.querySelector("#" + tabName), "show", true);
        toggleClass(event.target, "active", true);
      }
    </script>
  </body>
</html>
