<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $modelId = $_GET["id"];

  $InBaseCurrency = "(in " . COMPANY_CURRENCY . ")";
  $date = date("Y-m-d");

  $model = query("
    SELECT
      a.model_no                                                                    AS `model_no`,
      a.description                                                                 AS `description`,
      a.brand_code                                                                  AS `brand_code`,
      b.name                                                                        AS `brand_name`,
      CONCAT(a.cost_pri_currency_code, ' ', a.cost_pri, ' @ ', IFNULL(f.rate, 1))   AS `cost_pri`,
      a.cost_pri * IFNULL(f.rate, 1)                                                AS `cost_pri_base`,
      CONCAT(a.cost_sec_currency_code, ' ', a.cost_sec, ' @ ', IFNULL(g.rate, 1))   AS `cost_sec`,
      a.cost_sec * IFNULL(g.rate, 1)                                                AS `cost_sec_base`,
      a.cost_average                                                                AS `cost_average`,
      a.retail_normal                                                               AS `retail_normal`,
      a.retail_special                                                              AS `retail_special`,
      a.wholesale_normal                                                            AS `wholesale_normal`,
      a.wholesale_special                                                           AS `wholesale_special`,
      IFNULL(c.qty_on_hand, 0)                                                      AS `qty_on_hand`,
      IFNULL(d.qty_on_order, 0)                                                     AS `qty_on_order`,
      IFNULL(e.qty_on_reserve, 0)                                                   AS `qty_on_reserve`
    FROM
      `model` AS a
    LEFT JOIN
      `brand` AS b
    ON a.brand_code=b.code
    LEFT JOIN
      (SELECT
        model_no, brand_code, SUM(qty) AS `qty_on_hand`
      FROM
        `stock`
      GROUP BY
        model_no, brand_code) AS c
    ON a.model_no=c.model_no AND a.brand_code=c.brand_code
    LEFT JOIN
      (SELECT
        m.brand_code, m.model_no, SUM(GREATEST(qty_outstanding, 0)) AS `qty_on_order`
      FROM
        `po_model` AS m
      LEFT JOIN
        `po_header` AS h
      ON m.po_no=h.po_no
      WHERE
        h.status='POSTED'
      GROUP BY
        m.model_no, m.brand_code) AS d
    ON a.model_no=d.model_no AND a.brand_code=d.brand_code
    LEFT JOIN
      (SELECT
        brand_code, model_no, SUM(qty) AS `qty_on_reserve`
      FROM
        `so_allotment`
      GROUP BY
        model_no, brand_code) AS e
    ON a.model_no=e.model_no AND a.brand_code=e.brand_code
    LEFT JOIN
      `currency` AS f
    ON a.cost_pri_currency_code=f.code
    LEFT JOIN
      `currency` AS g
    ON a.cost_sec_currency_code=g.code
    WHERE
      a.id=\"$modelId\"
  ")[0];

  execute(array(
    "DROP TABLE IF EXISTS `temp_dates`",
    "CREATE TABLE `temp_dates` (date VARCHAR(30) NOT NULL)",
    "INSERT INTO `temp_dates` VALUES
      (DATE_FORMAT(NOW(), '%Y-%m')),
      (DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m')),
      (DATE_FORMAT(NOW() - INTERVAL 2 MONTH, '%Y-%m')),
      (DATE_FORMAT(NOW() - INTERVAL 3 MONTH, '%Y-%m'))
    "
  ));

  function getMonthlyTransactions($id, $whereClause) {
    return "
      SELECT
        DATE_FORMAT(t.transaction_date, '%Y-%m')          AS `date`,
        COUNT(*)                                          AS `count`,
        SUM(t.qty)                                        AS `qty`,
        SUM(t.qty * t.price * (100 - t.discount) / 100)   AS `amt`
      FROM
        `transaction` AS t
      LEFT JOIN
        `model` AS m
      ON t.brand_code=m.brand_code AND t.model_no=m.model_no
      WHERE
        m.id=\"$id\" AND ($whereClause)
      GROUP BY
        DATE_FORMAT(transaction_date, '%Y-%m')
    ";
  }

  function getYTDTransactions($id, $whereClause) {
    return "
      SELECT
        \"YTD\"                                           AS `date`,
        COUNT(*)                                          AS `count`,
        SUM(t.qty)                                        AS `qty`,
        SUM(t.qty * t.price * (100 - t.discount) / 100)   AS `amt`
      FROM
        `transaction` AS t
      LEFT JOIN
        `model` AS m
      ON t.brand_code=m.brand_code AND t.model_no=m.model_no
      WHERE
        m.id=\"$id\" AND
        t.transaction_date
          BETWEEN
            DATE_FORMAT(NOW() ,'%Y-01-01') AND
            NOW()
          AND
        ($whereClause)
    ";
  }

  function getPreviousYTDTransactions($id, $whereClause) {
    return "
      SELECT
        \"Previous YTD\"                                  AS `date`,
        COUNT(*)                                          AS `count`,
        SUM(t.qty)                                        AS `qty`,
        SUM(t.qty * t.price * (100 - t.discount) / 100)   AS `amt`
      FROM
        `transaction` AS t
      LEFT JOIN
        `model` AS m
      ON t.brand_code=m.brand_code AND t.model_no=m.model_no
      WHERE
        m.id=\"$id\" AND
        t.transaction_date
          BETWEEN
            DATE_FORMAT(NOW() - INTERVAL 1 YEAR, '%Y-01-01') AND
            LAST_DAY(DATE_FORMAT(NOW() - INTERVAL 1 YEAR, '%Y-12'))
          AND
        ($whereClause)
    ";
  }

  $monthlyTransactions = query("
    SELECT
      a.date              AS `date`,
      IFNULL(b.count, 0)  AS `sales_count`,
      IFNULL(b.qty, 0)    AS `sales_qty`,
      IFNULL(b.amt, 0)    AS `sales_amt`,
      IFNULL(c.count, 0)  AS `sales_return_count`,
      IFNULL(c.qty, 0)    AS `sales_return_qty`,
      IFNULL(c.amt, 0)    AS `sales_return_amt`,
      IFNULL(d.count, 0)  AS `purchase_count`,
      IFNULL(d.qty, 0)    AS `purchase_qty`,
      IFNULL(d.amt, 0)    AS `purchase_amt`,
      IFNULL(e.count, 0)  AS `purchase_return_count`,
      IFNULL(e.qty, 0)    AS `purchase_return_qty`,
      IFNULL(e.amt, 0)    AS `purchase_return_amt`
    FROM
      temp_dates AS a
    LEFT JOIN
      (" . getMonthlyTransactions($modelId, "transaction_code=\"S1\" OR transaction_code=\"S2\"") . ") AS b
    ON a.date=b.date
    LEFT JOIN
      (" . getMonthlyTransactions($modelId, "transaction_code=\"R3\"") . ") AS c
    ON a.date=c.date
    LEFT JOIN
      (" . getMonthlyTransactions($modelId, "transaction_code=\"R1\" OR transaction_code=\"R2\"") . ") AS d
    ON a.date=d.date
    LEFT JOIN
      (" . getMonthlyTransactions($modelId, "transaction_code=\"S3\"") . ") AS e
    ON a.date=e.date
  ");

  $ytdTransactions = query("
    SELECT
      a.date              AS `date`,
      IFNULL(a.count, 0)  AS `sales_count`,
      IFNULL(a.qty, 0)    AS `sales_qty`,
      IFNULL(a.amt, 0)    AS `sales_amt`,
      IFNULL(b.count, 0)  AS `sales_return_count`,
      IFNULL(b.qty, 0)    AS `sales_return_qty`,
      IFNULL(b.amt, 0)    AS `sales_return_amt`,
      IFNULL(c.count, 0)  AS `purchase_count`,
      IFNULL(c.qty, 0)    AS `purchase_qty`,
      IFNULL(c.amt, 0)    AS `purchase_amt`,
      IFNULL(d.count, 0)  AS `purchase_return_count`,
      IFNULL(d.qty, 0)    AS `purchase_return_qty`,
      IFNULL(d.amt, 0)    AS `purchase_return_amt`
    FROM
      (" . getYTDTransactions($modelId, "transaction_code=\"S1\" OR transaction_code=\"S2\"") . ") AS a
    LEFT JOIN
      (" . getYTDTransactions($modelId, "transaction_code=\"R3\"") . ") AS b
    ON a.date=b.date
    LEFT JOIN
      (" . getYTDTransactions($modelId, "transaction_code=\"R1\" OR transaction_code=\"R2\"") . ") AS c
    ON a.date=c.date
    LEFT JOIN
      (" . getYTDTransactions($modelId, "transaction_code=\"S3\"") . ") AS d
    ON a.date=d.date
  ");

  $ytdPreviousTransactions = query("
    SELECT
      a.date              AS `date`,
      IFNULL(a.count, 0)  AS `sales_count`,
      IFNULL(a.qty, 0)    AS `sales_qty`,
      IFNULL(a.amt, 0)    AS `sales_amt`,
      IFNULL(b.count, 0)  AS `sales_return_count`,
      IFNULL(b.qty, 0)    AS `sales_return_qty`,
      IFNULL(b.amt, 0)    AS `sales_return_amt`,
      IFNULL(c.count, 0)  AS `purchase_count`,
      IFNULL(c.qty, 0)    AS `purchase_qty`,
      IFNULL(c.amt, 0)    AS `purchase_amt`,
      IFNULL(d.count, 0)  AS `purchase_return_count`,
      IFNULL(d.qty, 0)    AS `purchase_return_qty`,
      IFNULL(d.amt, 0)    AS `purchase_return_amt`
    FROM
      (" . getPreviousYTDTransactions($modelId, "transaction_code=\"S1\" OR transaction_code=\"S2\"") . ") AS a
    LEFT JOIN
      (" . getPreviousYTDTransactions($modelId, "transaction_code=\"R3\"") . ") AS b
    ON a.date=b.date
    LEFT JOIN
      (" . getPreviousYTDTransactions($modelId, "transaction_code=\"R1\" OR transaction_code=\"R2\"") . ") AS c
    ON a.date=c.date
    LEFT JOIN
      (" . getPreviousYTDTransactions($modelId, "transaction_code=\"S3\"") . ") AS d
    ON a.date=d.date
  ");

  query("DROP TABLE `temp_dates`");

  $warehouseStocks = query("
    SELECT
      c.code    AS `warehouse_code`,
      c.name    AS `warehouse_name`,
      SUM(qty)  AS `qty`
    FROM
      `stock` as a
    LEFT JOIN
      `model` AS b
    ON a.brand_code=b.brand_code AND a.model_no=b.model_no
    LEFT JOIN
      `warehouse` AS c
    ON a.warehouse_code=c.code
    WHERE
      b.id=\"$modelId\"
    GROUP BY
      c.code, c.name
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
      <div class="headline"><?php echo DATA_MODEL_MODEL_DETAIL_TITLE; ?></div>
        <?php if (isset($model)): ?>
          <table id="model-header">
            <tr>
              <th>Model No.:</th>
              <td class="number"><?php echo $model["model_no"]; ?></td>
            </tr>
            <tr>
              <th>Description:</th>
              <td class="number"><?php echo $model["description"]; ?></td>
            </tr>
            <tr>
              <th>Brand:</th>
              <td class="number"><?php echo $model["brand_code"] . " - " . $model["brand_name"]; ?></td>
            </tr>
            <tr>
              <th>Cost Primary:</th>
              <td class="number"><?php echo $model["cost_pri"]; ?></td>
            </tr>
            <tr>
              <th>Cost Primary <?php echo $InBaseCurrency; ?>:</th>
              <td class="number"><?php echo number_format($model["cost_pri_base"], 6); ?></td>
            </tr>
            <tr>
              <th>Cost Special:</th>
              <td class="number"><?php echo $model["cost_sec"]; ?></td>
            </tr>
            <tr>
              <th>Cost Special <?php echo $InBaseCurrency; ?>:</th>
              <td class="number"><?php echo number_format($model["cost_sec_base"], 6); ?></td>
            </tr>
            <tr>
              <th>Average Cost:</th>
              <td class="number"><?php echo number_format($model["cost_average"], 6); ?></td>
            </tr>
            <tr>
              <th>Retail Normal Price:</th>
              <td class="number"><?php echo number_format($model["retail_normal"], 6); ?></td>
            </tr>
            <tr>
              <th>Retail Special Price:</th>
              <td class="number"><?php echo number_format($model["retail_special"], 6); ?></td>
            </tr>
            <tr>
              <th>End User Price:</th>
              <td class="number"><?php echo number_format($model["wholesale_normal"], 6); ?></td>
            </tr>
            <tr>
              <th>Qty On Hand:</th>
              <td class="number"><?php echo number_format($model["qty_on_hand"]); ?></td>
            </tr>
            <tr>
              <th>Qty On Order:</th>
              <td class="number"><?php echo number_format($model["qty_on_order"]); ?></td>
            </tr>
            <tr>
              <th>Qty On Reserve:</th>
              <td class="number"><?php echo number_format($model["qty_on_reserve"]); ?></td>
            </tr>
          </table>
          <table id="model-performance">
            <colgroup>
              <col style="width: 80px;">
              <col>
              <col>
              <col>
              <col>
              <col>
              <col>
              <col>
              <col>
            </colgroup>
            <thead>
              <tr>
                <th rowspan="2">Period</th>
                <th colspan="2">Sales</th>
                <th colspan="2">Sales Return</th>
                <th colspan="2">Purchase</th>
                <th colspan="2">Purchase Return</th>
              </tr>
              <tr>
                <th class="number">Qty</th>
                <th class="number">Amount</th>
                <th class="number">Qty</th>
                <th class="number">Amount</th>
                <th class="number">Qty</th>
                <th class="number">Amount</th>
                <th class="number">Qty</th>
                <th class="number">Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php
                function generateRows($transactions) {
                  for ($i = 0; $i < count($transactions); $i++) {
                    $transaction = $transactions[$i];
                    $date = $transaction["date"];
                    $salesCount = $transaction["sales_count"];
                    $salesQty = $transaction["sales_qty"];
                    $salesAmt = $transaction["sales_amt"];
                    $salesReturnCount = $transaction["sales_return_count"];
                    $salesReturnQty = $transaction["sales_return_qty"];
                    $salesReturnAmt = $transaction["sales_return_amt"];
                    $purchaseCount = $transaction["purchase_count"];
                    $purchaseQty = $transaction["purchase_qty"];
                    $purchaseAmt = $transaction["purchase_amt"];
                    $purchaseReturnCount = $transaction["purchase_return_count"];
                    $purchaseReturnQty = $transaction["purchase_return_qty"];
                    $purchaseReturnAmt = $transaction["purchase_return_amt"];

                    echo "
                      <tr>
                        <td title=\"$date\">$date</td>
                        <td class=\"number\" title=\"$salesQty\">" . number_format($salesQty) . "</td>
                        <td class=\"number\" title=\"$salesAmt\">" . number_format($salesAmt, 2) . "</td>
                        <td class=\"number\" title=\"$salesReturnQty\">" . number_format($salesReturnQty) . "</td>
                        <td class=\"number\" title=\"$salesReturnAmt\">" . number_format($salesReturnAmt, 2) . "</td>
                        <td class=\"number\" title=\"$purchaseQty\">" . number_format($purchaseQty) . "</td>
                        <td class=\"number\" title=\"$purchaseAmt\">" . number_format($purchaseAmt, 2) . "</td>
                        <td class=\"number\" title=\"$purchaseReturnQty\">" . number_format($purchaseReturnQty) . "</td>
                        <td class=\"number\" title=\"$purchaseReturnAmt\">" . number_format($purchaseReturnAmt, 2) . "</td>
                      </tr>
                    ";
                  }
                }

                generateRows($monthlyTransactions);
                generateRows($ytdTransactions);
                generateRows($ytdPreviousTransactions);
              ?>
            </tbody>
          </table>
          <?php if (count($warehouseStocks) > 0): ?>
            <table id="model-stock">
              <thead>
                <tr>
                  <th>Warehouse</th>
                  <th>Qty</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  for ($i = 0; $i < count($warehouseStocks); $i++) {
                    $warehouseStock = $warehouseStocks[$i];
                    $warehouseCode = $warehouseStock["warehouse_code"];
                    $warehouseName = $warehouseStock["warehouse_name"];
                    $qty = $warehouseStock["qty"];

                    echo "
                      <tr>
                        <td title=\"$warehouseCode\">$warehouseCode - $warehouseName</td>
                        <td class=\"number\" title=\"$qty\">" . number_format($qty) . "</td>
                      </tr>
                    ";
                  }
                ?>
              </tbody>
            </table>
          <?php else: ?>
            <div class="model-no-result">No stocks</div>
          <?php endif ?>
        <?php else: ?>
          <div class="model-no-result">No results</div>
        <?php endif ?>
    </div>
  </body>
</html>
