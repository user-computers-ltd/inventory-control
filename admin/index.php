<?php
  define("ROOT_PATH", "../");
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/admin.php";

  $systems = listDirectory(ROOT_PATH . "systems");
  $databases = listDatabases();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Inventory Control | Databases</title>
    <?php include_once ROOT_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <div id="admin-wrapper">
      <div id="systems">
        <h2>Systems</h2>
        <ul>
          <?php
            foreach ($systems as $system) {
              $name = $system["name"];
              echo "<li>"
              . "<div class=\"list-item-left\">"
              . "<a href='" . BASE_URL . "admin/sys?sys=$name'>$name</a>"
              . "</div>"
              . "<div class=\"list-item-right\">"
              . "</div>"
              . "</li>";
            }
          ?>
        </ul>
      </div>
      <div id="databases">
        <h2>Databases</h2>
        <button onclick="createDatabase()">create</button>
        <ul>
          <?php
            foreach ($databases as $database) {
              echo "<li>"
              . "<div class=\"list-item-left\">"
              . "<a href='" . BASE_URL . "admin/db?db=$database'>$database</a>"
              . "</div>"
              . "<div class=\"list-item-right\">"
              . "<div class=\"copy-button\" onclick=\"copyDatabase('$database')\"></div>"
              . "<div class=\"delete-button\" onclick=\"deleteDatabase('$database')\"></div>"
              . "</div>"
              . "</li>";
            }
          ?>
        </ul>
      </div>
    </div>
    <?php include_once ROOT_PATH . "includes/components/prompt-dialog/index.php"; ?>
    <?php include_once ROOT_PATH . "includes/components/confirm-dialog/index.php"; ?>
    <?php include_once ROOT_PATH . "includes/components/error-bar/index.php"; ?>
    <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
    <script>
      var url = "<?php echo BASE_URL; ?>admin/ajax.php";

      function reloadPage() {
        window.location.reload();
      }

      function createDatabase() {
        showPromptDialog("Please enter database name:", function (database) {
          if (database !== null && database.trim() !== "") {
            post({
              url: url,
              data: { action: "create-database", database: database.trim() },
              resolve: reloadPage,
              reject: showErrorBar
            });
          }
        });
      }

      function copyDatabase(database) {
        showPromptDialog("Please enter new database name:", function (database) {
          if (newDatabase !== null && newDatabase.trim() !== "") {
            post({
              url: url,
              data: { action: "copy-database", database1: newDatabase.trim(), database2: database },
              resolve: reloadPage,
              reject: showErrorBar
            });
          }
        });
      }

      function deleteDatabase(database) {
        showConfirmDialog("<b>Delete " + database + "?</b><br/><br/>Deleting a database cannot be undone.", function () {
          post({
            url: url,
            data: { action: "delete-database", database: database },
            resolve: reloadPage,
            reject: showErrorBar
          });
        });
      }
    </script>
  </body>
</html>
