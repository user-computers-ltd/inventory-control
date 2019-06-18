<?php
  define("SYSTEM_PATH", "../../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $id = $_GET["id"];

  $brand = query("
    SELECT
      code  AS `code`,
      name  AS `name`
    FROM
      `brand`
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
      <div class="headline"><?php echo DATA_MODEL_BRAND_DETAIL_TITLE; ?></div>
      <?php if (isset($brand)) : ?>
        <form class="web-only" action="<?php echo DATA_MODEL_BRAND_ENTRY_URL; ?>">
          <input type="hidden" name="id" value="<?php echo $id; ?>" />
          <button type="submit">Edit</button>
        </form>
        <table id="brand-header">
          <tr>
            <th>Brand Code:</th>
            <td><?php echo $brand["code"]; ?></td>
          </tr>
          <tr>
            <th>Brand Name:</th>
            <td><?php echo $brand["name"]; ?></td>
          </tr>
        </table>
      <?php else : ?>
        <div class="brand-no-result">Brand not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
