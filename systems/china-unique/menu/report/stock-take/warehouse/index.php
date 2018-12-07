<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $warehouses = query("
    SELECT
      a.id                            AS `warehouse_id`,
      CONCAT(a.code, ' - ', a.name)   AS `warehouse`,
      IFNULL(b.qty, 0)                AS `qty`,
      IFNULL(b.subtotal, 0)           AS `subtotal`
    FROM
      `warehouse` AS a
    LEFT JOIN
      (SELECT
        s.warehouse_code            AS `warehouse_code`,
        SUM(s.qty)                  AS `qty`,
        SUM(s.qty * m.cost_average) AS `subtotal`
      FROM
        `stock` AS s
      LEFT JOIN
        `model` AS m
      ON s.brand_code=m.brand_code AND s.model_no=m.model_no
      GROUP BY
        s.warehouse_code) AS b
    ON a.code=b.warehouse_code
    WHERE
      a.code IS NOT NULL
    ORDER BY
      a.code ASC
  ");
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once SYSTEM_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <?php include_once ROOT_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo REPORT_STOCK_TAKE_WAREHOUSE_TITLE; ?></div>
      <?php if (count($warehouses) > 0): ?>
        <table id="warehouse-results">
          <colgroup>
            <col>
            <col style="width: 80px;">
            <col style="width: 80px;">
          </colgroup>
          <thead>
            <tr></tr>
            <tr>
              <th>Warehouse</th>
              <th class="number">Qty</th>
              <th class="number">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $totalQty = 0;
              $totalAmt = 0;

              for ($i = 0; $i < count($warehouses); $i++) {
                $warehouse = $warehouses[$i];
                $id = $warehouse["warehouse_id"];
                $name = $warehouse["warehouse"];
                $qty = $warehouse["qty"];
                $subtotal = $warehouse["subtotal"];

                $totalQty += $qty;
                $totalAmt += $subtotal;

                echo "
                  <tr>
                    <td title=\"$name\"><a href=\"" . REPORT_STOCK_TAKE_WAREHOUSE_DETAIL_URL . "?id[]=$id\">$name</a></td>
                    <td class=\"number\" title=\"$qty\">" . number_format($qty) . "</td>
                    <td class=\"number\" title=\"$subtotal\">" . number_format($subtotal, 2) . "</td>
                  </tr>
                ";
              }
            ?>
          </tbody>
          <tfoot>
            <tr>
              <th class="number">Total:</th>
              <th class="number"><?php echo number_format($totalQty); ?></th>
              <th class="number"><?php echo number_format($totalAmt, 2); ?></th>
            </tr>
          </tfoot>
        </table>
      <?php else: ?>
        <div class="warehouse-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
