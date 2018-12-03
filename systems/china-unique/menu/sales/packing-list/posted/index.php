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
      AND a.pl_date >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.pl_date <= \"$to\"";
  }

  $plHeaders = query("
    SELECT
      DATE_FORMAT(a.pl_date, '%d-%m-%Y')                                      AS `date`,
      a.pl_no                                                                 AS `pl_no`,
      CONCAT(a.debtor_code, ' - ', IFNULL(c.english_name, 'Unknown'))         AS `debtor`,
      IFNULL(b.total_qty, 0)                                                  AS `qty`,
      a.discount                                                              AS `discount`,
      a.currency_code                                                         AS `currency`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100                       AS `total_amt`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100 * a.exchange_rate     AS `total_amt_base`
    FROM
      `pl_header` AS a
    LEFT JOIN
      (SELECT
        pl_no, SUM(qty) as total_qty, SUM(qty * price) as total_amt
      FROM
        `pl_model`
      GROUP BY
        pl_no) AS b
    ON a.pl_no=b.pl_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"POSTED\"
      $whereClause
    ORDER BY
      a.pl_date DESC
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
      <div class="headline"><?php echo PACKING_LIST_POSTED_TITLE; ?></div>
      <form>
        <table id="pl-input">
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
      <?php if (count($plHeaders) > 0): ?>
        <table id="pl-results">
          <colgroup>
            <col style="width: 70px">
            <col>
            <col>
            <col style="width: 80px">
            <col style="width: 80px">
            <col style="width: 80px">
            <col style="width: 80px">
            <col style="width: 80px">
          </colgroup>
          <thead>
            <tr></tr>
            <tr>
              <th>Date</th>
              <th>Order No.</th>
              <th>Customer</th>
              <th class="number">Total Qty</th>
              <th class="number">Discount</th>
              <th class="number">Currency</th>
              <th class="number">Total Amt</th>
              <th class="number"><?php echo $InBaseCurrCol; ?></th>
            </tr>
          </thead>
          <tbody>
            <?php
              $totalQty = 0;
              $totalAmtBaseSum = 0;

              for ($i = 0; $i < count($plHeaders); $i++) {
                $plHeader = $plHeaders[$i];
                $date = $plHeader["date"];
                $debtor = $plHeader["debtor"];
                $plNo = $plHeader["pl_no"];
                $qty = $plHeader["qty"];
                $discount = $plHeader["discount"];
                $currency = $plHeader["currency"];
                $totalAmt = $plHeader["total_amt"];
                $totalAmtBase = $plHeader["total_amt_base"];

                $totalQty += $qty;
                $totalAmtBaseSum += $totalAmtBase;

                echo "
                  <tr>
                    <td title=\"$date\">$date</td>
                    <td title=\"$plNo\"><a class=\"link\" href=\"" . PACKING_LIST_PRINTOUT_URL . "?pl_no=$plNo\">$plNo</a></td>
                    <td title=\"$debtor\">$debtor</td>
                    <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                    <td title=\"$discount\" class=\"number\">" . number_format($discount, 2) . "%</td>
                    <td title=\"$currency\" class=\"number\">$currency</td>
                    <td title=\"$totalAmt\" class=\"number\">" . number_format($totalAmt, 2) . "</td>
                    <td title=\"$totalAmtBase\" class=\"number\">" . number_format($totalAmtBase, 2) . "</td>
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
              <th></th>
              <th></th>
              <th></th>
              <th class="number"><?php echo number_format($totalAmtBaseSum, 2); ?></th>
            </tr>
          </tfoot>
        </table>
      <?php else: ?>
        <div class="pl-customer-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
