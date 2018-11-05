<?php
  define("ROOT_PATH", "");
  include_once ROOT_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";

  $systems = listDirectory("systems");
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Inventory Control</title>
    <?php include_once ROOT_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <div id="page-wrapper">
      <h1>Inentory Control</h1>
      <div id="system-list">
        <?php
          foreach ($systems as $system) {
            $name = $system["name"];
            echo "<a href=\"" . BASE_URL . "systems/$name\" class=\"system-link\">$name</a>";
          }
        ?>
      </div>
    </div>
  </body>
</html>
