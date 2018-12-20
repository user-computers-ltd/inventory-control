<?php
  define("ADMIN_PATH", "../");
  include_once ADMIN_PATH . "includes/php/config.php";
  include_once ADMIN_PATH . "includes/php/utils.php";

  $database = $_GET["db"];

  if (!isset($database)) {
    header("Location: " . BASE_URL . "admin");
    exit(1);
  }

  $tables = listTables($database);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once ADMIN_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <div id="database-wrapper">
      <div id="database">
        <h4>
          <?php echo "<a href='" . BASE_URL . "admin'>Databases</a>"; ?>
          <span>></span>
        </h4>
        <h2><?php echo $database; ?></h2>
        <div id="database-query">
          <textarea></textarea>
          <button onclick="queryDatabase()">query</button>
        </div>
        <button onclick="createTable()">create</button>
        <ul>
          <?php
            foreach ($tables as $table) {
              $name = $table["name"];
              $count = $table["count"];
              $columns = str_replace("\"", "'", json_encode($table["columns"]));

              echo "
              <li data-table=\"$name\">
                <div class=\"list-item-left\">
                  <a href=\"" . BASE_URL . "admin/table?db=$database&table=$name\">$name</a>
                </div>
                <div class=\"list-item-right\">
                  <div class=\"count\">$count rows</div>
                  <div class=\"image-button clear-import-image\" onclick=\"clearImportTable('$name', $columns)\"></div>
                  <div class=\"image-button import-image\" onclick=\"importTable('$name', $columns)\"></div>
                  <div class=\"image-button export-image\" onclick=\"exportTable('$name')\"></div>
                  <div class=\"image-button copy-image\" onclick=\"copyTable('$name')\"></div>
                  <div class=\"image-button clear-image\" onclick=\"clearTable('$name')\"></div>
                  <div class=\"image-button delete-image\" onclick=\"deleteTable('$name')\"></div>
                </div>
              </li>
              ";
            }
          ?>
        </ul>
      </div>
    </div>
    <?php include_once ROOT_PATH . "includes/components/import-dialog/index.php"; ?>
    <?php include_once ROOT_PATH . "includes/components/prompt-dialog/index.php"; ?>
    <?php include_once ROOT_PATH . "includes/components/confirm-dialog/index.php"; ?>
    <?php include_once ROOT_PATH . "includes/components/message-dialog/index.php"; ?>
    <?php include_once ROOT_PATH . "includes/components/loading-screen/index.php"; ?>
    <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
    <script>
      var database = "<?php echo $database; ?>";
      var queryInput = document.querySelector("#database-query textarea");
      var url = "<?php echo BASE_URL; ?>admin/includes/php/ajax.php";

      function sendRequest(settings) {
        var reloadPage = typeof settings.reloadPage !== "undefined" ? settings.reloadPage : true;
        var callback = settings.callback || function () {};

        toggleLoadingScreen(true);

        post({
          url: url,
          data: settings.data,
          urlEncoded: settings.urlEncoded,
          respondFile: settings.respondFile,
          resolve: function () {
            toggleLoadingScreen(false);
            callback(content);

            if (reloadPage) {
              window.location.reload();
            }
          },
          reject: function (message) {
            toggleLoadingScreen(false);
            showMessageDialog(message);
          }
        });
      }

      function queryDatabase() {
        sendRequest({
          data: {
            action: "query-database",
            database: database,
            sql: encodeURIComponent(queryInput.value)
          }
        });
      }

      function createTable() {
        showPromptDialog("Please enter table name:", function (table) {
          if (table !== null && table.trim() !== "") {
            sendRequest({
              data: {
                action: "create-table",
                database: database,
                table: table.trim()
              }
            });
          }
        });
      }

      function clearImportTable(table, columns) {
        openImportDialog({ table: table, columns: columns, clearImport: true }, function (data) {
          sendRequest({
            urlEncoded: false,
            data: data
          });
        });
      }

      function importTable(table, columns) {
        openImportDialog({ table: table, columns: columns, clearImport: false }, function (data) {
          sendRequest({
            urlEncoded: false,
            data: data
          });
        });
      }

      function exportTable(table) {
        sendRequest({
          respondFile: true,
          reloadPage: false,
          data: {
            action: "export-table",
            database: database,
            table: table
          }
        });
      }

      function copyTable(table) {
        showPromptDialog("Please enter new table name:", function (newTable) {
          if (newTable !== null && newTable.trim() !== "") {
            sendRequest({
              data: {
                action: "copy-table",
                database1: database,
                table1: table,
                database2: database,
                table2: newTable
              }
            });
          }
        });
      }

      function deleteTable(table) {
        showConfirmDialog("<b>Delete \"" + table + "\"?</b><br/><br/>Deleting a table cannot be undone.", function () {
          sendRequest({
            data: {
              action: "delete-table",
              database: database,
              table: table
            }
          });
        });
      }

      function clearTable(table) {
        showConfirmDialog(
          "<b>Clear \"" + table + "\"?</b>"
          + "<br/><br/>Removing all data from a table cannot be undone.", function () {
          sendRequest({
            data: {
              action: "clear-table",
              database: database,
              table: table
            }
          });
        });
      }
    </script>
  </body>
</html>
