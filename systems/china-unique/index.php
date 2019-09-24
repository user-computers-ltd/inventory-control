<?php
  define ("SYSTEM_PATH", '../../../inventory-control/systems/china-unique/');
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH. "includes/php/util.php";
  include_once SYSTEM_PATH . "includes/php/authentication.php";
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
        <a href="/idb/cu_ap" class="system-link">AP</a>
        <a href="/idb/cu_gledger" class="system-link">General Ledger</a>
      </div>
    </div>
  </body>
</html>
