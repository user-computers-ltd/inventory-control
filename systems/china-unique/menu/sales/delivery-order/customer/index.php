<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $debtorCodes = $_GET["debtor_code"];

  $whereClause = "";

  if (assigned($debtorCodes) && count($debtorCodes) > 0) {
    $whereClause = "
      AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code=\"$d\""; }, $debtorCodes)) . ")";
  }

  $results = query("
    SELECT
      a.id                                                                                AS `do_id`,
      CONCAT(a.debtor_code, ' - ', IFNULL(c.english_name, 'Unknown'))                     AS `debtor`,
      DATE_FORMAT(a.do_date, '%d-%m-%Y')                                                  AS `date`,
      a.do_no                                                                             AS `do_no`,
      IFNULL(b.total_qty, 0)                                                              AS `qty`,
      a.discount                                                                          AS `discount`,
      a.currency_code                                                                     AS `currency`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100                                   AS `total_amt`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100 * a.exchange_rate                 AS `total_amt_base`
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
      a.debtor_code IS NOT NULL
      $whereClause
    ORDER BY
      CONCAT(a.debtor_code, ' - ', IFNULL(c.english_name, 'Unknown')) ASC,
      a.do_date DESC
  ");

  $doHeaders = array();

  foreach ($results as $doHeader) {
    $client = $doHeader["debtor"];

    if (!isset($doHeaders[$client])) {
      $doHeaders[$client] = array();
    }

    array_push($doHeaders[$client], $doHeader);
  }

  $debtors = query("
    SELECT DISTINCT
      a.debtor_code                       AS `code`,
      IFNULL(b.english_name, 'Unknown')   AS `name`
    FROM
      `sdo_header` AS a
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
      <div class="headline"><?php echo SALES_DELIVERY_ORDER_CUSTOMER_TITLE; ?></div>
      <form>
        <table id="do-input" class="web-only">
          <colgroup>
            <col style="width: 100px">
          </colgroup>
          <tr>
            <td><label for="do-debtors">Client:</label></td>
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
        if (count($doHeaders) > 0) {

          foreach ($doHeaders as $client => $headers) {
            $totalQtySum = 0;
            $totalAmtSum = 0;
            $totalAmtSumBase = 0;

            echo "
              <div class=\"do-client\">
                <h4>$client</h4>
                <table class=\"do-results\">
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
                      <th class=\"number\">$InBaseCurrency</th>
                    </tr>
                  </thead>
                  <tbody>
            ";

            for ($i = 0; $i < count($headers); $i++) {
              $doHeader = $headers[$i];
              $doId = $doHeader["do_id"];
              $date = $doHeader["date"];
              $debtor = $doHeader["debtor"];
              $doNo = $doHeader["do_no"];
              $totalQty = $doHeader["qty"];
              $discount = $doHeader["discount"];
              $currency = $doHeader["currency"];
              $totalAmt = $doHeader["total_amt"];
              $totalAmtBase = $doHeader["total_amt_base"];

              $totalQtySum += $totalQty;
              $totalAmtSum += $totalAmt;
              $totalAmtSumBase += $totalAmtBase;

              echo "
                <tr>
                  <td title=\"$date\">$date</td>
                  <td title=\"$doNo\"><a class=\"link\" href=\"" . SALES_DELIVERY_ORDER_URL . "?id=$doId\">$doNo</a></td>
                  <td title=\"$totalQty\" class=\"number\">" . number_format($totalQty) . "</td>
                  <td title=\"$discount\" class=\"number\">" . number_format($discount, 2) . "%</td>
                  <td title=\"$currency\" class=\"number\">$currency</td>
                  <td title=\"$totalAmt\" class=\"number\">" . number_format($totalAmt, 2) . "</td>
                  <td title=\"$totalAmtBase\" class=\"number\">" . number_format($totalAmtBase, 2) . "</td>
                </tr>
              ";
            }

            echo "
                    <tr>
                      <th></th>
                      <th class=\"number\">Total:</th>
                      <th class=\"number\">" . number_format($totalQtySum) . "</th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th class=\"number\">" . number_format($totalAmtSumBase, 2) . "</th>
                    </tr>
                    </tbody>
                </table>
              </div>
            ";
          }
        } else {
          echo "<div class=\"do-client-no-results\">No results</div>";
        }
      ?>
    </div>
  </body>
</html>
