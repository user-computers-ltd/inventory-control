<?php
  define("SYSTEM_PATH", "../../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(in " . COMPANY_CURRENCY . ")";

  $ids = $_GET["id"];

  $whereClause = "";

  if (assigned($ids) && count($ids) > 0) {
    $whereClause = "
      AND (" . join(" OR ", array_map(function ($id) { return "c.id=\"$id\""; }, $ids)) . ")";
  }

  $results = query("
    SELECT
      CONCAT(a.debtor_code, ' - ', IFNULL(c.english_name, 'Unknown'))                     AS `debtor`,
      DATE_FORMAT(a.so_date, '%d-%m-%Y')                                                  AS `date`,
      a.id                                                                                AS `so_id`,
      a.so_no                                                                             AS `so_no`,
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
        so_no                         AS `so_no`,
        SUM(qty)                      AS `total_qty`,
        SUM(qty_outstanding)          AS `total_qty_outstanding`,
        SUM(qty_outstanding * price)  AS `total_outstanding_amt`
      FROM
        `so_model`
      GROUP BY
        so_no) AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"POSTED\"
      $whereClause
    ORDER BY
      a.debtor_code ASC,
      a.so_date DESC
  ");

  $soHeaders = array();

  foreach ($results as $soHeader) {
    $customer = $soHeader["debtor"];

    if (!isset($soHeaders[$customer])) {
      $soHeaders[$customer] = array();
    }

    array_push($soHeaders[$customer], $soHeader);
  }

  $debtors = query("
    SELECT DISTINCT
      a.debtor_code                       AS `code`,
      IFNULL(b.english_name, 'Unknown')   AS `name`
    FROM
      `so_header` AS a
    LEFT JOIN
      `debtor` AS b
    ON a.debtor_code=b.code
    ORDER BY
      a.debtor_code ASC
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
      <div class="headline"><?php echo SALES_REPORT_CUSTOMER_DETAIL_TITLE; ?></div>
      <?php
        if (count($soHeaders) > 0) {

          foreach ($soHeaders as $customer => $headers) {
            $totalQty = 0;
            $totalOutstanding = 0;
            $totalAmtBase = 0;

            echo "
              <div class=\"so-customer\">
                <h4>$customer</h4>
                <table class=\"so-results\">
                  <colgroup>
                    <col style=\"width: 80px\">
                    <col>
                    <col style=\"width: 80px\">
                    <col style=\"width: 80px\">
                    <col style=\"width: 60px\">
                    <col style=\"width: 80px\">
                    <col style=\"width: 80px\">
                    <col style=\"width: 40px\">
                  </colgroup>
                  <thead>
                    <tr></tr>
                    <tr>
                      <th>Date</th>
                      <th>Order No.</th>
                      <th class=\"number\">Total Qty</th>
                      <th class=\"number\">Outstanding Qty</th>
                      <th class=\"number\">Currency</th>
                      <th class=\"number\">Outstanding Amt</th>
                      <th class=\"number\">$InBaseCurrency</th>
                      <th class=\"number\">Discount</th>
                    </tr>
                  </thead>
                  <tbody>
            ";

            for ($i = 0; $i < count($headers); $i++) {
              $soHeader = $headers[$i];
              $date = $soHeader["date"];
              $debtor = $soHeader["debtor"];
              $soId = $soHeader["so_id"];
              $soNo = $soHeader["so_no"];
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
                  <td title=\"$soNo\"><a class=\"link\" href=\"" . SALES_ORDER_PRINTOUT_URL . "?id=$soId\">$soNo</a></td>
                  <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                  <td title=\"$outstandingQty\" class=\"number\">" . number_format($outstandingQty) . "</td>
                  <td title=\"$currency\" class=\"number\">$currency</td>
                  <td title=\"$outstandingAmt\" class=\"number\">" . number_format($outstandingAmt, 2) . "</td>
                  <td title=\"$outstandingAmtBase\" class=\"number\">" . number_format($outstandingAmtBase, 2) . "</td>
                  <td title=\"$discount\" class=\"number\">" . number_format($discount, 2) . "%</td>
                </tr>
              ";
            }

            echo "
                  </tbody>
                  <tfoot>
                    <tr>
                      <th></th>
                      <th class=\"number\">Total:</th>
                      <th class=\"number\">" . number_format($totalQty) . "</th>
                      <th class=\"number\">" . number_format($totalOutstanding) . "</th>
                      <th></th>
                      <th></th>
                      <th class=\"number\">" . number_format($totalAmtBase, 2) . "</th>
                      <th></th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            ";
          }
        } else {
          echo "<div class=\"so-customer-no-results\">No results</div>";
        }
      ?>
    </div>
  </body>
</html>
