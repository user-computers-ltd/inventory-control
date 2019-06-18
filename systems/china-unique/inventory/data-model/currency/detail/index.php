<?php
  define("SYSTEM_PATH", "../../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $id = $_GET["id"];

  $currency = query("
    SELECT
      code  AS `code`,
      name  AS `name`,
      rate  AS `rate`
    FROM
      `currency`
    WHERE
      id=\"$id\"
  ")[0];
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
      <div class="headline"><?php echo DATA_MODEL_CURRENCY_DETAIL_TITLE; ?></div>
      <?php if (isset($currency)) : ?>
        <form class="web-only" action="<?php echo DATA_MODEL_CURRENCY_ENTRY_URL; ?>">
          <input type="hidden" name="id" value="<?php echo $id; ?>" />
          <button type="submit">Edit</button>
        </form>
        <table id="currency-header">
          <tr>
            <th>Currency Code:</th>
            <td><?php echo $currency["code"]; ?></td>
          </tr>
          <tr>
            <th>Currency Name:</th>
            <td><?php echo $currency["name"]; ?></td>
          </tr>
          <tr>
            <th>Exchange Rate:</th>
            <td><?php echo $currency["rate"]; ?></td>
          </tr>
        </table>
      <?php else : ?>
        <div class="currency-no-result">Currency not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
