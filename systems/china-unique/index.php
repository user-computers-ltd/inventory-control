<?php
  define("SYSTEM_PATH", "");
  include_once SYSTEM_PATH . "includes/php/config.php";
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once SYSTEM_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <div id="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div id="system-list">
        <a href="<?php echo INVENTORY_URL; ?>" class="system-link">Inventory</a>
        <a href="<?php echo AR_URL; ?>" class="system-link">AR</a>
      </div>
    </div>
  </body>
</html>
