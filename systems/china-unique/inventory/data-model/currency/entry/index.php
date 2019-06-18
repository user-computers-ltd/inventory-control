<?php
  define("SYSTEM_PATH", "../../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include "process.php";
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once SYSTEM_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <?php include_once SYSTEM_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo $headline; ?></div>
      <?php if (!assigned($id) || isset($currency)) : ?>
        <form method="post">
          <table id="currency-table">
            <tr>
              <td>Currency Code:</td>
              <td><input type="text" name="currency_code" value="<?php echo $currencyCode; ?>" <?php echo $editMode ? "readonly" : ""; ?> required /></td>
            </tr>
            <tr>
              <td>Currency Name:</td>
              <td><input type="text" name="currency_name" value="<?php echo $currencyName; ?>" required /></td>
            </tr>
            <tr>
              <td>Exchange Rate:</td>
              <td><input type="number" name="exchange_rate" min="0" step="0.00000001" value="<?php echo $exchangeRate; ?>" required /></td>
            </tr>
          </table>
          <button type="submit"><?php echo $editMode ? "Edit" : "Create"; ?></button>
        </form>
      <?php else : ?>
        <div class="currency-no-result">Currency not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
