<?php
  define("SYSTEM_PATH", "../../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $id = $_GET["id"];

  $warehouse = query("
    SELECT
      code  AS `code`,
      name  AS `name`
    FROM
      `warehouse`
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
      <div class="headline"><?php echo DATA_MODEL_WAREHOUSE_DETAIL_TITLE; ?></div>
      <?php if (isset($warehouse)) : ?>
        <form class="web-only" action="<?php echo DATA_MODEL_WAREHOUSE_ENTRY_URL; ?>">
          <input type="hidden" name="id" value="<?php echo $id; ?>" />
          <button type="submit">Edit</button>
        </form>
        <table id="warehouse-header">
          <tr>
            <th>Warehouse Code:</th>
            <td><?php echo $warehouse["code"]; ?></td>
          </tr>
          <tr>
            <th>Warehouse Name:</th>
            <td><?php echo $warehouse["name"]; ?></td>
          </tr>
        </table>
      <?php else : ?>
        <div class="warehouse-no-result">Warehouse not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
