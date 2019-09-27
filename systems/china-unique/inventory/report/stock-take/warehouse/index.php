<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $results = query("
    SELECT
      CONCAT(b.code, \" - \", b.name)                  AS `warehouse`,
      b.id                                             AS `warehouse_id`,
      c.code                                           AS `brand_code`,
      c.name                                           AS `brand_name`,
      SUM(IFNULL(a.qty, 0))                            AS `qty`,
      SUM(e.qty_on_reserve)                            AS `qty_on_reserve`,
      SUM(ROUND(f.qty_on_loan * d.cost_average, 2))    AS `subtotal_loaned`,
      SUM(ROUND(f.qty_on_borrow * d.cost_average, 2))  AS `subtotal_borrowed`,
      SUM(ROUND(IFNULL(a.qty, 0) * d.cost_average, 2)) AS `subtotal`
    FROM
      (SELECT x.brand_code, x.model_no, y.code AS `warehouse_code` FROM `model` AS x CROSS JOIN `warehouse` AS y) AS z
    LEFT JOIN
      `stock` AS a
    ON z.warehouse_code=a.warehouse_code AND z.brand_code=a.brand_code AND z.model_no=a.model_no
    LEFT JOIN
      `warehouse` AS b
    ON z.warehouse_code=b.code
    LEFT JOIN
      `brand` AS c
    ON z.brand_code=c.code
    LEFT JOIN
      `model` AS d
    ON z.brand_code=d.brand_code AND z.model_no=d.model_no
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
    ON z.warehouse_code=e.warehouse_code AND z.brand_code=e.brand_code AND z.model_no=e.model_no
    LEFT JOIN
      (SELECT
        brand_code,
        model_no,
        warehouse_code,
        SUM(IF(transaction_code=\"S7\", qty, 0)) - SUM(IF(transaction_code=\"R8\", qty, 0)) AS `qty_on_loan`,
        SUM(IF(transaction_code=\"R7\", qty, 0)) - SUM(IF(transaction_code=\"S8\", qty, 0)) AS `qty_on_borrow`
      FROM
        `transaction`
      GROUP BY
        brand_code, model_no, warehouse_code) AS f
    ON z.warehouse_code=f.warehouse_code AND z.brand_code=f.brand_code AND z.model_no=f.model_no
    WHERE
      (a.qty > 0 OR
      f.qty_on_loan != 0 OR
      f.qty_on_borrow != 0)
    GROUP BY
      z.warehouse_code, z.brand_code
    ORDER BY
      z.warehouse_code ASC,
      z.brand_code ASC
  ");

  $stocks = array();
  $all = array();

  foreach ($results as $stock) {
    $warehouse = $stock["warehouse"];
    $bCode = $stock["brand_code"];

    $arrayPointer = &$stocks;

    if (!isset($arrayPointer[$warehouse])) {
      $arrayPointer[$warehouse] = array();
    }

    $arrayPointer = &$arrayPointer[$warehouse];

    array_push($arrayPointer, $stock);

    if (!isset($all[$bCode])) {
      $all[$bCode] = $stock;
      unset($all[$bCode]["warehouse_id"]);
    } else {
      $all[$bCode]["qty"] += $stock["qty"];
      $all[$bCode]["qty_on_reserve"] += $stock["qty_on_reserve"];
      $all[$bCode]["subtotal_loaned"] += $stock["subtotal_loaned"];
      $all[$bCode]["subtotal_borrowed"] += $stock["subtotal_borrowed"];
      $all[$bCode]["subtotal"] += $stock["subtotal"];
    }
  }

  $stocks["ALL"] = array();

  foreach ($all as $bCode => $stock) {
    array_push($stocks["ALL"], $stock);
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
                <col style="width: 80px;">
                <col style="width: 80px;">
              </colgroup>
              <thead>
                <tr></tr>
                <tr>
                  <th>Brand</th>
                  <th class="number">Total Value on Loan</th>
                  <th class="number">Total Value on Borrow</th>
                  <th class="number">Qty</th>
                  <th class="number">Reserved</th>
                  <th class="number">Available</th>
                  <th class="number">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $totalValueOnLoan = 0;
                  $totalValueOnBorrow = 0;
                  $totalQty = 0;
                  $totalQtyOnReserve = 0;
                  $totalQtyAvailable = 0;
                  $totalAmt = 0;

                  for ($i = 0; $i < count($warehouseStocks); $i++) {
                    $warehouseStock = $warehouseStocks[$i];
                    $warehouseId = $warehouseStock["warehouse_id"];
                    $brandCode = $warehouseStock["brand_code"];
                    $brandName = $warehouseStock["brand_name"];
                    $valueOnLoan = $warehouseStock["subtotal_loaned"];
                    $valueOnBorrow = $warehouseStock["subtotal_borrowed"];
                    $qty = $warehouseStock["qty"];
                    $qtyOnReserve = $warehouseStock["qty_on_reserve"];
                    $qtyAvailable = $qty - $qtyOnReserve;
                    $subtotal = $warehouseStock["subtotal"];

                    $totalValueOnLoan += $valueOnLoan;
                    $totalValueOnBorrow += $valueOnBorrow;
                    $totalQty += $qty;
                    $totalQtyOnReserve += $qtyOnReserve;
                    $totalQtyAvailable += $qtyAvailable;
                    $totalAmt += $subtotal;

                    echo "
                      <tr>
                        <td title=\"$brandCode\">
                          <a href=\"" . REPORT_STOCK_TAKE_WAREHOUSE_DETAIL_URL . "?" . (isset($warehouseId) ? "id[]=$warehouseId&" : "") . "filter_brand_code[]=$brandCode\">$brandCode - $brandName</a>
                        </td>
                        <td title=\"$valueOnLoan\" class=\"number\">" . number_format($valueOnLoan) . "</td>
                        <td title=\"$valueOnBorrow\" class=\"number\">" . number_format($valueOnBorrow) . "</td>
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
                  <th class="number"><?php echo number_format($totalValueOnLoan); ?></th>
                  <th class="number"><?php echo number_format($totalValueOnBorrow); ?></th>
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
