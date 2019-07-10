<?php
  define("SYSTEM_PATH", "../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $periods = array_map(function ($p) { return $p["period"]; }, query("
    SELECT DISTINCT
      DATE_FORMAT(invoice_date, \"%Y-%m\") AS `period`
    FROM
      `ar_inv_header`
    ORDER BY
      period DESC
  "));

  $period = assigned($_GET["period"]) ? $_GET["period"] : (count($periods) > 0 ? $periods[0] : "");
  $filterDebtorCodes = $_GET["filter_debtor_code"];

  $whereClause = "";

  if (assigned($period)) {
    $whereClause = $whereClause . "
      AND DATE_FORMAT(a.invoice_date, \"%Y-%m\")=\"$period\"";
  }

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code=\"$d\""; }, $filterDebtorCodes)) . ")";
  }

  $results = query("
    SELECT
      a.id                                                                                        AS `id`,
      DATE_FORMAT(a.invoice_date, \"%d-%m-%Y\")                                                   AS `date`,
      a.invoice_no                                                                                AS `invoice_no`,
      a.debtor_code                                                                               AS `debtor_code`,
      IFNULL(e.english_name, \"Unknown\")                                                         AS `debtor_name`,
      ROUND(IFNULL(b.amount, 0), 2)                                                               AS `amount`,
      ROUND(IFNULL(c.credit_amount, 0) - IFNULL(d.credited_amount, 0), 2)                         AS `dr_cr_amount`,
      ROUND(IFNULL(c.payment_amount, 0), 2)                                                       AS `paid_amount`,
      ROUND(IFNULL(b.amount, 0) - IFNULL(c.settled_amount, 0) + IFNULL(d.credited_amount, 0), 2)  AS `balance`
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
        SUM(IF(credit_note_no!=\"\", amount, 0))    AS `credit_amount`,
        SUM(IF(payment_no!=\"\", amount, 0))        AS `payment_amount`,
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
      a.status=\"SAVED\" OR a.status=\"SETTLED\"
      $whereClause
    ORDER BY
      DATE_FORMAT(a.invoice_date, \"%d-%m-%Y\") DESC,
      a.invoice_no ASC
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
      WHERE
        a.status=\"SAVED\" OR a.status=\"SETTLED\"
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
      <div class="headline"><?php echo AR_REPORT_MONTHLY_SALES_TITLE; ?></div>
      <form>
        <table id="inv-input">
          <tr>
            <th>Period:</th>
            <th>Client:</th>
          </tr>
          <tr>
            <td>
              <select name="period" class="web-only" onchange="this.form.submit()">
                <?php
                  foreach ($periods as $p) {
                    $selected = $period === $p ? "selected" : "";
                    echo "<option value=\"$p\" $selected>$p</option>";
                  }
                ?>
              </select>
              <span class="print-only"><?php echo $period; ?></span>
            </td>
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
            <col>
            <col style="width: 80px">
            <col style="width: 80px">
            <col style="width: 80px">
            <col style="width: 80px">
          </colgroup>
          <thead>
            <tr></tr>
            <tr>
              <th>Date</th>
              <th>Invoice No.</th>
              <th>Code</th>
              <th>Client</th>
              <th class="number">Invoice Amount</th>
              <th class="number">DR/CR Amount</th>
              <th class="number">Paid</th>
              <th class="number">Balance</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $totalInvAmount = 0;
              $totalDRCRAmount = 0;
              $totalPaidAmount = 0;
              $totalBalance = 0;

              for ($i = 0; $i < count($results); $i++) {
                $result = $results[$i];
                $id = $result["id"];
                $date = $result["date"];
                $invoiceNo = $result["invoice_no"];
                $debtorCode = $result["debtor_code"];
                $debtorName = $result["debtor_name"];
                $invoiceAmount = $result["amount"];
                $DRCRAmount = $result["dr_cr_amount"];
                $paidAmount = $result["paid_amount"];
                $balance = $result["balance"];

                $totalInvAmount += $invoiceAmount;
                $totalDRCRAmount += $DRCRAmount;
                $totalPaidAmount += $paidAmount;
                $totalBalance += $balance;

                echo "
                  <tr>
                    <td title=\"$date\">$date</td>
                    <td title=\"$invoiceNo\"><a class=\"link\" href=\"" . AR_INVOICE_URL . "?id=$id\">$invoiceNo</a></td>
                    <td title=\"$debtorCode\">$debtorCode</td>
                    <td title=\"$debtorName\">$debtorName</td>
                    <td class=\"number\" title=\"$invoiceAmount\">". number_format($invoiceAmount, 2) . "</td>
                    <td class=\"number\" title=\"$DRCRAmount\">". number_format($DRCRAmount, 2) . "</td>
                    <td class=\"number\" title=\"$paidAmount\">". number_format($paidAmount, 2) . "</td>
                    <td class=\"number\" title=\"$balance\">". number_format($balance, 2) . "</td>
                  </tr>
                ";
              }
            ?>
          </tbody>
          <tbody>
            <tr>
              <th></th>
              <th></th>
              <th></th>
              <th class="number">Total:</th>
              <th class="number"><?php echo number_format($totalInvAmount, 2); ?></th>
              <th class="number"><?php echo number_format($totalDRCRAmount, 2); ?></th>
              <th class="number"><?php echo number_format($totalPaidAmount, 2); ?></th>
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
