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
      a.invoice_no,
      a.invoice_date,
      a.maturity_date,
      a.debtor_code                               AS `debtor_code`,
      IFNULL(c.english_name, \"Unknown\")         AS `debtor_name`,
      a.amount,
      a.balance,
      a.remarks,
      a.username,
      b.id
    FROM
      `ar_audit_trail` AS a
    LEFT JOIN
      `ar_inv_header` AS b
    ON a.invoice_no=b.invoice_no
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

    $arrayPointer = &$dailyResults;

    if (!isset($arrayPointer[$date])) {
      $arrayPointer[$date] = array();
    }
    $arrayPointer = &$arrayPointer[$date];

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
            <th>From:</th>
            <th>To:</th>
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
              <input type="date" name="from" value="<?php echo $from; ?>" max="<?php echo date("Y-m-d"); ?>" />
              <span class="print-only"><?php echo assigned($from) ? $from : "ANY"; ?></span>
            </td>
            <td>
              <input type="date" name="to" value="<?php echo $to; ?>" max="<?php echo date("Y-m-d"); ?>" />
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
      <?php foreach ($dailyResults as $date => $results) : ?>
        <h4><?php echo $date; ?></h4>
        <?php if (count($results) > 0) : ?>
          <table id="inv-results" class="sortable">
            <colgroup>
              <col style="width: 80px">
              <col style="width: 100px">
              <col>
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 80px">
              <col>
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 80px">
              <col>
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th>Time</th>
                <th>Action</th>
                <th>Invoice No.</th>
                <th>Invoice Date</th>
                <th>Maturity Date</th>
                <th>Code</th>
                <th>Client</th>
                <th class="number">Invoice Amount</th>
                <th class="number">Balance</th>
                <th>Invoice Remarks</th>
                <th>User</th>
              </tr>
            </thead>
            <tbody>
              <?php
                for ($i = 0; $i < count($results); $i++) {
                  $result = $results[$i];
                  $id = $result["id"];
                  $time = $result["time"];
                  $action = $result["action"];
                  $invoiceNo = $result["invoice_no"];
                  $invoiceDate = $result["invoice_date"];
                  $maturityDate = $result["maturity_date"];
                  $debtorCode = $result["debtor_code"];
                  $debtorName = $result["debtor_name"];
                  $invoiceAmount = $result["amount"];
                  $balance = $result["balance"];
                  $remarks = $result["remarks"];
                  $user = $result["username"];

                  echo "
                    <tr>
                      <td title=\"$time\">$time</td>
                      <td title=\"$action\">$action</td>
                      <td title=\"$invoiceNo\"><a class=\"link\" href=\"" . AR_INVOICE_URL . "?id=$id\">$invoiceNo</a></td>
                      <td title=\"$invoiceDate\">$invoiceDate</td>
                      <td title=\"$maturityDate\">$maturityDate</td>
                      <td title=\"$debtorCode\">$debtorCode</td>
                      <td title=\"$debtorName\">$debtorName</td>
                      <td class=\"number\" title=\"$invoiceAmount\">". number_format($invoiceAmount, 2) . "</td>
                      <td class=\"number\" title=\"$balance\">". number_format($balance, 2) . "</td>
                      <td title=\"$remarks\">$remarks</td>
                      <td title=\"$user\">$user</td>
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
    </div>
  </body>
</html>
