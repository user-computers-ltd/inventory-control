<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $results = query("
    SELECT
      CONCAT(b.code, ' - ', b.name)   AS `warehouse`,
      b.id                            AS `warehouse_id`,
      c.code                          AS `brand_code`,
      c.name                          AS `brand_name`,
      SUM(a.qty)                      AS `qty`,
      SUM(e.qty_on_reserve)           AS `qty_on_reserve`,
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
    LEFT JOIN
      (SELECT
        h.warehouse_code  AS `warehouse_code`,
        m.brand_code      AS `brand_code`,
        m.model_no        AS `model_no`,
        SUM(m.qty)        AS `qty_on_reserve`
      FROM
        `sdo_model` AS m
      LEFT JOIN
        `sdo_header` AS h
      ON m.do_no=h.do_no
      WHERE
        h.status=\"SAVED\"
      GROUP BY
        h.warehouse_code, m.brand_code, m.model_no) AS e
    ON a.warehouse_code=e.warehouse_code AND a.brand_code=e.brand_code AND a.model_no=e.model_no
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
    $warehouse = $stock["warehouse"];

    $arrayPointer = &$stocks;

    if (!isset($arrayPointer[$warehouse])) {
      $arrayPointer[$warehouse] = array();
    }
    $arrayPointer = &$arrayPointer[$warehouse];

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
      <div class="headline"><?php echo REPORT_STOCK_TAKE_WAREHOUSE_TITLE; ?></div>
      <?php if (count($stocks) > 0) : ?>
        <?php foreach ($stocks as $warehouse => &$warehouseStocks) : ?>
          <div class="warehouse-name">
            <h4><?php echo $warehouse; ?></h4>
            <table class="warehouse-results">
              <colgroup>
                <col>
                <col style="width: 80px;">
                <col style="width: 80px;">
                <col style="width: 80px;">
                <col style="width: 80px;">
              </colgroup>
              <thead>
                <tr></tr>
                <tr>
                  <th>Brand</th>
                  <th class="number">Qty</th>
                  <th class="number">Reserved</th>
                  <th class="number">Available</th>
                  <th class="number">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $totalQty = 0;
                  $totalQtyOnReserve = 0;
                  $totalQtyAvailable = 0;
                  $totalAmt = 0;

                  for ($i = 0; $i < count($warehouseStocks); $i++) {
                    $warehouseStock = $warehouseStocks[$i];
                    $warehouseId = $warehouseStock["warehouse_id"];
                    $brandCode = $warehouseStock["brand_code"];
                    $brandName = $warehouseStock["brand_name"];
                    $qty = $warehouseStock["qty"];
                    $qtyOnReserve = $warehouseStock["qty_on_reserve"];
                    $qtyAvailable = $qty - $qtyOnReserve;
                    $subtotal = $warehouseStock["subtotal"];

                    $totalQty += $qty;
                    $totalQtyOnReserve += $qtyOnReserve;
                    $totalQtyAvailable += $qtyAvailable;
                    $totalAmt += $subtotal;

                    echo "
                      <tr>
                        <td title=\"$brandCode\">
                          <a href=\"" . REPORT_STOCK_TAKE_WAREHOUSE_DETAIL_URL . "?id[]=$warehouseId&filter_brand_code[]=$brandCode\">$brandCode - $brandName</a>
                        </td>
                        <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                        <td title=\"$qtyOnReserve\" class=\"number\">" . number_format($qtyOnReserve) . "</td>
                        <td title=\"$qtyAvailable\" class=\"number\">" . number_format($qtyAvailable) . "</td>
                        <td title=\"$subtotal\" class=\"number\">" . number_format($subtotal, 2) . "</td>
                      </tr>
                    ";
                  }
                ?>
                <tr>
                  <th class="number">Total:</th>
                  <th class="number"><?php echo number_format($totalQty); ?></th>
                  <th class="number"><?php echo number_format($totalQtyOnReserve); ?></th>
                  <th class="number"><?php echo number_format($totalQtyAvailable); ?></th>
                  <th class="number"><?php echo number_format($totalAmt, 2); ?></th>
                </tr>
              </tbody>
            </table>
          </div>
        <?php endforeach ?>
      <?php else : ?>
        <div class="warehouse-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
