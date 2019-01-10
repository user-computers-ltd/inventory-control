<?php
  include_once ROOT_PATH . "includes/php/config.php";
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
        <div class="company-detail">郵政編碼: <?php echo COMPANY_POST_NO; ?> 電話: <?php echo COMPANY_TEL; ?></div>
      </div>
    </div>
  </body>
</html>
