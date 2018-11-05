<?php
  define("ROOT_PATH", "../../");
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/admin.php";

  if (!isset($_GET["db"])) {
    header("Location: " . BASE_URL . "admin");
    exit(1);
  }

  $database = $_GET["db"];

  $tables = listTables($database);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Inventory Control | Systems > <?php echo "$database > Database"; ?></title>
    <?php include_once ROOT_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>admin/admin.css">
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <div id="database-wrapper">
      <div id="database">
        <h4>
          <?php echo "<a href='" . BASE_URL . "admin'>Systems</a>"; ?>
          <span>></span>
          <?php echo "<a href='" . BASE_URL . "admin/sys?sys=$database'>$database</a>"; ?>
          <span>></span>
        </h4>
        <h2>Database</h2>
        <div id="database-query">
          <textarea></textarea>
          <button onclick="queryDatabase()">query</button>
          <pre></pre>
        </div>
        <button onclick="createTable()">create</button>
        <button onclick="createAndImportTable()">create & import</button>
        <ul>
          <?php
            foreach ($tables as $table) {
              $name = $table["name"];
              $count = $table["count"];
              $columns = str_replace("\"", "'", json_encode($table["columns"]));

              echo "<li data-table=\"$name\">"
              . "<div class=\"list-item-left\">"
              . "<a href=\"" . BASE_URL . "admin/table?db=$database&table=$name\">$name</a>"
              . "</div>"
              . "<div class=\"list-item-right\">"
              . "<div class=\"count\">$count rows</div>"
              . "<div class=\"import-button\" onclick=\"importTable('$name', $columns)\"></div>"
              . "<div class=\"export-button\"></div>"
              . "<div class=\"copy-button\" onclick=\"copyTable('$name')\"></div>"
              . "<div class=\"clear-button\" onclick=\"clearTable('$name')\"></div>"
              . "<div class=\"delete-button\" onclick=\"deleteTable('$name')\"></div>"
              . "</div>"
              . "</li>";
            }
          ?>
        </ul>
      </div>
    </div>
    <?php include_once  "create-import-dialog/index.php"; ?>
    <?php include_once  "import-dialog/index.php"; ?>
    <?php include_once ROOT_PATH . "includes/components/prompt-dialog/index.php"; ?>
    <?php include_once ROOT_PATH . "includes/components/confirm-dialog/index.php"; ?>
    <?php include_once ROOT_PATH . "includes/components/error-bar/index.php"; ?>
    <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
    <script>
      var database = "<?php echo $database; ?>";
      var url = "<?php echo BASE_URL; ?>admin/ajax.php";
      var queryInput = document.querySelector("#database-query");
      var textarea = queryInput.querySelector("textarea");
      var statusText = queryInput.querySelector("pre");

      function updateStatus(message) {
        if (message === true) {
          window.location.reload();
        } else {
          statusText.innerHTML = JSON.stringify(message);
        }
      }

      function queryDatabase() {
        get({
          url: "<?php echo BASE_URL; ?>includes/php/query.php",
          params: {
            database: database,
            sql: textarea.value
          },
          resolve: updateStatus,
          reject: updateStatus
        });
      }

      function reloadPage() {
        window.location.reload();
      }

      function createTable() {
        showPromptDialog("Please enter table name:", function (table) {
          if (table !== null && table.trim() !== "") {
            post({
              url: url,
              data: { action: "create-table", database: database, table: table.trim() },
              resolve: reloadPage,
              reject: showErrorBar
            });
          }
        });
      }

      function copyTable(table) {
        showPromptDialog("Please enter new table name:", function (newTable) {
          if (newTable !== null && newTable.trim() !== "") {
            post({
              url: url,
              data: { action: "copy-table", table1: database + "." + newTable.trim(), table2: database + "." + table },
              resolve: reloadPage,
              reject: showErrorBar
            });
          }
        });
      }

      function deleteTable(table) {
        showConfirmDialog("<b>Delete " + table + "?</b><br/><br/>Deleting a table cannot be undone.", function () {
          post({
            url: url,
            data: { action: "delete-table", database: database, table: table },
            resolve: reloadPage,
            reject: showErrorBar
          });
        });
      }

      function clearTable(table) {
        showConfirmDialog("<b>Truncate " + table + "?</b><br/><br/>Removing all data from a table cannot be undone.", function () {
          post({
            url: url,
            data: { action: "clear-table", database: database, table: table },
            resolve: reloadPage,
            reject: showErrorBar
          });
        });
      }
    </script>
  </body>
</html>
