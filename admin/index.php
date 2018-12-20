<?php
  define("ADMIN_PATH", "");
  include_once ADMIN_PATH . "includes/php/config.php";
  include_once ADMIN_PATH . "includes/php/utils.php";

  $systems = array_map(function ($i) { return $i["name"]; }, listDirectory(ROOT_PATH . "systems"));
  $databases = listDatabases();
  $systemDatabases = array_filter($systems, function ($i) use ($databases) { return !in_array($i, $databases); });
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once ADMIN_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <div id="admin-wrapper">
      <div id="databases">
        <h2>Databases</h2>
        <button onclick="createDatabase()">create</button>
        <ul>
          <?php
            foreach ($databases as $database) {
              echo "
                <li>
                  <div class=\"list-item-left\">
                    <a href='" . BASE_URL . "admin/db?db=$database'>$database</a>
                  </div>
                  <div class=\"list-item-right\">
                  " . (in_array($database, $systems) ?
                  "<div class=\"image-button restart-image\" onclick=\"restartDatabase('$database')\"></div>" : "") . "
                  <div class=\"image-button clear-import-image\" onclick=\"clearImportDatabase('$database')\"></div>
                  <div class=\"image-button import-image\" onclick=\"importDatabase('$database')\"></div>
                  <div class=\"image-button export-image\" onclick=\"exportDatabase('$database')\"></div>
                  <div class=\"image-button copy-image\" onclick=\"copyDatabase('$database')\"></div>
                  <div class=\"image-button delete-image\" onclick=\"deleteDatabase('$database')\"></div>
                  </div>
                </li>
              ";
            }
          ?>
          <?php
            foreach ($systemDatabases as $database) {
              echo "
                <li>
                  <div class=\"list-item-left\">
                  <a href='" . BASE_URL . "admin/db?db=$database'>$database</a>
                  </div>
                  <div class=\"list-item-right\">
                  <div class=\"image-button restart-image\" onclick=\"restartDatabase('$database')\"></div>
                  </div>
                </li>
              ";
            }
          ?>
        </ul>
      </div>
    </div>
    <?php include_once ROOT_PATH . "includes/components/prompt-dialog/index.php"; ?>
    <?php include_once ROOT_PATH . "includes/components/confirm-dialog/index.php"; ?>
    <?php include_once ROOT_PATH . "includes/components/message-dialog/index.php"; ?>
    <?php include_once ROOT_PATH . "includes/components/loading-screen/index.php"; ?>
    <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
    <script>
      var databases = <?php echo "[\"" . join("\",\"", $databases) . "\"]"; ?>;
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

      function createDatabase() {
        showPromptDialog("Please enter database name:", function (database) {
          if (database !== null && database.trim() !== "") {
            sendRequest({
              data: {
                action: "create-database",
                database: database.trim()
              }
            });
          }
        });
      }

      function copyDatabase(database) {
        showPromptDialog("Please enter new database name:", function (newDatabase) {
          if (newDatabase !== null && newDatabase.trim() !== "") {
            sendRequest({
              data: {
                action: "copy-database",
                database1: newDatabase.trim(),
                database2: database
              }
            });
          }
        });
      }

      function deleteDatabase(database) {
        showConfirmDialog("<b>Delete \"" + database + "\"?</b><br/><br/>Deleting a database cannot be undone.", function () {
          sendRequest({
            data: {
              action: "delete-database",
              database: database
            }
          });
        });
      }

      function exportDatabase(database) {
        sendRequest({
          respondFile: true,
          reloadPage: false,
          data: {
            action: "export-database",
            database: database
          }
        });
      }

      function restartDatabase(database) {
        if (databases.indexOf(database) !== -1) {
          showConfirmDialog(
             "<b>Overwrite database \"" + database + "\"?</b>"
           + "<br/><br/>Overwriting the existing database cannot be undone.", function () {
            sendRequest({
              data: {
                action: "restart-database",
                system: database,
                overwrite: true
              }
            });
          });
        } else {
          sendRequest({
            data: {
              action: "restart-database",
              system: database,
              overwrite: false
            }
          });
        }
      }
    </script>
  </body>
</html>
