<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $filterDebtorCodes = $_GET["filter_debtor_code"];

  $whereClause = "";

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code=\"$d\""; }, $filterDebtorCodes)) . ")";
  }

  $invoiceMonth = "DATE_FORMAT(a.invoice_date, \"%Y-%m\")";
  $invoiceYear = "DATE_FORMAT(a.invoice_date, \"%Y\")";
  $currentMonth = "DATE_FORMAT(NOW(), \"%Y-%m\")";
  $previousMonth = "DATE_FORMAT(NOW() - INTERVAL 1 MONTH, \"%Y-%m\")";
  $currentYear = "DATE_FORMAT(NOW(), \"%Y\")";
  $previousYear = "DATE_FORMAT(NOW() - INTERVAL 1 YEAR, \"%Y\")";
  $balance = "IFNULL(b.amount, 0) - IFNULL(c.settled_amount, 0) + IFNULL(d.credited_amount, 0)";

  $results = query("
    SELECT
      a.debtor_code                                                           AS `debtor_code`,
      IFNULL(e.english_name, \"Unknown\")                                     AS `debtor_name`,
      ROUND(SUM(IF($invoiceMonth=$currentMonth, IFNULL(b.amount, 0), 0)), 2)  AS `current_period_amount`,
      ROUND(SUM(IF($invoiceMonth=$previousMonth, IFNULL(b.amount, 0), 0)), 2) AS `previous_period_amount`,
      ROUND(SUM(IF($invoiceYear=$currentYear, IFNULL(b.amount, 0), 0)), 2)    AS `current_ytd_amount`,
      ROUND(SUM(IF($invoiceYear=$previousYear, IFNULL(b.amount, 0), 0)), 2)   AS `previous_ytd_amount`,
      ROUND(SUM(IF(a.status=\"SAVED\", $balance, 0)), 2)                      AS `balance`
    FROM
      `ar_inv_header` AS a
    LEFT JOIN
      (SELECT
        COUNT(*)                                  AS `count`,
        invoice_no                                AS `invoice_no`,
        SUM(amount)                               AS `amount`
      FROM
        `ar_inv_item`
      GROUP BY
        invoice_no) AS b
    ON a.invoice_no=b.invoice_no
    LEFT JOIN
      (SELECT
        invoice_no                                  AS `invoice_no`,
        SUM(amount)                                 AS `settled_amount`
      FROM
        `ar_settlement`
      GROUP BY
        invoice_no) AS c
    ON a.invoice_no=c.invoice_no
    LEFT JOIN
      (SELECT
        invoice_no    AS `invoice_no`,
        SUM(amount)   AS `credited_amount`
      FROM
        `ar_credit_note`
      GROUP BY
        invoice_no) AS d
    ON a.invoice_no=d.invoice_no
    LEFT JOIN
      `debtor` AS e
    ON a.debtor_code=e.code
    WHERE
      a.invoice_no IS NOT NULL
      $whereClause
    GROUP BY
      a.debtor_code
    ORDER BY
      a.debtor_code ASC
    ");

    $debtors = query("
      SELECT DISTINCT
        a.debtor_code                         AS `code`,
        IFNULL(b.english_name, \"Unknown\")   AS `name`
      FROM
        `ar_inv_header` AS a
      LEFT JOIN
        `debtor` AS b
      ON a.debtor_code=b.code
      ORDER BY
        code ASC
    ");
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once SYSTEM_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <?php include_once SYSTEM_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper landscape">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo AR_REPORT_CLIENT_STATISTICS_TITLE; ?></div>
      <form>
        <table id="inv-input">
          <tr>
            <th>Client:</th>
          </tr>
          <tr>
            <td>
              <select name="filter_debtor_code[]" multiple class="web-only">
                <?php
                  foreach ($debtors as $debtor) {
                    $code = $debtor["code"];
                    $name = $debtor["name"];
                    $selected = assigned($filterDebtorCodes) && in_array($code, $filterDebtorCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
              <span class="print-only">
                <?php
                  echo assigned($filterDebtorCodes) ? join(", ", array_map(function ($d) {
                    return $d["code"] . " - " . $d["name"];
                  }, array_filter($debtors, function ($i) use ($filterDebtorCodes) {
                    return in_array($i["code"], $filterDebtorCodes);
                  }))) : "ALL";
                ?>
              </span>
            </td>
            <td><button type="submit" class="web-only">Go</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($results) > 0) : ?>
        <table id="inv-results" class="sortable">
          <colgroup>
            <col style="width: 80px">
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
              <th>Code</th>
              <th>Client</th>
              <th class="number">Current Period Amount</th>
              <th class="number">Previous Period Amount</th>
              <th class="number">Current YTD Amount</th>
              <th class="number">Previous Year Amount</th>
              <th class="number">Total Balance</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $totalCurrPeriodAmount = 0;
              $totalPrevPeriodAmount = 0;
              $totalCurrYTDAmount = 0;
              $totalPrevYTDAmount = 0;
              $totalBalance = 0;

              for ($i = 0; $i < count($results); $i++) {
                $result = $results[$i];
                $debtorCode = $result["debtor_code"];
                $debtorName = $result["debtor_name"];
                $currPeriodAmount = $result["current_period_amount"];
                $prevPeriodAmount = $result["previous_period_amount"];
                $currYTDAmount = $result["current_ytd_amount"];
                $prevYTDAmount = $result["previous_ytd_amount"];
                $balance = $result["balance"];

                $totalCurrPeriodAmount += $currPeriodAmount;
                $totalPrevPeriodAmount += $prevPeriodAmount;
                $totalCurrYTDAmount += $currYTDAmount;
                $totalPrevYTDAmount += $prevYTDAmount;
                $totalBalance += $balance;

                echo "
                  <tr>
                    <td title=\"$debtorCode\">$debtorCode</td>
                    <td title=\"$debtorName\">$debtorName</td>
                    <td class=\"number\" title=\"$currPeriodAmount\">". number_format($currPeriodAmount, 2) . "</td>
                    <td class=\"number\" title=\"$prevPeriodAmount\">". number_format($prevPeriodAmount, 2) . "</td>
                    <td class=\"number\" title=\"$currYTDAmount\">". number_format($currYTDAmount, 2) . "</td>
                    <td class=\"number\" title=\"$prevYTDAmount\">". number_format($prevYTDAmount, 2) . "</td>
                    <td class=\"number\" title=\"$balance\">". number_format($balance, 2) . "</td>
                  </tr>
                ";
              }
            ?>
          </tbody>
          <tbody>
            <tr>
              <th></th>
              <th class="number">Total:</th>
              <th class="number"><?php echo number_format($totalCurrPeriodAmount, 2); ?></th>
              <th class="number"><?php echo number_format($totalPrevPeriodAmount, 2); ?></th>
              <th class="number"><?php echo number_format($totalCurrYTDAmount, 2); ?></th>
              <th class="number"><?php echo number_format($totalPrevYTDAmount, 2); ?></th>
              <th class="number"><?php echo number_format($totalBalance, 2); ?></th>
            </tr>
          </tbody>
        </table>
      <?php else : ?>
        <div class="inv-client-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
