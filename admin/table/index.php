<?php
  define("ROOT_PATH", "../../");
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/admin.php";

  if (!isset($_GET["table"])) {
    if (!isset($_GET["db"])) {
      header("Location: " . BASE_URL . "admin");
    } else {
      header("Location: " . BASE_URL . "admin/db/?db=" . $_GET["db"]);
    }
    exit(1);
  }

  $database = $_GET["db"];
  $table = $_GET["table"];
  $columns = listColumns($database, $table);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Inventory Control | <?php echo "$database - $table"; ?></title>
    <?php include_once ROOT_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>admin/admin.css">
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <div id="table-wrapper" class="page-wrapper">
      <h4>
        <?php echo "<a href='" . BASE_URL . "admin'>Databases</a>"; ?>
        <span>></span>
        <?php echo "<a href='" . BASE_URL . "admin/db?db=$database'>$database</a>"; ?>
        <span>></span>
      </h4>
      <h2><?php echo $table; ?></h2>
      <div id="table-tab">
        <div class="tablink<?php echo !$showData ? " active" : ""; ?>" onclick="openTab(event, 'structure')">
          structure
        </div><div class="tablink<?php echo $showData ? " active" : ""; ?>" onclick="openTab(event, 'content')">
          content
        </div>
      </div>
      <div class="card">
        <div id="structure" class="tabcontent<?php echo !$showData ? " show" : ""; ?>">
          <button onclick="showCreateDialog()">create column</button>
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
              <th></th>
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
                  echo "<td><div class=\"delete-button\" onclick=\"deleteColumn('$name')\"></div></td>";
                  echo "</tr>";
                }
              ?>
            </tbody>
          </table>
        </div>
        <div id="content" class="tabcontent<?php echo $showData ? " show" : ""; ?>">
          <?php
            $tableId = "content-table";
            $query = "SELECT * FROM $table";

            include ROOT_PATH . "includes/components/query-table/index.php";
          ?>
        </div>
      </div>
    </div>
    <?php include_once  "create-dialog/index.php"; ?>
    <?php include_once ROOT_PATH . "includes/components/confirm-dialog/index.php"; ?>
    <?php include_once ROOT_PATH . "includes/components/error-bar/index.php"; ?>
    <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
    <script>
      var database = "<?php echo $database; ?>";
      var table = "<?php echo $table; ?>";
      var url = "<?php echo BASE_URL; ?>admin/ajax.php";
      var createOverlay = document.querySelector("#create-overlay");
      var createForm = createOverlay.querySelector("form");

      function openTab(event, tabName) {
        var tabcontent = document.querySelectorAll(".tabcontent");
        var tablink = document.querySelectorAll(".tablink");

        for (var i = 0; i < tabcontent.length; i++) {
          tabcontent[i].className = tabcontent[i].className.replace(" show", "");
        }

        for (var i = 0; i < tablink.length; i++) {
          tablink[i].className = tablink[i].className.replace(" active", "");
        }

        document.querySelector("#" + tabName).className += " show";
        event.target.className += " active";
      }

      function reloadPage() {
        window.location.reload();
      }

      function deleteColumn(column) {
        var response = confirm("Deleting a column cannot be undone.");

        if (response === true) {
          post({
            url: url,
            data: { action: "delete-column", database: database, table: table, column: column },
            resolve: reloadPage,
            reject: showErrorBar
          });
        }
      }
    </script>
  </body>
</html>
