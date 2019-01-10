<?php
  define("SYSTEM_PATH", "../../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $debtorCodes = $_GET["debtor_code"];
  $showMode = assigned($_GET["show_mode"]) ? $_GET["show_mode"] : "outstanding_only";

  $whereClause = "";

  if (assigned($debtorCodes) && count($debtorCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code=\"$d\""; }, $debtorCodes)) . ")";
  }

  if ($showMode == "outstanding_only") {
    $whereClause = $whereClause . "
      AND IFNULL(b.total_qty_outstanding, 0) > 0";
  }

  $results = query("
    SELECT
      CONCAT(a.debtor_code, ' - ', IFNULL(c.english_name, 'Unknown'))                     AS `debtor`,
      DATE_FORMAT(a.so_date, '%d-%m-%Y')                                                  AS `date`,
      a.id                                                                                AS `so_id`,
      a.so_no                                                                             AS `so_no`,
      IFNULL(b.total_qty, 0)                                                              AS `qty`,
      IFNULL(b.total_qty_outstanding, 0)                                                  AS `qty_outstanding`,
      a.discount                                                                          AS `discount`,
      a.currency_code                                                                     AS `currency`,
      IFNULL(b.total_amt_outstanding, 0) * (100 - a.discount) / 100                       AS `amt_outstanding`,
      IFNULL(b.total_amt_outstanding, 0) * (100 - a.discount) / 100 * a.exchange_rate     AS `amt_outstanding_base`
    FROM
      `so_header` AS a
    LEFT JOIN
      (SELECT
        so_no                         AS `so_no`,
        SUM(qty)                      AS `total_qty`,
        SUM(qty_outstanding)          AS `total_qty_outstanding`,
        SUM(qty_outstanding * price)  AS `total_amt_outstanding`
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
    $client = $soHeader["debtor"];

    if (!isset($soHeaders[$client])) {
      $soHeaders[$client] = array();
    }

    array_push($soHeaders[$client], $soHeader);
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
    WHERE
      a.status=\"POSTED\"
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
      <form>
        <table id="so-input" class="web-only">
          <tr>
            <th>Client:</th>
          </tr>
          <tr>
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
          <tr>
            <th>
              <input
                id="input-outstanding-only"
                type="checkbox"
                onchange="onOutstandingOnlyChanged(event)"
                <?php echo $showMode == "outstanding_only" ? "checked" : "" ?>
              />
              <label for="input-outstanding-only">Outstanding only</label>
              <input
                id="input-show-mode"
                type="hidden"
                name="show_mode"
                value="<?php echo $showMode; ?>"
              />
            </th>
          </tr>
        </table>
      </form>
      <?php if (count($soHeaders) > 0) : ?>
        <?php foreach ($soHeaders as $client => &$headers) : ?>
          <div class="so-client">
            <h4><?php echo $client; ?></h4>
            <table class="so-results">
              <colgroup>
                <col style="width: 80px">
                <col>
                <col style="width: 80px">
                <col style="width: 80px">
                <col style="width: 60px">
                <col style="width: 80px">
                <col style="width: 80px">
                <col style="width: 40px">
              </colgroup>
              <thead>
                <tr></tr>
                <tr>
                  <th>Date</th>
                  <th>Order No.</th>
                  <th class="number">Total Qty</th>
                  <th class="number">Outstanding Qty</th>
                  <th class="number">Currency</th>
                  <th class="number">Outstanding Amt</th>
                  <th class="number">$InBaseCurrency</th>
                  <th class="number">Discount</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $totalQty = 0;
                  $totalOutstanding = 0;
                  $totalAmtBase = 0;

                  for ($i = 0; $i < count($headers); $i++) {
                    $soHeader = $headers[$i];
                    $date = $soHeader["date"];
                    $soId = $soHeader["so_id"];
                    $soNo = $soHeader["so_no"];
                    $qty = $soHeader["qty"];
                    $outstandingQty = $soHeader["qty_outstanding"];
                    $discount = $soHeader["discount"];
                    $currency = $soHeader["currency"];
                    $outstandingAmt = $soHeader["amt_outstanding"];
                    $outstandingAmtBase = $soHeader["amt_outstanding_base"];

                    $totalQty += $qty;
                    $totalOutstanding += $outstandingQty;
                    $totalAmtBase += $outstandingAmtBase;

                    echo "
                      <tr>
                        <td title=\"$date\">$date</td>
                        <td title=\"$soNo\">
                          <a class=\"link\" href=\"" . SALES_ORDER_INTERNAL_PRINTOUT_URL . "?id[]=$soId\">$soNo</a>
                        </td>
                        <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                        <td title=\"$outstandingQty\" class=\"number\">" . number_format($outstandingQty) . "</td>
                        <td title=\"$currency\" class=\"number\">$currency</td>
                        <td title=\"$outstandingAmt\" class=\"number\">" . number_format($outstandingAmt, 2) . "</td>
                        <td title=\"$outstandingAmtBase\" class=\"number\">" . number_format($outstandingAmtBase, 2) . "</td>
                        <td title=\"$discount\" class=\"number\">" . number_format($discount, 2) . "%</td>
                      </tr>
                    ";
                  }
                ?>
                <tr>
                  <th></th>
                  <th class="number">Total:</th>
                  <th class="number"><?php echo number_format($totalQty); ?></th>
                  <th class="number"><?php echo number_format($totalOutstanding); ?></th>
                  <th></th>
                  <th></th>
                  <th class="number"><?php echo number_format($totalAmtBase, 2); ?></th>
                  <th></th>
                </tr>
              </tbody>
            </table>
          </div>
        <?php endforeach ?>
      <?php else : ?>
        <div class="so-client-no-results">No results</div>
      <?php endif ?>
    </div>
    <script>
      function onOutstandingOnlyChanged(event) {
        var showMode = event.target.checked ? "outstanding_only" : "show_all";
        document.querySelector("#input-show-mode").value = showMode;
        event.target.form.submit();
      }
    </script>
  </body>
</html>
