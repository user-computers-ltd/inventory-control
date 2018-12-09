<?php
  define("SYSTEM_PATH", "../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(in " . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];

  $whereClause = "";

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND a.stock_in_date >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.stock_in_date <= \"$to\"";
  }

  $stockInHeaders = query("
    SELECT
      a.id                                                                    AS `id`,
      DATE_FORMAT(a.stock_in_date, '%d-%m-%Y')                                AS `date`,
      b.count                                                                 AS `count`,
      a.stock_in_no                                                           AS `stock_in_no`,
      IFNULL(c.english_name, 'Unknown')                                       AS `creditor_name`,
      IFNULL(b.total_qty, 0)                                                  AS `qty`,
      a.discount                                                              AS `discount`,
      a.currency_code                                                         AS `currency`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100                       AS `total_amt`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100 * a.exchange_rate     AS `total_amt_base`,
      a.transaction_code                                                      AS `transaction_code`
    FROM
      `stock_in_header` AS a
    LEFT JOIN
      (SELECT
        COUNT(*)                      AS `count`,
        stock_in_no                   AS `stock_in_no`,
        SUM(qty)                      AS `total_qty`,
        SUM(qty * price)              AS `total_amt`
      FROM
        `stock_in_model`
      GROUP BY
        stock_in_no) AS b
    ON a.stock_in_no=b.stock_in_no
    LEFT JOIN
      `creditor` AS c
    ON a.creditor_code=c.code
    WHERE
      a.status=\"POSTED\"
      $whereClause
    ORDER BY
      a.stock_in_date DESC
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
      <div class="headline"><?php echo STOCK_IN_POSTED_TITLE; ?></div>
      <form>
        <table id="stock-in-input">
          <tr>
            <th>From:</th>
            <th>To:</th>
          </tr>
          <tr>
            <td><input type="date" name="from" value="<?php echo $from; ?>" max="<?php echo date("Y-m-d"); ?>" /></td>
            <td><input type="date" name="to" value="<?php echo $to; ?>" max="<?php echo date("Y-m-d"); ?>" /></td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($stockInHeaders) > 0): ?>
        <table id="stock-in-results">
          <colgroup>
            <col style="width: 70px">
            <col style="width: 30px">
            <col>
            <col>
            <col style="width: 80px">
            <col style="width: 60px">
            <col style="width: 60px">
            <col style="width: 80px">
            <col style="width: 80px">
            <col style="width: 30px">
          </colgroup>
          <thead>
            <tr></tr>
            <tr>
              <th>Date</th>
              <th class="number">#</th>
              <th>Order No.</th>
              <th>Creditor</th>
              <th class="number">Total Qty</th>
              <th class="number">Discount</th>
              <th class="number">Currency</th>
              <th class="number">Total Amt</th>
              <th class="number"><?php echo $InBaseCurrency; ?></th>
              <th>T.C.</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $totalQty = 0;
              $totalAmtBaseSum = 0;

              for ($i = 0; $i < count($stockInHeaders); $i++) {
                $stockInHeader = $stockInHeaders[$i];
                $id = $stockInHeader["id"];
                $count = $stockInHeader["count"];
                $date = $stockInHeader["date"];
                $stockInNo = $stockInHeader["stock_in_no"];
                $creditorName = $stockInHeader["creditor_name"];
                $qty = $stockInHeader["qty"];
                $discount = $stockInHeader["discount"];
                $currency = $stockInHeader["currency"];
                $totalAmt = $stockInHeader["total_amt"];
                $totalAmtBase = $stockInHeader["total_amt_base"];
                $transactionCode = $stockInHeader["transaction_code"];
                $transactionName = $TRANSACTION_CODES[$transactionCode];
                $miscellaneous = $stockInHeader["transaction_code"] != "R1" && $stockInHeader["transaction_code"] != "R3";

                $totalQty += $qty;
                $totalAmtBaseSum += $totalAmtBase;

                echo "
                  <tr>
                    <td title=\"$date\">$date</td>
                    <td title=\"$count\" class=\"number\">$count</td>
                    <td title=\"$stockInNo\"><a class=\"link\" href=\"" . STOCK_IN_PRINTOUT_URL . "?id=$id\">$stockInNo</a></td>
                    " . ($miscellaneous ? "<td></td>" : "<td title=\"$creditorName\">$creditorName</td>") . "
                    <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                    " . ($miscellaneous ? "<td></td>" : "<td title=\"$discount\" class=\"number\">" . number_format($discount, 2) . "%</td>") . "
                    " . ($miscellaneous ? "<td></td>" : "<td title=\"$currency\" class=\"number\">$currency</td>") . "
                    " . ($miscellaneous ? "<td></td>" : "<td title=\"$totalAmt\" class=\"number\">" . number_format($totalAmt, 2) . "</td>") . "
                    " . ($miscellaneous ? "<td></td>" : "<td title=\"$totalAmtBase\" class=\"number\">" . number_format($totalAmtBase, 2) . "</td>") . "
                    <td title=\"$transactionCode - $transactionName\">$transactionCode</td>
                  </tr>
                ";
              }
            ?>
          </tbody>
          <tfoot>
            <tr>
              <th></th>
              <th class="number"></th>
              <th></th>
              <th class="number">Total:</th>
              <th class="number"><?php echo number_format($totalQty); ?></th>
              <th></th>
              <th></th>
              <th></th>
              <th class="number"><?php echo number_format($totalAmtBaseSum, 2); ?></th>
              <th></th>
            </tr>
          </tfoot>
        </table>
      <?php else: ?>
        <div class="stock-in-customer-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
