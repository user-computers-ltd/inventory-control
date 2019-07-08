<?php
  include_once ROOT_PATH . "includes/php/config.php";

  $urlPath = str_replace(SYSTEM_URL, "", CURRENT_URL);
  $sector = "";

  if (strpos($urlPath, "inventory") === 0) {
    $sector = "Inventory Control";
  } else if (strpos($urlPath, "ar") === 0) {
    $sector = "Accounts Receivable";
  } else if (strpos($urlPath, "ap") === 0) {
    $sector = "Accounts Payable";
  }
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="stylesheet" href="<?php echo SYSTEM_URL; ?>includes/components/header/style.css">
  </head>
  <body>
    <div class="system-header-wrapper">
      <div class="system-header">
        <div class="company-name"><?php echo COMPANY_NAME_CHI; ?></div>
        <div class="company-detail"><?php echo COMPANY_ADDRESS; ?></div>
        <div class="company-detail">電話: <?php echo COMPANY_TEL; ?></div>
        <div class="company-sector headline"><?php echo $sector; ?></div>
      </div>
    </div>
  </body>
</html>
