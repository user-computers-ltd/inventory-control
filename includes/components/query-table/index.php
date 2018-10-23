<?php
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $tId = str_replace("-", "", $tableId);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>includes/components/query-table/style.css">
  </head>
  <body>
    <?php if(!isset($hideInput) || $hideInput == false): ?>
      <div class="query-table-input" >
        <textarea name="table" onchange="changeQuery<?php echo $tId; ?>(event)"><?php echo $query; ?></textarea>
        <button onclick="loadTable<?php echo $tId; ?>()">query</button>
      </div>
    <?php endif; ?>
    <table id="query-table-<?php echo $tableId; ?>">
      <thead>
        <tr></tr>
      </thead>
      <tbody>
      </tbody>
    </table>
    <div id="query-table-<?php echo $tableId; ?>-count"></div>
    <div id="query-table-<?php echo $tableId; ?>-status"></div>
    <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
    <script>
      var database<?php echo $tId; ?> = <?php echo json_encode($database); ?>;
      var sql<?php echo $tId; ?> = <?php echo json_encode($query); ?>;
      var tableElement<?php echo $tId; ?> = document.querySelector("#query-table-<?php echo $tableId; ?>");
      var input<?php echo $tId; ?> = document.querySelector("#query-table-<?php echo $tableId; ?>-input");
      var dataCount<?php echo $tId; ?> = document.querySelector("#query-table-<?php echo $tableId; ?>-count");
      var status<?php echo $tId; ?> = document.querySelector("#query-table-<?php echo $tableId; ?>-status");
      var headerRow<?php echo $tId; ?> = tableElement<?php echo $tId; ?>.querySelector("thead tr");
      var tableBody<?php echo $tId; ?> = tableElement<?php echo $tId; ?>.querySelector("tbody");

      function changeQuery<?php echo $tId; ?>(event) {
        sql<?php echo $tId; ?> = event.target.value;
      }

      function drawTable<?php echo $tId; ?>(results) {
        var count = results.length;
        var columns = results[0] ? Object.keys(results[0]) : [];

        var columnHTML = "";

        for (var i = 0; i < columns.length; i++) {
          columnHTML += "<th>" + columns[i] + "</th>";
        }

        headerRow<?php echo $tId; ?>.innerHTML = columnHTML;

        var dataHTML = "";

        for (var i = 0; i < results.length; i++) {
          dataHTML += "<tr>";

          for (var j = 0; j < columns.length; j++) {
            dataHTML += "<td>" + results[i][columns[j]] + "</td>";
          }

          dataHTML += "</tr>";
        }

        tableBody<?php echo $tId; ?>.innerHTML = dataHTML;
        dataCount<?php echo $tId; ?>.innerHTML = count + " entr" + (count > 1 ? "ies" : "y");
        status<?php echo $tId; ?>.innerHTML = "";
      }

      function showErrorOnTable<?php echo $tId; ?>(message) {
        status<?php echo $tId; ?>.innerHTML = message;
      }

      function loadTable<?php echo $tId; ?>() {
        dataCount<?php echo $tId; ?>.innerHTML = "";
        headerRow<?php echo $tId; ?>.innerHTML = "";
        tableBody<?php echo $tId; ?>.innerHTML = "";
        status<?php echo $tId; ?>.innerHTML = "loading...";

        get({
          url: "<?php echo BASE_URL; ?>includes/php/query.php",
          params: {
            database: database<?php echo $tId; ?>,
            sql: sql<?php echo $tId; ?>
          },
          resolve: drawTable<?php echo $tId; ?>,
          reject: showErrorOnTable<?php echo $tId; ?>
        });
      }

      loadTable<?php echo $tId; ?>();
    </script>
  </body>
</html>
