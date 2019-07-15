<?php
  define("SYSTEM_PATH", "../../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $filterDebtorCodes = $_GET["filter_debtor_code"];

  $whereClause = "";

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code=\"$d\""; }, $filterDebtorCodes)) . ")";
  }

  $results = query("
    SELECT
      a.id                                                                                        AS `id`,
      DATE_FORMAT(a.invoice_date, \"%d-%m-%Y\")                                                   AS `date`,
      DATE_FORMAT(a.maturity_date, \"%d-%m-%Y\")                                                  AS `maturity_date`,
      IF(a.maturity_date < NOW(), \"overdue\", \"\")                                              AS `due_status`,
      a.invoice_no                                                                                AS `invoice_no`,
      a.debtor_code                                                                               AS `debtor_code`,
      IFNULL(e.english_name, \"Unknown\")                                                         AS `debtor_name`,
      ROUND(IFNULL(b.amount, 0), 2)                                                               AS `amount`,
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
      a.status=\"SAVED\" AND
      ROUND(IFNULL(b.amount, 0) - IFNULL(c.settled_amount, 0) + IFNULL(d.credited_amount, 0), 2) !== 0
      $whereClause
    ORDER BY
      a.debtor_code ASC,
      DATE_FORMAT(a.invoice_date, \"%d-%m-%Y\") DESC
  ");

  $debtorResults = array();

  foreach ($results as $result) {
    $debtor = $result["debtor_code"] . " - " . $result["debtor_name"];

    $arrayPointer = &$debtorResults;

    if (!isset($arrayPointer[$debtor])) {
      $arrayPointer[$debtor] = array();
    }
    $arrayPointer = &$arrayPointer[$debtor];

    array_push($arrayPointer, $result);
  }

  $debtors = query("
    SELECT DISTINCT
      a.debtor_code                         AS `code`,
      IFNULL(b.english_name, \"Unknown\")   AS `name`
    FROM
      `ar_inv_header` AS a
    LEFT JOIN
      `debtor` AS b
    ON a.debtor_code=b.code
    LEFT JOIN
      (SELECT
        COUNT(*)                                  AS `count`,
        invoice_no                                AS `invoice_no`,
        SUM(amount)                               AS `amount`
      FROM
        `ar_inv_item`
      GROUP BY
        invoice_no) AS c
    ON a.invoice_no=c.invoice_no
    LEFT JOIN
      (SELECT
        invoice_no                                  AS `invoice_no`,
        SUM(amount)                                 AS `settled_amount`
      FROM
        `ar_settlement`
      GROUP BY
        invoice_no) AS d
    ON a.invoice_no=d.invoice_no
    LEFT JOIN
      (SELECT
        invoice_no    AS `invoice_no`,
        SUM(amount)   AS `credited_amount`
      FROM
        `ar_credit_note`
      GROUP BY
        invoice_no) AS e
    ON a.invoice_no=e.invoice_no
    WHERE
      a.status=\"SAVED\" AND
      ROUND(IFNULL(c.amount, 0) - IFNULL(d.settled_amount, 0) + IFNULL(e.credited_amount, 0), 2) !== 0
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
      <div class="headline"><?php echo AR_REPORT_CLIENT_OUTSTANDING_DETAIL_TITLE; ?></div>
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
      <?php if (count($debtorResults) > 0) : ?>
        <?php $grandInvAmount = 0; ?>
        <?php $grandTotalBalance = 0; ?>
        <?php foreach ($debtorResults as $debtorName => $results) : ?>
          <h4><?php echo $debtorName; ?></h4>
          <table id="inv-results" class="sortable">
            <colgroup>
              <col style="width: 80px">
              <col style="width: 80px">
              <col>
              <col style="width: 80px">
              <col style="width: 80px">
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th>Maturity Date</th>
                <th>Invoice Date</th>
                <th>Invoice No</th>
                <th class="number">Amount</th>
                <th class="number">Balance</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalInvAmount = 0;
                $totalBalance = 0;

                for ($i = 0; $i < count($results); $i++) {
                  $result = $results[$i];
                  $id = $result["id"];
                  $date = $result["date"];
                  $maturityDate = $result["maturity_date"];
                  $dueStatus = $result["due_status"];
                  $invoiceNo = $result["invoice_no"];
                  $debtorCode = $result["debtor_code"];
                  $debtorName = $result["debtor_name"];
                  $invoiceAmount = $result["amount"];
                  $balance = $result["balance"];

                  $totalInvAmount += $invoiceAmount;
                  $totalBalance += $balance;
                  $grandInvAmount += $invoiceAmount;
                  $grandTotalBalance += $balance;

                  echo "
                    <tr>
                      <td title=\"$maturityDate\" class=\"$dueStatus\">$maturityDate</td>
                      <td title=\"$date\">$date</td>
                      <td title=\"$invoiceNo\"><a class=\"link\" href=\"" . AR_INVOICE_URL . "?id=$id\">$invoiceNo</a></td>
                      <td class=\"number\" title=\"$invoiceAmount\">". number_format($invoiceAmount, 2) . "</td>
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
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalInvAmount, 2); ?></th>
                <th class="number"><?php echo number_format($totalBalance, 2); ?></th>
              </tr>
            </tbody>
          </table>
        <?php endforeach ?>
        <table id="inv-results">
          <colgroup>
            <col style="width: 80px">
            <col style="width: 80px">
            <col>
            <col style="width: 80px">
            <col style="width: 80px">
          </colgroup>
          <tbody>
            <tr>
              <th></th>
              <th></th>
              <th class="number">Grand Total:</th>
              <th class="number"><?php echo number_format($grandInvAmount, 2); ?></th>
              <th class="number"><?php echo number_format($grandTotalBalance, 2); ?></th>
            </tr>
          </tbody>
        </table>
      <?php else : ?>
        <div class="inv-client-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
