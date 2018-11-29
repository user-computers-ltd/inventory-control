<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrCol = "(in " . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];

  $whereClause = "";

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND a.so_date >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.so_date <= \"$to\"";
  }

  $soHeaders = query("
    SELECT
      DATE_FORMAT(a.so_date, '%d-%m-%Y')                                                  AS `date`,
      a.so_no                                                                             AS `so_no`,
      CONCAT(a.debtor_code, ' - ', IFNULL(c.english_name, 'Unknown'))                     AS `debtor`,
      IFNULL(b.total_qty, 0)                                                              AS `qty`,
      IFNULL(b.total_qty_outstanding, 0)                                                  AS `outstanding_qty`,
      a.discount                                                                          AS `discount`,
      a.currency_code                                                                     AS `currency`,
      IFNULL(b.total_outstanding_amt, 0) * (100 - a.discount) / 100                       AS `outstanding_amt`,
      IFNULL(b.total_outstanding_amt, 0) * (100 - a.discount) / 100 * a.exchange_rate     AS `outstanding_amt_base`
    FROM
      `so_header` AS a
    LEFT JOIN
      (SELECT
        so_no, SUM(qty) as total_qty, SUM(qty_outstanding) AS total_qty_outstanding, SUM(qty_outstanding * price) as total_outstanding_amt
      FROM
        `so_model`
      GROUP BY
        so_no) AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"SAVED\"
      $whereClause
    ORDER BY
      a.so_date DESC
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
      <div class="headline"><?php echo SALES_ORDER_SAVED_TITLE; ?></div>
      <form>
        <table id="so-input">
          <colgroup>
            <col style="width: 100px">
            <col>
            <col style="width: 100px">
            <col>
          </colgroup>
          <tr>
            <td><label>From:</label></td>
            <td><input type="date" name="from" value="<?php echo $from; ?>" max="<?php echo date("Y-m-d"); ?>" /></td>
            <td><label>To:</label></td>
            <td><input type="date" name="to" value="<?php echo $to; ?>" max="<?php echo date("Y-m-d"); ?>" /></td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($soHeaders) > 0): ?>
        <table id="so-results">
          <colgroup>
            <col style="width: 70px">
            <col style="width: 100px">
            <col style="width: 100px">
            <col>
            <col>
            <col>
            <col>
            <col>
            <col>
          </colgroup>
          <thead>
            <tr></tr>
            <tr>
              <th>Date</th>
              <th>Order No.</th>
              <th>Customer</th>
              <th class="number">Total Qty</th>
              <th class="number">Outstanding Qty</th>
              <th class="number">Discount</th>
              <th class="number">Currency</th>
              <th class="number">Outstanding Amt</th>
              <th class="number"><?php echo $InBaseCurrCol; ?></th>
            </tr>
          </thead>
          <tbody>
            <?php
              $totalQty = 0;
              $totalOutstanding = 0;
              $totalAmtBase = 0;

              for ($i = 0; $i < count($soHeaders); $i++) {
                $soHeader = $soHeaders[$i];
                $date = $soHeader["date"];
                $soNo = $soHeader["so_no"];
                $debtor = $soHeader["debtor"];
                $qty = $soHeader["qty"];
                $outstandingQty = $soHeader["outstanding_qty"];
                $discount = $soHeader["discount"];
                $currency = $soHeader["currency"];
                $outstandingAmt = $soHeader["outstanding_amt"];
                $outstandingAmtBase = $soHeader["outstanding_amt_base"];

                $totalQty += $qty;
                $totalOutstanding += $outstandingQty;
                $totalAmtBase += $outstandingAmtBase;

                echo "
                  <tr>
                    <td title=\"$date\">$date</td>
                    <td title=\"$soNo\"><a class=\"link\" href=\"" . SALES_ORDER_URL . "?so_no=$soNo\">$soNo</a></td>
                    <td title=\"$debtor\">$debtor</td>
                    <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                    <td title=\"$outstandingQty\" class=\"number\">" . number_format($outstandingQty) . "</td>
                    <td title=\"$discount\" class=\"number\">" . number_format($discount, 2) . "%</td>
                    <td title=\"$currency\" class=\"number\">$currency</td>
                    <td title=\"$outstandingAmt\" class=\"number\">" . number_format($outstandingAmt, 2) . "</td>
                    <td title=\"$outstandingAmtBase\" class=\"number\">" . number_format($outstandingAmtBase, 2) . "</td>
                  </tr>
                ";
              }
            ?>
          </tbody>
          <tfoot>
            <tr>
              <th></th>
              <th></th>
              <th class="number">Total:</th>
              <th class="number"><?php echo number_format($totalQty); ?></th>
              <th class="number"><?php echo number_format($totalOutstanding); ?></th>
              <th></th>
              <th></th>
              <th></th>
              <th class="number"><?php echo number_format($totalAmtBase, 2); ?></th>
            </tr>
          </tfoot>
        </table>
      <?php else: ?>
        <div class="so-customer-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
