<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(in " . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];

  $whereClause = "";

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND a.do_date >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.do_date <= \"$to\"";
  }

  $doHeaders = query("
    SELECT
      a.id                                                                    AS `do_id`,
      DATE_FORMAT(a.do_date, '%d-%m-%Y')                                      AS `date`,
      a.do_no                                                                 AS `do_no`,
      CONCAT(a.debtor_code, ' - ', IFNULL(c.english_name, 'Unknown'))         AS `debtor`,
      IFNULL(b.total_qty, 0)                                                  AS `qty`,
      a.discount                                                              AS `discount`,
      a.currency_code                                                         AS `currency`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100                       AS `total_amt`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100 * a.exchange_rate     AS `total_amt_base`
    FROM
      `sdo_header` AS a
    LEFT JOIN
      (SELECT
        do_no, SUM(qty) as total_qty, SUM(qty * price) as total_amt
      FROM
        `sdo_model`
      GROUP BY
        do_no) AS b
    ON a.do_no=b.do_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"SAVED\"
      $whereClause
    ORDER BY
      a.do_date DESC
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
      <div class="headline"><?php echo SALES_DELIVERY_ORDER_SAVED_TITLE; ?></div>
      <form>
        <table id="do-input">
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
      <?php if (count($doHeaders) > 0): ?>
        <table id="do-results">
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
              <th class="number"><?php echo $InBaseCurrency; ?></th>
            </tr>
          </thead>
          <tbody>
            <?php
              $totalQty = 0;
              $totalAmtBaseSum = 0;

              for ($i = 0; $i < count($doHeaders); $i++) {
                $doHeader = $doHeaders[$i];
                $doId = $doHeader["do_id"];
                $date = $doHeader["date"];
                $debtor = $doHeader["debtor"];
                $doNo = $doHeader["do_no"];
                $qty = $doHeader["qty"];
                $discount = $doHeader["discount"];
                $currency = $doHeader["currency"];
                $totalAmt = $doHeader["total_amt"];
                $totalAmtBase = $doHeader["total_amt_base"];

                $totalQty += $qty;
                $totalAmtBaseSum += $totalAmtBase;

                echo "
                  <tr>
                    <td title=\"$date\">$date</td>
                    <td title=\"$doNo\"><a class=\"link\" href=\"" . SALES_DELIVERY_ORDER_URL . "?id=$doId\">$doNo</a></td>
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
        <div class="do-customer-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
