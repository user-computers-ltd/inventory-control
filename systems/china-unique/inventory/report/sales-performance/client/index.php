<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $debtors = query("
    SELECT DISTINCT
      a.client_code                         AS `code`,
      IFNULL(b.english_name, \"Unknown\")   AS `name`
    FROM
      `transaction` AS a
    LEFT JOIN
      `debtor` AS b
    ON a.client_code=b.code
    WHERE
      a.transaction_code REGEXP \"S1|S2|R3\"
    ORDER BY
      code ASC
  ");

  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $filterDebtorCodes = $_GET["filter_debtor_code"];

  $whereClause = "";

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND a.transaction_date>=\"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.transaction_date<=\"$to\"";
  }

  if (assigned($filterDebtorCodes)) {
    $whereClause = $whereClause . "
    AND (" . join(" OR ", array_map(function ($i) { return "a.client_code=\"$i\""; }, $filterDebtorCodes)) . ")";
  }

  $debtorResults = query("
    SELECT
      a.client_code                                                                                       AS `debtor_code`,
      IFNULL(b.english_name, \"Unknown\")                                                                 AS `debtor_name`,
      COUNT(*)                                                                                            AS `total_count`,
      SUM(IF(a.transaction_code=\"R3\", -a.qty, a.qty))                                                   AS `total_qty`,
      SUM(ROUND(IF(a.transaction_code=\"R3\", -a.qty, a.qty) * a.price * ((100 - a.discount) / 100), 2))  AS `total_amount`
    FROM
      `transaction` AS a
    LEFT JOIN
      `debtor` AS b
    ON a.client_code=b.code
    WHERE
      a.transaction_code REGEXP \"S1|S2|R3\"
      $whereClause
    GROUP BY
      a.client_code
    ORDER BY
      SUM(ROUND(IF(a.transaction_code=\"R3\", -a.qty, a.qty) * a.price * ((100 - a.discount) / 100), 2)) DESC,
      a.client_code ASC
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
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo REPORT_SALES_PERFORMANCE_CLIENT_TITLE; ?></div>
      <form>
        <table id="client-input">
          <tr>
            <th>From:</th>
            <th>To:</th>
            <th>Client Code:</th>
          </tr>
          <tr>
            <td>
              <input type="date" name="from" value="<?php echo $from; ?>" max="<?php echo date("Y-m-d"); ?>" class="web-only" />
              <span class="print-only"><?php echo assigned($from) ? $from : "ANY"; ?></span>
            </td>
            <td>
              <input type="date" name="to" value="<?php echo $to; ?>" max="<?php echo date("Y-m-d"); ?>" class="web-only" />
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
      <?php if (count($debtorResults) > 0) : ?>
        <table id="client-results" class="sortable">
          <colgroup>
            <col style="width: 60px">
            <col>
            <col style="width: 100px">
            <col style="width: 100px">
            <col style="width: 100px">
          </colgroup>
          <thead>
            <tr></tr>
            <tr>
              <th>Code</th>
              <th>Client</th>
              <th class="number"># Transactions</th>
              <th class="number">Total Qty</th>
              <th class="number">Total Amount <?php echo $InBaseCurrency; ?></th>
            </tr>
          </thead>
          <tbody>
            <?php
              $totalQty = 0;
              $totalAmount = 0;

              for ($i = 0; $i < count($debtorResults); $i++) {
                $debtorResult = $debtorResults[$i];
                $debtorCode = $debtorResult["debtor_code"];
                $debtorName = $debtorResult["debtor_name"];
                $count = $debtorResult["total_count"];
                $qty = $debtorResult["total_qty"];
                $amount = $debtorResult["total_amount"];

                $totalQty += $qty;
                $totalAmount += $amount;

                $_GET["filter_debtor_code"] = array($debtorCode);

                echo "
                  <tr>
                    <td title=\"$debtorCode\">$debtorCode</td>
                    <td title=\"$debtorName\">
                      <a href=\"" . generateRedirectURL(REPORT_SALES_PERFORMANCE_MODEL_URL) . "\">$debtorName</a>
                    </td>
                    <td class=\"number\" title=\"$count\">" . number_format($count) . "</td>
                    <td class=\"number\" title=\"$qty\">" . number_format($qty) . "</td>
                    <td class=\"number\" title=\"$amount\">" . number_format($amount, 2) . "</td>
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
              <th class="number"><?php echo number_format($totalQty); ?></th>
              <th class="number"><?php echo number_format($totalAmount, 2); ?></th>
            </tr>
          </tbody>
        </table>
      <?php else : ?>
        <div class="client-client-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
