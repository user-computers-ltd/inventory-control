<?php
  define("SYSTEM_PATH", "../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $actions = array_map(function ($a) { return $a["action"]; }, query("
    SELECT DISTINCT
      action
    FROM
      `ar_audit_trail`
    ORDER BY
      action DESC
  "));

  $filterActions = $_GET["filter_action"];
  $filterDebtorCodes = $_GET["filter_debtor_code"];

  $whereClause = "";

  if (assigned($filterActions) && count($filterActions) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "a.action=\"$d\""; }, $filterActions)) . ")";
  }

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND DATE_FORMAT(a.datetime, \"%d-%m-%Y\") >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND DATE_FORMAT(a.datetime, \"%d-%m-%Y\") <= \"$from\"";
  }

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code=\"$d\""; }, $filterDebtorCodes)) . ")";
  }

  $results = query("
    SELECT
      a.action,
      a.datetime,
      DATE_FORMAT(a.datetime, \"%H:%i:%s\") AS `time`,
      a.invoice_no                                AS  `voucher_no`,
      a.invoice_date,
      a.maturity_date,
      a.debtor_code                               AS `debtor_code`,
      IFNULL(c.english_name, \"Unknown\")         AS `debtor_name`,
      a.amount,
      a.balance,
      a.remarks,
      a.username,
      CASE
        WHEN a.action LIKE \"%_payment\" THEN b2.id
        WHEN a.action LIKE \"%_credit_note\" THEN b3.id
        ELSE b.id
      END                                         AS  `id`
    FROM
      `ar_audit_trail` AS a
    LEFT JOIN
      `ar_inv_header` AS b
    ON a.invoice_no=b.invoice_no
    LEFT JOIN
      `ar_payment` AS b2
    ON a.invoice_no=b2.payment_no
    LEFT JOIN
      `ar_credit_note` AS b3
    ON a.invoice_no=b3.credit_note_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.action IS NOT NULL
      $whereClause
  ");

  $dailyResults = array();

  foreach ($results as $result) {
    $date = substr($result["datetime"], 0, strpos($result["datetime"], " "));
    $action = $result["action"];

    $arrayPointer = &$dailyResults;

    if (!isset($arrayPointer[$date])) {
      $arrayPointer[$date] = array();
    }
    $arrayPointer = &$arrayPointer[$date];

    if (!isset($arrayPointer[$action])) {
      $arrayPointer[$action] = array();
    }
    $arrayPointer = &$arrayPointer[$action];

    array_push($arrayPointer, $result);
  }

  $debtors = query("
    SELECT DISTINCT
      a.debtor_code                         AS `code`,
      IFNULL(b.english_name, \"Unknown\")   AS `name`
    FROM
      `ar_audit_trail` AS a
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
      <div class="headline"><?php echo AR_REPORT_DAILY_REPORT_TITLE; ?></div>
      <form>
        <table id="inv-input">
          <tr>
            <th>Action:</th>
            <th>From Input Date:</th>
            <th>To Input Date:</th>
          </tr>
          <tr>
            <td>
              <select name="filter_action[]" multiple class="web-only">
                <?php
                  foreach ($actions as $action) {
                    $selected = assigned($filterActions) && in_array($action, $filterActions) ? "selected" : "";
                    echo "<option value=\"$action\" $selected>$action</option>";
                  }
                ?>
              </select>
              <span class="print-only">
                <?php
                  echo assigned($filterActions) ? join(", ", $filterActions) : "ALL";
                ?>
              </span>
            </td>
            <td>
              <input type="date" class="web-only" name="from" value="<?php echo $from; ?>" max="<?php echo date("Y-m-d"); ?>" />
              <span class="print-only"><?php echo assigned($from) ? $from : "ANY"; ?></span>
            </td>
            <td>
              <input type="date" class="web-only" name="to" value="<?php echo $to; ?>" max="<?php echo date("Y-m-d"); ?>" />
              <span class="print-only"><?php echo assigned($to) ? $to : "ANY"; ?></span>
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
      <?php foreach ($dailyResults as $date => $actionResults) : ?>
        <h4><?php echo $date; ?></h4>
        <?php foreach ($actionResults as $action => $results) : ?>
          <h4><?php echo $action; ?></h4>
          <?php
            $voucher = "";

            if (strpos($action, "_invoice") !== false) { $voucher = "Invoice"; }
            else if (strpos($action, "_settlement") !== false) { $voucher = "Settlement"; }
            else if (strpos($action, "_payment") !== false) { $voucher = "Payment"; }
            else if (strpos($action, "_credit_note") !== false) { $voucher = "Credit Note"; }
          ?>
          <?php if (count($results) > 0) : ?>
            <table id="inv-results" class="sortable">
              <colgroup>
                <col style="width: 80px">
                <col>
                <col style="width: 80px">
                <col>
                <col style="width: 80px">
                <col style="width: 80px">
                <col>
                <col style="width: 80px">
                <col style="width: 80px">
                <col>
              </colgroup>
              <thead>
                <tr></tr>
                <tr>
                  <th><?php echo $voucher; ?> Date</th>
                  <th><?php echo $voucher; ?> No.</th>
                  <th>Code</th>
                  <th>Client</th>
                  <th class="number"><?php echo $voucher; ?> Amount</th>
                  <th class="number">Balance</th>
                  <th>Description</th>
                  <th>User</th>
                  <th>Time</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  for ($i = 0; $i < count($results); $i++) {
                    $result = $results[$i];
                    $id = $result["id"];
                    $time = $result["time"];
                    $voucherNo = $result["voucher_no"];
                    $invoiceDate = $result["invoice_date"];
                    $maturityDate = $result["maturity_date"];
                    $debtorCode = $result["debtor_code"];
                    $debtorName = $result["debtor_name"];
                    $invoiceAmount = $result["amount"];
                    $balance = $result["balance"];
                    $remarks = $result["remarks"];
                    $user = $result["username"];

                    $link = $voucherNo;
                    if (assigned($id)) {
                      if ($voucher === "Invoice") {
                        $link = "<a class=\"link\" href=\"" . AR_INVOICE_URL . "?id=$id\">$voucherNo</a>";
                      } else if ($voucher === "Settlement") {
                        $link = "<a class=\"link\" href=\"" . AR_INVOICE_SETTLEMENT_URL . "?id=$id\">$voucherNo</a>";
                      } else if ($voucher === "Payment") {
                        $link = "<a class=\"link\" href=\"" . AR_PAYMENT_URL . "?id=$id\">$voucherNo</a>";
                      } else if ($voucher === "Credit Note") {
                        $link = "<a class=\"link\" href=\"" . AR_CREDIT_NOTE_URL . "?id=$id\">$voucherNo</a>";
                      }
                    }

                    echo "
                      <tr>
                        <td title=\"$invoiceDate\">$invoiceDate</td>
                        <td title=\"$voucherNo\">$link</td>
                        <td title=\"$debtorCode\">$debtorCode</td>
                        <td title=\"$debtorName\">$debtorName</td>
                        <td class=\"number\" title=\"$invoiceAmount\">". number_format($invoiceAmount, 2) . "</td>
                        <td class=\"number\" title=\"$balance\">". number_format($balance, 2) . "</td>
                        <td title=\"$remarks\">$remarks</td>
                        <td title=\"$user\">$user</td>
                        <td title=\"$time\">$time</td>
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
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                </tr>
              </tbody>
            </table>
          <?php else : ?>
            <div class="inv-client-no-results">No results</div>
          <?php endif ?>
        <?php endforeach ?>
      <?php endforeach ?>
    </div>
  </body>
</html>
