<?php
  define("SYSTEM_PATH", "../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $filterWarehouses = $_GET["filter_warehouse_code"];

  $whereClause = "";

  if (assigned($filterWarehouses) && count($filterWarehouses) > 0) {
    $whereClause = "
      (" . join(" OR ", array_map(function ($i) { return "code=\"$i\""; }, $filterWarehouses)) . ")";
  }

  $results = query("
    SELECT
      id   AS `id`,
      code AS `code`,
      name AS `name`
    FROM
      `warehouse`
    ORDER BY
      code ASC
  ");

  $warehouses = query("
    SELECT DISTINCT
      code AS `code`,
      name AS `name`
    FROM
      `warehouse`
    ORDER BY
      code ASC
  ");
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
      <div class="headline"><?php echo DATA_MODEL_WAREHOUSE_TITLE; ?></div>
      <form>
        <table id="warehouse-input" class="web-only">
          <tr>
            <th>Warehouse:</th>
          </tr>
          <tr>
            <td>
              <select name="filter_warehouse_code[]" multiple>
                <?php
                  foreach ($warehouses as $warehouse) {
                    $code = $warehouse["code"];
                    $name = $warehouse["name"];
                    $selected = assigned($filterWarehouses) && in_array($code, $filterWarehouses) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <form action="<?php echo DATA_MODEL_WAREHOUSE_ENTRY_URL; ?>" class="web-only">
        <button type="submit">Create</button>
      </form>
      <?php if (count($results) > 0) : ?>
        <table id="warehouse-results">
          <thead>
            <tr></tr>
            <tr>
              <th>Warehouse Code</th>
              <th>Warehouse Name</th>
            </tr>
          </thead>
          <tbody>
            <?php
              foreach ($results as $warehouse) {
                $id = $warehouse["id"];
                $warehouseCode = $warehouse["code"];
                $warehouseName = $warehouse["name"];

                echo "
                  <tr>
                    <td title=\"$warehouseCode\"><a href=\"" . DATA_MODEL_WAREHOUSE_DETAIL_URL . "?id=$id\">$warehouseCode</a></td>
                    <td title=\"$warehouseName\">$warehouseName</td>
                  </tr>
                ";
              }
            ?>
          </tbody>
        </table>
      <?php else : ?>
        <div class="warehouse-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
