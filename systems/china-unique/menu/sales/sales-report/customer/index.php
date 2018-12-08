<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(in " . COMPANY_CURRENCY . ")";

  $debtorCodes = $_GET["debtor_code"];

  $whereClause = "";

  if (assigned($debtorCodes) && count($debtorCodes) > 0) {
    $whereClause = "
      AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code=\"$d\""; }, $debtorCodes)) . ")";
  }

  $soHeaders = query("
    SELECT
      c.id                                                                                    AS `id`,
      CONCAT(a.debtor_code, ' - ', IFNULL(c.english_name, 'Unknown'))                         AS `debtor`,
      SUM(IFNULL(b.total_qty, 0))                                                             AS `qty`,
      SUM(IFNULL(b.total_qty_outstanding, 0))                                                 AS `outstanding_qty`,
      SUM(IFNULL(b.total_outstanding_amt, 0) * (100 - a.discount) / 100 * a.exchange_rate)    AS `outstanding_amt_base`
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
    GROUP BY
      a.debtor_code
    ORDER BY
      a.debtor_code ASC
  ");

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
      <div class="headline"><?php echo SALES_REPORT_CUSTOMER_SUMMARY_TITLE; ?></div>
      <form>
        <table id="so-input">
          <tr>
            <th>Customer:</th>
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
        </table>
      </form>
      <?php if (count($soHeaders) > 0): ?>
        <table class="so-results">
          <colgroup>
            <col>
            <col style="width: 100px">
            <col style="width: 100px">
            <col style="width: 100px">
          </colgroup>
          <thead>
            <tr></tr>
            <tr>
              <th>Client</th>
              <th class="number">Total Qty</th>
              <th class="number">Outstanding Qty</th>
              <th class="number"><?php echo $InBaseCurrency; ?></th>
            </tr>
          </thead>
          <tbody>
          <?php
            $totalQty = 0;
            $totalOutstanding = 0;
            $totalAmtBase = 0;

            for ($i = 0; $i < count($soHeaders); $i++) {
              $soHeader = $soHeaders[$i];
              $id = $soHeader["id"];
              $debtor = $soHeader["debtor"];
              $qty = $soHeader["qty"];
              $outstandingQty = $soHeader["outstanding_qty"];
              $outstandingAmtBase = $soHeader["outstanding_amt_base"];

              $totalQty += $qty;
              $totalOutstanding += $outstandingQty;
              $totalAmtBase += $outstandingAmtBase;

              echo "
                <tr>
                  <td title=\"$debtor\"><a class=\"link\" href=\"" . SALES_REPORT_CUSTOMER_DETAIL_URL . "?id[]=$id\">$debtor</a></td>
                  <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                  <td title=\"$outstandingQty\" class=\"number\">" . number_format($outstandingQty) . "</td>
                  <td title=\"$outstandingAmtBase\" class=\"number\">" . number_format($outstandingAmtBase, 2) . "</td>
                </tr>
              ";
            }
          ?>
          </tbody>
          <tfoot>
            <tr>
              <th class="number">Total:</th>
              <th class="number"><?php echo number_format($totalQty); ?></th>
              <th class="number"><?php echo number_format($totalOutstanding); ?></th>
              <th class="number"><?php echo number_format($totalAmtBase, 2); ?></th>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php else: ?>
      <div class="so-customer-no-results">No results</div>";
    <?php endif ?>
  </body>
</html>
