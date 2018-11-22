<?php
  define("ROOT_PATH", "../../");
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/admin.php";

  if (!isset($_GET["sys"])) {
    header("Location: " . BASE_URL . "admin");
    exit(1);
  }

  $system = $_GET["sys"];

  $tables = array_map(function ($filename) { return str_replace(".sql", "", $filename); }, listFile(ROOT_PATH . "systems/$system/data-model/tables"));

  $databaseExists = count(array_filter(listDatabases(), function ($database) use ($system) { return $database == $system; })) > 0;
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Inventory Control | Systems > <?php echo $system; ?></title>
    <?php include_once ROOT_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>admin/admin.css">
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <div id="system-wrapper">
      <div id="system">
        <h4>
          <?php echo "<a href='" . BASE_URL . "admin'>Systems</a>"; ?>
          <span>></span>
        </h4>
        <h2><?php echo $system; ?></h2>
        <hr/>
        <button onclick="reinitializeDatabase()">Reinitialize Database</button>
        <?php if ($databaseExists) : ?>
          <a href="<?php echo BASE_URL; ?>admin/db?db=<?php echo $system; ?>" class="database-link">go to database</a>
        <?php endif ?>
        <hr/>
        <div id="data-model">
          <?php
            foreach ($tables as $table) {
              echo "<div class=\"table-model\" data-table=\"$table\">$table</div>";
            }
          ?>
        </div>
      </div>
    </div>
    <?php include_once ROOT_PATH . "includes/components/confirm-dialog/index.php"; ?>
    <?php include_once ROOT_PATH . "includes/components/error-bar/index.php"; ?>
    <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
    <script async defer>
      var system = "<?php echo $system; ?>";
      var url = "<?php echo BASE_URL; ?>admin/ajax.php";

      function loadDatabasePage() {
        window.location.href = "<?php echo BASE_URL; ?>admin/db/?db=<?php echo $system; ?>";
      }

      function sendReinitializeDatabase(overwrite = false) {
        post({
          url: url,
          data: { action: "reinitialize-database", system: system, overwrite: overwrite },
          resolve: loadDatabasePage,
          reject: showErrorBar
        });
      }

      function reinitializeDatabase() {
        if (<?php echo json_encode($databaseExists); ?>) {
          showConfirmDialog("<b>Database " + system + " already exists. Overwrite?</b><br/><br/>Overwriting the existing database cannot be undone.", function () {
            sendReinitializeDatabase(true);
          });
        } else {
          sendReinitializeDatabase();
        }
      }
    </script>
  </body>
</html>
