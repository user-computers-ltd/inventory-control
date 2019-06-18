<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $results = query("
    SELECT
      CONCAT(c.code, \" - \", c.name) AS `brand`,
      c.id                            AS `brand_id`,
      b.code                          AS `warehouse_code`,
      b.name                          AS `warehouse_name`,
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
        h.status=\"SAVED\" AND
        m.ia_no=\"\"
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
    <?php include_once SYSTEM_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo REPORT_STOCK_TAKE_BRAND_TITLE; ?></div>
      <?php if (count($stocks) > 0) : ?>
        <?php foreach ($stocks as $brand => &$brandStocks) : ?>
          <div class="brand-name">
            <h4><?php echo $brand; ?></h4>
            <table class="brand-results">
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
                  <th>Warehouse</th>
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

                  for ($i = 0; $i < count($brandStocks); $i++) {
                    $brandStock = $brandStocks[$i];
                    $brandId = $brandStock["brand_id"];
                    $warehouseCode = $brandStock["warehouse_code"];
                    $warehouseName = $brandStock["warehouse_name"];
                    $qty = $brandStock["qty"];
                    $qtyOnReserve = $brandStock["qty_on_reserve"];
                    $qtyAvailable = $qty - $qtyOnReserve;
                    $subtotal = $brandStock["subtotal"];

                    $totalQty += $qty;
                    $totalQtyOnReserve += $qtyOnReserve;
                    $totalQtyAvailable += $qtyAvailable;
                    $totalAmt += $subtotal;

                    echo "
                      <tr>
                        <td title=\"$warehouseCode\">
                          <a href=\"" . REPORT_STOCK_TAKE_BRAND_DETAIL_URL . "?id[]=$brandId&filter_warehouse_code[]=$warehouseCode\">$warehouseCode - $warehouseName</a>
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
        <div class="brand-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
