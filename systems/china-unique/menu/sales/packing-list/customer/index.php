<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrCol = "(in " . COMPANY_CURRENCY . ")";

  $debtorCodes = $_GET["debtor_code"];

  $whereClause = "";

  if (assigned($debtorCodes) && count($debtorCodes) > 0) {
    $whereClause = "
      AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code='$d'"; }, $debtorCodes)) . ")";
  }

  $results = query("
    SELECT
      CONCAT(a.debtor_code, ' - ', IFNULL(c.english_name, 'Unknown'))                     AS `debtor`,
      DATE_FORMAT(a.pl_date, '%d-%m-%Y')                                                  AS `date`,
      a.pl_no                                                                             AS `pl_no`,
      IFNULL(b.total_qty, 0)                                                              AS `qty`,
      a.discount                                                                          AS `discount`,
      a.currency_code                                                                     AS `currency`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100                                   AS `total_amt`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100 * a.exchange_rate                 AS `total_amt_base`
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
      a.debtor_code IS NOT NULL
      $whereClause
    ORDER BY
      CONCAT(a.debtor_code, ' - ', IFNULL(c.english_name, 'Unknown')) ASC,
      a.pl_date DESC
  ");

  $plHeaders = array();

  foreach ($results as $plHeader) {
    $customer = $plHeader["debtor"];

    if (!isset($plHeaders[$customer])) {
      $plHeaders[$customer] = array();
    }

    array_push($plHeaders[$customer], $plHeader);
  }

  $debtors = query("
    SELECT DISTINCT
      a.debtor_code                       AS `code`,
      IFNULL(b.english_name, 'Unknown')   AS `name`
    FROM
      `pl_header` AS a
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
      <div class="headline"><?php echo PACKING_LIST_CUSTOMER_TITLE; ?></div>
      <form>
        <table id="pl-input">
          <colgroup>
            <col style="width: 100px">
          </colgroup>
          <tr>
            <td><label for="pl-debtors">Customer:</label></td>
            <td>
              <select name="debtor_code[]" multiple>
                <?php
                  foreach ($debtors as $debtor) {
                    $code = $debtor["code"];
                    $name = $debtor["name"];
                    $selected = assigned($debtorCodes) && in_array($code, $debtorCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <?php
        if (count($plHeaders) > 0) {

          foreach ($plHeaders as $customer => $headers) {
            $totalQtySum = 0;
            $totalAmtSum = 0;
            $totalAmtSumBase = 0;

            echo "
              <div class=\"pl-customer\">
                <h4>$customer</h4>
                <table class=\"pl-results\">
                  <colgroup>
                    <col>
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
                      <th>Packing List No.</th>
                      <th class=\"number\">Total Qty</th>
                      <th class=\"number\">Discount</th>
                      <th class=\"number\">Currency</th>
                      <th class=\"number\">Total</th>
                      <th class=\"number\">$InBaseCurrCol</th>
                    </tr>
                  </thead>
                  <tbody>
            ";

            for ($i = 0; $i < count($headers); $i++) {
              $plHeader = $headers[$i];
              $date = $plHeader["date"];
              $debtor = $plHeader["debtor"];
              $plNo = $plHeader["pl_no"];
              $totalQty = $plHeader["qty"];
              $discount = $plHeader["discount"];
              $currency = $plHeader["currency"];
              $totalAmt = $plHeader["total_amt"];
              $totalAmtBase = $plHeader["total_amt_base"];

              $totalQtySum += $totalQty;
              $totalAmtSum += $totalAmt;
              $totalAmtSumBase += $totalAmtBase;

              echo "
                <tr>
                  <td title=\"$date\">$date</td>
                  <td title=\"$plNo\"><a class=\"link\" href=\"" . PACKING_LIST_URL . "?pl_no=$plNo\">$plNo</a></td>
                  <td title=\"$totalQty\" class=\"number\">" . number_format($totalQty) . "</td>
                  <td title=\"$discount\" class=\"number\">" . number_format($discount, 2) . "%</td>
                  <td title=\"$currency\" class=\"number\">$currency</td>
                  <td title=\"$totalAmt\" class=\"number\">" . number_format($totalAmt, 2) . "</td>
                  <td title=\"$totalAmtBase\" class=\"number\">" . number_format($totalAmtBase, 2) . "</td>
                </tr>
              ";
            }

            echo "
                  </tbody>
                  <tfoot>
                    <tr>
                      <th></th>
                      <th class=\"number\">Total:</th>
                      <th class=\"number\">" . number_format($totalQtySum) . "</th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th class=\"number\">" . number_format($totalAmtSumBase, 2) . "</th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            ";
          }
        } else {
          echo "<div class=\"pl-customer-no-results\">No results</div>";
        }
      ?>
    </div>
  </body>
</html>
