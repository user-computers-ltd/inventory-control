<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $results = query("
    SELECT
      CONCAT(c.code, ' - ', c.name)   AS `brand`,
      c.id                            AS `brand_id`,
      b.code                          AS `warehouse_code`,
      b.name                          AS `warehouse_name`,
      SUM(a.qty)                      AS `qty`,
      SUM(a.qty * d.cost_average)     AS `subtotal`
    FROM
      `stock` AS a
    LEFT JOIN
      `warehouse` AS b
    ON a.warehouse_code=b.code
    LEFT JOIN
      `brand` AS c
    ON a.brand_code=c.code
    LEFT JOIN
      `model` AS d
    ON a.brand_code=d.brand_code AND a.model_no=d.model_no
    WHERE
      a.qty > 0
    GROUP BY
      a.warehouse_code, a.brand_code
    ORDER BY
      a.warehouse_code ASC,
      a.brand_code ASC
  ");

  $stocks = array();

  foreach ($results as $stock) {
    $brand = $stock["brand"];

    $arrayPointer = &$stocks;

    if (!isset($arrayPointer[$brand])) {
      $arrayPointer[$brand] = array();
    }
    $arrayPointer = &$arrayPointer[$brand];

    array_push($arrayPointer, $stock);
  }
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
      <div class="headline"><?php echo REPORT_STOCK_TAKE_BRAND_TITLE; ?></div>
      <?php
        if (count($stocks) > 0) {

          foreach ($stocks as $brand => $brandStocks) {
            $totalQty = 0;
            $totalAmt = 0;

            echo "
              <div class=\"brand-name\">
                <h4>$brand</h4>
                <table class=\"brand-results\">
                  <colgroup>
                    <col>
                    <col style=\"width: 80px;\">
                    <col style=\"width: 80px;\">
                  </colgroup>
                  <thead>
                    <tr></tr>
                    <tr>
                      <th>Warehouse</th>
                      <th class=\"number\">Qty</th>
                      <th class=\"number\">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody>
            ";

            for ($i = 0; $i < count($brandStocks); $i++) {
              $brandStock = $brandStocks[$i];
              $brandId = $brandStock["brand_id"];
              $warehouseCode = $brandStock["warehouse_code"];
              $warehouseName = $brandStock["warehouse_name"];
              $qty = $brandStock["qty"];
              $subtotal = $brandStock["subtotal"];

              $totalQty += $qty;
              $totalAmt += $subtotal;

              echo "
                <tr>
                  <td title=\"$warehouseCode\">
                    <a href=\"" . REPORT_STOCK_TAKE_BRAND_DETAIL_URL . "?id[]=$brandId&filter_warehouse_code[]=$warehouseCode\">$warehouseCode - $warehouseName</a>
                  </td>
                  <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                  <td title=\"$subtotal\" class=\"number\">" . number_format($subtotal, 2) . "</td>
                </tr>
              ";
            }

            echo "
                  </tbody>
                  <tfoot>
                    <tr>
                      <th class=\"number\">Total:</th>
                      <th class=\"number\">" . number_format($totalQty) . "</th>
                      <th class=\"number\">" . number_format($totalAmt, 2) . "</th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            ";
          }
        } else {
          echo "<div class=\"brand-no-results\">No results</div>";
        }
      ?>
    </div>
  </body>
</html>
